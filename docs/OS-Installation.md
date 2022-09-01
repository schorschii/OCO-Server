# OCO: OS Installation
You can use additional software to extend your OCO server for (unattended) OS installations via network (PXE).

## 1. Install Server Software
Install the necessary packages on your Debian server:
- General: `apt install isc-dhcp-server tftp-hpa`
- For Linux installation: `apt install nfs-kernel-server`
- For Windows installation: `apt install samba wimtools mkisofs cdrkit`

## 2. Configure DHCP/TFTP Server
Create the necessary configuration files from the following examples. Adjust your DHCP configuration for your environment (especially the subnet declaration).

```
*** /etc/dhcp/dhcpd.conf ***

option domain-name "mydomain.org";
option domain-name-servers 10.0.1.1, 10.0.1.2; # addresses of your DNS servers
option pxe-system-type code 93 = unsigned integer 16;

default-lease-time 600;
max-lease-time 7200;

include "/etc/dhcp/reservations.conf";

if exists user-class and option user-class = "iPXE" {
        filename "/ipxe.cfg";
} elsif substring ( option vendor-class-identifier, 0, 9) = "PXEClient" {
        if option pxe-system-type = 00:07 {
                filename = "/efi/ipxe.efi";
        } else {
                filename = "/bios/undionly.kpxe";
        }
}

# Subnet Declarations - please adjust for your own network(s)
subnet 10.0.1.0 netmask 255.255.255.0 {
  option routers 10.0.1.1;
  next-server 10.0.1.3; # address of your OCO server
}
# maybe more subnets ...
subnet 10.0.2.0 netmask 255.255.255.0 {
  option routers 10.0.2.1;
  next-server 10.0.1.3;

  # optional address pool
  pool {
    range 10.0.2.20 10.0.2.90;
  }
}
```
```
*** /etc/dhcp/reservations.conf ***

# you can add reservations specific for each client
host NOTEBOOK01 {
  hardware ethernet ca:ff:ee:ca:ff:ee;
  fixed-address 10.0.2.10;
}
# more hosts here...

# you can use the OCO ISC DHCP server reservation editor extension to edit this file in the web UI (https://github.com/schorschii/oco-server-extensions)

# or: define an address pool in subnet declaration
```
```
*** /etc/default/tftp-hpa ***

TFTP_USERNAME="tftp"
TFTP_DIRECTORY="/srv/tftp"
TFTP_ADDRESS="0.0.0.0:69"
TFTP_OPTIONS="--secure -vvv -m /etc/tftp_remap.conf"
```
```
*** /etc/default/rftp_remap.conf ***
rg \\ /
```

## 3. Set Up iPXE Bootloader
- Create `/srv/tftp` directory
- Download iPXE network bootloader files from: https://ipxe.org/download
  - `ipxe.efi` -> `/srv/tftp/efi/ipxe.efi`
  - `undionly.kpxe` -> `/srv/tftp/bios/undionly.kpxe`
- Create the iPXE configuration file: `/srv/tftp/ipxe.cfg` from the example [examples/ipxe.cfg](examples/ipxe.cfg).
  - The files/paths in this example (`/iso-windows` and `/linux-live`) will be created in the following steps 4 and 5.
  - For the Ubuntu setup, replace `nfsroot=10.0.1.3` with your server's IP address (the NFS server will be installed in the Linux section, step 4).

## 4. Set Up Linux Installation
- For example, extract a Linux Mint 20.1 `.iso` file into `/srv/tftp/linux-live/LinuxMint20.1_amd64/` (this also works with Ubuntu or other derived distros)
- Configure the NFS server:
  - Share the Mint/Ubuntu sources with your client computers by adding the following line into `/etc/exports`:
    ```
    /srv/tftp/linux-live/LinuxMint20.1_amd64 0.0.0.0/0.0.0.0(ro,no_root_squash,sync,no_subtree_check)
    ```
  - Restart the NFS server.
- Remaster the Linux squashfs live filesystem (`/srv/tftp/linux-live/LinuxMint20.1_amd64/casper/filesystem.squashfs`) in order to integrate the OCO agent (and other software), so you don't have to manually install it after the OS installation finished. The procedure is described in detail [here](https://help.ubuntu.com/community/LiveCDCustomization). The most important steps are:
  ```
  # extract the squashfs and copy odo-agent.deb in place
  cd /srv/tftp/linux-live/LinuxMint20.1_amd64/casper/
  unsquashfs filesystem.squashfs
  mv squashfs-root edit

  # mount the special file systems (in order to work with apt etc.) and dive into chroot
  mount --bind /run/ edit/run
  mount --bind /dev/ edit/dev
  chroot edit
  mount -t proc none /proc
  mount -t sysfs none /sys
  mount -t devpts none /dev/pts

  # do the customizations you like
  cd /tmp
  wget https://github.com/schorschii/OCO-Agent/releases/download/vX.X.X/oco-agent.deb
  echo "oco-agent oco-agent/server-name string oco.example.com" | sudo debconf-set-selections
  echo "oco-agent oco-agent/agent-key string 12345678" | sudo debconf-set-selections
  gdebi -n oco-agent.deb
  echo "oco-agent oco-agent/server-name string " | sudo debconf-set-selections
  echo "oco-agent oco-agent/agent-key string " | sudo debconf-set-selections

  # clean up and leave chroot
  umount /proc || umount -lf /proc
  umount /sys
  umount /dev/pts
  umount /dev
  exit

  # create new squashfs
  rm filesystem.squashfs
  mksquashfs edit filesystem.squashfs
  ```

### Unattended Installation
You can now create preseed file `/srv/tftp/linux-live/LinuxMint20.1_amd64/preseed/myconfig.cfg` for automatic installation (see [examples/mint.cfg](examples/mint.cfg) for examples).

In contrast to Windows, it is not necessary to create separate config files for every computer, because the Ubuntu setup will take the hostname given as kernel parameter from iPXE (`hostname=${hostname}`).

Notice that the NFS share is readable for everyone. Therefore, you should not put any plaintext password in it. Instead, store an appropriate password hash in the preseed file.

In addition to that, can use dynamic administrator passwords by using [LAPS](https://github.com/schorschii/LAPS4LINUX).

## 5. Set Up Windows Installation
- Download the iPXE wimboot module: https://ipxe.org/wimboot
  - move it to `/srv/tftp/efi/wimboot`
- Configure the Samba server:
  - Create a directory for your Windows images, e.g. `/srv/smb/images`.
  - Share the Windows sources with your client computers by adding the following into `/etc/samba/smb.conf`:
    ```
    [images]
    path = /srv/smb/images
    guest ok = yes
    comment=installation files
    usershare_acl=S-1-1-0:F
    read only = yes
    ```
  - Restart the Samba server.
- Create a directory for your current Windows version, e.g. `/srv/smb/images/Windows10`
  - Extract your Windows `.iso` file into this folder
- Create a minimal Windows setup environment ("WinPE") `.iso` using the Linux command line tool `mkwinpeimg`.
  - Create a start script `/tmp/startnet.cmd` for your Windows setup (this script will be integrated into your WinPE `.iso`). The example script ([examples/startnet.cmd](examples/startnet.cmd)) checks if there exists a XML file with the mac address of the client. If yes, it starts the setup with this XML file for unattended installation. If not, it starts the setup in normal (user-interactive) mode.
  - Replace `YOUROCOSERVER.example.com` with your server address.
  - Execute `mkwinpeimg --iso --start-script=/tmp/startnet.cmd --windows-dir=/srv/smb/images/Windows10 /srv/tftp/iso-windows/Windows10.iso` to create the WinPE `.iso` file.
- Create a directory for your current Windows version, e.g. `/srv/tftp/iso-windows/W10`
  - Extract the files `BCD`, `boot.sdi` and `boot.wim` from your WinPE `/srv/tftp/iso-windows/Windows10.iso` into this directory.
- Add the OCO agent setup to the Windows sources for automatic installation.
  - Insert the setup `.exe` into `/srv/smb/images/Windows10/sources/$OEM$/$$/Setup/Files`.
  - Create the post-installation script `/srv/smb/images/Windows10/sources/$OEM$/$$/Setup/Scripts/SetupComplete.cmd` from the example file [examples/SetupComplete.cmd](examples/SetupComplete.cmd).

More information can be found in the official wimboot documentation: https://ipxe.org/howto/winpe

### Unattended Installation
To make fully unattended installations, you can now place some XML files into `/srv/smb/images/preseed` with the name of the MAC address of your computer, e.g. `00-50-56-aa-bb-cc.xml` (separate config files per computer are necessary under Windows in order to give every computer the desired hostname). Of course, this file creation should be automated with some kind of user interface and own scripts in your specific device deployment workflow.

There are several tools out there to create such Windows setup answer files, e.g. https://www.windowsafg.com/win10x86_x64_uefi.html.

Notice that the Samba share is readable for everyone. Therefore, you should not set an administrator password in your unattended `.xml`, as it is only stored Base64 encoded. This can simply be decoded into plaintext!

Instead, you can use a technique described [here](https://georg-sieber.de/?page=blog-windows-hash) to put a password hash into the `.xml` file (as known from Linux preseed files) instead of a plaintext password.

In addition to that, can use dynamic administrator passwords by using [LAPS](https://www.microsoft.com/en-us/download/details.aspx?id=46899).

## 6. Boot!
Boot your client device via PXE. Maybe you need to enter your BIOS/UEFI settings and set the network card as first boot device.

Make sure that you boot in EFI mode. For that, disable CSM (Compatibility Support Module) in the BIOS/UEFI settings of your computer.

You also need to disable Secure Boot in order to load iPXE. After installation, you can turn it on again.

## More Information
### Ubuntu/Mint Image Customization
- [Ubuntu Live CD Customization](https://help.ubuntu.com/community/LiveCDCustomization)
- [Live CD remastering](https://wiki.ubuntuusers.de/LiveCD_manuell_remastern/)

### Windows Image Customization
- [NTLite](https://www.ntlite.com/)
- [DISM](https://docs.microsoft.com/de-de/windows-hardware/manufacture/desktop/what-is-dism)
