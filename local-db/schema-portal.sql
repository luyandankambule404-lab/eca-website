-- ECA portal database (eca_portal_local)
-- Membership, applications, certificates, CPD, payments.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS tbl_client (
  client_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CompanyRegistrationName VARCHAR(255) DEFAULT NULL,
  TradingName VARCHAR(255) DEFAULT NULL,
  EmailAddress VARCHAR(255) DEFAULT NULL,
  Cellphone VARCHAR(255) DEFAULT NULL,
  telephone VARCHAR(255) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  Region VARCHAR(255) DEFAULT NULL,
  businesstype VARCHAR(255) DEFAULT NULL,
  Enterprise VARCHAR(255) DEFAULT NULL,
  Status VARCHAR(255) DEFAULT NULL,
  Clasification VARCHAR(255) DEFAULT NULL,
  declaration TEXT,
  DateOfRegistration DATETIME DEFAULT NULL,
  active VARCHAR(255) DEFAULT NULL,
  MembershipNumber VARCHAR(255) DEFAULT NULL,
  CertificateNumber VARCHAR(64) DEFAULT NULL,
  application_reference VARCHAR(32) DEFAULT NULL,
  application_status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (client_id),
  KEY idx_tbl_client_membership (MembershipNumber),
  KEY idx_tbl_client_email (EmailAddress),
  UNIQUE KEY uq_tbl_client_app_ref (application_reference),
  KEY idx_tbl_client_application_status (application_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS owners (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  clientid INT UNSIGNED DEFAULT NULL,
  name VARCHAR(255) DEFAULT NULL,
  citizen VARCHAR(255) DEFAULT NULL,
  gender VARCHAR(255) DEFAULT NULL,
  shares VARCHAR(255) DEFAULT NULL,
  application_id INT DEFAULT NULL,
  PRIMARY KEY (id),
  KEY clientid (clientid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tbl_client_documents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id INT UNSIGNED DEFAULT NULL,
  document_type VARCHAR(255) DEFAULT NULL,
  file_name VARCHAR(255) DEFAULT NULL,
  file_path VARCHAR(512) DEFAULT NULL,
  storage_key VARCHAR(64) DEFAULT NULL,
  original_name VARCHAR(255) DEFAULT NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME DEFAULT NULL,
  reviewed_by VARCHAR(190) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY client_id (client_id),
  UNIQUE KEY uq_tbl_client_documents_storage_key (storage_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS userss (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  membership_number VARCHAR(64) DEFAULT NULL,
  full_name VARCHAR(190) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  password VARCHAR(255) DEFAULT NULL,
  role ENUM('MEMBER') NOT NULL DEFAULT 'MEMBER',
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_userss_membership (membership_number),
  UNIQUE KEY uq_userss_email (email),
  KEY idx_userss_email_status_role (email, status, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role VARCHAR(32) NOT NULL DEFAULT 'CONTRACTOR',
  company_name VARCHAR(255) DEFAULT NULL,
  full_name VARCHAR(190) DEFAULT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(64) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'PENDING',
  image VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  banner VARCHAR(255) DEFAULT NULL,
  start_date DATETIME DEFAULT NULL,
  end_date DATETIME DEFAULT NULL,
  venue VARCHAR(255) DEFAULT NULL,
  capacity INT DEFAULT NULL,
  points DECIMAL(8,2) DEFAULT 0,
  commitment_fee DECIMAL(10,2) DEFAULT NULL,
  fee DECIMAL(10,2) DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'DRAFT',
  created_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cpd_applications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id INT UNSIGNED DEFAULT NULL,
  company_name VARCHAR(255) DEFAULT NULL,
  membership_number VARCHAR(64) DEFAULT NULL,
  discipline VARCHAR(128) DEFAULT NULL,
  full_name VARCHAR(190) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  phone VARCHAR(64) DEFAULT NULL,
  id_number VARCHAR(64) DEFAULT NULL,
  gender VARCHAR(32) DEFAULT NULL,
  position VARCHAR(128) DEFAULT NULL,
  learning_objectives TEXT,
  qualification_level VARCHAR(128) DEFAULT NULL,
  qualification_name VARCHAR(255) DEFAULT NULL,
  qualification VARCHAR(255) DEFAULT NULL,
  payment_proof VARCHAR(255) DEFAULT NULL,
  payment_method VARCHAR(64) DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'Pending',
  training_status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cpd_membership (membership_number),
  KEY idx_cpd_course_status (course_id, status),
  KEY idx_cpd_email_status (email, training_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS course_applications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id INT UNSIGNED DEFAULT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'PENDING',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS course_attendance (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id INT UNSIGNED NOT NULL,
  application_id INT UNSIGNED NOT NULL,
  attendance_date DATE NOT NULL,
  status VARCHAR(32) DEFAULT NULL,
  marked_by INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_attendance_lookup (course_id, application_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cpd_points_ledger (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  course_id INT UNSIGNED DEFAULT NULL,
  points DECIMAL(8,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ledger_user_created (user_id, created_at),
  KEY idx_ledger_course_points (course_id, points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS course_resources (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(255) DEFAULT NULL,
  category VARCHAR(128) DEFAULT NULL,
  description TEXT,
  file_path VARCHAR(255) DEFAULT NULL,
  file_type VARCHAR(32) DEFAULT NULL,
  file_size VARCHAR(32) DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'Published',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS course_activity (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id INT UNSIGNED DEFAULT NULL,
  application_id INT UNSIGNED DEFAULT NULL,
  q1 TEXT,
  q2 TEXT,
  q3 TEXT,
  q4 TEXT,
  submitted_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) DEFAULT NULL,
  message TEXT,
  target_role VARCHAR(32) DEFAULT NULL,
  course_id INT UNSIGNED DEFAULT NULL,
  link VARCHAR(255) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'Published',
  created_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(255) DEFAULT NULL,
  message TEXT,
  type VARCHAR(64) DEFAULT NULL,
  link VARCHAR(255) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  announcement_id INT UNSIGNED DEFAULT NULL,
  membership_number VARCHAR(50) DEFAULT NULL,
  client_id INT DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_membership (membership_number),
  KEY idx_notifications_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wallet_transactions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id VARCHAR(64) DEFAULT NULL,
  membership_number VARCHAR(64) DEFAULT NULL,
  application_id INT DEFAULT NULL,
  reference_id VARCHAR(64) DEFAULT NULL,
  transaction_id VARCHAR(64) DEFAULT NULL,
  mobile_number VARCHAR(32) DEFAULT NULL,
  amount DECIMAL(10,2) DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'PENDING',
  approved_at DATETIME DEFAULT NULL,
  remote_response TEXT,
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_wallet_request (request_id),
  KEY idx_wallet_membership_status (membership_number, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  payment_year VARCHAR(16) DEFAULT NULL,
  payment_date DATE DEFAULT NULL,
  proof_file VARCHAR(255) DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'pending',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payments_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS membership_years (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id INT NOT NULL,
  type VARCHAR(32) DEFAULT NULL,
  status VARCHAR(64) DEFAULT NULL,
  year VARCHAR(16) DEFAULT NULL,
  expiry_date DATE DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_membership_years_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS membership_application_notes (
  id INT NOT NULL AUTO_INCREMENT,
  client_id INT NOT NULL,
  admin_email VARCHAR(190) NULL,
  note TEXT NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_app_notes_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS membership_certificates (
  id INT NOT NULL AUTO_INCREMENT,
  client_id INT NOT NULL,
  membership_number VARCHAR(50) NULL,
  certificate_number VARCHAR(64) NOT NULL,
  classification VARCHAR(150) NULL,
  company_name VARCHAR(255) NULL,
  issued_at DATE NULL,
  expiry_date DATE NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'ACTIVE',
  qr_token VARCHAR(64) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_membership_certificates_number (certificate_number),
  KEY idx_membership_certificates_client (client_id),
  KEY idx_membership_certificates_member (membership_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS news (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) DEFAULT NULL,
  summary TEXT,
  author VARCHAR(190) DEFAULT NULL,
  categories VARCHAR(190) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  video VARCHAR(255) DEFAULT NULL,
  link VARCHAR(255) DEFAULT NULL,
  date DATE DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'Active',
  `count` INT NOT NULL DEFAULT 0,
  created_at DATE DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resources (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  file_path VARCHAR(255) DEFAULT NULL,
  file_type VARCHAR(32) DEFAULT NULL,
  category VARCHAR(128) DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'Published',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS downloads (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  file_name VARCHAR(255) NOT NULL,
  `count` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_downloads_file (file_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS member_projects (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  membership_number VARCHAR(64) NOT NULL,
  client_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  client_entity VARCHAR(255) DEFAULT NULL,
  contract_type VARCHAR(64) DEFAULT NULL,
  start_date DATE DEFAULT NULL,
  finish_date DATE DEFAULT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
  pending_vo INT UNSIGNED NOT NULL DEFAULT 0,
  unapproved_vo_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  eot_date DATE DEFAULT NULL,
  eot_status VARCHAR(64) DEFAULT 'NOT REQUESTED',
  intervention VARCHAR(32) DEFAULT 'No',
  assigned_staff VARCHAR(190) DEFAULT NULL,
  dispute_level VARCHAR(190) DEFAULT NULL,
  next_action VARCHAR(255) DEFAULT NULL,
  contract_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  location VARCHAR(255) DEFAULT NULL,
  current_stage VARCHAR(190) DEFAULT NULL,
  manager_name VARCHAR(190) DEFAULT NULL,
  manager_phone VARCHAR(64) DEFAULT NULL,
  manager_email VARCHAR(190) DEFAULT NULL,
  health_status VARCHAR(32) NOT NULL DEFAULT 'on_track',
  current_challenge VARCHAR(64) DEFAULT NULL,
  issue_type VARCHAR(190) DEFAULT NULL,
  issue_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  issue_days INT NOT NULL DEFAULT 0,
  issue_impact VARCHAR(190) DEFAULT NULL,
  eca_action VARCHAR(190) DEFAULT NULL,
  eca_action_status VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_member_projects_membership (membership_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS member_project_files (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  membership_number VARCHAR(64) NOT NULL,
  client_id INT UNSIGNED DEFAULT NULL,
  project_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(255) DEFAULT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  mime VARCHAR(128) DEFAULT NULL,
  file_size INT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_member_projects_membership (membership_number),
  KEY idx_member_project_files_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS member_project_packages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  membership_number VARCHAR(64) NOT NULL,
  name VARCHAR(190) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'not_started',
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_member_project_packages_project (project_id),
  KEY idx_member_project_packages_member (membership_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS member_project_timeline (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  membership_number VARCHAR(64) NOT NULL,
  event_date VARCHAR(32) DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'done',
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_member_project_timeline_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS member_contractor_capacity (
  membership_number VARCHAR(64) NOT NULL,
  employees INT UNSIGNED NOT NULL DEFAULT 0,
  technical_staff INT UNSIGNED NOT NULL DEFAULT 0,
  skilled_workers INT UNSIGNED NOT NULL DEFAULT 0,
  administrative INT UNSIGNED NOT NULL DEFAULT 0,
  excavators INT UNSIGNED NOT NULL DEFAULT 0,
  tlb INT UNSIGNED NOT NULL DEFAULT 0,
  trucks INT UNSIGNED NOT NULL DEFAULT 0,
  other_equipment INT UNSIGNED NOT NULL DEFAULT 0,
  spec_building TINYINT UNSIGNED NOT NULL DEFAULT 0,
  spec_roads TINYINT UNSIGNED NOT NULL DEFAULT 0,
  spec_civil TINYINT UNSIGNED NOT NULL DEFAULT 0,
  spec_water TINYINT UNSIGNED NOT NULL DEFAULT 0,
  completed_projects INT UNSIGNED NOT NULL DEFAULT 0,
  total_project_value VARCHAR(64) DEFAULT NULL,
  private_projects INT UNSIGNED NOT NULL DEFAULT 0,
  years_operating INT UNSIGNED NOT NULL DEFAULT 0,
  active_projects INT UNSIGNED NOT NULL DEFAULT 0,
  active_value VARCHAR(64) DEFAULT NULL,
  attention_projects INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (membership_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS likes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  file_name VARCHAR(255) NOT NULL,
  `count` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_likes_file (file_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_tickets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ticket_no VARCHAR(64) DEFAULT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  full_name VARCHAR(190) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  subject VARCHAR(255) DEFAULT NULL,
  category VARCHAR(128) DEFAULT NULL,
  priority VARCHAR(32) DEFAULT NULL,
  message TEXT,
  admin_reply TEXT,
  status VARCHAR(32) DEFAULT 'Open',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_support_status (status),
  KEY idx_support_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS feedback (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  full_name VARCHAR(190) DEFAULT NULL,
  subject VARCHAR(255) DEFAULT NULL,
  rating TINYINT DEFAULT NULL,
  message TEXT,
  course_id INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_feedback_email (email),
  KEY idx_feedback_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS system_email_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id INT UNSIGNED DEFAULT NULL,
  sent_by INT UNSIGNED DEFAULT NULL,
  total_sent INT DEFAULT 0,
  total_failed INT DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
