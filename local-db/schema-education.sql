-- ECA Education & Capacity Building CMS (hub database)

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS education_settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS education_venues (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(190) NOT NULL,
  address VARCHAR(255) DEFAULT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_education_venue_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS education_facilitators (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(190) NOT NULL,
  organisation VARCHAR(190) DEFAULT NULL,
  bio TEXT,
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_education_facilitator_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS education_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  section VARCHAR(32) NOT NULL,
  name VARCHAR(128) NOT NULL,
  slug VARCHAR(128) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_education_cat_section_slug (section, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS education_courses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  start_date DATETIME DEFAULT NULL,
  end_date DATETIME DEFAULT NULL,
  venue_id INT UNSIGNED DEFAULT NULL,
  venue_text VARCHAR(255) DEFAULT NULL,
  audience VARCHAR(255) DEFAULT NULL,
  fees VARCHAR(190) DEFAULT NULL,
  cpd_points DECIMAL(8,2) DEFAULT NULL,
  cpd_info VARCHAR(255) DEFAULT NULL,
  description TEXT,
  registration_url VARCHAR(512) DEFAULT NULL,
  learner_portal_url VARCHAR(512) DEFAULT NULL,
  cpd_course_id INT UNSIGNED DEFAULT NULL,
  facilitator_id INT UNSIGNED DEFAULT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_education_course_slug (slug),
  KEY idx_education_course_status (status, start_date),
  KEY idx_education_course_featured (is_featured, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS education_programmes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  summary TEXT,
  body TEXT,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_education_programme_slug (slug),
  KEY idx_education_programme_status (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS education_articles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kind VARCHAR(32) NOT NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  category_id INT UNSIGNED DEFAULT NULL,
  resource_type VARCHAR(64) DEFAULT NULL,
  summary TEXT,
  body TEXT,
  what_changed TEXT,
  why_matters TEXT,
  contractor_action TEXT,
  eca_support TEXT,
  video_url VARCHAR(512) DEFAULT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_education_article_kind_slug (kind, slug),
  KEY idx_education_article_status (kind, status, published_at),
  KEY idx_education_article_cat (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS education_resources (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  category_id INT UNSIGNED DEFAULT NULL,
  resource_type VARCHAR(64) NOT NULL DEFAULT 'guide',
  file_path VARCHAR(255) DEFAULT NULL,
  file_type VARCHAR(32) DEFAULT NULL,
  external_url VARCHAR(512) DEFAULT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_education_res_status (status, resource_type),
  KEY idx_education_res_cat (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO education_settings (setting_key, setting_value) VALUES
  ('learner_portal_url', '/cpd/login.php'),
  ('learner_portal_label', 'ACCESS LEARNER PORTAL');

INSERT IGNORE INTO education_venues (id, name, address, status) VALUES
  (1, 'Sibane Sami Hotel', 'Eswatini', 'ACTIVE'),
  (2, 'Bethel Court Zulwini', 'Ezulwini, Eswatini', 'ACTIVE'),
  (3, 'ECA Offices, Cooper Centre', 'Suite 40, Cooper Centre, Mbabane', 'ACTIVE');

INSERT IGNORE INTO education_facilitators (id, name, organisation, bio, status) VALUES
  (1, 'ECA Training Desk', 'Eswatini Contractors Association', 'ECA coordinates contractor training, CPD and industry capacity-building programmes.', 'ACTIVE');

INSERT IGNORE INTO education_categories (id, section, name, slug, sort_order, status) VALUES
  (1, 'knowledge', 'Contracts', 'contracts', 10, 'ACTIVE'),
  (2, 'knowledge', 'Procurement', 'procurement', 20, 'ACTIVE'),
  (3, 'knowledge', 'Business & Finance', 'business-finance', 30, 'ACTIVE'),
  (4, 'knowledge', 'Project Management', 'project-management', 40, 'ACTIVE'),
  (5, 'knowledge', 'Compliance', 'compliance', 50, 'ACTIVE'),
  (6, 'knowledge', 'Health & Safety', 'health-safety', 60, 'ACTIVE'),
  (7, 'resources', 'PDFs', 'pdfs', 10, 'ACTIVE'),
  (8, 'resources', 'Training presentations', 'presentations', 20, 'ACTIVE'),
  (9, 'resources', 'Guides', 'guides', 30, 'ACTIVE'),
  (10, 'resources', 'Forms', 'forms', 40, 'ACTIVE'),
  (11, 'resources', 'Checklists', 'checklists', 50, 'ACTIVE'),
  (12, 'resources', 'Videos', 'videos', 60, 'ACTIVE'),
  (13, 'resources', 'Reference documents', 'reference', 70, 'ACTIVE'),
  (14, 'policy', 'Regulations', 'regulations', 10, 'ACTIVE'),
  (15, 'policy', 'Government policy', 'government-policy', 20, 'ACTIVE'),
  (16, 'policy', 'Procurement changes', 'procurement-changes', 30, 'ACTIVE'),
  (17, 'policy', 'Advocacy outcomes', 'advocacy-outcomes', 40, 'ACTIVE');

INSERT IGNORE INTO education_courses
  (id, title, slug, start_date, end_date, venue_id, venue_text, audience, fees, cpd_points, cpd_info, description, registration_url, learner_portal_url, cpd_course_id, facilitator_id, status, is_featured)
VALUES
  (1, 'Construction Contract Administration', 'construction-contract-administration',
   '2026-05-25 08:30:00', '2026-05-28 14:00:00', 1, 'SIBANE SAMI HOTEL',
   'Active ECA contractor members and contract administrators', 'E350', 4.00, '4 CPD points',
   'Stage 4 trains contractors to administer the contract from signature through to completion of the works, so that they receive every entitlement the contract fairly grants them. The course uses JBCC, GCC and FIDIC as illustrations, but the discipline applies to any contract.',
   '/cpd/registration.php', '/cpd/login.php', 1, 1, 'COMPLETED', 1),
  (2, 'Multilateral Development Bank (MDB) Standard Bidding Documents (SBDs) Training', 'mdb-standard-bidding-documents',
   '2026-08-10 08:30:00', '2026-08-11 14:00:00', 2, 'BETHEL COURT ZULWINI',
   'Contractors bidding on MDB-funded projects', 'E450', 2.00, '2 CPD points',
   'Tender interpretation, financial capacity ratios, joint-venture structuring and environmental and social compliance for multilateral development bank standard bidding documents.',
   '/cpd/registration.php', '/cpd/login.php', 3, 1, 'COMPLETED', 1);

INSERT IGNORE INTO education_programmes (id, title, slug, summary, body, status, is_featured, sort_order) VALUES
  (1, 'Emerging Contractor Development', 'emerging-contractor-development',
   'A structured pathway for newer firms to build systems, compliance and delivery capacity.',
   'This programme helps emerging contractors move from informal practice to documented, bid-ready operations. It covers company setup, basic financial controls, site administration and how to use ECA membership support.',
   'PUBLISHED', 1, 10),
  (2, 'Contractor Mentorship', 'contractor-mentorship',
   'Pairing developing firms with experienced ECA members for practical guidance.',
   'Mentorship focuses on real project problems: pricing, cash flow, contract notices and client communication. ECA will expand mentor matching as more senior members join the programme.',
   'PUBLISHED', 0, 20),
  (3, 'Business Readiness', 'business-readiness',
   'Prepare the firm, not only the site team, for sustainable work.',
   'Business readiness covers registrations, banking, tax, insurance, record-keeping and the documents clients and funders expect before award.',
   'PUBLISHED', 0, 30),
  (4, 'Leadership Development', 'leadership-development',
   'Strengthen owners and site leaders who run contractor businesses.',
   'Sessions focus on decision-making, people management, ethics and how leaders keep a firm compliant while winning work.',
   'PUBLISHED', 0, 40),
  (5, 'Digital Skills', 'digital-skills',
   'Practical digital tools for tendering, records and project control.',
   'Digital skills training introduces contractors to the tools they already need: email discipline, document control, spreadsheets, and online tender portals.',
   'PUBLISHED', 0, 50),
  (6, 'Technical Capacity Building', 'technical-capacity-building',
   'Build construction method, quality and supervision capability.',
   'Technical modules sit alongside CPD courses and can be expanded as ECA identifies skill gaps in civil, building, electrical and specialist trades.',
   'PUBLISHED', 0, 60),
  (7, 'Financial Readiness', 'financial-readiness',
   'Cash flow, costing and the financial evidence required for bids.',
   'Financial readiness explains what evaluators look for, how to present statements, and how to avoid under-pricing that collapses a project.',
   'PUBLISHED', 0, 70),
  (8, 'Project Management', 'project-management',
   'Plan, programme and close out work to contract requirements.',
   'This programme translates project-management practice into contractor language: programmes, resources, notices, quality and handover.',
   'PUBLISHED', 0, 80),
  (9, 'Women Contractor Development', 'women-contractor-development',
   'Targeted support aligned with ECA’s Women in Construction and Balingani work.',
   'The programme supports women-owned firms with access to networks, practical business skills and visibility through ECA platforms such as the Balingani directory.',
   'PUBLISHED', 1, 90),
  (10, 'Youth Contractor Development', 'youth-contractor-development',
   'Entry support for younger contractors building their first firms.',
   'Youth contractor development introduces association membership, basic compliance and the training path from learner status into a contracting business.',
   'PUBLISHED', 0, 100);

INSERT IGNORE INTO education_articles
  (id, kind, title, slug, category_id, resource_type, summary, body, what_changed, why_matters, contractor_action, eca_support, status, is_featured, published_at)
VALUES
  (1, 'knowledge', 'Reading a construction contract before you sign', 'reading-a-construction-contract',
   1, 'guide',
   'A short contractor guide to the clauses that decide payment, time and risk.',
   'Before you sign, identify the form of contract, the scope, the payment terms, the notice periods and who carries delay risk. Ask ECA if a clause is unusual for Eswatini practice.',
   NULL, NULL, NULL, NULL, 'PUBLISHED', 1, NOW()),
  (2, 'knowledge', 'What a complete tender file must contain', 'complete-tender-file',
   2, 'checklist',
   'A practical checklist for assembling a compliant bid.',
   'Most disqualifications are document failures, not price. Collect company papers, experience records, financials, declarations and the exact forms the invitation requested — then check names and dates.',
   NULL, NULL, NULL, NULL, 'PUBLISHED', 0, NOW()),
  (3, 'knowledge', 'Cash flow on a live site', 'cash-flow-on-a-live-site',
   3, 'article',
   'How payment cycles affect labour, plant and materials.',
   'Price the payment cycle, not only the bill of quantities. Track certified work, retention and the days it actually takes to be paid. A profitable rate still fails if cash arrives after suppliers have been paid.',
   NULL, NULL, NULL, NULL, 'PUBLISHED', 0, NOW()),
  (4, 'knowledge', 'Site programme in one page', 'site-programme-in-one-page',
   4, 'template',
   'A simple way to show sequence, duration and responsibility.',
   'A one-page programme should show the main activities, who owns them, and the dates that trigger notices. Update it when the site changes — a stale programme cannot support a claim.',
   NULL, NULL, NULL, NULL, 'PUBLISHED', 0, NOW()),
  (5, 'knowledge', 'Statutory registrations contractors must keep current', 'statutory-registrations',
   5, 'faq',
   'The core compliance file clients ask for again and again.',
   'Keep company registration, tax, labour, CIC and insurance documents together and diary their renewal dates. A lapsed certificate can stop a payment or a tender at the last hour.',
   NULL, NULL, NULL, NULL, 'PUBLISHED', 0, NOW()),
  (6, 'knowledge', 'Toolbox talks that actually happen', 'toolbox-talks',
   6, 'guide',
   'Short, repeatable health and safety briefings for construction teams.',
   'A toolbox talk should name the day’s risk, the control, and who stops the work if the control is missing. Record attendance. ECA wellness and safety resources can support the topics.',
   NULL, NULL, NULL, NULL, 'PUBLISHED', 0, NOW()),
  (7, 'policy', 'Inclusive procurement and contractor participation', 'inclusive-procurement-contractor-participation',
   16, 'article',
   'What inclusive procurement means for ECA members in practical terms.',
   'ECA’s advocacy on inclusive procurement is about access to work, not only policy language. This note translates the issue into contractor actions.',
   'Public buyers are being asked to open opportunities in ways that local contractors can actually meet — including documentation, joint ventures and reserved packages where policy allows.',
   'If you cannot interpret a bidding document or prove experience in the format requested, you are excluded before price is considered. Inclusive rules only help firms that can still submit a complete, compliant bid.',
   'Read the invitation in full. Confirm eligibility, packaging, JV rules and the exact forms. Use ECA training on standard bidding documents where the project is MDB-funded.',
   'ECA publishes guidance, runs CPD on bidding documents, and represents contractors when procurement rules shut out capable local firms. Contact the secretariat if a live tender appears unworkable.',
   'PUBLISHED', 1, NOW());

INSERT IGNORE INTO education_resources
  (id, title, description, category_id, resource_type, file_path, file_type, external_url, status, is_featured)
VALUES
  (1, 'Training catalogue 2026–2027', 'ECA training catalogue for the current membership year.', 13, 'reference', NULL, 'pdf', '/download.php?file=CATALOGUE2026-2027.pdf', 'PUBLISHED', 1),
  (2, 'Training report 2025', 'Annual training report for members and partners.', 7, 'pdf', NULL, 'pdf', '/download.php?file=Training-Report-2025.pdf', 'PUBLISHED', 0),
  (3, 'Inclusive procurement white paper', 'ECA white paper on inclusive procurement.', 13, 'reference', NULL, 'pdf', '/download.php?file=ECA-WHITE-PAPER-2025.pdf', 'PUBLISHED', 1),
  (4, 'Membership application form', 'Current ECA membership application form.', 10, 'form', NULL, 'pdf', '/download.php?file=2025-2026-ECAMembershipApplicationForm.pdf', 'PUBLISHED', 0),
  (5, 'Membership renewal form', 'Current ECA membership renewal form.', 10, 'form', NULL, 'pdf', '/download.php?file=2025%202026%20MEMBERSHIP%20RENEWAL%20FORM.pdf', 'PUBLISHED', 0);