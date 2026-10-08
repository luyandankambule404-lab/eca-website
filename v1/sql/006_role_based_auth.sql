-- Make the existing member account role explicit and database-authoritative.
-- The CPD `user` and administration `users` tables already contain role fields.

ALTER TABLE userss
    ADD COLUMN role ENUM('MEMBER') NOT NULL DEFAULT 'MEMBER' AFTER password,
    ADD INDEX idx_userss_email_status_role (email, status, role);
