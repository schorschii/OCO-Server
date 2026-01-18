# Policies
With OCO policies, you can configure your managed computers similar to Microsoft group policies, but for Linux and macOS too. For example, you can centrally manage policies for Chrome and Firefox for Windows, Linux, macOS with a single policy object on the OCO server.

## Import Policy Templates
First, you need to set up policy templates for the settings you want to deploy to your clients. This must be done using the .admx group policy template import script or manually by inserting apporopriate rows into `policy_definition` table.

A good first step is to import existing Windows group policies from .admx and .adml files. For this, copy the entire directory `C:\Windows\PolicyDefinitions` from a Windows domain controller server onto your OCO server (including translation subdirectories). Then, on the OCO server command line, execute `php scripts/admx-importer.php /path/to/PolicyDefinitions`.

You can do this step again with the [Google Chrome](https://dl.google.com/dl/edgedl/chrome/policy/policy_templates.zip) and [Mozilla Firefox](https://github.com/mozilla/policy-templates/releases) group policy templates.

Now, you have a set of working policy templates for Windows. To make them work on Linux and macOS, you need to fill the columns `manifestation_linux` and `manifestation_macos` in the `policy_definition` table. For Chrome/Chromium and Firefox policies, you can execute the script `scripts/chrome-firefox-policies.php` to automatically fill the Linux/macOS manifestation based on the Windows manifestation.

### Manually Writing Manifestations
A `manifestation_*` column in the database can be NULL or contain one or multiple lines of the following items:
- `REGISTRY:<key>:<valueName>` (only available on Windows)
   - if the policy is set, OCO will create this registry key and valueName with the value provided in OCO web console "policy object"
   - `<key>` should not contain `HKLM\` or `HKCU\`
   - `<valueName>` must be omitted for policies of type `DICT` and `LIST`
- `JSON:<filePath>:<key>`
  - if the policy is set, OCO will create this JSON file or add additional values to it if it exists
  - `<key>` defines the place in the JSON structure, where to write the value provided in OCO web console "policy object"
- `XML:<filePath>:<key>`
  - if the policy is set, OCO will create this XML file or add additional values to it if it exists
  - `<key>` defines the place in the XML structure, where to write the value provided in OCO web console "policy object"

The `options` column can be one of the following:
- `TEXT`: the admin can enter a single-line string
  - optionally, min and max length can be set using `TEXT:5:10`
- `TEXT-MULTILINE`: the admin can enter a multi-line string
- `INT`: the admin can enter a integer number
  - optionally, min and max values can be set using `INT:0:100`
- `LIST`: the admin can enter a list of strings
- `DICT`: the admin can enter a dictionary (key-value combination)
- a JSON string in form `{"Enabled":1, "Disabled":0}` providing pre-defined options for a select box
  - the array key is the display name for the admin
  - the corresponding value is used in the manifestation on the managed computer

The `class` field defines the scope of the policy and must contain one of the following values:
- `1`: the policy is a machine policy
- `2`: the policy is a user policy
- `3`: the policies can be used in both scopes

## Creating Policy Objects
To deploy policies, create a policy object in the OCO web frontend (sidebar "Policies"). Open it and configure the settings you want. Save it with the button in the top toolbar.

Then, assign a policy object to a computer group by opening the "Policies" start page again. Select your policy object in the table using the checkbox and click on "Assign" on the bottom. Now, select a computer group.

Important: policy objects are inherited in the computer group hierarchy. If you have nested computer groups, policies from sub-diretories will override superior policies.

On the managed computers, policies are updated when the agent updates the inventory data. This interval can be adjusted in the OCO settings or manually enforced using the "Force update" button in the computer detail view.
