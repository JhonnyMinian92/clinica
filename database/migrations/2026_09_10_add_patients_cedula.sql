-- Adds a stable national-ID (cedula/documento) column to patients so a person
-- can be reliably identified at booking time even if they type their name,
-- email or phone differently across appointments.
--
-- Safe to run once against an existing database (fresh installs get this
-- column already via database/import_infinityfree_clean.sql).

ALTER TABLE `patients`
  ADD COLUMN `cedula` varchar(20) DEFAULT NULL AFTER `phone`;

ALTER TABLE `patients`
  ADD UNIQUE KEY `uq_patients_cedula` (`cedula`);
