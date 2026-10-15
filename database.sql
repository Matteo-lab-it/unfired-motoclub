-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql107.infinityfree.com
-- Creato il: Set 08, 2026 alle 04:29
-- Versione del server: 11.4.13-MariaDB
-- Versione PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_42352310_unfaired`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `contacts`
--

CREATE TABLE `contacts` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(512) NOT NULL,
  `email` varchar(512) NOT NULL,
  `subject` varchar(512) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','archived') NOT NULL DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `subject`, `message`, `status`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'enc:v1:fh2fyo00cuM21GsDRDllLBcbcqkKmaPcH95vUrO35pw=', 'enc:v1:mggmw1OcPhBASGhX3Je2F+gbqDtirbrwN9ugyH9i9vdXU9K3T8gVMw==', 'enc:v1:slx9aptFuhfv13RGvIZel1TIDxsPxgx69ItTcranFQw=', 'enc:v1:HSQ11KwYJzZwUneAT2xc8dVIipflpnTJNLZhVmgOyUk=', 'new', '81.56.105.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-08 20:59:26');

-- --------------------------------------------------------

--
-- Struttura della tabella `events`
--

CREATE TABLE `events` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `location` varchar(160) NOT NULL,
  `route_summary` text DEFAULT NULL,
  `map_url` varchar(500) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `event_time`, `location`, `route_summary`, `map_url`, `image_url`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'Giro delle Alpi', 'Una giornata tra passi di montagna, curve e panorami spettacolari.', '2026-07-12', '08:30:00', 'Torino', NULL, NULL, 'assets/img/home.jpeg', 1, '2026-07-06 21:37:49', '2026-07-06 21:37:49'),
(2, 'Unfired Summer Night', 'Serata aperta a tutti con musica, moto, food e nuovi incontri.', '2026-07-26', '19:00:00', 'Club House', NULL, NULL, 'assets/img/home.jpeg', 1, '2026-07-06 21:37:49', '2026-07-06 21:37:49'),
(3, 'Ride for Charity', 'Un motoraduno solidale per sostenere un progetto del territorio.', '2026-09-06', '09:00:00', 'Piemonte', NULL, NULL, 'assets/img/home.jpeg', 1, '2026-07-06 21:37:49', '2026-07-06 21:37:49');

-- --------------------------------------------------------

--
-- Struttura della tabella `event_attendees`
--

CREATE TABLE `event_attendees` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(512) NOT NULL,
  `motorcycle_model` varchar(512) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `event_photos`
--

CREATE TABLE `event_photos` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `caption` varchar(180) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `event_route_stops`
--

CREATE TABLE `event_route_stops` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `membership_requests`
--

CREATE TABLE `membership_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `membership_type` varchar(80) NOT NULL,
  `full_name` varchar(512) NOT NULL,
  `birth_date` date NOT NULL,
  `fiscal_code` varchar(512) NOT NULL,
  `city` varchar(512) NOT NULL,
  `phone` varchar(512) NOT NULL,
  `email` varchar(512) NOT NULL,
  `motorcycle_brand` varchar(512) DEFAULT NULL,
  `motorcycle_model` varchar(512) DEFAULT NULL,
  `motorcycle_plate` varchar(512) DEFAULT NULL,
  `already_fmi` enum('Si','No') NOT NULL DEFAULT 'No',
  `notes` text DEFAULT NULL,
  `status` enum('new','processing','completed','archived') NOT NULL DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `membership_requests`
--

INSERT INTO `membership_requests` (`id`, `membership_type`, `full_name`, `birth_date`, `fiscal_code`, `city`, `phone`, `email`, `motorcycle_brand`, `motorcycle_model`, `motorcycle_plate`, `already_fmi`, `notes`, `status`, `ip_address`, `user_agent`, `created_at`) VALUES
(4, 'Member FMI', 'enc:v1:Mr2IuBGxnnq35ifiz3v6LxfBCRCT+afXBWgZHbaAquLjT4ai7MfMBkVYCwqv', '2001-09-19', 'enc:v1:r9MkzPiWoauFd6k+dAArx90t2Qk4z7ByWk9EznS4LNJEH0F7i+OWacCN5CA=', 'enc:v1:Ua3kYbEJfRH4Vt59RrTTgkid8IQQCnqdA483F3/9FblnETM=', 'enc:v1:CCeCH/j/EWittBY+n7KKOM5FDeevY6xLJQ5khwcrhnaS+MHOFVw=', 'enc:v1:gHgy6OapJ1voclNK9GMAmEP+Kc+LCt6CoRzzxWzyLwNzWIhz6ir77SOi6upnAw9xUc4=', 'enc:v1:s0BPyIylO7Tb5JNk9tDn7G4bzB7klbn6Gg4eByZ/yQ==', 'enc:v1:xBAb+r5loHNisFP60eYU9sa7A9DIgLiP/iTu0NizfOnhkmEb', 'enc:v1:v6h/rYku5IViWM+YpDUgRuLb86AbBKEyS+9jYJn+FeJtdBY=', 'Si', NULL, 'archived', '94.165.177.0', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/27.0 Mobile/15E148 Safari/604.1', '2026-07-09 04:52:54'),
(5, 'Member FMI', 'enc:v1:rjeD+8pKjiw0uQSKbdw8ka8GNPMaxH60SMy2BS8r+r6gogij0jSery/qMcZGCQ==', '1996-07-16', 'enc:v1:E4v1O+5x9LosX/g4cSI+PcXGnAUKb7p+TFWeBdiZrHJpzDQ/Ot8Xz4d7r88=', 'enc:v1:HuZG2hV97CvX2liCmbq4UIixquORvHysXUTNmgr03hQnNA==', 'enc:v1:XsPFzY31kOj5h2//2mz+/xN7Ju504vWZzQGODBwodjj/VTJtkS8=', 'enc:v1:p49HPVCimSwY503hJayRVH9UcEwUYoBGBnsld8R7Jx1f+UJLcH6c6c48Zw7Q86gEaLQKYhchsfRrtoM=', 'enc:v1:PDeg3GhELDrs+AZ1Ix9yEZVpydcxjsZ2vcsEj2AeHkXVnX8=', 'enc:v1:2Wn0I36Fpj40Be/9oPho8qqPF+VwY+dJyxSUI+LovAsUdSyTuEXQ+GxQulOp', 'enc:v1:ts/bR9zEwhS5bplWh3bIhLDZPhRaqIpHvEfMRwMecMDq9zE=', 'Si', 'enc:v1:fH+JnAz4DVeJ3YhVBqnqjHFA7dT2/Hn4SNH0pe32jCU2g5MbojPO1NSXf0FxnA==', 'completed', '217.216.60.96', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1', '2026-07-09 07:00:23');

-- --------------------------------------------------------

--
-- Struttura della tabella `shop_products`
--

CREATE TABLE `shop_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `sizes` varchar(255) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `shop_products`
--

INSERT INTO `shop_products` (`id`, `name`, `description`, `sizes`, `image_path`, `is_published`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Cappellino Ufficiale Unfaired Motoclub', 'Cappellino ufficiale riservato ai soci, pensato per eventi, ride e attivita del club.', 'Unica', 'assets/img/cappellini.jpeg', 1, 10, '2026-07-06 21:37:49', '2026-07-06 21:37:49'),
(2, 'Felpa Ufficiale Unfaired Motoclub', 'Felpa ufficiale del club, riservata ai soci e utilizzata negli eventi ufficiali.', 'S, M, L, XL, XXL', 'assets/img/felpa.jpeg', 1, 20, '2026-07-06 21:37:49', '2026-07-06 21:37:49'),
(3, 'T-shirt Ufficiale Unfaired Motoclub', 'T-shirt ufficiale riservata ai soci, pensata per rappresentare il club nelle attivita ufficiali.', 'S, M, L, XL, XXL', 'assets/img/t-shirt.jpeg', 1, 30, '2026-07-06 21:37:49', '2026-07-06 21:37:49');

-- --------------------------------------------------------

--
-- Struttura della tabella `shop_requests`
--

CREATE TABLE `shop_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(180) NOT NULL,
  `selected_size` varchar(40) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `customer_name` varchar(512) NOT NULL,
  `customer_email` varchar(512) NOT NULL,
  `customer_phone` varchar(512) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('new','processing','completed','archived') NOT NULL DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `shop_requests`
--

INSERT INTO `shop_requests` (`id`, `product_id`, `product_name`, `selected_size`, `quantity`, `customer_name`, `customer_email`, `customer_phone`, `notes`, `status`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'Cappellino Ufficiale Unfaired Motoclub', 'Unica', 1, 'enc:v1:dcSE5PwL5EpvdSCL5C8iy28mmFD1nTBpAdtRs9gx+MV70g==', 'enc:v1:2d4NGza7UiK3A9ahmfYuE85tdfHi1gA+bHjPtS115rRJYl00dl4ZcB96PVcUjn8IB5Hp', 'enc:v1:0UU+1Yt2hQAQGXvfe+BYQDG7g5INjpGbkj34gNBeyP/0RsfNaY6u8pfl', NULL, 'completed', '81.56.105.34', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36 EdgA/149.0.0.0', '2026-07-06 22:08:06');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contacts_created_at_index` (`created_at`),
  ADD KEY `contacts_status_index` (`status`),
  ADD KEY `contacts_email_index` (`email`);

--
-- Indici per le tabelle `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `events_date_index` (`event_date`,`event_time`),
  ADD KEY `events_published_index` (`is_published`);

--
-- Indici per le tabelle `event_attendees`
--
ALTER TABLE `event_attendees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_attendees_event_index` (`event_id`,`created_at`);

--
-- Indici per le tabelle `event_photos`
--
ALTER TABLE `event_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_photos_event_index` (`event_id`,`sort_order`,`id`);

--
-- Indici per le tabelle `event_route_stops`
--
ALTER TABLE `event_route_stops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_route_stops_event_index` (`event_id`,`sort_order`,`id`);

--
-- Indici per le tabelle `membership_requests`
--
ALTER TABLE `membership_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `membership_requests_created_at_index` (`created_at`),
  ADD KEY `membership_requests_status_index` (`status`),
  ADD KEY `membership_requests_email_index` (`email`);

--
-- Indici per le tabelle `shop_products`
--
ALTER TABLE `shop_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_products_published_index` (`is_published`),
  ADD KEY `shop_products_sort_index` (`sort_order`,`id`);

--
-- Indici per le tabelle `shop_requests`
--
ALTER TABLE `shop_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_requests_status_index` (`status`),
  ADD KEY `shop_requests_created_at_index` (`created_at`),
  ADD KEY `shop_requests_product_index` (`product_id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `events`
--
ALTER TABLE `events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `event_attendees`
--
ALTER TABLE `event_attendees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `event_photos`
--
ALTER TABLE `event_photos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `event_route_stops`
--
ALTER TABLE `event_route_stops`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `membership_requests`
--
ALTER TABLE `membership_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `shop_products`
--
ALTER TABLE `shop_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `shop_requests`
--
ALTER TABLE `shop_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `event_attendees`
--
ALTER TABLE `event_attendees`
  ADD CONSTRAINT `event_attendees_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `event_photos`
--
ALTER TABLE `event_photos`
  ADD CONSTRAINT `event_photos_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `event_route_stops`
--
ALTER TABLE `event_route_stops`
  ADD CONSTRAINT `event_route_stops_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `shop_requests`
--
ALTER TABLE `shop_requests`
  ADD CONSTRAINT `shop_requests_product_fk` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
