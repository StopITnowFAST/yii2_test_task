-- MySQL / MariaDB: таблицы сервиса коротких ссылок.
-- Кодировка utf8mb4 рекомендуется для веб-проектов.
-- Версии: MySQL 8.0+ или MariaDB 10.3+ (проверено по синтаксису InnoDB / FK).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `short_link_click`;
DROP TABLE IF EXISTS `short_link`;

CREATE TABLE `short_link` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_url` varchar(2048) NOT NULL,
  `code` varchar(16) NOT NULL,
  `click_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_short_link_code` (`code`),
  KEY `idx_short_link_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `short_link_click` (
  `id` int NOT NULL AUTO_INCREMENT,
  `short_link_id` int NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_short_link_click_link` (`short_link_id`),
  KEY `idx_short_link_click_created` (`created_at`),
  CONSTRAINT `fk_short_link_click_link` FOREIGN KEY (`short_link_id`) REFERENCES `short_link` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
