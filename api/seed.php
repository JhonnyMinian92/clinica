<?php
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/data.php';

try {
  $pdo = clinica_get_db();
  echo "Conexión a la base de datos establecida.\n\n";

  // 1. OBTENER DATOS DEL JSON
  $data = clinica_read_database();
  echo "Datos leídos desde gestion-data.json.\n";

  // 2. CREAR TABLAS (ELIMINANDO LAS ANTERIORES SI EXISTEN)
  $schema = file_get_contents(__DIR__ . '/../context.md');
  preg_match('/```sql(.*?)```/s', $schema, $matches);
  $sqlSchema = $matches[1] ?? '';

  if (empty($sqlSchema)) {
    throw new Exception("No se pudo extraer el esquema SQL del context.md");
  }

  // Separar y ejecutar cada sentencia
  $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
  $tables = ['audit_logs', 'appointments', 'patients', 'doctor_schedules', 'users', 'doctors', 'specialties', 'services', 'testimonials', 'clinics'];
  foreach ($tables as $table) {
    $pdo->exec("DROP TABLE IF EXISTS `$table`;");
    echo "Tabla `$table` eliminada (si existía).\n";
  }

  $pdo->exec($sqlSchema);
  $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
  echo "\nEsquema de base de datos creado exitosamente.\n\n";

  // 3. POBLAR TABLAS
  echo "Iniciando el poblamiento de datos (seeding)...\n";

  // Clinics
  $defaultClinicData = clinica_get_seed_data()['clinic'];
  $jsonClinicData = $data['clinic'] ?? [];
  $clinicData = array_merge($defaultClinicData, $jsonClinicData);
  $clinicData['stats'] = json_encode($clinicData['stats'] ?? []);
  $clinicData['about_cards'] = json_encode($clinicData['about_cards'] ?? []);
  $stmt = $pdo->prepare("
    INSERT INTO clinics (
      name, legal_name, logo_label, logo_image, tagline, description, meta_title, meta_description, canonical_url,
      hero_title, hero_subtitle, hero_image, hero_mobile_image,
      about_title, about_subtitle, services_title, services_subtitle, specialties_title, specialties_subtitle,
      doctors_title, doctors_subtitle, comments_title, comments_subtitle,
      reservation_title, reservation_subtitle, reservation_summary_note, reservation_success_title, reservation_success_text,
      reservation_back_home_label, reservation_next_label, reservation_confirm_label, reservation_previous_label,
      address, email, phone_display, phone_href, emergency_phone, whatsapp_url, facebook_url, instagram_url,
      stats, about_cards
    ) VALUES (
      :name, :legal_name, :logo_label, :logo_image, :tagline, :description, :meta_title, :meta_description, :canonical_url,
      :hero_title, :hero_subtitle, :hero_image, :hero_mobile_image,
      :about_title, :about_subtitle, :services_title, :services_subtitle, :specialties_title, :specialties_subtitle,
      :doctors_title, :doctors_subtitle, :comments_title, :comments_subtitle,
      :reservation_title, :reservation_subtitle, :reservation_summary_note, :reservation_success_title, :reservation_success_text,
      :reservation_back_home_label, :reservation_next_label, :reservation_confirm_label, :reservation_previous_label,
      :address, :email, :phone_display, :phone_href, :emergency_phone, :whatsapp_url, :facebook_url, :instagram_url,
      :stats, :about_cards
    )
  ");
  $stmt->execute($clinicData);
  echo "- Tabla 'clinics' poblada.\n";

  // Specialties
  $stmt = $pdo->prepare("INSERT INTO specialties (id, name, summary, description, icon, featured, active) VALUES (:id, :name, :summary, :description, :icon, :featured, :active)");
  foreach ($data['specialties'] as $item) {
    $stmt->execute([
      ':id' => $item['id'],
      ':name' => $item['name'],
      ':summary' => $item['summary'],
      ':description' => $item['description'],
      ':icon' => $item['icon'],
      ':featured' => (int)(bool)$item['featured'],
      ':active' => (int)(bool)$item['active'],
    ]);
  }
  echo "- Tabla 'specialties' poblada.\n";

  // Services (asumiendo que existe en el JSON)
  if (isset($data['services'])) {
    $stmt = $pdo->prepare("INSERT INTO services (id, title, description, icon, featured, active) VALUES (:id, :title, :description, :icon, :featured, :active)");
    foreach ($data['services'] as $item) {
      $stmt->execute([
        ':id' => $item['id'],
        ':title' => $item['title'],
        ':description' => $item['description'],
        ':icon' => $item['icon'],
        ':featured' => (int)(bool)$item['featured'],
        ':active' => (int)(bool)$item['active'],
      ]);
    }
    echo "- Tabla 'services' poblada.\n";
  }

  // Doctors
  $stmt = $pdo->prepare("INSERT INTO doctors (id, name, photo, specialty_id, sub_specialty, experience_years, university, languages, recognitions, rating, patients, featured, active) VALUES (:id, :name, :photo, :specialty_id, :sub_specialty, :experience_years, :university, :languages, :recognitions, :rating, :patients, :featured, :active)");
  foreach ($data['doctors'] as $item) {
    $stmt->execute([
      ':id' => $item['id'],
      ':name' => $item['name'],
      ':photo' => $item['photo'],
      ':specialty_id' => $item['specialty_id'],
      ':sub_specialty' => $item['sub_specialty'],
      ':experience_years' => $item['experience_years'],
      ':university' => $item['university'],
      ':languages' => json_encode($item['languages']),
      ':recognitions' => json_encode($item['recognitions']),
      ':rating' => $item['rating'],
      ':patients' => $item['patients'],
      ':featured' => (int)(bool)$item['featured'],
      ':active' => (int)(bool)$item['active'],
    ]);
  }
  echo "- Tabla 'doctors' poblada.\n";

  // Users
  $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, doctor_id, active) VALUES (:username, :password, :name, :role, :doctor_id, :active)");
  foreach ($data['users'] as $item) {
    $stmt->execute([
      ':username' => $item['username'],
      ':password' => password_hash($item['password'], PASSWORD_DEFAULT), // HASHING PASSWORD
      ':name' => $item['name'],
      ':role' => $item['role'],
      ':doctor_id' => empty($item['doctor_id']) ? null : $item['doctor_id'],
      ':active' => (int)(bool)$item['active'],
    ]);
  }
  echo "- Tabla 'users' poblada (contraseñas hasheadas).\n";

  // Doctor Schedules
  $stmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, weekday, start_time, end_time, slot_minutes, active, date) VALUES (:doctor_id, :weekday, :start_time, :end_time, :slot_minutes, :active, :date)");
  foreach ($data['schedules'] as $item) {
    $stmt->execute([
      ':doctor_id' => $item['doctor_id'],
      ':weekday' => $item['weekday'],
      ':start_time' => $item['start_time'],
      ':end_time' => $item['end_time'],
      ':slot_minutes' => $item['slot_minutes'],
      ':active' => (int)(bool)$item['active'],
      ':date' => empty($item['date']) ? null : $item['date'],
    ]);
  }
  echo "- Tabla 'doctor_schedules' poblada.\n";

  // Seed initial appointments
  $appointments = clinica_seed_appointments();
  $patientStmt = $pdo->prepare("INSERT INTO patients (name, email, phone) VALUES (:name, :email, :phone) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), name=VALUES(name), phone=VALUES(phone)");
  $appointmentStmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, specialty_id, date, time, status, notes) VALUES (:patient_id, :doctor_id, :specialty_id, :date, :time, :status, :notes)");

  foreach ($appointments as $app) {
    // 1. Find or create patient
    $patientStmt->execute([
      ':name' => $app['nombre_paciente'],
      ':email' => $app['email_paciente'],
      ':phone' => $app['telefono_paciente'],
    ]);
    $patientId = $pdo->lastInsertId();

    // 2. Find specialty_id from doctor_id
    $docStmt = $pdo->prepare("SELECT specialty_id FROM doctors WHERE id = ?");
    $docStmt->execute([$app['doctor_id']]);
    $doctor = $docStmt->fetch();
    $specialtyId = $doctor['specialty_id'] ?? 'cardiologia'; // Fallback

    // 3. Insert appointment
    $appointmentStmt->execute([
      ':patient_id' => $patientId,
      ':doctor_id' => $app['doctor_id'],
      ':specialty_id' => $specialtyId,
      ':date' => $app['fecha'],
      ':time' => $app['hora'],
      ':status' => $app['estado'],
      ':notes' => $app['motivo_consulta'],
    ]);
  }
  echo "- Tablas 'patients' y 'appointments' pobladas con datos de ejemplo.\n";

  // Testimonials (comments)
  if (isset($data['comments'])) {
    $stmt = $pdo->prepare("INSERT INTO testimonials (id, author, detail, text, rating, avatar, featured, active) VALUES (:id, :author, :detail, :text, :rating, :avatar, :featured, :active)");
    foreach ($data['comments'] as $item) {
      $stmt->execute([
        ':id' => $item['id'],
        ':author' => $item['author'],
        ':detail' => $item['detail'],
        ':text' => $item['text'],
        ':rating' => $item['rating'],
        ':avatar' => $item['avatar'],
        ':featured' => (int)(bool)$item['featured'],
        ':active' => (int)(bool)$item['active'],
      ]);
    }
    echo "- Tabla 'testimonials' poblada.\n";
  }

  echo "\n¡PROCESO DE SEEDING COMPLETADO!\n";
} catch (PDOException $e) {
  die("Error de base de datos: " . $e->getMessage());
} catch (Exception $e) {
  die("Error general: " . $e->getMessage());
}
