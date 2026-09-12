-- Harden the adaptive doctor/specialty relation for installations where the table
-- already existed before the foreign keys were introduced.
-- Safe/idempotent: never drops or truncates data and only adds missing constraints.

SET @has_doctor_fk := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'doctor_specialties'
    AND CONSTRAINT_NAME = 'fk_doctor_specialties_doctor'
);
SET @sql := IF(
  @has_doctor_fk = 0,
  'ALTER TABLE doctor_specialties ADD CONSTRAINT fk_doctor_specialties_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_specialty_fk := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'doctor_specialties'
    AND CONSTRAINT_NAME = 'fk_doctor_specialties_specialty'
);
SET @sql := IF(
  @has_specialty_fk = 0,
  'ALTER TABLE doctor_specialties ADD CONSTRAINT fk_doctor_specialties_specialty FOREIGN KEY (specialty_id) REFERENCES specialties(id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
