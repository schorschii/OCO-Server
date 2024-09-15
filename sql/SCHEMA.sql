-- Server-Version: 10.3.27-MariaDB-0+deb10u1
-- PHP-Version: 7.3.27-1~deb10u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Tabellenstruktur für Tabelle `software`
--

CREATE TABLE IF NOT EXISTS `software` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_bin NOT NULL,
  `version` varchar(200) COLLATE utf8mb4_bin NOT NULL,
  `description` varchar(350) COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`version`,`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `system_user_role`
--

CREATE TABLE IF NOT EXISTS `system_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `c_system_user_role_1` CHECK(JSON_VALID(permissions))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `system_user_role`
--

REPLACE INTO `system_user_role` (`id`, `name`, `permissions`) VALUES
(1, 'Superadmin', '{\"Special\\\\ClientApi\": true, \"Special\\\\WebFrontend\": true, \"Special\\\\GeneralConfiguration\": true, \"Special\\\\EventQueryRules\": true, \"Special\\\\DeletedObjects\": true, \"Models\\\\Computer\": {\"*\": {\"read\": true, \"write\": true, \"wol\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\ComputerGroup\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\Package\": {\"*\": {\"read\": true, \"write\": true, \"download\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\PackageGroup\": {\"create\": true, \"*\": {\"read\": true, \"write\": true, \"delete\": true}}, \"Models\\\\PackageFamily\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\DomainUser\": {\"read\": true, \"delete\": true}, \"Models\\\\SystemUser\": true, \"Models\\\\Report\": {\"create\": true, \"*\": {\"read\": true, \"write\": true, \"delete\": true} }, \"Models\\\\ReportGroup\": {\"create\":true, \"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}}, \"Models\\\\JobContainer\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\Software\": true, \"Models\\\\DeploymentRule\": {\"*\": {\"read\": true, \"write\": true, \"delete\": true}, \"create\": true}, \"Models\\\\MobileDevice\": {\"*\": {\"read\": true, \"write\": true, \"delete\": true, \"deploy\": true}, \"create\": true}, \"Models\\\\MobileDeviceGroup\": {\"*\": {\"read\": true, \"write\": true, \"create\": true, \"delete\": true}, \"create\": true}, \"Models\\\\Profile\": {\"*\": {\"read\": true, \"write\": true, \"deploy\": true, \"delete\": true}, \"create\": true}}');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `system_user`
--

CREATE TABLE IF NOT EXISTS `system_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(200) NOT NULL,
  `username` text NOT NULL,
  `display_name` text NOT NULL,
  `password` text DEFAULT NULL,
  `ldap` tinyint(4) NOT NULL DEFAULT 0,
  `email` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
  `mobile` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `system_user_role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_system_user_role_id` (`system_user_role_id`),
  UNIQUE KEY `uid` (`uid`),
  CONSTRAINT `fk_system_user_role_id` FOREIGN KEY (`system_user_role_id`) REFERENCES `system_user_role` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `domain_user_role`
--

CREATE TABLE IF NOT EXISTS `domain_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `c_domain_user_role_1` CHECK(JSON_VALID(permissions))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `domain_user`
--

CREATE TABLE IF NOT EXISTS `domain_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(200) DEFAULT NULL,
  `domain` text DEFAULT NULL,
  `username` text NOT NULL,
  `display_name` text NOT NULL,
  `domain_user_role_id` int(11) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `ldap` tinyint(4) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_domain_user_1` (`domain_user_role_id`),
  UNIQUE KEY `uid` (`uid`),
  CONSTRAINT `fk_domain_user_1` FOREIGN KEY (`domain_user_role_id`) REFERENCES `domain_user_role` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_family`
--

CREATE TABLE IF NOT EXISTS `package_family` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `notes` text NOT NULL,
  `icon` mediumblob DEFAULT NULL,
  `license_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package`
--

CREATE TABLE IF NOT EXISTS `package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_family_id` int(11) NOT NULL,
  `version` text NOT NULL,
  `notes` text NOT NULL,
  `install_procedure` text NOT NULL,
  `install_procedure_success_return_codes` text NOT NULL,
  `install_procedure_post_action` tinyint(4) NOT NULL DEFAULT 0,
  `upgrade_behavior` tinyint(4) NOT NULL DEFAULT 2,
  `uninstall_procedure` text NOT NULL,
  `uninstall_procedure_success_return_codes` text NOT NULL,
  `download_for_uninstall` tinyint(4) NOT NULL DEFAULT 0,
  `uninstall_procedure_post_action` tinyint(4) NOT NULL DEFAULT 0,
  `compatible_os` text DEFAULT NULL,
  `compatible_os_version` text DEFAULT NULL,
  `license_count` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by_system_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_package_family_id` (`package_family_id`),
  KEY `fk_package_2` (`created_by_system_user_id`),
  CONSTRAINT `fk_package_family_id` FOREIGN KEY (`package_family_id`) REFERENCES `package_family` (`id`),
  CONSTRAINT `fk_package_2` FOREIGN KEY (`created_by_system_user_id`) REFERENCES `system_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_conflict`
--

CREATE TABLE IF NOT EXISTS `package_conflict` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `conflict_package_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_conflict_package_id` (`conflict_package_id`),
  KEY `fk_package_id_2` (`package_id`),
  CONSTRAINT `fk_conflict_package_id` FOREIGN KEY (`conflict_package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_package_id_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_dependency`
--

CREATE TABLE IF NOT EXISTS `package_dependency` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `dependent_package_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_dependend_package_id` (`dependent_package_id`),
  KEY `fk_package_id` (`package_id`),
  CONSTRAINT `fk_dependend_package_id` FOREIGN KEY (`dependent_package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_package_id` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_group`
--

CREATE TABLE IF NOT EXISTS `package_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_package_group_id` int(11) DEFAULT NULL,
  `name` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_package_group_id` (`parent_package_group_id`),
  CONSTRAINT `fk_parent_package_group_id` FOREIGN KEY (`parent_package_group_id`) REFERENCES `package_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_group_member`
--

CREATE TABLE IF NOT EXISTS `package_group_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `package_group_id` int(11) NOT NULL,
  `sequence` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_package_group_1` (`package_group_id`),
  KEY `fk_package_group_2` (`package_id`),
  CONSTRAINT `fk_package_group_1` FOREIGN KEY (`package_group_id`) REFERENCES `package_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_package_group_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `event_query_rule`
--

CREATE TABLE IF NOT EXISTS `event_query_rule` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `log` text NOT NULL,
  `query` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer`
--

CREATE TABLE IF NOT EXISTS `computer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(200) DEFAULT NULL,
  `hostname` varchar(200) NOT NULL,
  `os` text NOT NULL,
  `os_version` text NOT NULL,
  `os_license` text NOT NULL,
  `os_locale` text NOT NULL,
  `kernel_version` text NOT NULL,
  `architecture` text NOT NULL,
  `cpu` text NOT NULL,
  `gpu` text NOT NULL,
  `ram` text NOT NULL,
  `agent_version` text NOT NULL,
  `remote_address` text NOT NULL,
  `serial` text NOT NULL,
  `manufacturer` text NOT NULL,
  `model` text NOT NULL,
  `bios_version` text NOT NULL,
  `uptime` int(11) NOT NULL DEFAULT 0,
  `boot_type` text NOT NULL,
  `secure_boot` text NOT NULL,
  `domain` text NOT NULL,
  `last_ping` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `force_update` tinyint(4) NOT NULL DEFAULT 0,
  `notes` text NOT NULL,
  `agent_key` text NOT NULL,
  `server_key` text NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by_system_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_computer_1` (`created_by_system_user_id`),
  KEY `hostname` (`hostname`),
  UNIQUE KEY `uid` (`uid`),
  CONSTRAINT `fk_computer_1` FOREIGN KEY (`created_by_system_user_id`) REFERENCES `system_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_service`
--

CREATE TABLE IF NOT EXISTS `computer_service` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(4) NOT NULL,
  `name` varchar(200) NOT NULL,
  `metrics` text NOT NULL,
  `details` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `computer_id` (`computer_id`),
  KEY `name` (`name`),
  CONSTRAINT `fk_computer_service_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_event`
--

CREATE TABLE IF NOT EXISTS `computer_event` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `log` varchar(200) NOT NULL,
  `timestamp` datetime NOT NULL,
  `provider` text NOT NULL,
  `level` tinyint(4) NOT NULL,
  `event_id` int(11) NOT NULL,
  `data` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `computer_id` (`computer_id`),
  KEY `log` (`log`),
  CONSTRAINT `fk_computer_event_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_group`
--

CREATE TABLE IF NOT EXISTS `computer_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_computer_group_id` int(11) DEFAULT NULL,
  `name` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_computer_group_id` (`parent_computer_group_id`),
  CONSTRAINT `fk_parent_computer_group_id` FOREIGN KEY (`parent_computer_group_id`) REFERENCES `computer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_group_member`
--

CREATE TABLE IF NOT EXISTS `computer_group_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `computer_group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_computer_group_member_1` (`computer_group_id`),
  KEY `fk_computer_group_member_2` (`computer_id`),
  CONSTRAINT `fk_computer_group_member_1` FOREIGN KEY (`computer_group_id`) REFERENCES `computer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_computer_group_member_2` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_network`
--

CREATE TABLE IF NOT EXISTS `computer_network` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `nic_number` int(11) NOT NULL,
  `address` text NOT NULL,
  `netmask` text NOT NULL,
  `broadcast` text DEFAULT NULL,
  `mac` text DEFAULT NULL,
  `interface` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_computer_network_1` (`computer_id`),
  CONSTRAINT `fk_computer_network_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_package`
--

CREATE TABLE IF NOT EXISTS `computer_package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `installed_procedure` text,
  `installed_by_system_user_id` int(11) DEFAULT NULL,
  `installed_by_domain_user_id` int(11) DEFAULT NULL,
  `installed` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_computer_package_1` (`computer_id`),
  KEY `fk_computer_package_2` (`package_id`),
  KEY `fk_computer_package_3` (`installed_by_system_user_id`),
  KEY `fk_computer_package_4` (`installed_by_domain_user_id`),
  CONSTRAINT `fk_computer_package_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_computer_package_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_computer_package_3` FOREIGN KEY (`installed_by_system_user_id`) REFERENCES `system_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_computer_package_4` FOREIGN KEY (`installed_by_domain_user_id`) REFERENCES `domain_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_partition`
--

CREATE TABLE IF NOT EXISTS `computer_partition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `device` text NOT NULL,
  `mountpoint` text NOT NULL,
  `filesystem` text NOT NULL,
  `size` bigint(20) NOT NULL,
  `free` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_computer_partition_1` (`computer_id`),
  CONSTRAINT `fk_computer_partition_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_printer`
--

CREATE TABLE IF NOT EXISTS `computer_printer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `driver` text NOT NULL,
  `paper` text NOT NULL,
  `dpi` text NOT NULL,
  `uri` text NOT NULL,
  `status` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_computer_printer_1` (`computer_id`),
  CONSTRAINT `fk_computer_printer_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_screen`
--

CREATE TABLE IF NOT EXISTS `computer_screen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `manufacturer` text NOT NULL,
  `type` text NOT NULL,
  `resolution` text NOT NULL,
  `size` text NOT NULL,
  `manufactured` text NOT NULL,
  `serialno` text NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_computer_screen_1` (`computer_id`),
  CONSTRAINT `fk_computer_screen_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_software`
--

CREATE TABLE IF NOT EXISTS `computer_software` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `software_id` int(11) NOT NULL,
  `installed` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_computer_software_1` (`computer_id`),
  KEY `fk_computer_software_2` (`software_id`),
  CONSTRAINT `fk_computer_software_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_computer_software_2` FOREIGN KEY (`software_id`) REFERENCES `software` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `deployment_rule`
--

CREATE TABLE IF NOT EXISTS `deployment_rule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `notes` text NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT 1,
  `computer_group_id` int(11) NOT NULL,
  `package_group_id` int(11) NOT NULL,
  `sequence_mode` tinyint(4) NOT NULL DEFAULT 0,
  `priority` tinyint(4) NOT NULL DEFAULT 0,
  `post_action_timeout` int(11) NOT NULL DEFAULT 5,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by_system_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_deployment_rule_1` (`computer_group_id`),
  KEY `fk_deployment_rule_2` (`package_group_id`),
  KEY `fk_deployment_rule_3` (`created_by_system_user_id`),
  CONSTRAINT `fk_deployment_rule_1` FOREIGN KEY (`computer_group_id`) REFERENCES `computer_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_deployment_rule_2` FOREIGN KEY (`package_group_id`) REFERENCES `package_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_deployment_rule_3` FOREIGN KEY (`created_by_system_user_id`) REFERENCES `system_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `deployment_rule_job`
--

CREATE TABLE IF NOT EXISTS `deployment_rule_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deployment_rule_id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `procedure` text NOT NULL,
  `success_return_codes` text NOT NULL,
  `upgrade_behavior` tinyint(4) NOT NULL DEFAULT 2,
  `is_uninstall` tinyint NOT NULL DEFAULT 0,
  `download` tinyint NOT NULL DEFAULT 1,
  `post_action` int(11) DEFAULT NULL,
  `post_action_timeout` int(11) DEFAULT NULL,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `state` int(11) NOT NULL DEFAULT 0,
  `download_progress` float DEFAULT NULL,
  `return_code` bigint(11) DEFAULT NULL,
  `message` text NOT NULL,
  `wol_shutdown_set` datetime DEFAULT NULL,
  `download_started` datetime DEFAULT NULL,
  `execution_started` datetime DEFAULT NULL,
  `execution_finished` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_deployment_rule_job_1` (`deployment_rule_id`),
  KEY `fk_deployment_rule_job_2` (`computer_id`),
  KEY `fk_deployment_rule_job_3` (`package_id`),
  CONSTRAINT `fk_deployment_rule_job_1` FOREIGN KEY (`deployment_rule_id`) REFERENCES `deployment_rule` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_deployment_rule_job_2` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_deployment_rule_job_3` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `domain_user_logon`
--

CREATE TABLE IF NOT EXISTS `domain_user_logon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `domain_user_id` int(11) NOT NULL,
  `console` text DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_domain_user_logon_1` (`domain_user_id`),
  KEY `fk_domain_user_logon_2` (`computer_id`),
  CONSTRAINT `fk_domain_user_logon_1` FOREIGN KEY (`domain_user_id`) REFERENCES `domain_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_domain_user_logon_2` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `job_container`
--

CREATE TABLE IF NOT EXISTS `job_container` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `start_time` datetime NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `notes` text NOT NULL,
  `wol_sent` tinyint(4) NOT NULL DEFAULT 0,
  `shutdown_waked_after_completion` tinyint(4) NOT NULL DEFAULT 0,
  `sequence_mode` tinyint(4) NOT NULL DEFAULT 0,
  `priority` tinyint(4) NOT NULL DEFAULT 0,
  `agent_ip_ranges` text DEFAULT NULL,
  `time_frames` text DEFAULT NULL,
  `self_service` tinyint(4) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by_system_user_id` int(11) DEFAULT NULL,
  `created_by_domain_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_job_container_1` (`created_by_system_user_id`),
  KEY `fk_job_container_2` (`created_by_domain_user_id`),
  CONSTRAINT `fk_job_container_1` FOREIGN KEY (`created_by_system_user_id`) REFERENCES `system_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_job_container_2` FOREIGN KEY (`created_by_domain_user_id`) REFERENCES `domain_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `job_container_job`
--

CREATE TABLE IF NOT EXISTS `job_container_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_container_id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `procedure` text NOT NULL,
  `success_return_codes` text NOT NULL,
  `upgrade_behavior` tinyint(4) NOT NULL DEFAULT 2,
  `is_uninstall` tinyint(4) NOT NULL DEFAULT 0,
  `download` tinyint(4) NOT NULL DEFAULT 1,
  `post_action` int(11) DEFAULT NULL,
  `post_action_timeout` int(11) DEFAULT NULL,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `state` int(11) NOT NULL DEFAULT 0,
  `download_progress` float DEFAULT NULL,
  `return_code` bigint(11) DEFAULT NULL,
  `message` longtext NOT NULL,
  `wol_shutdown_set` datetime DEFAULT NULL,
  `download_started` datetime DEFAULT NULL,
  `execution_started` datetime DEFAULT NULL,
  `execution_finished` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_job_container_job_1` (`job_container_id`),
  KEY `fk_job_container_job_2` (`computer_id`),
  KEY `fk_job_container_job_3` (`package_id`),
  CONSTRAINT `fk_job_container_job_1` FOREIGN KEY (`job_container_id`) REFERENCES `job_container` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_container_job_2` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_job_container_job_3` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `level` tinyint(4) NOT NULL,
  `host` text NOT NULL,
  `user` text DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `action` text NOT NULL,
  `data` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `report_group`
--

CREATE TABLE IF NOT EXISTS `report_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_report_group_id` int(11) DEFAULT NULL,
  `name` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_report_group_id` (`parent_report_group_id`),
  CONSTRAINT `fk_parent_report_group_id` FOREIGN KEY (`parent_report_group_id`) REFERENCES `report_group` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `report_group`
--

REPLACE INTO `report_group` (`id`, `parent_report_group_id`, `name`) VALUES
(1, NULL, 'report_predefined');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `report`
--

CREATE TABLE IF NOT EXISTS `report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_group_id` int(11) DEFAULT NULL,
  `name` text NOT NULL,
  `notes` text NOT NULL,
  `query` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_report_1` (`report_group_id`),
  CONSTRAINT `fk_report_1` FOREIGN KEY (`report_group_id`) REFERENCES `report_group` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1000;

--
-- Daten für Tabelle `report`
--

REPLACE INTO `report` (`id`, `report_group_id`, `name`, `notes`, `query`) VALUES
(1, 1, 'report_secureboot_disabled', '', 'SELECT id AS computer_id, hostname, os, os_version FROM computer WHERE secure_boot = 0'),
(2, 1, 'report_packages_without_installations', '', 'SELECT p.id AS package_id, pf.name, p.version, count(cp.package_id) AS install_count FROM package p LEFT JOIN computer_package cp ON p.id = cp.package_id INNER JOIN package_family pf ON p.package_family_id = pf.id GROUP BY p.id HAVING install_count = 0'),
(3, 1, 'report_recognized_software_chrome', '', 'SELECT id as software_id, name FROM software WHERE name LIKE \'%chrome%\''),
(4, 1, 'report_domain_users_multiple_computers', '', 'SELECT du.id AS domain_user_id, username, (SELECT count(DISTINCT dl2.computer_id) FROM domain_user_logon dl2 WHERE dl2.domain_user_id = du.id) AS \'computer_count\' FROM domain_user du HAVING computer_count > 1'),
(5, 1, 'report_expired_job_containers', '', 'SELECT id AS job_container_id, name, end_time FROM job_container WHERE end_time IS NOT NULL AND end_time < CURRENT_TIME()'),
(6, 1, 'report_preregistered_computers', '', 'SELECT id AS computer_id, hostname FROM computer WHERE last_update IS NULL OR last_update <= \'2000-01-01 00:00:00\''),
(7, 1, 'report_all_monitors', '', 'SELECT c.hostname, cs.* FROM computer_screen cs INNER JOIN computer c ON c.id = cs.computer_id WHERE cs.serialno != \"\"'),
(8, 1, 'report_7_days_no_agent', '', 'SELECT id AS \'computer_id\', hostname, os, os_version, last_ping FROM computer WHERE last_ping IS NULL OR last_ping < NOW() - INTERVAL 7 DAY'),
(9, 1, 'report_total_disk_space', '', 'SELECT ROUND(SUM(free)/1024/1024/1024/1024, 2) AS \'Free Space (TiB)\', ROUND(SUM(size)/1024/1024/1024/1024, 2) AS \'Total Space (TiB)\' FROM computer_partition'),
(10, 1, 'report_total_ram_space', '', 'SELECT ROUND(SUM(ram)/1024/1024/1024, 2) AS \'Total RAM (GiB)\' FROM computer'),
(11, 1, 'report_all_19_monitors', '', 'SELECT c.hostname, cs.*, ROUND(SQRT(POW(SUBSTRING_INDEX(cs.size, \" x \", 1),2) + POW(SUBSTRING_INDEX(cs.size, \" x \", -1),2)) * 0.393701) AS \'Size (inches)\' FROM `computer_screen` cs INNER JOIN `computer` c ON c.id = cs.computer_id WHERE cs.serialno != \"\" HAVING `Size (inches)` <= 19 AND `Size (inches)` > 0'),
(12, 1, 'report_less_than_20gib_on_drive_c', '', 'SELECT c.id AS \'computer_id\', c.hostname, ROUND((SELECT cp.free FROM computer_partition cp WHERE cp.computer_id = c.id AND cp.mountpoint LIKE \'C:\')/1024/1024/1024) AS \'Free Space (GiB)\' FROM computer c HAVING `Free Space (GiB)` < 20'),
(13, 1, 'report_critical_services', '', 'SELECT cs.computer_id, c.hostname, cs.status, cs.timestamp, cs.updated, cs.details FROM computer_service cs INNER JOIN computer c ON c.id = cs.computer_id WHERE cs.id IN (SELECT MAX(cs2.id) FROM computer_service cs2 GROUP BY cs2.computer_id, cs2.name) AND cs.status != 0 ORDER BY c.hostname ASC'),
(14, 1, 'report_critical_events', '', 'SELECT timestamp, computer_id, hostname, event_id, IF(level=2, "ERROR", IF(level=3, "WARNING", level)) AS "level", data AS "Error Description" FROM computer_event ce INNER JOIN computer c ON c.id = ce.computer_id ORDER BY timestamp DESC');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `setting`
--

CREATE TABLE IF NOT EXISTS `setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` text NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mobile_device`
--

CREATE TABLE `mobile_device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `udid` varchar(40) DEFAULT NULL,
  `device_name` text NOT NULL,
  `serial` varchar(100) NOT NULL,
  `vendor_description` text NOT NULL,
  `model` text NOT NULL,
  `os` text NOT NULL,
  `device_family` text NOT NULL,
  `color` text NOT NULL,
  `profile_uuid` text DEFAULT NULL,
  `push_token` text DEFAULT NULL,
  `push_magic` text DEFAULT NULL,
  `push_sent` timestamp NULL DEFAULT NULL,
  `unlock_token` blob DEFAULT NULL,
  `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `notes` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_update` timestamp NULL DEFAULT NULL,
  `force_update` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `udid` (`udid`),
  UNIQUE KEY `serial_number` (`serial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mobile_device_command`
--

CREATE TABLE `mobile_device_command` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile_device_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `parameter` text NOT NULL,
  `state` int(11) NOT NULL DEFAULT 0,
  `message` text DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `finished` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_mobile_device_command_1` (`mobile_device_id`),
  CONSTRAINT `fk_mobile_device_command_1` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mobile_device_group`
--

CREATE TABLE `mobile_device_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_mobile_device_group_id` int(11) DEFAULT NULL,
  `name` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_parent_mobile_device_group_id` (`parent_mobile_device_group_id`),
  CONSTRAINT `fk_parent_mobile_device_group_id` FOREIGN KEY (`parent_mobile_device_group_id`) REFERENCES `mobile_device_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mobile_device_group_member`
--

CREATE TABLE `mobile_device_group_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile_device_id` int(11) NOT NULL,
  `mobile_device_group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_mobile_device_group_member_1` (`mobile_device_group_id`),
  KEY `fk_mobile_device_group_member_2` (`mobile_device_id`),
  CONSTRAINT `fk_mobile_device_group_member_1` FOREIGN KEY (`mobile_device_group_id`) REFERENCES `mobile_device_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mobile_device_group_member_2` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `app`
--

CREATE TABLE `app` (
  `id` int(11) NOT NULL,
  `identifier` varchar(350) NOT NULL,
  `name` varchar(350) NOT NULL,
  `display_version` text NOT NULL,
  `version` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `identifier` (`identifier`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mobile_device_app`
--

CREATE TABLE `mobile_device_app` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile_device_id` int(11) NOT NULL,
  `app_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_mobile_device_app_1` (`mobile_device_id`),
  KEY `fk_mobile_device_app_2` (`app_id`),
  CONSTRAINT `fk_mobile_device_app_1` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mobile_device_app_2` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `profile`
--

CREATE TABLE `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `payload` text NOT NULL,
  `notes` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by_system_user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_profile_1` (`created_by_system_user_id`),
  CONSTRAINT `fk_profile_1` FOREIGN KEY (`created_by_system_user_id`) REFERENCES `system_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mobile_device_group_profile`
--

CREATE TABLE `mobile_device_group_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile_device_group_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_mobile_device_group_profile_1` (`mobile_device_group_id`),
  KEY `fk_mobile_device_group_profile_2` (`profile_id`),
  CONSTRAINT `fk_mobile_device_group_profile_1` FOREIGN KEY (`mobile_device_group_id`) REFERENCES `mobile_device_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mobile_device_group_profile_2` FOREIGN KEY (`profile_id`) REFERENCES `profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mobile_device_profile`
--

CREATE TABLE `mobile_device_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile_device_id` int(11) NOT NULL,
  `uuid` varchar(350) NOT NULL,
  `identifier` text NOT NULL,
  `display_name` text NOT NULL,
  `version` text NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_mobile_device_profile` (`mobile_device_id`),
  KEY `uuid` (`uuid`),
  CONSTRAINT `fk_mobile_device_profile` FOREIGN KEY (`mobile_device_id`) REFERENCES `mobile_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
