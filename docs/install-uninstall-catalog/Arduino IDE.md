# Arduino IDE

## Windows (including drivers)
Driver installations are tricky. The installers will call a windows driver installation utility, which displays a confirmation dialog to the user if the driver certificate is not already in the system trust store. Since the OCO agent is running as a service, this dialog is never visible on the screen. That's why we have to import those certificates first.

1. Get the certificate file by right-clicking you driver's `.cat` file -> Properties -> Digital Signatures -> Details-> View Certificate -> Details tab -> Copy to File…
   - For Arduino IDE, you have to to this with these 3 files: `AdafruitCircuitPlayground.cat`, `arduino.cat`, `linino-boards_amd64.cat` and save the certificate as `*.cer` file.
2. Create a ZIP package with those 3 certificate files plus the setup EXE. Use this ZIP as archive for your OCO package.
3. Use the following commands as OCO installation procedure. This will install the certificates first and then execute the regular setup (a common Nullsoft Installer which understands the `/S` parameter for silent installation).
   ```
   certutil -addstore "TrustedPublisher" ".\AdafruitCircuitPlayground.cer" && certutil -addstore "TrustedPublisher" ".\arduino.cer" && certutil -addstore "TrustedPublisher" ".\linino-boards_amd64.cer" && arduino-x.y.z-windows.exe /S
   ```
