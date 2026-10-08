-- ECA performance and security indexes.
-- Apply once after confirming the production schema backup.

ALTER TABLE tbl_client
    ADD INDEX idx_tbl_client_membership (MembershipNumber),
    ADD INDEX idx_tbl_client_email (EmailAddress),
    ADD INDEX idx_tbl_client_application_reference (application_reference),
    ADD INDEX idx_tbl_client_application_status (application_status);

ALTER TABLE companies
    ADD INDEX idx_companies_registration (registration_number),
    ADD INDEX idx_companies_email (email),
    ADD INDEX idx_companies_industry_status (industry, status);

ALTER TABLE companies1
    ADD INDEX idx_companies1_registration (registration_number),
    ADD INDEX idx_companies1_email (email),
    ADD INDEX idx_companies1_industry_status (industry, status);

ALTER TABLE cpd_applications
    ADD INDEX idx_cpd_membership (membership_number),
    ADD INDEX idx_cpd_course_status (course_id, status),
    ADD INDEX idx_cpd_email_status (email, training_status);

ALTER TABLE cpd_points_ledger
    ADD INDEX idx_ledger_user_created (user_id, created_at),
    ADD INDEX idx_ledger_course_points (course_id, points);

ALTER TABLE event_registrations
    ADD INDEX idx_event_registrations_event (event_id);

ALTER TABLE wallet_transactions
    ADD UNIQUE INDEX idx_wallet_request (request_id),
    ADD INDEX idx_wallet_membership_status (membership_number, status);
