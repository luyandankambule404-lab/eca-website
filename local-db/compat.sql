-- Local-only compatibility tables for the PHP membership app.
-- Does not modify companies, companies1, users, or the original SQL backup.

CREATE TABLE IF NOT EXISTS `tbl_client` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `CompanyRegistrationName` varchar(255) DEFAULT NULL,
  `TradingName` varchar(255) DEFAULT NULL,
  `EmailAddress` varchar(255) DEFAULT NULL,
  `Cellphone` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `Region` varchar(255) DEFAULT NULL,
  `businesstype` varchar(255) DEFAULT NULL,
  `Enterprise` varchar(255) DEFAULT NULL,
  `Status` varchar(255) DEFAULT NULL,
  `Clasification` varchar(255) DEFAULT NULL,
  `declaration` text DEFAULT NULL,
  `DateOfRegistration` datetime DEFAULT NULL,
  `active` varchar(255) DEFAULT NULL,
  `MembershipNumber` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `owners` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `clientid` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `citizen` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `shares` varchar(255) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_client_documents` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` int(11) UNSIGNED DEFAULT NULL,
  `document_type` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
