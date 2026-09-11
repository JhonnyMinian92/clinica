<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/data.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Inicializando base de datos de Clinica...\n";

try {
  $credentials = clinica_db_credentials();
  $port = trim((string) ($credentials['port'] ?? ''));
  $portSegment = $port !== '' ? ";port={$port}" : '';
  $serverDsn = "mysql:host={$credentials['host']}{$portSegment};charset={$credentials['charset']}";
  try {
    $serverPdo = new PDO($serverDsn, $credentials['user'], $credentials['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $dbName = str_replace('`', '``', $credentials['dbname']);
    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  } catch (PDOException $prepareException) {
    echo "No se pudo preparar la base desde este usuario; se usara la base existente {$credentials['dbname']}.\n";
  }

  $pdo = clinica_get_db();
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  clinica_seed_create_schema($pdo);
  clinica_seed_apply_migrations($pdo);
  clinica_seed_base_data($pdo);
  echo "Base de datos lista sin eliminar datos existentes.\n";
} catch (PDOException $e) {
  echo "Error durante la inicializacion: " . $e->getMessage() . "\n";
  exit(1);
}

function clinica_seed_create_schema(PDO $pdo): void
{
  $pdo->exec("CREATE TABLE IF NOT EXISTS clinics (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(255) NOT NULL, legal_name VARCHAR(255) DEFAULT NULL,
      logo_label VARCHAR(255) DEFAULT NULL, logo_image VARCHAR(255) DEFAULT NULL, tagline VARCHAR(255) DEFAULT NULL,
      description TEXT DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description TEXT DEFAULT NULL,
      canonical_url VARCHAR(255) DEFAULT NULL, hero_title VARCHAR(255) DEFAULT NULL, hero_subtitle TEXT DEFAULT NULL,
      hero_image VARCHAR(255) DEFAULT NULL, hero_mobile_image VARCHAR(255) DEFAULT NULL, about_title VARCHAR(255) DEFAULT NULL,
      about_subtitle TEXT DEFAULT NULL, services_title VARCHAR(255) DEFAULT NULL, services_subtitle TEXT DEFAULT NULL,
      specialties_title VARCHAR(255) DEFAULT NULL, specialties_subtitle TEXT DEFAULT NULL, doctors_title VARCHAR(255) DEFAULT NULL,
      doctors_subtitle TEXT DEFAULT NULL, comments_title VARCHAR(255) DEFAULT NULL, comments_subtitle TEXT DEFAULT NULL,
      reservation_title VARCHAR(255) DEFAULT NULL, reservation_subtitle TEXT DEFAULT NULL, reservation_summary_note TEXT DEFAULT NULL,
      reservation_success_title VARCHAR(255) DEFAULT NULL, reservation_success_text TEXT DEFAULT NULL, reservation_back_home_label VARCHAR(100) DEFAULT NULL,
      reservation_next_label VARCHAR(100) DEFAULT NULL, reservation_confirm_label VARCHAR(100) DEFAULT NULL, reservation_previous_label VARCHAR(100) DEFAULT NULL,
      address VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone_display VARCHAR(50) DEFAULT NULL, phone_href VARCHAR(50) DEFAULT NULL,
      emergency_phone VARCHAR(50) DEFAULT NULL, whatsapp_url VARCHAR(255) DEFAULT NULL, facebook_url VARCHAR(255) DEFAULT NULL, instagram_url VARCHAR(255) DEFAULT NULL,
      stats LONGTEXT DEFAULT NULL, about_cards LONGTEXT DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS specialties (
      id VARCHAR(50) NOT NULL, name VARCHAR(255) NOT NULL, summary TEXT DEFAULT NULL, description TEXT DEFAULT NULL,
      icon TEXT DEFAULT NULL, featured BOOLEAN NOT NULL DEFAULT FALSE, active BOOLEAN NOT NULL DEFAULT TRUE,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS doctors (
      id VARCHAR(50) NOT NULL, name VARCHAR(255) NOT NULL, photo VARCHAR(255) DEFAULT NULL, specialty_id VARCHAR(50) NOT NULL,
      sub_specialty VARCHAR(255) DEFAULT NULL, experience_years INT UNSIGNED DEFAULT 0, university VARCHAR(255) DEFAULT NULL,
      languages LONGTEXT DEFAULT NULL, recognitions LONGTEXT DEFAULT NULL, rating TINYINT UNSIGNED DEFAULT 5, patients INT UNSIGNED DEFAULT 0,
      featured BOOLEAN NOT NULL DEFAULT FALSE, active BOOLEAN NOT NULL DEFAULT TRUE, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY doctors_specialty_id_foreign (specialty_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS doctor_specialties (
      doctor_id VARCHAR(50) NOT NULL, specialty_id VARCHAR(50) NOT NULL, active BOOLEAN NOT NULL DEFAULT TRUE,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (doctor_id, specialty_id), KEY idx_doctor_specialties_specialty (specialty_id, doctor_id),
      KEY idx_doctor_specialties_active (active, doctor_id, specialty_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS users (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT, username VARCHAR(50) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL,
      role ENUM('ADMINISTRATOR', 'SECRETARIA', 'DOCTOR') NOT NULL, doctor_id VARCHAR(50) DEFAULT NULL, active BOOLEAN NOT NULL DEFAULT TRUE,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id), KEY users_doctor_id_foreign (doctor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS services (
      id VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, icon TEXT DEFAULT NULL,
      featured BOOLEAN NOT NULL DEFAULT FALSE, active BOOLEAN NOT NULL DEFAULT TRUE, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS testimonials (
      id VARCHAR(50) NOT NULL, author VARCHAR(255) NOT NULL, detail VARCHAR(255) DEFAULT NULL, text TEXT NOT NULL, rating TINYINT UNSIGNED NOT NULL,
      avatar VARCHAR(255) DEFAULT NULL, featured BOOLEAN NOT NULL DEFAULT FALSE, active BOOLEAN NOT NULL DEFAULT TRUE,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS doctor_schedules (
      id VARCHAR(50) NOT NULL, doctor_id VARCHAR(50) NOT NULL, date DATE DEFAULT NULL, weekday TINYINT UNSIGNED NOT NULL, start_time TIME NOT NULL,
      end_time TIME NOT NULL, slot_minutes INT UNSIGNED NOT NULL DEFAULT 15, active BOOLEAN NOT NULL DEFAULT TRUE,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id), KEY doctor_schedules_doctor_id_foreign (doctor_id), KEY idx_schedule_lookup (doctor_id, weekday, start_time), KEY idx_schedule_date_lookup (doctor_id, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS patients (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL UNIQUE, phone VARCHAR(50) DEFAULT NULL,
      cedula VARCHAR(20) DEFAULT NULL, active BOOLEAN NOT NULL DEFAULT TRUE, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uq_patients_cedula (cedula)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS appointments (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT, patient_id INT UNSIGNED NOT NULL, doctor_id VARCHAR(50) NOT NULL, specialty_id VARCHAR(50) NOT NULL,
      date DATE NOT NULL, time TIME NOT NULL, slot_minutes INT UNSIGNED NOT NULL DEFAULT 15,
      status ENUM('pendiente','atendida','no-presentado','rechazada','cancelada') NOT NULL DEFAULT 'pendiente', notes TEXT DEFAULT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id), UNIQUE KEY uq_appointment_slot (doctor_id, date, time), KEY appointments_patient_id_foreign (patient_id),
      KEY appointments_specialty_id_foreign (specialty_id), KEY idx_appointment_lookup (date, time), KEY idx_appointment_status (status, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS appointment_status_history (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, appointment_id INT UNSIGNED NOT NULL,
      old_status ENUM('pendiente','atendida','no-presentado','rechazada','cancelada') DEFAULT NULL,
      new_status ENUM('pendiente','atendida','no-presentado','rechazada','cancelada') NOT NULL, changed_by_user_id INT UNSIGNED DEFAULT NULL,
      reason TEXT DEFAULT NULL, changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id),
      KEY appointment_status_history_appointment_id_foreign (appointment_id), KEY appointment_status_history_changed_by_user_id_foreign (changed_by_user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS audit_logs (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id INT UNSIGNED DEFAULT NULL, action VARCHAR(100) NOT NULL,
      resource_type VARCHAR(50) DEFAULT NULL, resource_id VARCHAR(50) DEFAULT NULL, details LONGTEXT DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY audit_logs_user_id_foreign (user_id), KEY idx_audit_resource (resource_type, resource_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ");
}

function clinica_seed_apply_migrations(PDO $pdo): void
{
  $pdo->exec("ALTER TABLE appointments MODIFY status ENUM('pendiente','atendida','no-presentado','rechazada','cancelada') NOT NULL DEFAULT 'pendiente'");
  clinica_seed_add_column($pdo, 'appointments', 'slot_minutes', "ALTER TABLE appointments ADD COLUMN slot_minutes INT UNSIGNED NOT NULL DEFAULT 15 AFTER time");
  clinica_seed_add_column($pdo, 'patients', 'active', "ALTER TABLE patients ADD COLUMN active BOOLEAN NOT NULL DEFAULT TRUE AFTER phone");
  clinica_seed_add_column($pdo, 'patients', 'cedula', "ALTER TABLE patients ADD COLUMN cedula VARCHAR(20) DEFAULT NULL AFTER phone");
  $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND INDEX_NAME = 'uq_patients_cedula'");
  if ((int) $stmt->fetchColumn() === 0) $pdo->exec("ALTER TABLE patients ADD UNIQUE KEY uq_patients_cedula (cedula)");

  // Adaptive architecture: create the relation incrementally and backfill the legacy primary specialty.
  $pdo->exec("CREATE TABLE IF NOT EXISTS doctor_specialties (
    doctor_id VARCHAR(50) NOT NULL, specialty_id VARCHAR(50) NOT NULL, active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (doctor_id, specialty_id), KEY idx_doctor_specialties_specialty (specialty_id, doctor_id), KEY idx_doctor_specialties_active (active, doctor_id, specialty_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  $pdo->exec("INSERT IGNORE INTO doctor_specialties (doctor_id, specialty_id, active) SELECT id, specialty_id, active FROM doctors WHERE specialty_id IS NOT NULL AND specialty_id <> ''");

  $stmt = $pdo->query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctor_schedules' AND COLUMN_NAME = 'id'");
  if (($stmt->fetchColumn() ?: '') !== 'varchar') $pdo->exec("ALTER TABLE doctor_schedules MODIFY id VARCHAR(50) NOT NULL");

  clinica_seed_add_column($pdo, 'patients', 'email_opt_in', "ALTER TABLE patients ADD COLUMN email_opt_in BOOLEAN NOT NULL DEFAULT TRUE AFTER active");
  clinica_seed_add_column($pdo, 'patients', 'whatsapp_opt_in', "ALTER TABLE patients ADD COLUMN whatsapp_opt_in BOOLEAN NOT NULL DEFAULT TRUE AFTER email_opt_in");
  clinica_seed_add_column($pdo, 'clinics', 'notify_email_enabled', "ALTER TABLE clinics ADD COLUMN notify_email_enabled BOOLEAN NOT NULL DEFAULT TRUE AFTER whatsapp_url");
  clinica_seed_add_column($pdo, 'clinics', 'notify_whatsapp_enabled', "ALTER TABLE clinics ADD COLUMN notify_whatsapp_enabled BOOLEAN NOT NULL DEFAULT FALSE AFTER notify_email_enabled");
  clinica_seed_add_column($pdo, 'clinics', 'reminder_minutes_before', "ALTER TABLE clinics ADD COLUMN reminder_minutes_before INT UNSIGNED NOT NULL DEFAULT 15 AFTER notify_whatsapp_enabled");

  $pdo->exec("CREATE TABLE IF NOT EXISTS notification_log (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, appointment_id INT UNSIGNED NOT NULL, channel ENUM('email','whatsapp') NOT NULL,
      type ENUM('confirmation','reminder') NOT NULL, status ENUM('sent','failed','skipped') NOT NULL, provider VARCHAR(50) DEFAULT NULL,
      recipient VARCHAR(255) DEFAULT NULL, error_message TEXT DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id), KEY idx_notification_appointment (appointment_id), KEY idx_notification_lookup (appointment_id, channel, type, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function clinica_seed_add_column(PDO $pdo, string $table, string $column, string $sql): void
{
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
  $stmt->execute([$table, $column]);
  if ((int) $stmt->fetchColumn() === 0) $pdo->exec($sql);
}

function clinica_seed_base_data(PDO $pdo): void
{
  $seed = clinica_read_database();
  if ((int) $pdo->query("SELECT COUNT(*) FROM clinics")->fetchColumn() === 0 && !empty($seed['clinic'])) {
    $clinic = $seed['clinic'];
    $clinic['stats'] = json_encode($clinic['stats'] ?? [], JSON_UNESCAPED_UNICODE);
    $clinic['about_cards'] = json_encode($clinic['about_cards'] ?? [], JSON_UNESCAPED_UNICODE);
    clinica_seed_insert_assoc($pdo, 'clinics', $clinic); echo "Clinica insertada.\n";
  }
  if (clinica_seed_table_is_empty($pdo, 'specialties')) clinica_seed_insert_rows_ignore($pdo, 'specialties', $seed['specialties'] ?? []);
  if (clinica_seed_table_is_empty($pdo, 'doctors')) {
    clinica_seed_insert_rows_ignore($pdo, 'doctors', array_map(static function (array $doctor): array {
      $doctor['languages'] = json_encode($doctor['languages'] ?? [], JSON_UNESCAPED_UNICODE);
      $doctor['recognitions'] = json_encode($doctor['recognitions'] ?? [], JSON_UNESCAPED_UNICODE); return $doctor;
    }, $seed['doctors'] ?? []));
  }
  if (clinica_seed_table_is_empty($pdo, 'services')) clinica_seed_insert_rows_ignore($pdo, 'services', $seed['services'] ?? []);
  if (clinica_seed_table_is_empty($pdo, 'testimonials')) clinica_seed_insert_rows_ignore($pdo, 'testimonials', $seed['testimonials'] ?? []);
  if (clinica_seed_table_is_empty($pdo, 'doctor_schedules')) clinica_seed_insert_rows_ignore($pdo, 'doctor_schedules', $seed['schedules'] ?? []);
  foreach ($seed['users'] ?? [] as $user) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?"); $stmt->execute([$user['username'] ?? '']);
    if ($stmt->fetch()) continue;
    $user['password'] = password_hash((string) ($user['password'] ?? ''), PASSWORD_DEFAULT); unset($user['id']);
    clinica_seed_insert_assoc($pdo, 'users', $user); echo "Usuario {$user['username']} insertado.\n";
  }
}

function clinica_seed_table_is_empty(PDO $pdo, string $table): bool
{
  $safeTable = str_replace('`', '``', $table); return (int) $pdo->query("SELECT COUNT(*) FROM `{$safeTable}`")->fetchColumn() === 0;
}
function clinica_seed_insert_rows_ignore(PDO $pdo, string $table, array $rows): void
{
  foreach ($rows as $row) if (is_array($row)) clinica_seed_insert_assoc($pdo, $table, $row, true);
}
function clinica_seed_insert_assoc(PDO $pdo, string $table, array $row, bool $ignore = false): void
{
  $columns = array_keys($row); $columnSql = implode(', ', array_map(static fn(string $column): string => "`$column`", $columns));
  $placeholderSql = implode(', ', array_map(static fn(string $column): string => ":$column", $columns));
  $verb = $ignore ? 'INSERT IGNORE' : 'INSERT';
  $stmt = $pdo->prepare("$verb INTO `$table` ($columnSql) VALUES ($placeholderSql)"); $stmt->execute($row);
}
