# Mobile Device Management (MDM)

## iOS
- ABM: Apple Business Manager
- ASM: Apple School Manager
- APN: Apple Push Notification Service

TL;DR: it's complicated. Apple did everything to make it as hard as possible to operate an own MDM system, so you need some patience to set it up, and it will not be for free.

### Prerequisites
- Apple Business account (https://business.apple.com/) for your company to access ABM (including approval with DUNS number)
- MDM APN certificate
  - A MDM APN cert signing request (CSR) sent to Apple must be signed using a special MDM vendor certificate.
  - Such a MDM vendor certificate is only available with a paid Apple developer account. Since the cert can be revoked at any time on misuse, it's not possible to publish my MDM vendor cert with OCO source code.
  - I'm signing MDM APN CSRs for free with my MDM vendor cert for users who have bought and own a valid OCO license. For that, only an internet connection is required from your OCO server.

### Enrollment Options
#### Automated Device Enrollment (ADE)
Previously called Apple Device Enrollment Program (DEP). This option configures settings using ABM/ASM (requires an MDM server token for OCO set up in ABM/ASM). It enrolls a large number of devices, without ever touching them. These devices are purchased from Apple, have your preconfigured settings, and can be shipped directly to users or schools.

#### Manual (Apple Configurator)
Requires you to install the OCO MDM enrollment profile manually, e.g. by downloading it via Safari or sending it via email. You can download this `.mobileconfig` file in OCO Settings -> Mobile Device Management -> Download Enrollment Profile.

### Setup
1. Get a MDM Vendor Cert from Apple or an OCO license - one of them is needed to sign the MDM APN (next step).
   - MDM Vendor Cert from Apple: create an Apple developer account, pay 99$ for one year and [request access to an MDM Vendor CSR Signing Certificate](https://developer.apple.com/help/account/manage-your-team/requesting-access-to-vendor-signing-certificate/).  
     OCO: Go to Settings -> Mobile Device Management. Download the Apple MDM Vendor CSR and upload it [here](https://developer.apple.com/account/resources/certificates/add).  
     Finally, upload the generated cert `mdm.cer` you got from Apple on the MDM settings page in your OCO server.
   - OCO license: make sure your OCO license file is installed and not expired (in your OCO server: Settings -> General Settings).

2. Get a MDM APN certificate from Apple
   1. OCO: Go to Settings -> Mobile Device Management. Download the Apple MDM APN CSR.
   2. Go to https://identity.apple.com/pushcert/, sign in with your ABM/ASM account and upload the CSR. You'll get a CER file, which needs to be uploaded back in the OCO MDM settings.

   **Important!** This certificate expires after 1 year. When you renew the certificate, use the renew button in the Apple Pushcert Portal. If you create a new certificate, you need to delete and re-register all iOS devices!

3. Define your MDM API URL
   - OCO: Go to Settings -> Mobile Device Management and enter your server URL including the path to the MDM api script as reachable by the iOS device, e.g. `https://oco.example.com/api-mdm.php`.

4. Get a MDM Server Token (only necessary for ADE)
   - OCO: Go to Settings -> Mobile Device Management and download the MDM Server Token public key (`mdm-token-cert.pem`).
   - Add a new MDM server [in ABM/ASM](https://business.apple.com/#/main/preferences/mdmserver-new) by uploading the public key file you downloaded from your OCO server before.
   - Upload the token you got from ABM/ASM (`MDM Server_Token_xxxx-xx-xxTxx-xx-xxZ_smime.p7m`) into OCO.

5. Define activation profile (only necessary for ADE)
   - Define an activation profile like the following example. This profile is the first thing the iPhone will get after activating with Apple servers.
   - The URL must be the path to your server URL including the path to the MDM api script with `/profile` attached. More information about this profile can be found [here](https://developer.apple.com/documentation/devicemanagement/profile).
     ```
     {
        "profile_name": "OCO MDM",
        "url": "https://oco.example.com/api-mdm.php/profile",
        "support_email_address": "test@example.com",
        "skip_setup_items": ["Accessibility","ActionButton","Appearance","AppleID","AppStore","Biometric","DeviceToDeviceMigration","Diagnostics","DisplayTone","iMessageAndFaceTime","Location","Passcode","Privacy","Restore","Siri","SoftwareUpdate","Welcome","Zoom"]
     }
     ```

5. Create a crontab entry for executing `php console.php applesync` and `php console.php mdmcron` every 10 minutes.

Now, you can assign devices in ABM/ASM to your OCO server. Note that you can set OCO as default MDM server for every new device bought.

### Join Devices Into MDM
After OCO synced with ABM/ASM, the iOS devices are automatically visible in OCO. Now, when first put the iDevice into operation and when factory reset the device, it will automatically contact your OCO server as MDM solution.

Without factory reset, you can click on "New iOS Device" in OCO and download an enrollment profile, which needs to be sent and installed on the target device. Note that not all MDM features are available when using this method.

### Management
After the device checked in into OCO MDM (via ADE or manual enrollment profile installation), you can use the OCO MDM deployment assistant to roll out configuration profiles or send device commands (e.g. locking a device).

Configuration profiles (`.mobileconfig` files) can be created using Apple Configurator.

### Further Information
- [Setting Up Push Notifications for Your MDM Customers](https://developer.apple.com/documentation/devicemanagement/implementing_device_management/setting_up_push_notifications_for_your_mdm_customers)
- [Sending notification requests to APNs](https://developer.apple.com/documentation/usernotifications/sending-notification-requests-to-apns)
- [The iOS MDM Protocol](https://media.blackhat.com/bh-us-11/Schuetz/BH_US_11_Schuetz_InsideAppleMDM_WP.pdf)
- [Understanding MDM Certificates](https://micromdm.io/blog/certificates/)


## Android
Coming soon (maybe)!
