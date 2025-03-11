# Mobile Device Management (MDM)
First, define the URL to your OCO MDM API as seen by your devices. This URL is used in enrollment profiles and QR codes and cannot be changed as soon as you have one mobile device in management. In OCO web frontend, go to Settings -> Mobile Device Management and enter your server URL including the path to the MDM API PHP script as reachable by the devices, e.g. `https://oco.example.com/api-mdm.php`.

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

3. Get a MDM Server Token (only necessary for ADE)
   - OCO: Go to Settings -> Mobile Device Management and download the MDM Server Token public key (`mdm-token-cert.pem`).
   - Add a new MDM server [in ABM/ASM](https://business.apple.com/#/main/preferences/mdmserver-new) by uploading the public key file you downloaded from your OCO server before.
   - Upload the token you got from ABM/ASM (`MDM Server_Token_xxxx-xx-xxTxx-xx-xxZ_smime.p7m`) into OCO.

4. Define activation profile (only necessary for ADE)
   - Define an activation profile like the following example. This profile is the first thing the iPhone will get after activating with Apple servers.
   - The URL must be the path to your server URL including the path to the MDM api script with `/profile` attached. More information about this profile can be found [here](https://developer.apple.com/documentation/devicemanagement/profile).
     ```
     {
        "profile_name": "OCO MDM",
        "url": "https://oco.example.com/api-mdm.php/profile",
        "support_email_address": "test@example.com",
        "is_supervised": true,
        "is_mandatory": true,
        "is_mdm_removable": false,
        "language": "de",
        "region": "DE",
        "skip_setup_items": ["Accessibility","ActionButton","Appearance","AppleID","AppStore","Biometric","DeviceToDeviceMigration","Diagnostics","DisplayTone","iMessageAndFaceTime","Location","Passcode","Privacy","Restore","ScreenTime","Siri","SoftwareUpdate","Welcome","Zoom"]
     }
     ```
     You may also need to add `anchor_certs` if you are using a self-signed server certificate.

5. Create a crontab entry for executing `php console.php applesync` every 30 minutes and `php console.php mdmcron` every minute.

Now, you can assign devices in ABM/ASM to your OCO server. Note that you can set OCO as default MDM server for every new device bought.

### Join Devices Into MDM
After OCO synced with ABM/ASM, the iOS devices are automatically visible in OCO. Now, when first put the iDevice into operation and when factory reset the device, it will automatically contact your OCO server as MDM solution. The device is now "supervised".

Without factory reset, you can click on "New iOS Device" in OCO and download an enrollment profile, which needs to be sent and installed on the target device. Note that not all MDM commands/features are available when using this method (the device is "not supervised").

### General/Configuration Management
First, upload your configuration profiles (`.mobileconfig` files) in the corresponding "Profiles and Policies" section in the OCO sidebar. Such profiles can be created using Apple Configurator, but Apple Configurator only knows a subset of all possible config options. For example configuring an Exchange profile must be done manually. See the [configuration payload reference](https://developer.apple.com/documentation/devicemanagement/profile-specific-payload-keys) for all possible configuration values. After creating a profile, assign it to mobile device groups.

Common configuration profiles:
- [Email Account](https://developer.apple.com/documentation/devicemanagement/mail)
- [Exchange Active Sync](https://developer.apple.com/documentation/devicemanagement/exchangeactivesync)
- [Enforce Strong Passcode](https://developer.apple.com/documentation/devicemanagement/passcode)
- [Enforce Update Settings](https://developer.apple.com/documentation/devicemanagement/softwareupdate)
- [Display Single App (Kiosk Mode)](https://developer.apple.com/documentation/devicemanagement/applock)

After the device checked in into OCO MDM (via ADE or manual enrollment profile installation), you can add the device to mobile device groups. This will install the assigned configuration profiles.

With the button "Send Command" on the device detail page, you can e.g. lock or erase a device.

### Purchasing & Installing Apps
ABM/ASM offers features for volume purchases of apps and books from the App Store. Before you can deploy apps through OCO, you first need to purchase them in ABM/ASM (even if they are free).

#### Import Content Token (cToken)
1. To allow OCO to manage your bought assets from the Volume Purchasing Program (VPP), content managers can download a location-based cToken from the "Apps and Books" section under the "Settings" tab in ABM/ASM. Upload this .vpptoken file in OCO Settings -> "Mobile Device Management" -> "VPP Token".
2. To retrieve app metadata, you need a key to authorize agaist the App Store API.
   - [Create a service identifier and private key](https://developer.apple.com/help/account/manage-service-configurations/apps-and-books-for-organizations) using a Apple developer account allows you to obtain a key ID to use in your developer token.
   - Upload the .p8 file with the private key in in OCO settings -> "Mobile Device Management" -> "App Store API key". Set the corresponding 10 character key ID and your 10 character Apple developer team ID there too.

#### Buy Things
Next, you need to buy something in ABM/ASM (even if the desired apps are free). Then, OCO can deploy this assets.

1. In ABM/ASM, go to ["Locations"](https://business.apple.com/#/main/locations) and select your target location.
2. Then, switch to ["Apps and Books"](https://business.apple.com/#/main/appsandbooks) and search the app you want to buy. Enter a quantity and buy the desired app(s).
3. After the next syncup, your purchases should be visible in OCO "Mobile Devices" -> "Managed Apps". Here, you can now assign apps to a device group in order to start the deployment.

### Further Information
- [Setting Up Push Notifications for Your MDM Customers](https://developer.apple.com/documentation/devicemanagement/implementing_device_management/setting_up_push_notifications_for_your_mdm_customers)
- [Sending notification requests to APNs](https://developer.apple.com/documentation/usernotifications/sending-notification-requests-to-apns)
- [The iOS MDM Protocol](https://media.blackhat.com/bh-us-11/Schuetz/BH_US_11_Schuetz_InsideAppleMDM_WP.pdf)
- [Understanding MDM Certificates](https://micromdm.io/blog/certificates/)


## Android
### Setup
1. Prepare OCO for communication with the Android Management API by Google.
   - With a Google account, create a Google Cloud project, enable the "Android Management API" in this project and create a service account for OCO. Follow the steps described [here](https://developers.google.com/android/management/service-account).
   - When creating the service account key, select key type "JavaScript Object Notation" to download it as .json file. Upload the .json file in OCO Settings -> Mobile Device Management -> Google API Service Account.
   - Don't forget to grant the role "Android Management User" to your service account as described on the linked Google page.
2. Click "Generate Signup URL" in OCO Settings -> Mobile Device Management. Then, follow the link to the Google Android Enterprise login. Sign in with a Google account and follow the instructions. After that, you will be redirected to OCO and you should see that the "Company" is now set in the OCO Mobile Device Management settings.
3. Create a crontab entry for executing `php console.php googlesync` every 30 minutes and `php console.php mdmcron` every minute.

### Enrollment Options
#### Classic Enrollment via QR code
You can now add Android devices into OCO by clicking "New Android Device" on the "Mobile Devices" page. Tap 6 times on Android welcome screen of a factory-resetted device, then scan the QR code displayed by OCO. The QR code is valid only for 1 hour.

#### Zero-Touch Enrollment
// TODO

### General/Configuration Management
First, ceate a policy (JSON format) in the corresponding "Profiles and Policies" section in the OCO sidebar. See the [Android policy reference](https://developers.google.com/android/management/reference/rest/v1/enterprises.policies#Policy) for possible configuration values. After creating a policy, assign it to mobile device groups.

After the device checked in into OCO MDM, you can add the device to mobile device groups. This will apply the assigned policies.

With the button "Send Command" on the device detail page, you can e.g. lock a device or reset the passcode.

### Installing Apps
To deploy Android apps, open the managed Play Store by clicking "Manage Android Apps" on the "Managed Apps" page in OCO. A Google Play iframe will be displayed, where you can select the desired apps. After you imported an app into OCO, you can assign them to mobile device groups like on iOS and they will be installed automatically.

When assigning apps to groups, you can add conguration values for the app. Consult the documentation of the specific app which configuration values are supported.
