# Install Windows Drivers
You may want to deploy drivers for printers, scanners etc. They can be installed with the `pnputil` command line utility from windows. Example:
```
pnputil -i -a oemsetup.inf
```
Where `oemsetup.inf` is the name of your driver's `.inf` file. Important: the ZIP archive must contain all necessary driver files, not only the `.inf` metadata file!
