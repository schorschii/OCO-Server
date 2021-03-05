-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `oco`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer`
--

CREATE TABLE `computer` (
  `id` int(11) NOT NULL,
  `hostname` text NOT NULL,
  `os` text NOT NULL,
  `os_version` text NOT NULL,
  `kernel_version` text NOT NULL,
  `architecture` text NOT NULL,
  `cpu` text NOT NULL,
  `gpu` text NOT NULL,
  `ram` text NOT NULL,
  `agent_version` text NOT NULL,
  `serial` text NOT NULL,
  `manufacturer` text NOT NULL,
  `model` text NOT NULL,
  `bios_version` text NOT NULL,
  `boot_type` text NOT NULL,
  `secure_boot` text NOT NULL,
  `last_ping` datetime NOT NULL DEFAULT current_timestamp(),
  `last_update` datetime DEFAULT current_timestamp(),
  `notes` text NOT NULL,
  `agent_key` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_command`
--

CREATE TABLE `computer_command` (
  `id` int(11) NOT NULL,
  `icon` text NOT NULL,
  `name` text NOT NULL,
  `command` text NOT NULL,
  `new_tab` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `computer_command`
--

INSERT INTO `computer_command` (`id`, `icon`, `name`, `command`) VALUES
(1, 'img/screen-access.svg', 'VNC', 'vnc://$$TARGET$$'),
(2, 'img/screen-access.svg', 'RDP', 'rdp://$$TARGET$$'),
(3, 'img/screen-access.svg', 'SSH', 'ssh://$$TARGET$$'),
(4, 'img/ping.svg', 'Ping', 'ping://$$TARGET$$'),
(5, 'img/portscan.svg', 'Nmap', 'nmap://$$TARGET$$');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_group`
--

CREATE TABLE `computer_group` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_group_member`
--

CREATE TABLE `computer_group_member` (
  `id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `computer_group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_network`
--

CREATE TABLE `computer_network` (
  `id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `nic_number` int(11) NOT NULL,
  `addr` text NOT NULL,
  `netmask` text NOT NULL,
  `broadcast` text DEFAULT NULL,
  `mac` text DEFAULT NULL,
  `domain` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_package`
--

CREATE TABLE `computer_package` (
  `id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `installed_procedure` text NOT NULL,
  `installed` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_partition`
--

CREATE TABLE `computer_partition` (
  `id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `device` text NOT NULL,
  `mountpoint` text NOT NULL,
  `filesystem` text NOT NULL,
  `size` bigint NOT NULL,
  `free` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_printer`
--

CREATE TABLE `computer_printer` (
  `id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `driver` text NOT NULL,
  `paper` text NOT NULL,
  `dpi` text NOT NULL,
  `uri` text NOT NULL,
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_screen`
--

CREATE TABLE `computer_screen` (
  `id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `manufacturer` text NOT NULL,
  `type` text NOT NULL,
  `resolution` text NOT NULL,
  `size` text NOT NULL,
  `manufactured` text NOT NULL,
  `serialno` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `computer_software`
--

CREATE TABLE `computer_software` (
  `id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `software_id` int(11) NOT NULL,
  `version` text NOT NULL,
  `installed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `domainuser`
--

CREATE TABLE `domainuser` (
  `id` int(11) NOT NULL,
  `username` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `domainuser_logon`
--

CREATE TABLE `domainuser_logon` (
  `id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `domainuser_id` int(11) NOT NULL,
  `console` text DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `job`
--

CREATE TABLE `job` (
  `id` int(11) NOT NULL,
  `job_container_id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `package_procedure` text NOT NULL,
  `success_return_codes` text NOT NULL,
  `is_uninstall` tinyint(4) NOT NULL DEFAULT 0,
  `download` tinyint(4) NOT NULL DEFAULT 1,
  `restart` int(11) DEFAULT NULL,
  `shutdown` int(11) DEFAULT NULL,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `state` int(11) NOT NULL DEFAULT 0,
  `return_code` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `last_update` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `job_container`
--

CREATE TABLE `job_container` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `author` text NOT NULL,
  `start_time` datetime NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `notes` text NOT NULL,
  `wol_sent` tinyint(4) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package`
--

CREATE TABLE `package` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `notes` text NOT NULL,
  `version` text NOT NULL,
  `author` text NOT NULL,
  `install_procedure` text NOT NULL,
  `install_procedure_success_return_codes` text NOT NULL,
  `install_procedure_restart` tinyint(4) NOT NULL DEFAULT 0,
  `install_procedure_shutdown` tinyint(4) NOT NULL DEFAULT 0,
  `uninstall_procedure` text NOT NULL,
  `uninstall_procedure_success_return_codes` text NOT NULL,
  `download_for_uninstall` tinyint(4) NOT NULL DEFAULT 0,
  `uninstall_procedure_restart` tinyint(4) NOT NULL DEFAULT 0,
  `uninstall_procedure_shutdown` tinyint(4) NOT NULL DEFAULT 0,
  `created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_group`
--

CREATE TABLE `package_group` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `package_group_member`
--

CREATE TABLE `package_group_member` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `package_group_id` int(11) NOT NULL,
  `sequence` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `report`
--

CREATE TABLE `report` (
  `id` int(11) NOT NULL,
  `report_group_id` int(11) DEFAULT NULL,
  `name` text NOT NULL,
  `notes` text NOT NULL,
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `report`
--

INSERT INTO `report` (`id`, `report_group_id`, `name`, `notes`, `query`) VALUES
(1, 1, 'report_secureboot_disabled', '', 'SELECT id AS computer_id, hostname, os, os_version FROM computer WHERE secure_boot = 0'),
(2, 1, 'report_packages_without_installations', '', 'SELECT p.id AS package_id, name, count(cp.package_id) AS install_count FROM package p LEFT JOIN computer_package cp ON p.id = cp.package_id GROUP BY p.id HAVING install_count = 0'),
(3, 1, 'report_recognized_software_chrome', '', 'SELECT id as software_id, name FROM software WHERE name LIKE \'%chrome%\''),
(4, 1, 'report_domainusers_multiple_computers', '', 'SELECT du.id AS domainuser_id, username, (SELECT count(DISTINCT dl2.computer_id) FROM domainuser_logon dl2 WHERE dl2.domainuser_id = du.id) AS \'computer_count\' FROM domainuser du HAVING computer_count > 1'),
(5, 1, 'report_expired_jobcontainers', '', 'SELECT id AS jobcontainer_id, name, end_time FROM job_container WHERE end_time IS NOT NULL AND end_time < CURRENT_TIME()'),
(6, 1, 'report_preregistered_computers', '', 'SELECT id AS computer_id, hostname FROM computer WHERE last_update IS NULL OR last_update <= \'2000-01-01 00:00:00\''),
(7, 1, 'report_all_monitors', '', 'SELECT c.hostname, cs.* FROM computer_screen cs INNER JOIN computer c ON c.id = cs.computer_id WHERE cs.serialno != ""'),
(8, 1, 'report_7_days_no_agent', '', 'SELECT id AS \'computer_id\', hostname, os, os_version, last_ping FROM computer WHERE last_ping IS NULL OR last_ping < NOW() - INTERVAL 7 DAY');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `report_group`
--

CREATE TABLE `report_group` (
  `id` int NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `report_group`
--

INSERT INTO `report_group` (`id`, `name`) VALUES
(1, 'report_predefined');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL,
  `setting` text NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `setting`
--

INSERT INTO `setting` (`id`, `setting`, `value`) VALUES
(1, 'agent-key', '123'),
(2, 'agent-update-interval', '7200'),
(3, 'agent-registration-enabled', '1'),
(4, 'purge-succeeded-jobs', '7200'),
(5, 'purge-failed-jobs', '7200'),
(6, 'default-restart-timeout', '20'),
(7, 'motd', 'default_motd');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `software`
--

CREATE TABLE `software` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `systemuser`
--

CREATE TABLE `systemuser` (
  `id` int(11) NOT NULL,
  `username` text NOT NULL,
  `fullname` text NOT NULL,
  `password` text DEFAULT NULL,
  `ldap` tinyint(4) NOT NULL DEFAULT 0,
  `email` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
  `mobile` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `computer`
--
ALTER TABLE `computer`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `computer_command`
--
ALTER TABLE `computer_command`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `computer_group`
--
ALTER TABLE `computer_group`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `computer_group_member`
--
ALTER TABLE `computer_group_member`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_computer_group_member_1` (`computer_group_id`),
  ADD KEY `fk_computer_group_member_2` (`computer_id`);

--
-- Indizes für die Tabelle `computer_network`
--
ALTER TABLE `computer_network`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_computer_network_1` (`computer_id`);

--
-- Indizes für die Tabelle `computer_package`
--
ALTER TABLE `computer_package`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_computer_package_1` (`computer_id`),
  ADD KEY `fk_computer_package_2` (`package_id`);

--
-- Indizes für die Tabelle `computer_partition`
--
ALTER TABLE `computer_partition`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_computer_partition_1` (`computer_id`);

--
-- Indizes für die Tabelle `computer_printer`
--
ALTER TABLE `computer_printer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_computer_printer_1` (`computer_id`);

--
-- Indizes für die Tabelle `computer_screen`
--
ALTER TABLE `computer_screen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_computer_screen_1` (`computer_id`);

--
-- Indizes für die Tabelle `computer_software`
--
ALTER TABLE `computer_software`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_computer_software_1` (`computer_id`),
  ADD KEY `fk_computer_software_2` (`software_id`);

--
-- Indizes für die Tabelle `domainuser`
--
ALTER TABLE `domainuser`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `domainuser_logon`
--
ALTER TABLE `domainuser_logon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_domainuser_logon_1` (`domainuser_id`),
  ADD KEY `fk_domainuser_logon_2` (`computer_id`);

--
-- Indizes für die Tabelle `job`
--
ALTER TABLE `job`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_job_1` (`job_container_id`),
  ADD KEY `fk_job_2` (`computer_id`),
  ADD KEY `fk_job_3` (`package_id`);

--
-- Indizes für die Tabelle `job_container`
--
ALTER TABLE `job_container`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `package_group`
--
ALTER TABLE `package_group`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `package_group_member`
--
ALTER TABLE `package_group_member`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_package_group_1` (`package_group_id`),
  ADD KEY `fk_package_group_2` (`package_id`);

--
-- Indizes für die Tabelle `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_report_1` (`report_group_id`);

--
-- Indizes für die Tabelle `report_group`
--
ALTER TABLE `report_group`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `software`
--
ALTER TABLE `software`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `systemuser`
--
ALTER TABLE `systemuser`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `computer`
--
ALTER TABLE `computer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `computer_command`
--
ALTER TABLE `computer_command`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `computer_group`
--
ALTER TABLE `computer_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `computer_group_member`
--
ALTER TABLE `computer_group_member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `computer_network`
--
ALTER TABLE `computer_network`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `computer_package`
--
ALTER TABLE `computer_package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `computer_partition`
--
ALTER TABLE `computer_partition`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `computer_printer`
--
ALTER TABLE `computer_printer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `computer_screen`
--
ALTER TABLE `computer_screen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `computer_software`
--
ALTER TABLE `computer_software`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `domainuser`
--
ALTER TABLE `domainuser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `domainuser_logon`
--
ALTER TABLE `domainuser_logon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `job`
--
ALTER TABLE `job`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `job_container`
--
ALTER TABLE `job_container`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `package`
--
ALTER TABLE `package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `package_group`
--
ALTER TABLE `package_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `package_group_member`
--
ALTER TABLE `package_group_member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `report`
--
ALTER TABLE `report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `report_group`
--
ALTER TABLE `report_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `setting`
--
ALTER TABLE `setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `software`
--
ALTER TABLE `software`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `systemuser`
--
ALTER TABLE `systemuser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `computer_group_member`
--
ALTER TABLE `computer_group_member`
  ADD CONSTRAINT `fk_computer_group_member_1` FOREIGN KEY (`computer_group_id`) REFERENCES `computer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_computer_group_member_2` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `computer_network`
--
ALTER TABLE `computer_network`
  ADD CONSTRAINT `fk_computer_network_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `computer_package`
--
ALTER TABLE `computer_package`
  ADD CONSTRAINT `fk_computer_package_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_computer_package_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `computer_screen`
--
ALTER TABLE `computer_screen`
  ADD CONSTRAINT `fk_computer_screen_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `computer_software`
--
ALTER TABLE `computer_software`
  ADD CONSTRAINT `fk_computer_software_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_computer_software_2` FOREIGN KEY (`software_id`) REFERENCES `software` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `domainuser_logon`
--
ALTER TABLE `domainuser_logon`
  ADD CONSTRAINT `fk_domainuser_logon_1` FOREIGN KEY (`domainuser_id`) REFERENCES `domainuser` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_domainuser_logon_2` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `job`
--
ALTER TABLE `job`
  ADD CONSTRAINT `fk_job_1` FOREIGN KEY (`job_container_id`) REFERENCES `job_container` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_2` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_job_3` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `package_group_member`
--
ALTER TABLE `package_group_member`
  ADD CONSTRAINT `fk_package_group_1` FOREIGN KEY (`package_group_id`) REFERENCES `package_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_package_group_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

--
-- Constraints der Tabelle `computer_partition`
--
ALTER TABLE `computer_partition`
  ADD CONSTRAINT `fk_computer_partition_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `computer_printer`
--
ALTER TABLE `computer_printer`
  ADD CONSTRAINT `fk_computer_printer_1` FOREIGN KEY (`computer_id`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

--
-- Constraints der Tabelle `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `fk_report_1` FOREIGN KEY (`report_group_id`) REFERENCES `report_group` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
