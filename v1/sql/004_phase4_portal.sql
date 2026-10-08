-- Phase 4 portal extras (eca_portal_local).
-- New columns on notifications:
--   membership_number VARCHAR(50) NULL
--   client_id INT NULL

ALTER TABLE notifications
  ADD COLUMN membership_number VARCHAR(50) NULL,
  ADD COLUMN client_id INT NULL;

ALTER TABLE notifications
  ADD KEY idx_notifications_membership (membership_number),
  ADD KEY idx_notifications_client (client_id);
