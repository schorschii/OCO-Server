<?php
const LANG = [
	'app_name' => 'OCO IT Client Manager',
	'app_name_frontpage' => '[ Open Computer Orchestration ]',
	'app_subtitle' => 'Client inventory and software delivery made simple',
	'please_fill_required_fields' => 'Please fill the required fields.',
	'copy' => 'Copy (CTRL+C)',
	'refresh' => 'Refresh (F5)',
	'retry' => 'Retry',
	'home_page' => 'Home Page',
	'close' => 'Close',
	'search_placeholder' => 'Search...',
	'change' => 'Change',
	'new_password' => 'New Password',
	'confirm_password' => 'Confirm Password',
	'done' => 'Finish',
	'setup' => 'Setup',
	'login' => 'Login',
	'log_in' => 'Login',
	'login_failed' => 'Login failed',
	'user_locked' => 'User locked',
	'username' => 'Username',
	'password' => 'Password',
	'user_does_not_exist' => 'User does not exist',
	'login_failes' => 'Login failed',
	'log_out_successful' => 'Logout successful',
	'log_out' => 'Logout',
	'home_page' => 'Homepage',
	'passwords_do_not_match' => 'Passwords do not match',
	'database_error' => 'Database error. Please check logfiles.',
	'error' => 'Error',
	'hostname' => 'Hostname',
	'os' => 'OS',
	'version' => 'Version',
	'cpu' => 'CPU',
	'ram' => 'RAM',
	'ip_addresses' => 'IP Addresses',
	'mac_addresses' => 'MAC Addresses',
	'serial_no' => 'Serial No.',
	'agent' => 'Agent',
	'last_seen' => 'Last Seen',
	'last_updated' => 'Last Refreshed',
	'elements' => 'Object(s)',
	'elements_checked' => 'Object(s) selected',
	'deploy' => 'Deploy',
	'selected_elements' => 'Selected Objects',
	'delete' => 'Delete',
	'remove_from_group' => 'Remove from Group',
	'add_to' => 'Add to',
	'new_group' => 'New Group',
	'new_subgroup' => 'New Subgroup',
	'new_computer' => 'New Computer',
	'all_computer' => 'All Computers',
	'deploy_for_all' => 'Deploy for All',
	'delete_group' => 'Delete Group',
	'general' => 'Allgemein',
	'kernel_version' => 'Kernel-Version',
	'architecture' => 'Architecture',
	'vendor' => 'Manufacturer',
	'model' => 'Model',
	'bios_version' => 'BIOS-Version',
	'boot_type' => 'Boot-Type',
	'secure_boot' => 'Secure Boot',
	'agent_version' => 'Agent-Version',
	'assigned_groups' => 'Assigned Groups',
	'logins' => 'Logins',
	'computer' => 'Computer',
	'count' => 'Count',
	'last_login' => 'Last Login',
	'network' => 'Network',
	'ip_address' => 'IP Address',
	'mac_address' => 'MAC Address',
	'netmask' => 'Netmask',
	'broadcast' => 'Broadcast',
	'domain' => 'Domain',
	'screens' => 'Screens',
	'name' => 'Name',
	'resolution' => 'Resolution',
	'installed_packages' => 'Installed Packages',
	'package' => 'Package',
	'procedure' => 'Procedure',
	'installation_date' => 'Installation Date',
	'action' => 'Action',
	'recognised_software' => 'Recognised Software',
	'description' => 'Description',
	'deployment_assistant' => 'Deployment Assistant',
	'start' => 'Start',
	'end' => 'End',
	'target_computer' => 'Target Computer',
	'computer_groups' => 'Computer Groups',
	'packages_to_deploy' => 'Packages to Deploy',
	'packages' => 'Packages',
	'download' => 'Download',
	'package_groups' => 'Package Groups',
	'all_domain_user' => 'All Domain User',
	'login_name' => 'Login Name',
	'jobs' => 'Jobs',
	'job_container' => 'Job Container',
	'order' => 'Order',
	'status' => 'Status',
	'last_change' => 'Last Change',
	'new_deployment_job' => 'New Deployment Job',
	'created' => 'Created',
	'waiting_for_client' => 'Waiting for Client',
	'download_started' => 'Download Started',
	'execution_started' => 'Execution Started',
	'failed' => 'Failed',
	'succeeded' => 'Succeeded',
	'complete_package_library' => 'Complete Package Library',
	'all_packages' => 'All Packages',
	'new_package' => 'New Package',
	'deploy_all' => 'Deploy All',
	'set_end' => 'Set End',
	'send_wol' => 'Send WOL Packet',
	'no_jobs_created' => 'Your request did not produced any jobs. Please check if you have at least one computer and one package selected.',
	'no_connection_to_server' => 'No Connection To Server',
	'please_check_network' => 'Please check your network connection and reload the page.',
	'author' => 'Author',
	'install_procedure' => 'Installation Procedure',
	'uninstall_procedure' => 'Uninstallation Procedure',
	'zip_archive' => 'ZIP-Archive',
	'installed_on' => 'Installed On',
	'send' => 'Send',
	'users' => 'Users',
	'package_created' => 'Package created',
	'no_elements_selected' => 'No objects selected',
	'confirm_delete' => 'Really delete?',
	'confirm_delete_package' => 'Are you sure you want to delete the package(s)?\n\nAll computer assignments will be lost and you can no longer uninstall it using the web frontend.',
	'enter_name' => 'Please enter a name',
	'enter_new_hostname' => 'Please enter a new hostname.\n\nWarning: If you change the host name, you must also change the name on the computer, otherwise the agent can no longer establish a connection with the server!',
	'confirm_uninstall_package' => 'Are you sure you want to uninstall the package? An uninstall job will be created.\n\nPlease enter a start date.',
	'confirm_remove_package_assignment' => 'Are you sure you want to unassign the computer package? Usually the package should be uninstalled.',
	'confirm_delete_group' => 'Are you sure you want to delete the selected group(s)? The objects in the group are not deleted.',
	'confirm_delete_jobcontainer' => 'Are you sure you want to delete this job container? Pending jobs are not distributed.',
	'confirm_delete_job' => 'Are you sure you want to delete the selected job(s)?\nPending jobs are not distributed. Jobs that have already been executed are not automatically reversed.',
	'computer_added' => 'Computers were added',
	'packages_added' => 'Packages have been added',
	'remove_assignment' => 'Remove Assignment',
	'uninstall_package' => 'Uninstall Package',
	'uninstall' => 'Uninstall',
	'install' => 'Install',
	'settings' => 'Settings',
	'full_name' => 'Display Name',
	'system_users' => 'System Users',
	'ldap_account' => 'LDAP Account',
	'locked' => 'Locked',
	'lock' => 'Lock',
	'unlock' => 'Unlock',
	'installations' => 'Installations',
	'agent_registration_enabled' => 'Enable Agent Self-Registration',
	'agent_key' => 'Agent Key',
	'agent_update_interval' => 'Agent Update Interval (Seconds)',
	'purge_succeeded_jobs_after' => 'Purge Succeded Jobs after (Seconds)',
	'purge_failed_jobs_after' => 'Purge Failed Jobs after (Seconds)',
	'save' => 'Save',
	'saved' => 'Saved',
	'add' => 'Add',
	'notes' => 'Notes',
	'wol' => 'WOL',
	'wol_packet_sent' => 'WOL packet(s) sent',
	'move_up' => 'Move Up',
	'move_down' => 'Move Down',
	'client_extension_note' => 'This feature requires the OCO client extension installed on your computer.',
	'logons' => 'Logons',
	'computers' => 'Computers',
	'reports' => 'Reports',
	'id' => 'ID',
	'no_results' => 'Query returned no results.',
	'not_found' => 'Not found',
	'windows' => 'Windows',
	'linux' => 'Linux',
	'macos' => 'macOS',
	'package_exists_with_version' => 'A package with this name already exists with this version.',
	'size' => 'Size',
	'type' => 'Type',
	'driver' => 'Driver',
	'printers' => 'Printers',
	'file_systems' => 'File Systems',
	'file_system' => 'File System',
	'device' => 'Device',
	'mountpoint' => 'Mountpoint',
	'free' => 'Free',
	'address' => 'Address',
	'used' => 'Used',
	'server_overview' => 'Server Overview',
	'disk_space' => 'Disk Space',
	'usage' => 'Usage',
	'progress' => 'Progress',
	'expired' => 'Expired',
	'in_progress' => 'In Progress...',
	'renew_failed_jobs' => 'Renew Failed Jobs',
	'confirm_renew_jobs' => 'A new job container with all failed jobs will be created and the failed jobs will be removed from the original job container.',
	'renew' => 'Renew',
	'default_motd' => 'Thank you for using OCO. If you have any questions you can <a href="https://georg-sieber.de/?page=impressum" target="_blank">get professional support</a>.<br><br>Please have a look at the new <a href="https://github.com/schorschii/oco-server/blob/master/docs/Client-API.md" target="_blank">JSON-RPC-API</a> too.',
	'report_secureboot_disabled' => 'SecureBoot Disabled',
	'report_packages_without_installations' => 'Packages Without Installations',
	'report_recognized_software_chrome' => 'Recognized Software Chrome',
	'report_domainusers_multiple_computers' => 'Domain Users With Multiple PCs',
	'report_expired_jobcontainers' => 'Expired Job Containers',
	'report_preregistered_computers' => 'Pre-Registered Computers',
	'report_all_monitors' => 'All Monitors',
	'report_7_days_no_agent' => '7 Days No Agent Contact',
	'success_return_codes' => 'Success Return Codes',
	'success_return_codes_comma_separated' => 'Separate multiple codes with a comma',
	'auto_create_uninstall_jobs' => 'Automatically create uninstall jobs if another version is already installed',
	'download_for_uninstall' => 'Download for Uninstallation',
	'yes' => 'Yes',
	'no' => 'No',
	'csv' => 'CSV',
	'manufactured' => 'Manufactured',
	'after_completion' => 'After Completion',
	'no_action' => 'No Action',
	'restart' => 'Restart',
	'shutdown' => 'Shutdown',
	'restart_after' => 'Restart after',
	'shutdown_after' => 'Shutdown after',
	'timeout_for_reboot' => 'Timeout for Reboot',
	'timeout_for_reboot_description' => 'This allows the user to save his work before the computer restarts or shuts down (only applies to packages with enabled restart/shutdown and if at least one user is logged in).',
	'seconds' => 'Seconds',
	'minutes' => 'Minutes',
	'report_predefined' => 'Predefined',
	'rename_group' => 'Rename Group',
	'rename' => 'Rename',
	'group' => 'Group',
	'no_mac_addresses_for_wol' => 'No WOL packet could be sent because no MAC address of this computer is known. Please connect a network adapter to the computer and run the agent so that the MAC address is reported to the server.',
	'name_cannot_be_empty' => 'The name cannot be empty.',
	'hostname_cannot_be_empty' => 'The host name cannot be empty.',
	'username_cannot_be_empty' => 'The user name cannot be empty.',
	'password_cannot_be_empty' => 'The password cannot be empty.',
	'hostname_already_exists' => 'This hostname already exists.',
	'pending_jobs' => 'Pending Jobs',
	'remove_job' => 'Remove Job',
	'license' => 'License',
	'activated' => 'Activated',
	'not_activated' => 'Not Activated',
	'locale' => 'Locale',
	'confirm_create_empty_package' => 'No file selected. Do you want to create an empty package?',
	'package_family' => 'Name (Package Family)',
	'other_packages_from_this_family' => 'Other Packages From This Family',
	'search_computer_packages_job_container' => 'Search Computers, Packages, Job Containers, Domain Users, Reports...',
	'no_search_results' => 'No Search Results',
	'end_time_before_start_time' => 'The end time cannot be before the start time.',
	'waiting_for_start' => 'Waiting for Start',
	'online' => 'Online',
	'offline' => 'Offline',
	'edit' => 'Edit',
	'enter_new_value' => 'Please enter a new value',
	'enter_start_time' => 'Please enter a start time',
	'date_parse_error' => 'Date parse error. Please enter a date in the format: YYYY-MM-DD HH:MM:SS',
	'enter_new_procedure_post_action' => 'Please enter a new procedure post action:\n0 - No Action\n1 - Restart\n2 - Shutdown',
	'enter_new_download_for_uninstall_action' => 'Please indicate whether the package should be downloaded for the uninstallation:\n0 - No\n1 - Yes',
	'agent_download' => 'Agent Download',
	'agent_download_description' => 'You need to install the agent on your client computers in order to manage them with OCO server.',
	'new_report' => 'New Report',
	'database_schema' => 'Database Schema',
	'database_schema_description' => 'Take a look at the database schema in order to write SQL reports for the OCO database.',
	'move_to' => 'Move to',
	'enter_query' => 'Please enter a query',
	'edit_query' => 'Edit Query',
	'edit_description' => 'Edit Description',
	'unknown_error' => 'Unknown Error',
	'self_registration' => 'Self Registration',
	'desktop_notifications_not_supported' => 'Desktop notifications are not supported by your browser.',
	'desktop_notifications_denied' => 'You denied desktop notifications in your browser settings.',
	'desktop_notifications_already_permitted' => 'Desktop notifications already permitted.',
	'job_container_status_changed' => 'Job container status changed.',
	'user_settings' => 'User Settings',
	'enable_notifications' => 'Enable Notifications',
	'compatible_os' => 'Compatible OS',
	'compatible_os_version' => 'Compatible OS Version',
	'incompatible' => 'Incompatible',
	'package_conflict' => 'Package Conflict',
	'optional_hint' => '(optional)',
	'payload_corrupt' => 'Payload corrupt',
	'cannot_move_uploaded_file' => 'Cannot move uploaded file',
	'cannot_create_zip_file' => 'Cannot create ZIP file',
	'invalid_api_key' => 'Invalid API Key',
	'file_too_big' => 'File too big!',
	'change_icon' => 'Change Icon',
	'remove_icon' => 'Remove Icon',
	'are_you_sure' => 'Are you sure?',
	'restart_agent' => 'Restart Agent',
	'interface' => 'Interface',
	'package_creation_notes' => '
		<p>
			A package consists of a ZIP archive, which is unpacked into a temporary directory when it is made available. Then a command (the procedure) is executed to start the installation. Longer commands should be stored in a script (.bat or .sh) you have written yourself.
		</p>
		<p>
			If you upload a file type other than a ZIP archive, a ZIP archive is automatically created with the uploaded file. If you do not select a file, an empty archive will be created. This can be useful if you just want to execute a command without payload (e.g. install something using <code>apt</code> under Linux).
		</p>
		<p>
			Example Procedures:
			<ul>
				<li>EXE setup for Windows: <code>installer.exe /S</code></li>
				<li>EXE uninstallation for Windows: <code>C:\Program Files\MyProgram\unins000.exe /S</code>
				<br>The uninstallation command depends on the specific software, please consider repacking EXE setups as MSI package.</li>
				<li>MSI setup for Windows: <code>msiexec /quiet /i package.msi</code></li>
				<li>MSI uninstallation for Windows: <code>msiexec /quiet /x package.msi</code> or <code>{PRODUCT-GUID}</code></li>
				<li>DEB package for Linux: <code>gdebi -n package.deb</code></li>
				<li>DEB package for Linux uninstallation: <code>apt remove -y packagename</code></li>
				<li>.app directory for macOS: <code>cp -R program.app /Applications ; chmod -R +x /Applications/program.app</code></li>
				<li>.app directory for macOS uninstallation: <code>rm -R /Applications/program.app</code></li>
				<li>.pkg package for macOS: <code>installer -pkg package.pkg -target /</code> (no uninstallation support)</li>
				<li>Own Batch/Shell script: <code>myscript.bat</code> or <code>myscript.sh</code></li>
			</ul>
		</p>
	',
];
