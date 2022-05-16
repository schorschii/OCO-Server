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
- Create iPXE configuration file: `/srv/tftp/ipxe.cfg`
  ```
  #!ipxe

  menu Open Computer Orchestration PXE
    item --gap ${hostname}  ${ip}  ${mac}  via ${net0/next-server}
    item --gap
    item windows Windows Installation Bootloader via SMB
    item linux Linux Installation Bootloader via NFS
    choose os && goto ${os}

  :windows
    kernel /efi/wimboot
    initrd /iso-windows/W10/BCD           BCD
    initrd /iso-windows/W10/boot.sdi      boot.sdi
    initrd /iso-windows/W10/boot.wim      boot.wim
    boot

  :linux
    kernel /linux-live/LinuxMint20.1_amd64/casper/vmlinuz
    initrd /linux-live/LinuxMint20.1_amd64/casper/initrd.lz
    imgargs vmlinuz initrd=initrd.lz root=/dev/nfs boot=casper hostname=${hostname} ip=dhcp netboot=nfs nfsroot=10.0.1.3:/srv/tftp/linux-live/LinuxMint20.1_amd64/ toram -- automatic-ubiquity file=/cdrom/preseed/myconfig.cfg
    boot
  ```
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
- Create preseed file for automatic installation: `/srv/tftp/linux-live/LinuxMint20.1_amd64/preseed/myconfig.cfg` (see `examples/mint.cfg` for examples).
  - In contrast to Windows, it is not necessary to create separate config files for every computer, because the Ubuntu setup will take the hostname given as kernel parameter from iPXE (`hostname=${hostname}`).
- Remaster Linux squashfs live filesystem (`/srv/tftp/linux-live/LinuxMint20.1_amd64/casper/filesystem.squashfs`) in order to integrate the OCO agent (and other software), so you don't have to manually install it after OS installation finished.
  - https://help.ubuntu.com/community/LiveCDCustomization

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
  - Create a start script `/tmp/startnet.cmd` for your Windows setup (this script will be integrated into your WinPE `.iso`). This example script checks if there exists a XML file with the mac address of the client. If yes, it starts the setup with this XML file for unattended installation. If not, it starts the setup in normal (user-interactive) mode.
    ```
    @echo off

    REM ==============================
    REM OPEN COMPUTER ORCHESTRATION
    REM startnet.cmd
    REM Windows Setup Bootstrap Script
    REM ------------------------------
    REM (c) 2020-2022 Georg Sieber
    REM ==============================

    REM ==============================
    REM Variables
    REM ------------------------------
    set IMAGE_SHARE=\\YOUROCOSERVER.example.com\images
    set SETUP_EXE=Windows10\setup.exe
    set PRESEED_DIR=preseed
    REM ==============================

    echo OPEN COMPUTER ORCHESTRATION (OCO)
    echo Initializing Windows Setup. Please wait...

    REM init setup environment
    wpeinit

    REM wait for some NICs to come up (Fujitsu Qs)
    ping 127.0.0.1 -n 6 > nul

    REM print ipconfig for debugging
    ipconfig

    REM get mac address
    for /F "tokens=*" %%a in ('ipconfig /all') DO (
    	for /F "tokens=1,2 delims=:" %%b in ('echo %%a') DO (
    		if "%%b" == "Physische Adresse . . . . . . . . " (
    			SET mac=%%c
    		)
    	)
    )
    SET "mac=%mac: =%"

    REM print mac address for debugging
    echo.
    echo Meine MAC-Adresse: %mac%

    REM mount SMB share with windows sources
    echo Mount I: -> %IMAGE_SHARE%
    net use I: %IMAGE_SHARE% /user:dummy dummy

    REM start setup with specific unattended config
    if exist I:\%PRESEED_DIR%\%mac%.xml (
    	echo Starting setup with config file: I:\\%PRESEED_DIR%\\%mac%.xml ...
    	I:\%SETUP_EXE% /unattend:I:\%PRESEED_DIR%\%mac%.xml
    ) else (
    	echo Could not find an unattended installation answer file. We recommend a Linux installation instead.
    	I:\%SETUP_EXE%
    )

    REM fallback: start shell if setup could not be started
    cmd.exe
    pause
    ```
  - Replace `YOUROCOSERVER.example.com` with your server address.
  - Execute `mkwinpeimg --iso --start-script=/tmp/startnet.cmd --windows-dir=/srv/smb/images/Windows10 /srv/tftp/iso-windows/Windows10.iso` to create the WinPE `.iso` file.

At this point, the ISO file can already be used to boot machines and install Windows systems with the sources and unattended configuration from your Samba server. Next, we configure EFI PXE boot.

- Create a directory for your current Windows version, e.g. `/srv/tftp/iso-windows/W10`
  - Extract the files `BCD`, `boot.sdi` and `boot.wim` from your WinPE `/srv/tftp/iso-windows/Windows10.iso` into this directory.

More information can be found in the official wimboot documentation: https://ipxe.org/howto/winpe

To make fully unattended installations, you can now place some XML files into `/srv/smb/images/preseed` with the name of the MAC address of your computer, e.g. `00-50-56-aa-bb-cc.xml` (separate config files per computer are necessary under Windows in order to give every computer the desired hostname). There are several tools out there to create such Windows setup answer files, e.g. https://www.windowsafg.com/win10x86_x64_uefi.html.

## 6. Boot!
Boot your client device via PXE. Maybe you need to enter your BIOS/UEFI settings and set the network card as first boot device.

Make sure that you boot in EFI mode. For that, disable CSM (Compatibility Support Module) in the BIOS/UEFI settings of your computer.

You also need to disable Secure Boot in order to load iPXE. After installation, you can turn it on again.
