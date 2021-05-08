# OCO: OS Installation
You can use additional software to extend your OCO server for (unattended) OS installations via network (PXE).

## 1. Install Server Software
Install the necessary packages on your Debian server:
- isc-dhcp-server
- tftp-hpa

## 2. Configure DHCP/TFTP Server
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
    item windows Windows Installation Bootloader via SMB
    item linux Linux Installation Bootloader via NFS
    choose os && goto ${os}

  :windows
    kernel /efi/wimboot
    initrd /Boot/BCD         BCD
    initrd /Boot/boot.sdi    boot.sdi
    initrd /Boot/winpe_x64.wim boot.wim
    boot

  :linux
    kernel /linux-live/LinuxMint20.1_amd64/casper/vmlinuz
    initrd /linux-live/LinuxMint20.1_amd64/casper/initrd.lz
    imgargs vmlinuz initrd=initrd.lz root=/dev/nfs boot=casper ip=dhcp netboot=nfs nfsroot=10.0.1.3:/srv/tftp/linux-live/LinuxMint20.1_amd64/ toram -- automatic-ubiquity file=/cdrom/preseed/myconfig.cfg
    boot
  ```

## 4. Set Up Linux Installation
- Unzip Linux Mint 20.1 `.iso` file into `/srv/tftp/linux-live/LinuxMint20.1_amd64/`
- Create preseed file for automatic installation: `/srv/tftp/linux-live/LinuxMint20.1_amd64/preseed/myconfig.cfg` (see `mint.cfg` for examples)
- Remaster Linux squashfs live filesystem in order to integrate the OCO agent (and other software), so you don't have to manually install it after OS installation finished.
  - https://help.ubuntu.com/community/LiveCDCustomization

## 5. Set Up Windows Installation
- https://ipxe.org/howto/winpe

## 6. Boot!
Boot your client device via PXE. Maybe you need to enter your BIOS/EFI settings and set the network card as first boot device.

Disable "CSM" if possible, this will ensure your UEFI starts the OS in EFI mode, not in legacy BIOS mode.

You also need to disable Secure Boot in order to load iPXE. After installation, you can turn it on again.
