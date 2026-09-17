-- Extra local-only tables for membership forms and contact.
-- Does not alter companies, companies1, users, or the original SQL backup.

CREATE TABLE IF NOT EXISTS `tbl_client` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `CompanyRegistrationName` varchar(255) NOT NULL,
  `TradingName` varchar(255) NOT NULL,
  `EmailAddress` varchar(255) DEFAULT NULL,
  `Cellphone` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `Region` varchar(255) DEFAULT NULL,
  `businesstype` varchar(255) DEFAULT NULL,
  `Enterprise` varchar(255) DEFAULT NULL,
  `Status` varchar(255) DEFAULT 'Joining',
  `Clasification` varchar(255) DEFAULT NULL,
  `declaration` text DEFAULT NULL,
  `DateOfRegistration` datetime DEFAULT NULL,
  `active` varchar(255) DEFAULT 'Pending',
  `MembershipNumber` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `MembershipNumber` (`MembershipNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_client_documents` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` int(10) UNSIGNED NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(512) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `owners` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `clientid` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `citizen` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `shares` int(11) DEFAULT 0,
  `application_id` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clientid` (`clientid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
