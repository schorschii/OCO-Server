# Install Windows Upgrades
It is possible to even update your Windows installation to a newer build using a OCO software job!

1. Extract the contents of the new Windows `.iso` file and add them into a `.zip` file. Upload this `.zip` file on the OCO web console.
2. Choose the following command line as installation procedure: `setup.exe /auto upgrade /showoobe None /noreboot`
3. Choose "Reboot" as action after package installation.
