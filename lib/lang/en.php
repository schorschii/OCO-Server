<?php
/*
	Naming Convention Hints
	- Objects (Computers, Packages, Reports, Groups) are *created* while assignments are *added*
	- Objects (Computers, Packages, Reports, Groups) are *deleted* while assignments are *removed*
*/
const LANG = [
	'app_name' => 'OCO IT Client Manager',
	'project_name' => 'Open Computer Orchestration',
	'app_subtitle' => 'Client inventory and software delivery made simple',
	'app_copyright' => '© <a href="https://georg-sieber.de" target="_blank">Georg Sieber</a> 2020-2022 | <a href="https://github.com/schorschii/oco-server" target="_blank">OCO Project on Github</a>',
	'welcome_text' => 'Welcome to the OCO web console!',
	'welcome_description' => 'Thank you for using OCO.',
	'requested_view_does_not_exist' => 'The requested view does not exist',
	'about' => 'About',
	'please_fill_required_fields' => 'Please fill the required fields',
	'copy' => 'Copy (CTRL+C)',
	'refresh' => 'Refresh (F5), right click for auto refresh',
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
	'passwords_do_not_match' => 'Passwords do not match',
	'database_error' => 'Database error. Please check logfiles.',
	'error' => 'Error',
	'hostname' => 'Hostname',
	'os' => 'OS',
	'version' => 'Version',
	'cpu' => 'CPU',
	'gpu' => 'GPU',
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
	'general' => 'General',
	'general_and_hardware' => 'General and Hardware',
	'general_and_dependencies' => 'General and Dependencies',
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
	'packages_and_jobs' => 'Packages and Jobs',
	'computer_and_jobs' => 'Computer and Jobs',
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
	'waiting_for_agent' => 'Waiting for Agent',
	'download_started' => 'Download Started',
	'execution_started' => 'Execution Started',
	'failed' => 'Failed',
	'succeeded' => 'Succeeded',
	'complete_package_library' => 'Complete Package Library',
	'all_packages' => 'All Packages',
	'new_package' => 'New Package',
	'deploy_all' => 'Deploy All',
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
	'computer_created' => 'Computer created',
	'jobs_created' => 'Job(s) created',
	'group_created' => 'Group created',
	'report_created' => 'Report created',
	'user_created' => 'User created',
	'no_elements_selected' => 'No objects selected',
	'confirm_delete' => 'Really delete?',
	'confirm_delete_package' => 'Are you sure you want to delete the package(s)?\n\nAll computer assignments will be lost and you can no longer uninstall this package using the OCO server. If the shift key is pressed, all pending jobs and assigned package dependencies are also deleted automatically.',
	'enter_name' => 'Please enter a name',
	'enter_new_hostname' => 'Please enter a new hostname.\n\nWarning: If you change the host name, you must also change the name on the computer, otherwise the agent can no longer establish a connection with the server!',
	'confirm_remove_package_assignment' => 'Are you sure you want to unassign the computer package? Usually the package should be uninstalled.',
	'confirm_delete_group' => 'Are you sure you want to delete the selected group(s)? The objects in the group are not deleted.',
	'confirm_delete_jobcontainer' => 'Are you sure you want to delete this job container? Pending jobs are not distributed.',
	'confirm_delete_job' => 'Are you sure you want to delete the selected job(s)?\nPending jobs are not distributed. Jobs that have already been executed are not automatically reversed.',
	'computer_added' => 'Computers have been added',
	'packages_added' => 'Packages have been added',
	'remove_assignment' => 'Remove Assignment',
	'uninstall' => 'Uninstall',
	'install' => 'Install',
	'settings' => 'Settings',
	'display_name' => 'Display Name',
	'uid' => 'Unique Identifier',
	'ldap_account' => 'LDAP Account',
	'locked' => 'Locked',
	'lock' => 'Lock',
	'unlock' => 'Unlock',
	'installations' => 'Installations',
	'agent_registration_enabled' => 'Agent self registration enabled',
	'agent_key' => 'Agent Key',
	'agent_update_interval' => 'Agent update interval',
	'purge_succeeded_jobs_after' => 'Purge succeded job containers after',
	'purge_failed_jobs_after' => 'Purge failed job containers after',
	'assume_computer_offline_after' => 'Assume that computers are offline after',
	'purge_logs_after' => 'Purge log entries after',
	'purge_domain_user_logons_after' => 'Purge domain user logons after',
	'save' => 'Save',
	'saved' => 'Saved',
	'add' => 'Add',
	'notes' => 'Notes',
	'wol' => 'WOL',
	'wol_packet_sent' => 'WOL packet(s) sent',
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
	'renew_jobs_description' => 'A new job container with all failed jobs will be created and the failed jobs will be deleted from the original job container.',
	'renew' => 'Renew',
	'default_motd' => 'The documentation and the bug tracker can be found on <a href="https://github.com/schorschii/OCO-Server" target="_blank">Github</a> - feedback and pull requests are welcome!<br>If you have any questions you can also <a href="https://georg-sieber.de/?page=impressum" target="_blank">get professional support</a>.<br><br>Please have a look at the new <a href="https://github.com/schorschii/oco-server/blob/master/docs/Client-API.md" target="_blank">JSON-RPC-API</a> too.',
	'report_secureboot_disabled' => 'SecureBoot Disabled',
	'report_packages_without_installations' => 'Packages Without Installations',
	'report_recognized_software_chrome' => 'Recognized Software Chrome',
	'report_domain_users_multiple_computers' => 'Domain Users With Multiple PCs',
	'report_expired_jobcontainers' => 'Expired Job Containers',
	'report_preregistered_computers' => 'Pre-Registered Computers',
	'report_all_monitors' => 'All Monitors',
	'report_7_days_no_agent' => '7 Days No Agent Contact',
	'report_all_19_monitors' => 'All 19" Monitors',
	'report_less_than_20gib_on_drive_c' => 'Computers with less than 20 GiB on Drive C:',
	'report_total_disk_space' => 'Total Disk Space (All Computers)',
	'report_total_ram_space' => 'Total RAM Space (All Computers)',
	'success_return_codes' => 'Success Return Codes',
	'success_return_codes_comma_separated' => 'Separate multiple codes with a comma',
	'uninstall_old_package_versions' => 'Uninstall old package versions',
	'auto_create_uninstall_jobs' => 'Automatically create preceding uninstall jobs if another version is already installed',
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
	'object_renamed' => 'Object renamed',
	'group_renamed' => 'Group renamed',
	'group' => 'Group',
	'no_mac_addresses_for_wol' => 'No WOL packet could be sent because no MAC address of this computer is known. Please connect a network adapter to the computer and run the agent so that the MAC address is reported to the server.',
	'name_cannot_be_empty' => 'The name cannot be empty',
	'hostname_cannot_be_empty' => 'The host name cannot be empty',
	'username_cannot_be_empty' => 'The user name cannot be empty',
	'password_cannot_be_empty' => 'The password cannot be empty',
	'hostname_already_exists' => 'This hostname already exists',
	'username_already_exists' => 'This username already exists',
	'pending_jobs' => 'Pending Jobs',
	'license' => 'License',
	'activated' => 'Activated',
	'not_activated' => 'Not Activated',
	'locale' => 'Locale',
	'confirm_create_empty_package' => 'No file selected. Do you want to create an empty package?',
	'package_family_name' => 'Package Family Name',
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
	'enter_new_procedure_post_action' => 'Please enter a new procedure post action:\n0 - No Action\n1 - Restart\n2 - Shutdown\n3 - Agent restart (only install procedure)',
	'enter_new_download_for_uninstall_action' => 'Please indicate whether the package should be downloaded for the uninstallation:\n0 - No\n1 - Yes',
	'agent_download' => 'Agent Download',
	'agent_download_description' => 'You need to install the agent on your client computers in order to manage them with OCO server.',
	'new_report' => 'New Report',
	'database_schema' => 'Database Schema',
	'database_schema_description' => 'Take a look at the database schema in order to write SQL reports for the OCO database.',
	'move_to' => 'Move to',
	'edit_description' => 'Edit Description',
	'unknown_error' => 'Unknown Error',
	'unknown_method' => 'Unbekannte Methode',
	'self_registration' => 'Self Registration',
	'desktop_notifications_not_supported' => 'Desktop notifications are not supported by your browser.',
	'desktop_notifications_denied' => 'You denied desktop notifications in your browser settings.',
	'desktop_notifications_already_permitted' => 'Desktop notifications already permitted.',
	'job_container_status_changed' => 'Job container status changed.',
	'own_system_user_settings' => 'Own System User Settings',
	'configuration_overview' => 'Configuration Overview',
	'system_user_management' => 'System User Management',
	'enable_notifications' => 'Enable Desktop Notifications',
	'wol_shutdown_expiry_seconds' => 'Assume WOL did not worked after',
	'wol_satellites' => 'WOL Satellite Server',
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
	'invalid_input' => 'Invalid Value. Please check your input.',
	'abort_after_failed' => 'Abort after failed job',
	'aborted_after_failed' => 'Aborted after failed job',
	'ignore_failed' => 'Ignore failed jobs',
	'sequence_mode' => 'Sequence Mode',
	'priority' => 'Priority',
	'priority_description' => 'Job Container with higher priority will be executed first.',
	'force_update' => 'Force Update',
	'change_settings_in_config_file' => 'You can change the settings in the OCO config file (conf.php).',
	'client_api_enabled' => 'API enabled',
	'reorder_via_drag_drop' => 'Change sequence via Drag & Drop',
	'reorder_drag_drop_description' => 'Change sequence via Drag & Drop'."\n".'(only available if table is sorted by sequence)',
	'enter_new_sequence_number' => 'Please enter the new position number, to which the object should be moved to.',
	'move' => 'Verschieben',
	'package_families' => 'Package Families',
	'delete_failed_active_jobs' => 'The object could not be deleted because there are still active jobs referencing this object. Please delete the corresponding jobs first, then try again.',
	'delete_failed_subgroups' => 'The group could not be deleted because it still has subgroups. Please delete the subgroups first and try again.',
	'delete_failed_package_family_contains_packages' => 'The package family could not be deleted because it still contains at least one package. Please delete all packages of this family first and try again.',
	'depends_on' => 'Depends On',
	'dependent_packages' => 'Packages dependent on this package',
	'add_dependency' => 'Add Dependency',
	'add_dependent_package' => 'Add Dependent Package',
	'please_select_placeholder' => '=== Please Select ===',
	'new_version' => 'New Version',
	'newest' => 'Newest',
	'oldest' => 'Oldest',
	'delete_package_family' => 'Delete Package Family',
	'reinstall' => 'Reinstall',
	'force_installation_of_same_version' => 'Create installation jobs even if the same version is already installed',
	'already_installed' => 'Already installed',
	'delete_failed_dependent_packages' => 'The package could not be deleted because there are still dependent packages which are referenced to this package.',
	'show_contents' => 'Show Contents',
	'shutdown_waked_computers' => 'Shutdown waked computers',
	'shutdown_waked_after_completion' => 'Shutdown waked computers after completion',
	'history' => 'History',
	'timestamp' => 'Timestamp',
	'console' => 'Console',
	'aggregated_logins' => 'Aggregated Logins',
	'details' => 'Details',
	'all_os' => 'All Operating Systems',
	'results' => 'Results',
	'query' => 'Query',
	'remote_address' => 'Remote Address',
	'uptime' => 'Uptime',
	'days' => 'Day(s)',
	'hours' => 'Hour(s)',
	'minutes' => 'Minute(s)',
	'object_deleted' => 'Object(s) deleted',
	'group_deleted' => 'Group deleted',
	'object_removed_from_group' => 'Object(s) removed from group',
	'create_system_user' => 'Create System User',
	'create_report' => 'Create Report',
	'edit_report' => 'Edit Report',
	'edit_user' => 'Edit User',
	'help' => 'Help',
	'uninstall_packages' => 'Uninstall Packages',
	'uninstall_job_container_will_be_created' => 'An uninstall job container will be created.',
	'ldap_accounts_cannot_be_modified' => 'LDAP can only be edited using your directory service',
	'update_available' => 'Update available!',
	'prerelease_note' => '(Prerelease)',
	'permission_denied' => 'Permission denied. You do not have the necessary rights.',
	'web_interface_login_not_allowed' => 'User is not allowed to login on the web interface',
	'api_login_not_allowed' => 'User is not allowed to login on the API',
	'role' => 'Role',
	'agent_ip_range' => 'Agent IP range',
	'invalid_ip_address' => 'Invalid IP address',
	'change_password' => 'Change Password',
	'old_password' => 'Old Password',
	'old_password_is_not_correct' => 'Old password is not correct',
	'example' => 'Example',
	'show_hide_sidebar' => 'Show/Hide Sidebar',
	'select_all' => 'Select all',
	'installation' => 'Installation',
	'uninstallation' => 'Uninstallation',
	'package_content' => 'Package Content',
	'toggle_multi_line' => 'Toggle multi line text field',
	'initiator' => 'Initiator',
	'report_groups' => 'Report Groups',
	'expand_or_collapse_tree' => '(Double) click to exapnd or collapse tree',
	'create_package' => 'Create Package',
	'finished' => 'Finished',
	'execution_time' => 'Execution Time',
	'order_by' => 'Order By',
	'ldap_sync' => 'LDAP Sync',
	'default_view' => 'Default View',
	'advanced_view' => 'Advanced View',
	'abort_after_error_description' => 'If you disable this option, errors will be ignored and subsequent jobs will still run',
	'installation_behaviour' => 'Installation Behaviour',
	'element_already_exists' => 'Element already exists',
	'add_selected' => 'Add Selected',
	'remove_selected' => 'Remove Selected',
	'remove_end_time' => 'Remove End Time',
	'computer_selection' => 'Computer Selection',
	'package_selection' => 'Package Selection',
	'back' => 'Back',
	'runtime' => 'Runtime',
	'state' => 'State',
	'package_creation_notes' => '
		<p>
			A package consists of a ZIP archive, which is unpacked into a temporary directory when it is made available. Then a command (the procedure) is executed to start the installation. Longer commands should be stored in a script (.bat or .sh) you have written yourself.
		</p>
		<p>
			If you upload a file type other than a ZIP archive, a ZIP archive is automatically created with the uploaded file. If you do not select a file, an empty archive will be created. This can be useful if you just want to execute a command without payload (e.g. install something using <code>apt</code> under Linux).
		</p>
		<p>
			Example Procedures:
		</p>
		<ul>
			<li>EXE setup for Windows: <code>installer.exe /S</code></li>
			<li>EXE uninstallation for Windows: <code>C:\Program Files\MyProgram\unins000.exe /S</code>
			<br>The (un)installation command depends on the specific software, please consider repacking EXE setups as MSI package.</li>
			<li>MSI setup for Windows: <code>msiexec /quiet /i package.msi</code></li>
			<li>MSI uninstallation for Windows: <code>msiexec /quiet /x package.msi</code> or <code>msiexec /quiet /x {PRODUCT-GUID}</code></li>
			<li>DEB package for Linux: <code>gdebi -n package.deb</code></li>
			<li>DEB package for Linux uninstallation: <code>apt remove -y packagename</code></li>
			<li>.app directory for macOS from DMG file: <code>hdiutil attach program.dmg && cp -R /Volumes/program/program.app /Applications && hdiutil detach /Volumes/program</code></li>
			<li>.app directory for macOS uninstallation: <code>rm -R /Applications/program.app</code></li>
			<li>.pkg package for macOS: <code>installer -pkg package.pkg -target /</code> (no uninstallation support)</li>
			<li>Own Batch/Shell script: <code>myscript.bat</code> or <code>myscript.sh</code></li>
		</ul>
	',
];
