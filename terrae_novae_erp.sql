-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 16. Dez 2025 um 13:26
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `terrae_novae_erp`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `astronauts`
--

CREATE TABLE `astronauts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('training','ready','in_orbit','recovery') DEFAULT 'training',
  `experience_level` int(11) DEFAULT 1,
  `assigned_module_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `astronauts`
--

INSERT INTO `astronauts` (`id`, `user_id`, `name`, `status`, `experience_level`, `assigned_module_id`) VALUES
(1, 1, 'Dieter Schmidt', 'training', 1, NULL),
(2, 1, 'Peter Doofi', 'training', 1, NULL),
(3, 1, 'Hans Look', 'training', 1, NULL),
(4, 1, 'Peere Lachs', 'training', 1, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `building_types`
--

CREATE TABLE `building_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_cost` decimal(15,2) NOT NULL,
  `base_construction_time` int(11) NOT NULL,
  `cost_multiplier` decimal(4,2) DEFAULT 1.50
) ;

--
-- Daten für Tabelle `building_types`
--

INSERT INTO `building_types` (`id`, `name`, `description`, `base_cost`, `base_construction_time`, `cost_multiplier`) VALUES
(1, 'Startrampe Alpha', 'Erlaubt den Start kleiner Raketen.', 10000.00, 3600, 1.50),
(2, 'Forschungslabor', 'Generiert Wissenschaftspunkte.', 25000.00, 7200, 1.50),
(3, 'Hangar', 'Lagerplatz für Raketen.', 5000.00, 1800, 1.50),
(4, 'Astronauten-Zentrum', 'Ausbildung und Training von Raumfahrern.', 50000000.00, 14400, 1.50);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `corporations`
--

CREATE TABLE `corporations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `country_id` int(11) DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `corporations`
--

INSERT INTO `corporations` (`id`, `name`, `country_id`, `specialty`) VALUES
(1, 'ArianeGroup', 2, 'Heavy Launchers'),
(2, 'Avio', 3, 'Light Launchers'),
(3, 'OHB System', 1, 'Satellites'),
(4, 'SpaceX', 4, 'Reusable Launchers');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `countries`
--

CREATE TABLE `countries` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `flag_code` varchar(5) NOT NULL,
  `base_budget_pool` decimal(20,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `countries`
--

INSERT INTO `countries` (`id`, `name`, `flag_code`, `base_budget_pool`, `description`) VALUES
(1, 'Deutschland', 'de', 500000000.00, 'Fokus auf Technologie und Erdbeobachtung.'),
(2, 'Frankreich', 'fr', 550000000.00, 'Fokus auf Trägerraketen (Ariane) und Zugang zum All.'),
(3, 'Italien', 'it', 300000000.00, 'Spezialisiert auf kleine Träger (Vega) und Module.'),
(4, 'USA (NASA)', 'us', 0.00, 'Partner für internationale Kooperationen.');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `event_queue`
--

CREATE TABLE `event_queue` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT current_timestamp(),
  `end_time` datetime NOT NULL,
  `is_processed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `event_queue`
--

INSERT INTO `event_queue` (`id`, `user_id`, `event_type`, `reference_id`, `start_time`, `end_time`, `is_processed`) VALUES
(1, 1, 'MISSION_RETURN', 99, '2025-12-16 04:28:34', '2025-12-16 08:28:34', 1),
(2, 1, 'MISSION_RETURN', 99, '2025-12-16 04:29:10', '2025-12-16 08:29:10', 1),
(3, 1, 'MISSION_RETURN', 99, '2025-12-16 04:29:13', '2025-12-16 08:29:13', 1),
(4, 1, 'MISSION_RETURN', 99, '2025-12-16 04:35:24', '2025-12-16 08:35:24', 1),
(5, 1, 'MISSION_RETURN', 99, '2025-12-16 04:35:26', '2025-12-16 08:35:26', 1),
(6, 1, 'MISSION_RETURN', 99, '2025-12-16 04:35:29', '2025-12-16 08:35:29', 1),
(7, 1, 'MISSION_RETURN', 99, '2025-12-16 04:35:30', '2025-12-16 08:35:30', 1),
(8, 1, 'MISSION_RETURN', 99, '2025-12-16 04:35:31', '2025-12-16 08:35:31', 1),
(9, 1, 'MISSION_RETURN', 99, '2025-12-16 04:35:46', '2025-12-16 08:35:46', 1),
(10, 1, 'MISSION_RETURN', 99, '2025-12-16 04:35:48', '2025-12-16 08:35:48', 1),
(11, 1, 'MISSION_RETURN', 99, '2025-12-16 04:35:49', '2025-12-16 08:35:49', 1),
(12, 1, 'MISSION_RETURN', 99, '2025-12-16 04:42:40', '2025-12-16 08:42:40', 1),
(13, 1, 'MISSION_RETURN', 99, '2025-12-16 09:57:17', '2025-12-16 10:39:38', 1),
(14, 1, 'MISSION_RETURN', 99, '2025-12-16 05:01:29', '2025-12-16 09:01:29', 1),
(15, 1, 'MISSION_RETURN', 99, '2025-12-16 05:01:32', '2025-12-16 09:01:32', 1),
(16, 1, 'MISSION_RETURN', 99, '2025-12-16 10:01:41', '2025-12-16 10:39:38', 1),
(17, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:02:07', '2025-12-16 10:39:38', 1),
(18, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:02:09', '2025-12-16 10:39:38', 1),
(19, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:02:10', '2025-12-16 10:32:10', 1),
(20, 1, 'MISSION_RETURN', 99, '2025-12-16 05:03:00', '2025-12-16 09:03:00', 1),
(21, 1, 'MISSION_RETURN', 99, '2025-12-16 05:03:03', '2025-12-16 09:03:03', 1),
(22, 1, 'MISSION_RETURN', 99, '2025-12-16 05:03:04', '2025-12-16 09:03:04', 1),
(23, 1, 'MISSION_RETURN', 99, '2025-12-16 10:24:27', '2025-12-16 10:39:38', 1),
(24, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:32:36', '2025-12-16 10:39:38', 1),
(25, 1, 'MISSION_RETURN', 99, '2025-12-16 10:39:58', '2025-12-16 10:40:17', 1),
(26, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:40:10', '2025-12-16 10:40:17', 1),
(27, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:40:12', '2025-12-16 10:40:17', 1),
(28, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:40:14', '2025-12-16 10:40:17', 1),
(29, 1, 'MISSION_RETURN', 99, '2025-12-16 10:40:21', '2025-12-16 10:40:21', 1),
(30, 1, 'MISSION_RETURN', 99, '2025-12-16 10:40:24', '2025-12-16 10:40:24', 1),
(31, 1, 'MISSION_RETURN', 100, '2025-12-16 10:40:31', '2025-12-16 10:40:32', 1),
(32, 1, 'MISSION_RETURN', 99, '2025-12-16 10:40:32', '2025-12-16 10:40:32', 1),
(33, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:40:36', '2025-12-16 10:40:40', 1),
(34, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:40:38', '2025-12-16 10:40:40', 1),
(35, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:40:39', '2025-12-16 10:40:40', 1),
(36, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:40:43', '2025-12-16 10:40:46', 1),
(37, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:40:44', '2025-12-16 10:40:46', 1),
(38, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:40:46', '2025-12-16 10:40:46', 1),
(39, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:40:49', '2025-12-16 10:40:55', 1),
(40, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:40:51', '2025-12-16 10:40:55', 1),
(41, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:40:52', '2025-12-16 10:40:55', 1),
(42, 1, 'MISSION_RETURN', 99, '2025-12-16 10:40:54', '2025-12-16 10:40:55', 1),
(43, 1, 'MISSION_RETURN', 100, '2025-12-16 10:40:55', '2025-12-16 10:40:55', 1),
(44, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:41:00', '2025-12-16 10:41:05', 1),
(45, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:41:01', '2025-12-16 10:41:05', 1),
(46, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:41:02', '2025-12-16 10:41:05', 1),
(47, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:04', '2025-12-16 10:41:05', 1),
(48, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:05', '2025-12-16 10:41:05', 1),
(49, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:41:08', '2025-12-16 10:41:14', 1),
(50, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:41:09', '2025-12-16 10:41:14', 1),
(51, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:41:10', '2025-12-16 10:41:14', 1),
(52, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:11', '2025-12-16 10:41:14', 1),
(53, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:12', '2025-12-16 10:41:14', 1),
(54, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:27', '2025-12-16 10:41:29', 1),
(55, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:28', '2025-12-16 10:41:29', 1),
(56, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:32', '2025-12-16 10:41:33', 1),
(57, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:33', '2025-12-16 10:41:33', 1),
(58, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:35', '2025-12-16 10:41:36', 1),
(59, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:36', '2025-12-16 10:41:36', 1),
(60, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:38', '2025-12-16 10:41:39', 1),
(61, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:39', '2025-12-16 10:41:39', 1),
(62, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:41', '2025-12-16 10:41:42', 1),
(63, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:42', '2025-12-16 10:41:42', 1),
(64, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:44', '2025-12-16 10:41:46', 1),
(65, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:45', '2025-12-16 10:41:46', 1),
(66, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:48', '2025-12-16 10:41:49', 1),
(67, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:49', '2025-12-16 10:41:49', 1),
(68, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:51', '2025-12-16 10:41:52', 1),
(69, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:52', '2025-12-16 10:41:52', 1),
(70, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:55', '2025-12-16 10:41:56', 1),
(71, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:56', '2025-12-16 10:41:56', 1),
(72, 1, 'MISSION_RETURN', 99, '2025-12-16 10:41:58', '2025-12-16 10:41:59', 1),
(73, 1, 'MISSION_RETURN', 100, '2025-12-16 10:41:59', '2025-12-16 10:41:59', 1),
(74, 1, 'MISSION_RETURN', 99, '2025-12-16 10:42:01', '2025-12-16 10:42:02', 1),
(75, 1, 'MISSION_RETURN', 100, '2025-12-16 10:42:02', '2025-12-16 10:42:02', 1),
(76, 1, 'MISSION_RETURN', 99, '2025-12-16 10:42:04', '2025-12-16 10:42:05', 1),
(77, 1, 'MISSION_RETURN', 100, '2025-12-16 10:42:05', '2025-12-16 10:42:05', 1),
(78, 1, 'MISSION_RETURN', 101, '2025-12-16 10:42:22', '2025-12-16 10:42:25', 1),
(79, 1, 'MISSION_RETURN', 100, '2025-12-16 10:42:24', '2025-12-16 10:42:25', 1),
(80, 1, 'MISSION_RETURN', 99, '2025-12-16 10:42:25', '2025-12-16 10:42:25', 1),
(81, 1, 'MISSION_RETURN', 101, '2025-12-16 10:42:30', '2025-12-16 10:42:33', 1),
(82, 1, 'MISSION_RETURN', 99, '2025-12-16 10:42:31', '2025-12-16 10:42:33', 1),
(83, 1, 'MISSION_RETURN', 100, '2025-12-16 10:42:32', '2025-12-16 10:42:33', 1),
(84, 1, 'MISSION_RETURN', 101, '2025-12-16 10:42:44', '2025-12-16 10:42:50', 1),
(85, 1, 'MISSION_RETURN', 100, '2025-12-16 10:42:47', '2025-12-16 10:42:50', 1),
(86, 1, 'MISSION_RETURN', 99, '2025-12-16 10:42:47', '2025-12-16 10:42:50', 1),
(87, 1, 'MISSION_RETURN', 101, '2025-12-16 10:42:58', '2025-12-16 10:43:03', 1),
(88, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:42:59', '2025-12-16 10:43:03', 1),
(89, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:43:00', '2025-12-16 10:43:03', 1),
(90, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:43:01', '2025-12-16 10:43:03', 1),
(91, 1, 'MISSION_RETURN', 99, '2025-12-16 10:43:02', '2025-12-16 10:43:03', 1),
(92, 1, 'MISSION_RETURN', 100, '2025-12-16 10:43:03', '2025-12-16 10:43:03', 1),
(93, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:43:08', '2025-12-16 10:43:23', 1),
(94, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:43:09', '2025-12-16 10:43:23', 1),
(95, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:43:10', '2025-12-16 10:43:23', 1),
(96, 1, 'MISSION_RETURN', 99, '2025-12-16 10:43:11', '2025-12-16 10:43:23', 1),
(97, 1, 'MISSION_RETURN', 100, '2025-12-16 10:43:12', '2025-12-16 10:43:23', 1),
(98, 1, 'MISSION_RETURN', 101, '2025-12-16 10:43:15', '2025-12-16 10:43:23', 1),
(99, 1, 'MISSION_RETURN', 99, '2025-12-16 10:43:31', '2025-12-16 10:44:00', 1),
(100, 1, 'MISSION_RETURN', 100, '2025-12-16 10:43:32', '2025-12-16 10:44:00', 1),
(101, 1, 'MISSION_RETURN', 101, '2025-12-16 10:43:35', '2025-12-16 10:44:00', 1),
(102, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:43:39', '2025-12-16 10:44:00', 1),
(103, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:43:40', '2025-12-16 10:44:00', 1),
(104, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:43:42', '2025-12-16 10:44:00', 1),
(105, 1, 'MISSION_RETURN', 99, '2025-12-16 10:46:32', '2025-12-16 10:46:55', 1),
(106, 1, 'MISSION_RETURN', 100, '2025-12-16 10:46:34', '2025-12-16 10:46:55', 1),
(107, 1, 'MISSION_RETURN', 101, '2025-12-16 10:46:39', '2025-12-16 10:46:55', 1),
(108, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:46:43', '2025-12-16 10:46:55', 1),
(109, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:46:44', '2025-12-16 10:46:55', 1),
(110, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:46:45', '2025-12-16 10:46:55', 1),
(111, 1, 'MISSION_RETURN', 99, '2025-12-16 10:46:58', '2025-12-16 10:47:02', 1),
(112, 1, 'MISSION_RETURN', 100, '2025-12-16 10:46:59', '2025-12-16 10:47:02', 1),
(113, 1, 'MISSION_RETURN', 101, '2025-12-16 10:47:02', '2025-12-16 10:47:02', 1),
(114, 1, 'MISSION_RETURN', 99, '2025-12-16 10:53:17', '2025-12-16 10:53:25', 1),
(115, 1, 'MISSION_RETURN', 100, '2025-12-16 10:53:19', '2025-12-16 10:53:25', 1),
(116, 1, 'MISSION_RETURN', 101, '2025-12-16 10:53:23', '2025-12-16 10:53:25', 1),
(117, 1, 'MISSION_RETURN', 99, '2025-12-16 10:54:19', '2025-12-16 10:54:24', 1),
(118, 1, 'MISSION_RETURN', 100, '2025-12-16 10:54:20', '2025-12-16 10:54:24', 1),
(119, 1, 'MISSION_RETURN', 101, '2025-12-16 10:54:23', '2025-12-16 10:54:24', 1),
(120, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:54:42', '2025-12-16 10:54:51', 1),
(121, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:54:43', '2025-12-16 10:54:51', 1),
(122, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:54:43', '2025-12-16 10:54:51', 1),
(123, 1, 'MISSION_RETURN', 99, '2025-12-16 10:54:44', '2025-12-16 10:54:51', 1),
(124, 1, 'MISSION_RETURN', 100, '2025-12-16 10:54:45', '2025-12-16 10:54:51', 1),
(125, 1, 'MISSION_RETURN', 101, '2025-12-16 10:54:48', '2025-12-16 10:54:51', 1),
(126, 1, 'MISSION_RETURN', 99, '2025-12-16 10:54:54', '2025-12-16 10:54:58', 1),
(127, 1, 'MISSION_RETURN', 100, '2025-12-16 10:54:55', '2025-12-16 10:54:58', 1),
(128, 1, 'MISSION_RETURN', 101, '2025-12-16 10:54:57', '2025-12-16 10:54:58', 1),
(129, 1, 'MISSION_RETURN', 102, '2025-12-16 10:55:04', '2025-12-16 10:55:15', 1),
(130, 1, 'MISSION_RETURN', 99, '2025-12-16 10:55:05', '2025-12-16 10:55:15', 1),
(131, 1, 'MISSION_RETURN', 100, '2025-12-16 10:55:06', '2025-12-16 10:55:15', 1),
(132, 1, 'MISSION_RETURN', 101, '2025-12-16 10:55:10', '2025-12-16 10:55:15', 1),
(133, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 10:55:13', '2025-12-16 10:55:15', 1),
(134, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 10:55:14', '2025-12-16 10:55:15', 1),
(135, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 10:55:14', '2025-12-16 10:55:15', 1),
(136, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 11:08:19', '2025-12-16 11:08:45', 1),
(137, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 11:08:20', '2025-12-16 11:08:45', 1),
(138, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 11:08:21', '2025-12-16 11:08:45', 1),
(139, 1, 'MISSION_RETURN', 99, '2025-12-16 11:08:25', '2025-12-16 11:08:45', 1),
(140, 1, 'MISSION_RETURN', 100, '2025-12-16 11:08:34', '2025-12-16 11:08:45', 1),
(141, 1, 'MISSION_RETURN', 101, '2025-12-16 11:08:37', '2025-12-16 11:08:45', 1),
(142, 1, 'MISSION_RETURN', 102, '2025-12-16 11:08:42', '2025-12-16 11:08:45', 1),
(143, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 11:09:08', '2025-12-16 11:09:10', 1),
(144, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 11:09:09', '2025-12-16 11:09:10', 1),
(145, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 11:09:09', '2025-12-16 11:09:10', 1),
(146, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 11:09:13', '2025-12-16 11:09:26', 1),
(147, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 11:09:14', '2025-12-16 11:09:26', 1),
(148, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 11:09:15', '2025-12-16 11:09:26', 1),
(149, 1, 'MISSION_RETURN', 99, '2025-12-16 11:09:18', '2025-12-16 11:09:26', 1),
(150, 1, 'MISSION_RETURN', 100, '2025-12-16 11:09:19', '2025-12-16 11:09:26', 1),
(151, 1, 'MISSION_RETURN', 101, '2025-12-16 11:09:22', '2025-12-16 11:09:26', 1),
(152, 1, 'MISSION_RETURN', 102, '2025-12-16 11:09:25', '2025-12-16 11:09:26', 1),
(153, 1, 'MISSION_RETURN', 99, '2025-12-16 11:09:36', '2025-12-16 11:09:44', 1),
(154, 1, 'MISSION_RETURN', 100, '2025-12-16 11:09:37', '2025-12-16 11:09:44', 1),
(155, 1, 'MISSION_RETURN', 101, '2025-12-16 11:09:38', '2025-12-16 11:09:44', 1),
(156, 1, 'MISSION_RETURN', 102, '2025-12-16 11:09:41', '2025-12-16 11:09:44', 1),
(157, 1, 'MISSION_RETURN', 99, '2025-12-16 11:09:49', '2025-12-16 11:10:20', 1),
(158, 1, 'MISSION_RETURN', 100, '2025-12-16 11:09:50', '2025-12-16 11:10:20', 1),
(159, 1, 'MISSION_RETURN', 101, '2025-12-16 11:09:54', '2025-12-16 11:10:20', 1),
(160, 1, 'MISSION_RETURN', 102, '2025-12-16 11:09:58', '2025-12-16 11:10:20', 1),
(161, 1, 'MISSION_RETURN', 99, '2025-12-16 11:17:32', '2025-12-16 11:17:45', 1),
(162, 1, 'MISSION_RETURN', 100, '2025-12-16 11:17:33', '2025-12-16 11:17:45', 1),
(163, 1, 'MISSION_RETURN', 101, '2025-12-16 11:17:35', '2025-12-16 11:17:45', 1),
(164, 1, 'MISSION_RETURN', 102, '2025-12-16 11:17:37', '2025-12-16 11:17:45', 1),
(165, 1, 'MISSION_RETURN', 99, '2025-12-16 11:17:52', '2025-12-16 11:18:00', 1),
(166, 1, 'MISSION_RETURN', 100, '2025-12-16 11:17:53', '2025-12-16 11:18:00', 1),
(167, 1, 'MISSION_RETURN', 101, '2025-12-16 11:17:54', '2025-12-16 11:18:00', 1),
(168, 1, 'MISSION_RETURN', 102, '2025-12-16 11:17:55', '2025-12-16 11:18:00', 1),
(169, 1, 'MISSION_RETURN', 99, '2025-12-16 11:18:03', '2025-12-16 11:18:17', 1),
(170, 1, 'MISSION_RETURN', 100, '2025-12-16 11:18:04', '2025-12-16 11:18:17', 1),
(171, 1, 'MISSION_RETURN', 102, '2025-12-16 11:18:08', '2025-12-16 11:18:17', 1),
(172, 1, 'MISSION_RETURN', 101, '2025-12-16 11:18:09', '2025-12-16 11:18:17', 1),
(173, 1, 'MISSION_RETURN', 99, '2025-12-16 11:18:21', '2025-12-16 11:18:29', 1),
(174, 1, 'MISSION_RETURN', 100, '2025-12-16 11:18:22', '2025-12-16 11:18:29', 1),
(175, 1, 'MISSION_RETURN', 101, '2025-12-16 11:18:24', '2025-12-16 11:18:29', 1),
(176, 1, 'MISSION_RETURN', 102, '2025-12-16 11:18:26', '2025-12-16 11:18:29', 1),
(177, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 11:18:49', '2025-12-16 11:19:05', 1),
(178, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 11:18:50', '2025-12-16 11:19:05', 1),
(179, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 11:18:52', '2025-12-16 11:19:05', 1),
(180, 1, 'MISSION_RETURN', 99, '2025-12-16 11:18:53', '2025-12-16 11:19:05', 1),
(181, 1, 'MISSION_RETURN', 100, '2025-12-16 11:18:54', '2025-12-16 11:19:05', 1),
(182, 1, 'MISSION_RETURN', 101, '2025-12-16 11:18:57', '2025-12-16 11:19:05', 1),
(183, 1, 'MISSION_RETURN', 102, '2025-12-16 11:19:00', '2025-12-16 11:19:05', 1),
(184, 1, 'MISSION_RETURN', 99, '2025-12-16 11:19:08', '2025-12-16 11:19:14', 1),
(185, 1, 'MISSION_RETURN', 100, '2025-12-16 11:19:09', '2025-12-16 11:19:14', 1),
(186, 1, 'MISSION_RETURN', 102, '2025-12-16 11:19:12', '2025-12-16 11:19:14', 1),
(187, 1, 'MISSION_RETURN', 101, '2025-12-16 11:19:14', '2025-12-16 11:19:14', 1),
(196, 1, 'MISSION_RETURN', 99, '2025-12-16 11:44:52', '2025-12-16 11:45:08', 1),
(197, 1, 'MISSION_RETURN', 100, '2025-12-16 11:44:54', '2025-12-16 11:45:08', 1),
(198, 1, 'MISSION_RETURN', 101, '2025-12-16 11:44:57', '2025-12-16 11:45:08', 1),
(199, 1, 'MISSION_RETURN', 102, '2025-12-16 11:45:00', '2025-12-16 11:45:08', 1),
(200, 1, 'MISSION_RETURN', 99, '2025-12-16 06:45:23', '2025-12-16 10:45:23', 1),
(201, 1, 'MISSION_RETURN', 99, '2025-12-16 06:45:26', '2025-12-16 10:45:26', 1),
(202, 1, 'MISSION_RETURN', 99, '2025-12-16 06:45:27', '2025-12-16 10:45:27', 1),
(203, 1, 'MISSION_RETURN', 99, '2025-12-16 06:45:27', '2025-12-16 10:45:27', 1),
(204, 1, 'MISSION_RETURN', 99, '2025-12-16 06:45:28', '2025-12-16 10:45:28', 1),
(205, 1, 'MISSION_RETURN', 99, '2025-12-16 06:45:28', '2025-12-16 10:45:28', 1),
(206, 1, 'MISSION_RETURN', 99, '2025-12-16 06:45:28', '2025-12-16 10:45:28', 1),
(207, 1, 'MISSION_RETURN', 99, '2025-12-16 06:45:28', '2025-12-16 10:45:28', 1),
(208, 1, 'MISSION_RETURN', 99, '2025-12-16 06:45:29', '2025-12-16 10:45:29', 1),
(209, 1, 'MISSION_RETURN', 99, '2025-12-16 06:45:30', '2025-12-16 10:45:30', 1),
(210, 1, 'MISSION_RETURN', 99, '2025-12-16 06:47:33', '2025-12-16 10:47:33', 1),
(211, 1, 'MISSION_RETURN', 99, '2025-12-16 06:47:35', '2025-12-16 10:47:35', 1),
(212, 1, 'MISSION_RETURN', 99, '2025-12-16 06:47:35', '2025-12-16 10:47:35', 1),
(213, 1, 'MISSION_RETURN', 99, '2025-12-16 11:47:39', '2025-12-16 11:47:40', 1),
(217, 1, 'MISSION_RETURN', 99, '2025-12-16 11:48:02', '2025-12-16 11:48:17', 1),
(218, 1, 'MISSION_RETURN', 100, '2025-12-16 11:48:03', '2025-12-16 11:48:17', 1),
(219, 1, 'MISSION_RETURN', 101, '2025-12-16 11:48:06', '2025-12-16 11:48:17', 1),
(220, 1, 'MISSION_RETURN', 102, '2025-12-16 11:48:09', '2025-12-16 11:48:17', 1),
(225, 1, 'MISSION_RETURN', 99, '2025-12-16 12:39:40', '2025-12-16 12:39:49', 1),
(226, 1, 'MISSION_RETURN', 100, '2025-12-16 12:39:42', '2025-12-16 12:39:49', 1),
(227, 1, 'MISSION_RETURN', 101, '2025-12-16 12:39:45', '2025-12-16 12:39:49', 1),
(228, 1, 'MISSION_RETURN', 102, '2025-12-16 12:39:48', '2025-12-16 12:39:49', 1),
(230, 1, 'MISSION_RETURN', 102, '2025-12-16 12:40:12', '2025-12-16 12:40:20', 1),
(231, 1, 'MISSION_RETURN', 101, '2025-12-16 12:40:14', '2025-12-16 12:40:20', 1),
(232, 1, 'MISSION_RETURN', 100, '2025-12-16 12:40:16', '2025-12-16 12:40:20', 1),
(233, 1, 'MISSION_RETURN', 99, '2025-12-16 12:40:18', '2025-12-16 12:40:20', 1),
(234, 1, 'MISSION_RETURN', 99, '2025-12-16 12:43:38', '2025-12-16 12:44:05', 1),
(235, 1, 'MISSION_RETURN', 100, '2025-12-16 12:43:39', '2025-12-16 12:44:05', 1),
(236, 1, 'MISSION_RETURN', 102, '2025-12-16 12:43:45', '2025-12-16 12:44:05', 1),
(237, 1, 'MISSION_RETURN', 101, '2025-12-16 12:43:46', '2025-12-16 12:44:05', 1),
(241, 1, 'BUILDING_UPGRADE', 4, '2025-12-16 12:48:17', '2025-12-16 12:49:51', 1),
(243, 1, 'MISSION_RETURN', 102, '2025-12-16 12:50:20', '2025-12-16 12:50:23', 1),
(244, 1, 'MISSION_RETURN', 101, '2025-12-16 12:50:23', '2025-12-16 12:50:23', 1),
(245, 1, 'MISSION_RETURN', 102, '2025-12-16 12:50:29', '2025-12-16 12:50:29', 1),
(246, 1, 'MISSION_RETURN', 102, '2025-12-16 12:50:33', '2025-12-16 12:50:33', 1),
(247, 1, 'MISSION_RETURN', 102, '2025-12-16 12:50:36', '2025-12-16 12:50:36', 1),
(252, 1, 'MISSION_RETURN', 102, '2025-12-16 12:58:17', '2025-12-16 12:58:17', 1),
(253, 1, 'MISSION_RETURN', 102, '2025-12-16 12:58:22', '2025-12-16 12:58:27', 1),
(254, 1, 'MISSION_RETURN', 101, '2025-12-16 12:58:23', '2025-12-16 12:58:27', 1),
(255, 1, 'MISSION_RETURN', 100, '2025-12-16 12:58:25', '2025-12-16 12:58:27', 1),
(256, 1, 'MISSION_RETURN', 99, '2025-12-16 12:58:27', '2025-12-16 12:58:27', 1),
(257, 1, 'MISSION_RETURN', 102, '2025-12-16 12:58:30', '2025-12-16 12:58:34', 1),
(258, 1, 'MISSION_RETURN', 101, '2025-12-16 12:58:33', '2025-12-16 12:58:34', 1),
(259, 1, 'MISSION_RETURN', 100, '2025-12-16 12:58:34', '2025-12-16 12:58:34', 1),
(264, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 12:59:35', '2025-12-16 12:59:50', 1),
(265, 1, 'BUILDING_UPGRADE', 4, '2025-12-16 12:59:38', '2025-12-16 12:59:50', 1),
(266, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 12:59:39', '2025-12-16 12:59:50', 1),
(267, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 12:59:40', '2025-12-16 12:59:50', 1),
(271, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 13:06:03', '2025-12-16 13:06:03', 1),
(272, 1, 'MISSION_RETURN', 101, '2025-12-16 13:06:09', '2025-12-16 13:06:09', 1),
(273, 1, 'MISSION_RETURN', 101, '2025-12-16 13:06:22', '2025-12-16 13:06:54', 1),
(274, 1, 'MISSION_RETURN', 102, '2025-12-16 13:06:25', '2025-12-16 13:06:54', 1),
(275, 1, 'MISSION_RETURN', 99, '2025-12-16 13:06:29', '2025-12-16 13:06:54', 1),
(276, 1, 'MISSION_RETURN', 100, '2025-12-16 13:06:32', '2025-12-16 13:06:54', 1),
(277, 1, 'BUILDING_UPGRADE', 1, '2025-12-16 13:06:33', '2025-12-16 13:06:54', 1),
(278, 1, 'BUILDING_UPGRADE', 2, '2025-12-16 13:06:35', '2025-12-16 13:06:54', 1),
(279, 1, 'BUILDING_UPGRADE', 3, '2025-12-16 13:06:36', '2025-12-16 13:06:54', 1),
(280, 1, 'BUILDING_UPGRADE', 4, '2025-12-16 13:06:37', '2025-12-16 13:06:54', 1),
(285, 1, 'MISSION_RETURN', 99, '2025-12-16 13:11:38', '2025-12-16 13:11:38', 1),
(286, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:17', '2025-12-16 12:13:17', 1),
(287, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:17', '2025-12-16 12:13:17', 1),
(288, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:17', '2025-12-16 12:13:17', 1),
(289, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:18', '2025-12-16 12:13:18', 1),
(290, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:18', '2025-12-16 12:13:18', 1),
(291, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:18', '2025-12-16 12:13:18', 1),
(292, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:18', '2025-12-16 12:13:18', 1),
(293, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:18', '2025-12-16 12:13:18', 1),
(294, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:18', '2025-12-16 12:13:18', 1),
(295, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:19', '2025-12-16 12:13:19', 1),
(296, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:19', '2025-12-16 12:13:19', 1),
(297, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:19', '2025-12-16 12:13:19', 1),
(298, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:19', '2025-12-16 12:13:19', 1),
(299, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:19', '2025-12-16 12:13:19', 1),
(300, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:20', '2025-12-16 12:13:20', 1),
(301, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:20', '2025-12-16 12:13:20', 1),
(302, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:20', '2025-12-16 12:13:20', 1),
(303, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:20', '2025-12-16 12:13:20', 1),
(304, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:20', '2025-12-16 12:13:20', 1),
(305, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:20', '2025-12-16 12:13:20', 1),
(306, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:21', '2025-12-16 12:13:21', 1),
(307, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:21', '2025-12-16 12:13:21', 1),
(308, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:21', '2025-12-16 12:13:21', 1),
(309, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:21', '2025-12-16 12:13:21', 1),
(310, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:21', '2025-12-16 12:13:21', 1),
(311, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:22', '2025-12-16 12:13:22', 1),
(312, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:22', '2025-12-16 12:13:22', 1),
(313, 1, 'MISSION_RETURN', 99, '2025-12-16 08:13:22', '2025-12-16 12:13:22', 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mission_types`
--

CREATE TABLE `mission_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `required_cargo_capacity` int(11) NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `reward_money` decimal(15,2) NOT NULL,
  `reward_science` int(11) NOT NULL,
  `min_prestige_required` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `mission_types`
--

INSERT INTO `mission_types` (`id`, `name`, `description`, `required_cargo_capacity`, `duration_seconds`, `reward_money`, `reward_science`, `min_prestige_required`) VALUES
(1, 'CubeSat Deploy', 'Bringe kleine Uni-Satelliten in den Orbit.', 500, 7200, 2000000.00, 10, 0),
(2, 'ISS Resupply', 'Versorgungsgüter zur Raumstation.', 8000, 21600, 15000000.00, 50, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `rocket_types`
--

CREATE TABLE `rocket_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `manufacturer` varchar(50) DEFAULT NULL,
  `cost` decimal(15,2) NOT NULL,
  `cargo_capacity_leo` int(11) NOT NULL,
  `reliability` decimal(4,3) DEFAULT 0.950,
  `image_path` varchar(255) DEFAULT NULL,
  `manufacturer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `rocket_types`
--

INSERT INTO `rocket_types` (`id`, `name`, `manufacturer`, `cost`, `cargo_capacity_leo`, `reliability`, `image_path`, `manufacturer_id`) VALUES
(1, 'Vega-C', 'Avio (Italien)', 45000000.00, 2300, 0.920, NULL, 2),
(2, 'Ariane 62', 'Arianespace (Frankreich)', 75000000.00, 10300, 0.960, NULL, 1),
(3, 'Falcon 9', 'SpaceX (USA)', 62000000.00, 22800, 0.985, NULL, 4);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `specialists`
--

CREATE TABLE `specialists` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Scientist','Engineer') NOT NULL,
  `salary_cost` int(11) NOT NULL,
  `skill_value` int(11) NOT NULL,
  `avatar_image` varchar(50) DEFAULT 'avatar_1.png',
  `user_id` int(11) DEFAULT NULL,
  `busy_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `specialists`
--

INSERT INTO `specialists` (`id`, `name`, `type`, `salary_cost`, `skill_value`, `avatar_image`, `user_id`, `busy_until`) VALUES
(1, 'Dr. Schmidt', 'Scientist', 50000, 10, 'avatar_scientist_m.png', 1, '2025-12-16 13:06:54'),
(2, 'Prof. Hamilton', 'Scientist', 120000, 25, 'avatar_scientist_f.png', 1, '2025-12-16 13:06:54'),
(3, 'Ing. Kowalski', 'Engineer', 45000, 5, 'avatar_engineer_m.png', 1, '2025-12-16 13:06:54');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `station_module_types`
--

CREATE TABLE `station_module_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `mass_kg` int(11) NOT NULL,
  `cost` decimal(20,2) NOT NULL,
  `build_time_seconds` int(11) NOT NULL,
  `tech_id_required` int(11) DEFAULT NULL,
  `benefit_text` varchar(100) DEFAULT NULL,
  `power_generation` int(11) DEFAULT 0,
  `crew_capacity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `station_module_types`
--

INSERT INTO `station_module_types` (`id`, `name`, `description`, `mass_kg`, `cost`, `build_time_seconds`, `tech_id_required`, `benefit_text`, `power_generation`, `crew_capacity`) VALUES
(1, 'Harmony Kern-Modul', 'Das erste Bauteil. Bietet Strom, Lebenserhaltung und Andockknoten.', 12500, 150000000.00, 7200, 4, 'Ermöglicht den Bau der Station', 50, 2),
(2, 'Columbus Labor', 'Europäisches Forschungslabor.', 10300, 220000000.00, 10800, 3, '+150 SP/h wenn aktiv', -10, 2),
(3, 'Koppola Beobachtungskuppel', 'Bietet fantastische Aussicht. Steigert die Moral.', 4000, 50000000.00, 3600, 3, 'Prestige Bonus', -5, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `technologies`
--

CREATE TABLE `technologies` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cost_science_points` int(11) NOT NULL,
  `parent_tech_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `technologies`
--

INSERT INTO `technologies` (`id`, `name`, `description`, `cost_science_points`, `parent_tech_id`) VALUES
(1, 'Grundlagen der Orbitalmechanik', 'Erlaubt präzise Manöver im Orbit. Basis für alles weitere.', 50, NULL),
(2, 'Lebenserhaltungssysteme', 'Notwendig, um Menschen länger als ein paar Tage im All zu halten.', 150, 1),
(3, 'Modulare Konstruktion', 'Die Fähigkeit, Stationsteile im All zu koppeln.', 300, 1),
(4, 'Raumstations-Kernmodul', 'Der Bauplan für das erste Modul deiner Station!', 1000, 3),
(5, 'Wiederverwendbare Booster', 'Senkt die Startkosten für Raketen um 10%.', 500, 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_active` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `created_at`, `last_active`) VALUES
(1, 'Elon', 'hash123', 'elon@mars.com', '2025-12-16 08:28:34', '2025-12-16 12:13:26');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_buildings`
--

CREATE TABLE `user_buildings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `building_type_id` int(11) NOT NULL,
  `current_level` int(11) DEFAULT 1,
  `status` enum('active','upgrading','damaged') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `user_buildings`
--

INSERT INTO `user_buildings` (`id`, `user_id`, `building_type_id`, `current_level`, `status`) VALUES
(1, 1, 1, 20, 'active'),
(2, 1, 2, 19, 'active'),
(3, 1, 3, 20, 'active'),
(4, 1, 4, 3, 'active');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_fleet`
--

CREATE TABLE `user_fleet` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rocket_type_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `status` enum('idle','in_mission','maintenance','destroyed') DEFAULT 'idle',
  `flights_completed` int(11) DEFAULT 0,
  `current_mission_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `user_fleet`
--

INSERT INTO `user_fleet` (`id`, `user_id`, `rocket_type_id`, `name`, `status`, `flights_completed`, `current_mission_id`) VALUES
(99, 1, 1, 'Test-Rakete', 'idle', 112, NULL),
(100, 1, 1, 'Vega-C #217', 'idle', 47, NULL),
(101, 1, 3, 'Falcon 9 #959', 'idle', 33, NULL),
(102, 1, 2, 'Ariane 62 #104', 'idle', 24, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_modules`
--

CREATE TABLE `user_modules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_type_id` int(11) NOT NULL,
  `status` enum('constructing','stored','launched','assembled') DEFAULT 'constructing',
  `condition_percent` int(11) DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `user_modules`
--

INSERT INTO `user_modules` (`id`, `user_id`, `module_type_id`, `status`, `condition_percent`, `created_at`) VALUES
(1, 1, 2, 'constructing', 100, '2025-12-16 11:39:26'),
(2, 1, 1, 'constructing', 100, '2025-12-16 11:39:53'),
(3, 1, 2, 'constructing', 100, '2025-12-16 11:59:42'),
(4, 1, 1, 'constructing', 100, '2025-12-16 11:59:44'),
(5, 1, 3, 'constructing', 100, '2025-12-16 11:59:46');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_reputation`
--

CREATE TABLE `user_reputation` (
  `user_id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `reputation` int(11) DEFAULT 50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_resources`
--

CREATE TABLE `user_resources` (
  `user_id` int(11) NOT NULL,
  `money` decimal(20,2) DEFAULT 50000.00,
  `science_points` int(11) DEFAULT 0,
  `prestige` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `user_resources`
--

INSERT INTO `user_resources` (`user_id`, `money`, `science_points`, `prestige`) VALUES
(1, 514815407.11, 13888, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_technologies`
--

CREATE TABLE `user_technologies` (
  `user_id` int(11) NOT NULL,
  `tech_id` int(11) NOT NULL,
  `researched_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `user_technologies`
--

INSERT INTO `user_technologies` (`user_id`, `tech_id`, `researched_at`) VALUES
(1, 1, '2025-12-16 10:08:55'),
(1, 2, '2025-12-16 10:09:31'),
(1, 3, '2025-12-16 10:17:42'),
(1, 4, '2025-12-16 10:19:36'),
(1, 5, '2025-12-16 10:18:39');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `astronauts`
--
ALTER TABLE `astronauts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_module_id` (`assigned_module_id`);

--
-- Indizes für die Tabelle `building_types`
--
ALTER TABLE `building_types`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `corporations`
--
ALTER TABLE `corporations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indizes für die Tabelle `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `event_queue`
--
ALTER TABLE `event_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `end_time` (`end_time`),
  ADD KEY `is_processed` (`is_processed`);

--
-- Indizes für die Tabelle `mission_types`
--
ALTER TABLE `mission_types`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `rocket_types`
--
ALTER TABLE `rocket_types`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `specialists`
--
ALTER TABLE `specialists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indizes für die Tabelle `station_module_types`
--
ALTER TABLE `station_module_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tech_id_required` (`tech_id_required`);

--
-- Indizes für die Tabelle `technologies`
--
ALTER TABLE `technologies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_tech_id` (`parent_tech_id`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `username_2` (`username`);

--
-- Indizes für die Tabelle `user_buildings`
--
ALTER TABLE `user_buildings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_building` (`user_id`,`building_type_id`),
  ADD KEY `building_type_id` (`building_type_id`);

--
-- Indizes für die Tabelle `user_fleet`
--
ALTER TABLE `user_fleet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `rocket_type_id` (`rocket_type_id`),
  ADD KEY `fk_current_mission` (`current_mission_id`);

--
-- Indizes für die Tabelle `user_modules`
--
ALTER TABLE `user_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `module_type_id` (`module_type_id`);

--
-- Indizes für die Tabelle `user_reputation`
--
ALTER TABLE `user_reputation`
  ADD PRIMARY KEY (`user_id`,`country_id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indizes für die Tabelle `user_resources`
--
ALTER TABLE `user_resources`
  ADD PRIMARY KEY (`user_id`);

--
-- Indizes für die Tabelle `user_technologies`
--
ALTER TABLE `user_technologies`
  ADD PRIMARY KEY (`user_id`,`tech_id`),
  ADD KEY `tech_id` (`tech_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `astronauts`
--
ALTER TABLE `astronauts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `building_types`
--
ALTER TABLE `building_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `corporations`
--
ALTER TABLE `corporations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `event_queue`
--
ALTER TABLE `event_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=314;

--
-- AUTO_INCREMENT für Tabelle `mission_types`
--
ALTER TABLE `mission_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `rocket_types`
--
ALTER TABLE `rocket_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `specialists`
--
ALTER TABLE `specialists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `station_module_types`
--
ALTER TABLE `station_module_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `technologies`
--
ALTER TABLE `technologies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT für Tabelle `user_buildings`
--
ALTER TABLE `user_buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `user_fleet`
--
ALTER TABLE `user_fleet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT für Tabelle `user_modules`
--
ALTER TABLE `user_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `astronauts`
--
ALTER TABLE `astronauts`
  ADD CONSTRAINT `astronauts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `astronauts_ibfk_2` FOREIGN KEY (`assigned_module_id`) REFERENCES `user_modules` (`id`);

--
-- Constraints der Tabelle `corporations`
--
ALTER TABLE `corporations`
  ADD CONSTRAINT `corporations_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`);

--
-- Constraints der Tabelle `event_queue`
--
ALTER TABLE `event_queue`
  ADD CONSTRAINT `event_queue_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `specialists`
--
ALTER TABLE `specialists`
  ADD CONSTRAINT `specialists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `station_module_types`
--
ALTER TABLE `station_module_types`
  ADD CONSTRAINT `station_module_types_ibfk_1` FOREIGN KEY (`tech_id_required`) REFERENCES `technologies` (`id`);

--
-- Constraints der Tabelle `technologies`
--
ALTER TABLE `technologies`
  ADD CONSTRAINT `technologies_ibfk_1` FOREIGN KEY (`parent_tech_id`) REFERENCES `technologies` (`id`);

--
-- Constraints der Tabelle `user_buildings`
--
ALTER TABLE `user_buildings`
  ADD CONSTRAINT `user_buildings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_buildings_ibfk_2` FOREIGN KEY (`building_type_id`) REFERENCES `building_types` (`id`);

--
-- Constraints der Tabelle `user_fleet`
--
ALTER TABLE `user_fleet`
  ADD CONSTRAINT `fk_current_mission` FOREIGN KEY (`current_mission_id`) REFERENCES `mission_types` (`id`),
  ADD CONSTRAINT `user_fleet_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_fleet_ibfk_2` FOREIGN KEY (`rocket_type_id`) REFERENCES `rocket_types` (`id`);

--
-- Constraints der Tabelle `user_modules`
--
ALTER TABLE `user_modules`
  ADD CONSTRAINT `user_modules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_modules_ibfk_2` FOREIGN KEY (`module_type_id`) REFERENCES `station_module_types` (`id`);

--
-- Constraints der Tabelle `user_reputation`
--
ALTER TABLE `user_reputation`
  ADD CONSTRAINT `user_reputation_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_reputation_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`);

--
-- Constraints der Tabelle `user_resources`
--
ALTER TABLE `user_resources`
  ADD CONSTRAINT `user_resources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `user_technologies`
--
ALTER TABLE `user_technologies`
  ADD CONSTRAINT `user_technologies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_technologies_ibfk_2` FOREIGN KEY (`tech_id`) REFERENCES `technologies` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
