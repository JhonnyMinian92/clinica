-- Adaptive clinic architecture: a doctor may have one or many specialties.
-- Safe incremental migration: preserves doctors.specialty_id for backward compatibility.

CREATE TABLE IF NOT EXISTS doctor_specialties (
  doctor_id VARCHAR(50) NOT NULL,
  specialty_id VARCHAR(50) NOT NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (doctor_id, specialty_id),
  KEY idx_doctor_specialties_specialty (specialty_id, doctor_id),
  KEY idx_doctor_specialties_active (active, doctor_id, specialty_id),
  CONSTRAINT fk_doctor_specialties_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  CONSTRAINT fk_doctor_specialties_specialty FOREIGN KEY (specialty_id) REFERENCES specialties(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill the legacy primary specialty into the new relationship table.
INSERT IGNORE INTO doctor_specialties (doctor_id, specialty_id, active)
SELECT id, specialty_id, active
FROM doctors
WHERE specialty_id IS NOT NULL AND specialty_id <> '';
