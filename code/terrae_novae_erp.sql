-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 17. Dez 2025 um 08:54
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
  `assigned_module_id` int(11) DEFAULT NULL,
  `assigned_rocket_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `astronauts`
--

INSERT INTO `astronauts` (`id`, `user_id`, `name`, `status`, `experience_level`, `assigned_module_id`, `assigned_rocket_id`) VALUES
(1, 1, 'nummer 1', 'in_orbit', 1, NULL, NULL),
(2, 1, 'peter', 'in_orbit', 1, NULL, NULL),
(3, 1, 'Jenny', 'in_orbit', 1, NULL, NULL),
(4, 1, 'Jonnx', 'in_orbit', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `blueprints`
--

CREATE TABLE `blueprints` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `payload_capacity_leo` int(11) NOT NULL,
  `assembly_time_ticks` int(11) DEFAULT 4,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `blueprints`
--

INSERT INTO `blueprints` (`id`, `name`, `description`, `payload_capacity_leo`, `assembly_time_ticks`, `image_path`) VALUES
(1, 'Ariane 64', 'Schwerlastträger mit 4 Boostern. Das Arbeitspferd Europas.', 21600, 4, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `blueprint_items`
--

CREATE TABLE `blueprint_items` (
  `blueprint_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `blueprint_items`
--

INSERT INTO `blueprint_items` (`blueprint_id`, `component_id`, `quantity`) VALUES
(1, 1, 1),
(1, 2, 1),
(1, 3, 1),
(1, 4, 4),
(1, 5, 1);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Tabellenstruktur für Tabelle `components`
--

CREATE TABLE `components` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `cost` decimal(15,2) NOT NULL,
  `lead_time_ticks` int(11) NOT NULL,
  `reliability` decimal(4,3) DEFAULT 0.980
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `components`
--

INSERT INTO `components` (`id`, `name`, `type`, `supplier_id`, `cost`, `lead_time_ticks`, `reliability`) VALUES
(1, 'LLPM (Main Stage)', 'STAGE', 1, 18000000.00, 6, 0.980),
(2, 'Vulcain 2.1', 'ENGINE', 1, 12000000.00, 8, 0.980),
(3, 'ULPM (Upper Stage)', 'STAGE', 2, 15000000.00, 5, 0.980),
(4, 'P120C Solid Booster', 'BOOSTER', 3, 6000000.00, 4, 0.980),
(5, 'Payload Fairing (Large)', 'FAIRING', 4, 4000000.00, 3, 0.980);

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
(1, 'Deutschland', 'de', 500000000.00, 'Fokus auf Technologie.'),
(2, 'Frankreich', 'fr', 550000000.00, 'Fokus auf Trägerraketen.'),
(3, 'Italien', 'it', 300000000.00, 'Spezialisiert auf kleine Träger.'),
(4, 'USA (NASA)', 'us', 0.00, 'Partner.'),
(5, 'Schweiz', 'ch', 150000000.00, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('Research','Engineering','HR','Construction') NOT NULL,
  `head_specialist_id` int(11) DEFAULT NULL,
  `budget_per_hour` decimal(20,2) DEFAULT 0.00,
  `xp` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 1, 'MISSION_RETURN', 1, '2025-12-16 14:10:05', '2025-12-16 14:10:06', 1),
(2, 1, 'MISSION_RETURN', 1, '2025-12-16 14:10:09', '2025-12-16 14:10:21', 1),
(3, 1, 'ASTRO_TRAINING', 1, '2025-12-16 14:10:20', '2025-12-16 14:10:21', 1),
(4, 1, 'MODULE_CONSTRUCTION', 1, '2025-12-16 14:10:32', '2025-12-16 14:10:36', 1),
(5, 1, 'MODULE_CONSTRUCTION', 2, '2025-12-16 14:10:33', '2025-12-16 14:10:36', 1),
(6, 1, 'MODULE_CONSTRUCTION', 3, '2025-12-16 14:10:35', '2025-12-16 14:10:36', 1),
(7, 1, 'MISSION_RETURN', 1, '2025-12-16 14:10:41', '2025-12-16 14:10:42', 1),
(8, 1, 'MODULE_LAUNCH', 2, '2025-12-16 14:10:47', '2025-12-16 14:10:51', 1),
(9, 1, 'MODULE_LAUNCH', 1, '2025-12-16 14:10:54', '2025-12-16 14:10:59', 1),
(10, 1, 'MODULE_LAUNCH', 3, '2025-12-16 14:11:02', '2025-12-16 14:11:03', 1),
(11, 1, 'CREW_LAUNCH', 1, '2025-12-16 14:11:07', '2025-12-16 14:11:10', 1),
(12, 1, 'MISSION_RETURN', 1, '2025-12-16 14:14:26', '2025-12-16 14:14:29', 1),
(13, 1, 'MISSION_RETURN', 1, '2025-12-16 14:21:06', '2025-12-16 14:21:24', 1),
(14, 1, 'MISSION_RETURN', 3, '2025-12-16 14:21:10', '2025-12-16 14:21:24', 1),
(15, 1, 'MISSION_RETURN', 2, '2025-12-16 14:21:13', '2025-12-16 14:21:24', 1),
(16, 1, 'ASTRO_TRAINING', 2, '2025-12-16 14:21:22', '2025-12-16 14:21:24', 1),
(17, 1, 'CREW_LAUNCH', 2, '2025-12-16 14:21:32', '2025-12-16 14:21:33', 1),
(18, 1, 'MISSION_RETURN', 2, '2025-12-16 14:29:09', '2025-12-16 14:29:41', 1),
(19, 1, 'MISSION_RETURN', 3, '2025-12-16 14:29:12', '2025-12-16 14:29:41', 1),
(20, 1, 'MISSION_RETURN', 1, '2025-12-16 14:29:14', '2025-12-16 14:29:41', 1),
(21, 1, 'NEGOTIATION_MONEY', 2, '2025-12-16 14:29:26', '2025-12-16 14:29:41', 1),
(22, 1, 'NEGOTIATION_MONEY', 1, '2025-12-16 14:29:27', '2025-12-16 14:29:41', 1),
(23, 1, 'NEGOTIATION_MONEY', 2, '2025-12-16 14:29:28', '2025-12-16 14:29:41', 1),
(24, 1, 'MISSION_RETURN', 2, '2025-12-16 17:00:00', '2025-12-16 21:24:37', 1),
(25, 1, 'MISSION_RETURN', 5, '2025-12-16 17:00:32', '2025-12-16 21:24:37', 1),
(26, 1, 'MISSION_RETURN', 4, '2025-12-16 17:00:35', '2025-12-16 21:24:37', 1),
(27, 1, 'MISSION_RETURN', 3, '2025-12-16 17:00:37', '2025-12-16 21:24:37', 1),
(28, 1, 'MISSION_RETURN', 1, '2025-12-16 17:00:39', '2025-12-16 21:24:37', 1),
(29, 1, 'MISSION_RETURN', 6, '2025-12-16 17:00:43', '2025-12-16 19:00:43', 1),
(30, 1, 'NEGOTIATION_MONEY', 1, '2025-12-16 20:12:12', '2025-12-16 21:02:12', 1),
(31, 1, 'NEGOTIATION_MONEY', 1, '2025-12-16 20:12:13', '2025-12-16 20:47:13', 1),
(32, 1, 'NEGOTIATION_MONEY', 1, '2025-12-16 20:12:15', '2025-12-16 21:07:15', 1),
(33, 1, 'ASTRO_TRAINING', 3, '2025-12-16 20:12:24', '2025-12-16 21:24:37', 1),
(34, 1, 'ASTRO_TRAINING', 4, '2025-12-16 21:24:24', '2025-12-16 21:24:37', 1),
(35, 1, 'CREW_LAUNCH', 3, '2025-12-16 21:25:15', '2025-12-16 21:25:23', 1),
(36, 1, 'CREW_LAUNCH', 4, '2025-12-16 21:25:19', '2025-12-16 21:25:23', 1),
(37, 1, 'NEGOTIATION_MONEY', 1, '2025-12-16 21:25:37', '2025-12-16 21:25:49', 1),
(38, 1, 'NEGOTIATION_MONEY', 2, '2025-12-16 21:25:38', '2025-12-16 21:25:49', 1),
(39, 1, 'NEGOTIATION_MONEY', 3, '2025-12-16 21:25:42', '2025-12-16 21:25:49', 1),
(40, 1, 'MISSION_RETURN', 1, '2025-12-16 21:34:53', '2025-12-16 21:35:37', 1),
(41, 1, 'MISSION_RETURN', 2, '2025-12-16 21:35:00', '2025-12-16 21:35:37', 1),
(42, 1, 'MISSION_RETURN', 3, '2025-12-16 21:35:04', '2025-12-16 21:35:37', 1),
(43, 1, 'MISSION_RETURN', 4, '2025-12-16 21:35:08', '2025-12-16 21:35:37', 1),
(44, 1, 'MISSION_RETURN', 5, '2025-12-16 21:35:11', '2025-12-16 21:35:37', 1),
(45, 1, 'MISSION_RETURN', 6, '2025-12-16 21:35:14', '2025-12-16 21:35:37', 1),
(46, 1, 'MISSION_RETURN', 1, '2025-12-16 21:37:59', '2025-12-16 21:41:19', 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fleet`
--

CREATE TABLE `fleet` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rocket_name` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'Ariane 6',
  `status` varchar(20) DEFAULT 'idle'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `min_prestige_required` int(11) DEFAULT 0,
  `fuel_required_kg` int(11) DEFAULT 400000,
  `difficulty_level` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `mission_types`
--

INSERT INTO `mission_types` (`id`, `name`, `description`, `required_cargo_capacity`, `duration_seconds`, `reward_money`, `reward_science`, `min_prestige_required`, `fuel_required_kg`, `difficulty_level`) VALUES
(1, 'CubeSat Deploy', 'Bringe kleine Uni-Satelliten in den Orbit.', 500, 7200, 2000000.00, 10, 0, 400000, 1),
(2, 'ISS Resupply', 'Versorgungsgüter zur Raumstation.', 8000, 21600, 15000000.00, 50, 0, 400000, 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `payloads`
--

CREATE TABLE `payloads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('SATELLITE','PROBE','MODULE') NOT NULL,
  `mass_kg` int(11) NOT NULL,
  `value` decimal(20,2) NOT NULL,
  `status` enum('stored','integrated','launched') DEFAULT 'stored',
  `module_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `payloads`
--

INSERT INTO `payloads` (`id`, `user_id`, `name`, `type`, `mass_kg`, `value`, `status`, `module_id`) VALUES
(1, 1, 'Galileo Sat-23', 'SATELLITE', 700, 15000000.00, 'stored', NULL),
(2, 1, 'Copernicus Sentinel-6', 'SATELLITE', 1200, 25000000.00, 'stored', NULL),
(3, 1, 'MetOp-SG A1', 'SATELLITE', 4000, 45000000.00, 'stored', NULL);

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
(1, 'Vega-C', 'Avio', 45000000.00, 2300, 0.920, NULL, 2),
(2, 'Ariane 62', 'Arianespace', 75000000.00, 10300, 0.960, NULL, 1),
(3, 'Falcon 9', 'SpaceX', 62000000.00, 22800, 0.985, NULL, 4);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `specialists`
--

CREATE TABLE `specialists` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `salary_cost` int(11) DEFAULT 0,
  `skill_value` int(11) DEFAULT 0,
  `avatar_image` varchar(50) DEFAULT 'avatar_1.png',
  `user_id` int(11) DEFAULT NULL,
  `busy_until` datetime DEFAULT NULL,
  `xp` int(11) DEFAULT 0,
  `level` int(11) DEFAULT 1,
  `budget` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `specialists`
--

INSERT INTO `specialists` (`id`, `name`, `type`, `salary_cost`, `skill_value`, `avatar_image`, `user_id`, `busy_until`, `xp`, `level`, `budget`) VALUES
(1, 'Dr. Anna Schmidt', 'HR_Head', 50000, 50, 'avatar_1.png', 1, NULL, 0, 1, 0),
(2, 'Prof. John Doe', 'Research_Head', 75000, 60, 'avatar_1.png', 1, NULL, 0, 1, 0),
(3, 'Ing. Bob Builder', 'Construction_Head', 60000, 55, 'avatar_1.png', 1, NULL, 0, 1, 0),
(4, 'Dr. Sheldon Cooper', 'Scientist', 40000, 80, 'avatar_1.png', 1, NULL, 0, 1, 0),
(5, 'Howard Wolowitz', 'Engineer', 35000, 70, 'avatar_1.png', 1, NULL, 0, 1, 0);

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
(1, 'Harmony Kern-Modul', 'Das erste Bauteil. Bietet Strom & Andockknoten.', 12500, 150000000.00, 7200, 5, 'Basis der Station', 50, 2),
(2, 'Columbus Labor', 'Europäisches Forschungslabor.', 10300, 220000000.00, 10800, 3, '+SP', -10, 2),
(3, 'Koppola Beobachtungskuppel', 'Bietet fantastische Aussicht.', 4000, 50000000.00, 3600, 3, 'Prestige', -5, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `story_steps`
--

CREATE TABLE `story_steps` (
  `step_id` varchar(50) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unlocks_pages` text DEFAULT NULL,
  `required_condition_type` varchar(50) DEFAULT NULL,
  `required_condition_value` int(11) DEFAULT NULL,
  `next_step_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `story_steps`
--

INSERT INTO `story_steps` (`step_id`, `title`, `description`, `unlocks_pages`, `required_condition_type`, `required_condition_value`, `next_step_id`) VALUES
('build_lab', 'Forschung & Entwicklung', 'Bauen Sie ein Forschungslabor (Level 1), um neue Technologien zu entwickeln.', '[\"overview\", \"hr\", \"research\"]', 'BUILD_LAB', 1, 'research_fuel'),
('build_pad', 'Startbahnbau', 'Bauen Sie eine Startbahn für Ihre Raketen.', '[\"overview\", \"hr\", \"research\", \"station\"]', 'BUILD_PAD', 1, 'first_launch'),
('hire_hr', 'Die Personalabteilung', 'Die HR-Abteilung ist freigeschaltet. Stellen Sie einen HR-Leiter ein, um Personal zu verwalten.', '[\"overview\", \"hr\"]', 'HIRE_HR_MANAGER', 1, 'hire_research'),
('hire_research', 'Forschungsleitung', 'Wir benötigen einen wissenschaftlichen Leiter, um das Labor zu betreiben.', '[\"overview\", \"hr\"]', 'HIRE_RESEARCH_HEAD', 1, 'build_lab'),
('intro', 'Willkommen CEO', 'Begrüßung und System-Initialisierung.', '[\"overview\"]', 'MANUAL', 1, 'hire_hr'),
('research_fuel', 'Treibstoff-Forschung', 'Erforschen Sie den ersten Treibstoff.', '[\"overview\", \"hr\", \"research\"]', 'RESEARCH_TECH', 1, 'build_pad');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `base_reputation` int(11) DEFAULT 50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `country_code`, `specialty`, `base_reputation`) VALUES
(1, 'ArianeGroup (FR)', 'FR', 'Liquid Propulsion & Stages', 50),
(2, 'ArianeGroup (DE)', 'DE', 'Upper Stages', 50),
(3, 'Avio', 'IT', 'Solid Propulsion', 50),
(4, 'Beyond Gravity', 'CH', 'Payload Fairings', 50),
(5, 'Airbus Defence', 'NL', 'Structures', 50);

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
(1, 'Grundlagen der Orbitalmechanik', 'Erlaubt präzise Manöver im Orbit.', 50, NULL),
(2, 'Lebenserhaltungssysteme', 'Notwendig für Menschen im All.', 150, 1),
(3, 'Modulare Konstruktion', 'Die Fähigkeit, Stationsteile zu koppeln.', 300, 1),
(4, 'Wiederverwendbare Booster', 'Senkt die Startkosten um 10%.', 500, 1),
(5, 'Raumstations-Kernmodul', 'Der Bauplan für das erste Modul!', 1000, 3);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `last_active` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `last_active`, `created_at`) VALUES
(1, 'Luke', '$2y$10$Acm.WsGz4amjexs2iCti..5GiktFdBsphWNRBf6jlaDXN8MPa0MZ.', '', '2025-12-17 08:53:36', '2025-12-17 07:52:12');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_buildings`
--

CREATE TABLE `user_buildings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `building_type_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `current_level` int(11) DEFAULT 1,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `current_mission_id` int(11) DEFAULT NULL,
  `fuel_level` int(11) DEFAULT 100,
  `fuel_capacity_kg` int(11) DEFAULT 500000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `user_fleet`
--

INSERT INTO `user_fleet` (`id`, `user_id`, `rocket_type_id`, `name`, `status`, `flights_completed`, `current_mission_id`, `fuel_level`, `fuel_capacity_kg`) VALUES
(1, 1, 3, 'Falcon Heavy Test', 'idle', 14, NULL, 100, 500000),
(2, 1, 2, 'Ariane 62 #993', 'idle', 5, NULL, 100, 500000),
(3, 1, 2, 'Ariane 62 #577', 'idle', 4, NULL, 100, 500000),
(4, 1, 2, 'Ariane 62 #672', 'idle', 3, NULL, 100, 500000),
(5, 1, 3, 'Falcon 9 #183', 'idle', 2, NULL, 100, 500000),
(6, 1, 1, 'Vega-C #441', 'idle', 2, NULL, 100, 500000);

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
(1, 1, 3, 'assembled', 100, '2025-12-16 13:10:32'),
(2, 1, 1, 'assembled', 100, '2025-12-16 13:10:33'),
(3, 1, 2, 'assembled', 100, '2025-12-16 13:10:35');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_progression`
--

CREATE TABLE `user_progression` (
  `user_id` int(11) NOT NULL,
  `current_step_id` varchar(50) NOT NULL DEFAULT 'intro',
  `completed_steps` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `user_progression`
--

INSERT INTO `user_progression` (`user_id`, `current_step_id`, `completed_steps`, `updated_at`) VALUES
(1, 'build_lab', NULL, '2025-12-17 07:52:51');

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
  `money` bigint(20) DEFAULT 10000000,
  `science_points` int(11) DEFAULT 0,
  `fuel` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `user_resources`
--

INSERT INTO `user_resources` (`user_id`, `money`, `science_points`, `fuel`) VALUES
(1, 9740000, 5, 0);

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
(1, 1, '2025-12-16 13:09:55'),
(1, 2, '2025-12-16 13:09:55'),
(1, 3, '2025-12-16 13:09:55'),
(1, 4, '2025-12-16 13:09:55'),
(1, 5, '2025-12-16 13:09:55');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `warehouse`
--

CREATE TABLE `warehouse` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `status` enum('ON_ORDER','IN_TRANSIT','IN_STOCK','INSTALLED','USED') DEFAULT 'ON_ORDER',
  `ordered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_due_tick` int(11) NOT NULL,
  `quality_check` int(11) DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `astronauts`
--
ALTER TABLE `astronauts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_module_id` (`assigned_module_id`),
  ADD KEY `assigned_rocket_id` (`assigned_rocket_id`);

--
-- Indizes für die Tabelle `blueprints`
--
ALTER TABLE `blueprints`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `blueprint_items`
--
ALTER TABLE `blueprint_items`
  ADD PRIMARY KEY (`blueprint_id`,`component_id`),
  ADD KEY `component_id` (`component_id`);

--
-- Indizes für die Tabelle `building_types`
--
ALTER TABLE `building_types`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `components`
--
ALTER TABLE `components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

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
-- Indizes für die Tabelle `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `head_specialist_id` (`head_specialist_id`);

--
-- Indizes für die Tabelle `event_queue`
--
ALTER TABLE `event_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indizes für die Tabelle `fleet`
--
ALTER TABLE `fleet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indizes für die Tabelle `mission_types`
--
ALTER TABLE `mission_types`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `payloads`
--
ALTER TABLE `payloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `module_id` (`module_id`);

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
-- Indizes für die Tabelle `story_steps`
--
ALTER TABLE `story_steps`
  ADD PRIMARY KEY (`step_id`);

--
-- Indizes für die Tabelle `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_code` (`country_code`);

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
  ADD UNIQUE KEY `username` (`username`);

--
-- Indizes für die Tabelle `user_buildings`
--
ALTER TABLE `user_buildings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indizes für die Tabelle `user_fleet`
--
ALTER TABLE `user_fleet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `rocket_type_id` (`rocket_type_id`),
  ADD KEY `current_mission_id` (`current_mission_id`);

--
-- Indizes für die Tabelle `user_modules`
--
ALTER TABLE `user_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `module_type_id` (`module_type_id`);

--
-- Indizes für die Tabelle `user_progression`
--
ALTER TABLE `user_progression`
  ADD PRIMARY KEY (`user_id`);

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
-- Indizes für die Tabelle `warehouse`
--
ALTER TABLE `warehouse`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `component_id` (`component_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `astronauts`
--
ALTER TABLE `astronauts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `blueprints`
--
ALTER TABLE `blueprints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT für Tabelle `building_types`
--
ALTER TABLE `building_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `components`
--
ALTER TABLE `components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT für Tabelle `corporations`
--
ALTER TABLE `corporations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT für Tabelle `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `event_queue`
--
ALTER TABLE `event_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT für Tabelle `fleet`
--
ALTER TABLE `fleet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `mission_types`
--
ALTER TABLE `mission_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `payloads`
--
ALTER TABLE `payloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `rocket_types`
--
ALTER TABLE `rocket_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `specialists`
--
ALTER TABLE `specialists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT für Tabelle `station_module_types`
--
ALTER TABLE `station_module_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `user_fleet`
--
ALTER TABLE `user_fleet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT für Tabelle `user_modules`
--
ALTER TABLE `user_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `warehouse`
--
ALTER TABLE `warehouse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `astronauts`
--
ALTER TABLE `astronauts`
  ADD CONSTRAINT `astronauts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `astronauts_ibfk_2` FOREIGN KEY (`assigned_module_id`) REFERENCES `user_modules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `astronauts_ibfk_3` FOREIGN KEY (`assigned_rocket_id`) REFERENCES `user_fleet` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `blueprint_items`
--
ALTER TABLE `blueprint_items`
  ADD CONSTRAINT `blueprint_items_ibfk_1` FOREIGN KEY (`blueprint_id`) REFERENCES `blueprints` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blueprint_items_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`);

--
-- Constraints der Tabelle `components`
--
ALTER TABLE `components`
  ADD CONSTRAINT `components_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints der Tabelle `corporations`
--
ALTER TABLE `corporations`
  ADD CONSTRAINT `corporations_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`);

--
-- Constraints der Tabelle `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `departments_ibfk_2` FOREIGN KEY (`head_specialist_id`) REFERENCES `specialists` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `event_queue`
--
ALTER TABLE `event_queue`
  ADD CONSTRAINT `event_queue_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `fleet`
--
ALTER TABLE `fleet`
  ADD CONSTRAINT `fleet_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `payloads`
--
ALTER TABLE `payloads`
  ADD CONSTRAINT `payloads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payloads_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `user_modules` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `user_buildings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `user_fleet`
--
ALTER TABLE `user_fleet`
  ADD CONSTRAINT `user_fleet_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_fleet_ibfk_2` FOREIGN KEY (`rocket_type_id`) REFERENCES `rocket_types` (`id`),
  ADD CONSTRAINT `user_fleet_ibfk_3` FOREIGN KEY (`current_mission_id`) REFERENCES `mission_types` (`id`);

--
-- Constraints der Tabelle `user_modules`
--
ALTER TABLE `user_modules`
  ADD CONSTRAINT `user_modules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_modules_ibfk_2` FOREIGN KEY (`module_type_id`) REFERENCES `station_module_types` (`id`);

--
-- Constraints der Tabelle `user_progression`
--
ALTER TABLE `user_progression`
  ADD CONSTRAINT `user_progression_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

--
-- Constraints der Tabelle `warehouse`
--
ALTER TABLE `warehouse`
  ADD CONSTRAINT `warehouse_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warehouse_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
