-- Import limpio para InfinityFree
-- Base destino esperada: if0_42682038_clinical
-- Este script reemplaza las tablas del proyecto para evitar duplicados de PRIMARY KEY.

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `appointment_status_history`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `doctor_schedules`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `doctors`;
DROP TABLE IF EXISTS `patients`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `specialties`;
DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `clinics`;
SET FOREIGN_KEY_CHECKS=0;
-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- VersiÃ³n del servidor:         10.4.32-MariaDB - mariadb.org binary distribution
-- SO del servidor:              Win64
-- HeidiSQL VersiÃ³n:             12.17.0.7270
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Volcando estructura para tabla clinica_base.appointment_status_history
CREATE TABLE IF NOT EXISTS `appointment_status_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` int(10) unsigned NOT NULL,
  `old_status` enum('pendiente','atendida','no-presentado','rechazada','cancelada') DEFAULT NULL,
  `new_status` enum('pendiente','atendida','no-presentado','rechazada','cancelada') NOT NULL,
  `changed_by_user_id` int(10) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `appointment_status_history_appointment_id_foreign` (`appointment_id`),
  KEY `appointment_status_history_changed_by_user_id_foreign` (`changed_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.appointment_status_history: ~1 rows (aproximadamente)
INSERT INTO `appointment_status_history` (`id`, `appointment_id`, `old_status`, `new_status`, `changed_by_user_id`, `reason`, `changed_at`) VALUES
	(1, 12, 'pendiente', 'atendida', 1, 'Prueba funcional automatizada', '2026-08-17 16:30:24');

-- Volcando estructura para tabla clinica_base.appointments
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(10) unsigned NOT NULL,
  `doctor_id` varchar(50) NOT NULL,
  `specialty_id` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `slot_minutes` int(10) unsigned NOT NULL DEFAULT 15,
  `status` enum('pendiente','atendida','no-presentado','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_appointment_slot` (`doctor_id`,`date`,`time`),
  KEY `appointments_patient_id_foreign` (`patient_id`),
  KEY `appointments_specialty_id_foreign` (`specialty_id`),
  KEY `idx_appointment_lookup` (`date`,`time`),
  KEY `idx_appointment_status` (`status`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.appointments: ~11 rows (aproximadamente)
INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `specialty_id`, `date`, `time`, `slot_minutes`, `status`, `notes`, `created_at`, `updated_at`) VALUES
	(1, 1, 'doc1', 'cardiologia', '2026-08-06', '09:00:00', 15, 'pendiente', 'Chequeo anual', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(2, 2, 'doc3', 'neurologia', '2026-08-06', '09:15:00', 15, 'pendiente', 'Dolor de cabeza persistente', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(3, 3, 'doc3', 'neurologia', '2026-08-06', '09:30:00', 15, 'atendida', 'Resultados de analisis', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(4, 4, 'doc5', 'traumatologia', '2026-08-06', '10:00:00', 15, 'pendiente', 'Control post-operatorio', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(5, 5, 'doc3', 'neurologia', '2026-08-06', '10:30:00', 15, 'pendiente', 'Consulta de seguimiento', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(6, 6, 'doc1', 'cardiologia', '2026-08-06', '11:00:00', 15, 'no-presentado', 'No especificado', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(7, 7, 'doc4', 'dermatologia', '2026-08-05', '14:00:00', 15, 'rechazada', 'Revision dermatologica', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(8, 8, 'doc2', 'pediatria', '2026-08-07', '14:30:00', 15, 'pendiente', 'Vacunacion infantil', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(9, 9, 'doc8', 'psiquiatria', '2026-08-11', '15:00:00', 15, 'pendiente', 'Terapia', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(10, 10, 'doc3', 'neurologia', '2026-08-07', '11:45:00', 15, 'pendiente', 'Control neurologico', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(11, 11, 'doc3', 'neurologia', '2026-08-05', '08:30:00', 15, 'atendida', 'Seguimiento de migrana', '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(12, 12, 'doc1', 'cardiologia', '2026-08-24', '07:00:00', 15, 'atendida', 'Prueba funcional automatizada creada por QA.', '2026-08-17 16:30:24', '2026-08-17 16:30:24');

-- Volcando estructura para tabla clinica_base.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` varchar(50) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `idx_audit_resource` (`resource_type`,`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.audit_logs: ~48 rows (aproximadamente)
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `resource_type`, `resource_id`, `details`, `ip_address`, `created_at`) VALUES
	(1, NULL, 'appointment_created', NULL, NULL, '{"appointment_id":"0","patient_id":"12","doctorId":"doc1","date":"2026-08-24","time":"07:00"}', '::1', '2026-08-17 16:30:24'),
	(2, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '::1', '2026-08-17 16:30:24'),
	(3, 1, 'appointment_status_updated', 'appointment', '12', '{"appointment_id":"12","old_status":"pendiente","new_status":"atendida"}', '::1', '2026-08-17 16:30:24'),
	(4, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '::1', '2026-08-17 16:30:44'),
	(5, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '::1', '2026-08-17 16:31:12'),
	(6, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '::1', '2026-08-17 16:31:29'),
	(7, 1, 'services_updated', NULL, NULL, '{"id":"centro-quirurgico","changes":{"id":"centro-quirurgico","title":"Centro Quirurgico","description":"Quirofanos inteligentes equipados para procedimientos de alta complejidad.","icon":"<path d=\\"M14.5 2.5a2.5 2.5 0 0 0-5 0V6H8a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1.5V2.5z\\"><\\/path><path d=\\"M9 14h6\\"><\\/path><path d=\\"M12 11v6\\"><\\/path>","featured":true,"active":true}}', '::1', '2026-08-17 16:31:29'),
	(8, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '::1', '2026-08-17 16:32:45'),
	(9, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '::1', '2026-08-17 16:33:03'),
	(10, NULL, 'login_failed', 'user', 'admin', '{"username":"admin"}', '::1', '2026-08-17 16:33:20'),
	(11, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '::1', '2026-08-17 16:37:59'),
	(18, NULL, 'login_failed', 'user', 'admin', '{"username":"admin"}', '::1', '2026-08-18 01:24:04'),
	(19, NULL, 'login_failed', 'user', 'admin', '{"username":"admin"}', '::1', '2026-08-18 01:24:40'),
	(20, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 02:36:26'),
	(21, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 02:37:40'),
	(22, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 02:37:40'),
	(23, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 02:37:41'),
	(24, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 02:37:42'),
	(25, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 02:40:12'),
	(26, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 02:41:18'),
	(27, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 02:44:18'),
	(28, NULL, 'login_failed', 'user', 'secretaria', '{"username":"secretaria"}', '127.0.0.1', '2026-08-18 02:44:18'),
	(29, NULL, 'login_failed', 'user', 'dr.vargas', '{"username":"dr.vargas"}', '127.0.0.1', '2026-08-18 02:44:18'),
	(30, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 02:44:40'),
	(31, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:17:57'),
	(32, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:18:27'),
	(33, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:19:12'),
	(34, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:19:27'),
	(35, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:19:42'),
	(36, NULL, 'login_failed', 'user', 'secretaria', '{"username":"secretaria"}', '127.0.0.1', '2026-08-18 03:19:42'),
	(37, NULL, 'login_failed', 'user', 'dr.herrera', '{"username":"dr.herrera"}', '127.0.0.1', '2026-08-18 03:19:42'),
	(38, NULL, 'login_failed', 'user', 'dr.vargas', '{"username":"dr.vargas"}', '127.0.0.1', '2026-08-18 03:19:42'),
	(39, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:20:46'),
	(40, 2, 'login_success', NULL, NULL, '{"username":"secretaria"}', '127.0.0.1', '2026-08-18 03:20:46'),
	(41, 3, 'login_success', NULL, NULL, '{"username":"dr.herrera"}', '127.0.0.1', '2026-08-18 03:20:46'),
	(42, 4, 'login_success', NULL, NULL, '{"username":"dr.vargas"}', '127.0.0.1', '2026-08-18 03:20:46'),
	(43, 3, 'login_success', NULL, NULL, '{"username":"dr.herrera"}', '127.0.0.1', '2026-08-18 03:21:04'),
	(44, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:21:04'),
	(45, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:22:51'),
	(46, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:22:55'),
	(47, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '::1', '2026-08-18 03:27:40'),
	(48, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:36:15'),
	(49, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:36:18'),
	(50, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:36:21'),
	(51, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:36:24'),
	(52, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:38:03'),
	(53, 1, 'login_success', NULL, NULL, '{"username":"admin"}', '127.0.0.1', '2026-08-18 03:38:06'),
	(54, 1, 'schedules_created', NULL, NULL, '{"id":"0","data":{"id":"sch-6a83d4787c92f","doctor_id":"doc1","date":"2026-08-22","weekday":6,"start_time":"10:00","end_time":"17:00","slot_minutes":15,"active":true}}', '::1', '2026-08-18 03:41:44');

-- Volcando estructura para tabla clinica_base.clinics
CREATE TABLE IF NOT EXISTS `clinics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nombre comercial',
  `legal_name` varchar(255) DEFAULT NULL,
  `logo_label` varchar(255) DEFAULT NULL COMMENT 'Texto del logo (fallback)',
  `logo_image` varchar(255) DEFAULT NULL COMMENT 'Ruta a la imagen del logo',
  `tagline` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `canonical_url` varchar(255) DEFAULT NULL,
  `hero_title` varchar(255) DEFAULT NULL,
  `hero_subtitle` text DEFAULT NULL,
  `hero_image` varchar(255) DEFAULT NULL,
  `hero_mobile_image` varchar(255) DEFAULT NULL,
  `about_title` varchar(255) DEFAULT NULL,
  `about_subtitle` text DEFAULT NULL,
  `services_title` varchar(255) DEFAULT NULL,
  `services_subtitle` text DEFAULT NULL,
  `specialties_title` varchar(255) DEFAULT NULL,
  `specialties_subtitle` text DEFAULT NULL,
  `doctors_title` varchar(255) DEFAULT NULL,
  `doctors_subtitle` text DEFAULT NULL,
  `comments_title` varchar(255) DEFAULT NULL,
  `comments_subtitle` text DEFAULT NULL,
  `reservation_title` varchar(255) DEFAULT NULL,
  `reservation_subtitle` text DEFAULT NULL,
  `reservation_summary_note` text DEFAULT NULL,
  `reservation_success_title` varchar(255) DEFAULT NULL,
  `reservation_success_text` text DEFAULT NULL,
  `reservation_back_home_label` varchar(100) DEFAULT NULL,
  `reservation_next_label` varchar(100) DEFAULT NULL,
  `reservation_confirm_label` varchar(100) DEFAULT NULL,
  `reservation_previous_label` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_display` varchar(50) DEFAULT NULL,
  `phone_href` varchar(50) DEFAULT NULL,
  `emergency_phone` varchar(50) DEFAULT NULL,
  `whatsapp_url` varchar(255) DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stats`)),
  `about_cards` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`about_cards`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.clinics: ~0 rows (aproximadamente)
INSERT INTO `clinics` (`id`, `name`, `legal_name`, `logo_label`, `logo_image`, `tagline`, `description`, `meta_title`, `meta_description`, `canonical_url`, `hero_title`, `hero_subtitle`, `hero_image`, `hero_mobile_image`, `about_title`, `about_subtitle`, `services_title`, `services_subtitle`, `specialties_title`, `specialties_subtitle`, `doctors_title`, `doctors_subtitle`, `comments_title`, `comments_subtitle`, `reservation_title`, `reservation_subtitle`, `reservation_summary_note`, `reservation_success_title`, `reservation_success_text`, `reservation_back_home_label`, `reservation_next_label`, `reservation_confirm_label`, `reservation_previous_label`, `address`, `email`, `phone_display`, `phone_href`, `emergency_phone`, `whatsapp_url`, `facebook_url`, `instagram_url`, `stats`, `about_cards`, `created_at`, `updated_at`) VALUES
	(1, 'Clinica Estandar', 'Clinica Estandar', 'Clinica Estandar', '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="logo-svg" aria-hidden="true"><path d="M12 5C12 5 9 2 6 2C3 2 2 4 2 6C2 9 5 12 12 19C19 12 22 9 22 6C22 4 21 2 18 2C15 2 12 5 12 5Z" stroke="currentColor"', 'Medicina de vanguardia con un enfoque humano y personalizado.', 'Centro medico privado con especialistas, tecnologia avanzada y atencion cercana.', 'Clinica Estandar | Medicina de Vanguardia y Cuidado Humano', 'Clinica Estandar: atencion medica especializada, tecnologia de punta y profesionales comprometidos con su bienestar.', 'https://www.clinicaestandar.com', 'Medicina de Vanguardia, Cuidado Humano', 'Combinamos alta tecnologia con especialistas comprometidos con su salud y bienestar.', 'public/assets/banner.jpg', 'public/assets/banner_mobil.jpg', 'Su Salud, Nuestra Prioridad', 'Descubra por que somos la eleccion de confianza para miles de pacientes.', 'Nuestros Servicios', 'Ofrecemos una gama completa de servicios medicos para cuidar de usted en cada etapa.', 'Nuestras Especialidades', 'Cobertura medica integral para todas sus necesidades de salud.', 'Conozca a Nuestro Equipo', 'Profesionales que combinan conocimiento, experiencia y vocacion.', 'La Voz de Nuestros Pacientes', 'Historias reales de quienes han confiado su salud en nuestras manos.', 'Agendar una Cita', 'Siga los pasos para encontrar un horario conveniente para usted.', 'Recibira una confirmacion por correo electronico. Por favor, llegue 15 minutos antes de su cita.', 'Cita Agendada', 'Su cita ha sido confirmada exitosamente. Recibira un correo electronico con todos los detalles. Gracias por confiar en {clinic}.', 'Volver al Inicio', 'Siguiente', 'Confirmar Cita', 'Anterior', 'Buenavista y Tarqui, Machala, Ecuador', 'info@clinicaestandar.com', '(07) 293-3888', '+59372933888', '911', 'https://wa.me/593999999999', 'https://facebook.com/clinicaestandar', 'https://instagram.com/clinicaestandar', '[{"label":"Pacientes Atendidos","value":50000,"suffix":"+"},{"label":"Anos de Experiencia","value":25,"suffix":"+"},{"label":"Especialistas Certificados","value":100,"suffix":"+"},{"label":"Tasa de Satisfaccion","value":98,"suffix":"%"}]', '[{"title":"Tecnologia de Punta","text":"Equipos de ultima generacion para diagnosticos precisos y tratamientos efectivos.","icon":"<rect x=\\"2\\" y=\\"3\\" width=\\"20\\" height=\\"14\\" rx=\\"2\\" ry=\\"2\\"><\\/rect><line x1=\\"8\\" y1=\\"21\\" x2=\\"16\\" y2=\\"21\\"><\\/line><line x1=\\"12\\" y1=\\"17\\" x2=\\"12\\" y2=\\"21\\"><\\/line>"},{"title":"Especialistas de Elite","text":"Un equipo medico de renombre, con amplia experiencia y reconocimiento internacional.","icon":"<path d=\\"M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2\\"><\\/path><circle cx=\\"9\\" cy=\\"7\\" r=\\"4\\"><\\/circle><path d=\\"M23 21v-2a4 4 0 0 0-3-3.87\\"><\\/path><path d=\\"M16 3.13a4 4 0 0 1 0 7.75\\"><\\/path>"},{"title":"Atencion Humana","text":"Cuidado calido y personalizado, porque detras de cada paciente hay una historia.","icon":"<path d=\\"M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z\\"><\\/path>"}]', '2026-08-06 21:22:16', '2026-08-06 21:22:16');

-- Volcando estructura para tabla clinica_base.doctor_schedules
CREATE TABLE IF NOT EXISTS `doctor_schedules` (
  `id` varchar(50) NOT NULL,
  `doctor_id` varchar(50) NOT NULL,
  `date` date DEFAULT NULL COMMENT 'Si es NULL, es una regla semanal recurrente',
  `weekday` tinyint(3) unsigned NOT NULL COMMENT '1 (Lunes) a 7 (Domingo)',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_minutes` int(10) unsigned NOT NULL DEFAULT 15,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `doctor_schedules_doctor_id_foreign` (`doctor_id`),
  KEY `idx_schedule_lookup` (`doctor_id`,`weekday`,`start_time`),
  KEY `idx_schedule_date_lookup` (`doctor_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.doctor_schedules: ~24 rows (aproximadamente)
INSERT INTO `doctor_schedules` (`id`, `doctor_id`, `date`, `weekday`, `start_time`, `end_time`, `slot_minutes`, `active`, `created_at`, `updated_at`) VALUES
	('1', 'doc1', NULL, 1, '07:00:00', '13:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('10', 'doc3', NULL, 4, '07:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('11', 'doc3', NULL, 5, '07:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('12', 'doc4', NULL, 1, '13:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('13', 'doc4', NULL, 3, '13:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('14', 'doc4', NULL, 5, '13:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('15', 'doc5', NULL, 2, '07:00:00', '13:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('16', 'doc5', NULL, 4, '07:00:00', '13:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('17', 'doc6', NULL, 1, '13:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('18', 'doc6', NULL, 3, '07:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('19', 'doc6', NULL, 5, '07:00:00', '13:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('2', 'doc1', NULL, 3, '07:00:00', '13:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('20', 'doc7', NULL, 2, '13:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('21', 'doc7', NULL, 4, '13:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('22', 'doc8', NULL, 1, '07:00:00', '13:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('23', 'doc8', NULL, 4, '07:00:00', '13:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('3', 'doc1', NULL, 5, '13:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('4', 'doc2', NULL, 2, '13:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('5', 'doc2', NULL, 4, '13:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('6', 'doc2', NULL, 6, '07:00:00', '13:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('7', 'doc3', NULL, 1, '07:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('8', 'doc3', NULL, 2, '07:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('9', 'doc3', NULL, 3, '07:00:00', '22:00:00', 15, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('sch-6a83d4787c92f', 'doc1', '2026-08-22', 6, '10:00:00', '17:00:00', 15, 1, '2026-08-18 03:41:44', '2026-08-18 03:41:44');

-- Volcando estructura para tabla clinica_base.doctors
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` varchar(50) NOT NULL COMMENT 'ID basado en slug, ej: doc-alejandro-vargas',
  `name` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `specialty_id` varchar(50) NOT NULL,
  `sub_specialty` varchar(255) DEFAULT NULL,
  `experience_years` int(10) unsigned DEFAULT 0,
  `university` varchar(255) DEFAULT NULL,
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Almacena un array de idiomas' CHECK (json_valid(`languages`)),
  `recognitions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Almacena un array de reconocimientos' CHECK (json_valid(`recognitions`)),
  `rating` tinyint(3) unsigned DEFAULT 5,
  `patients` int(10) unsigned DEFAULT 0,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `doctors_specialty_id_foreign` (`specialty_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.doctors: ~8 rows (aproximadamente)
INSERT INTO `doctors` (`id`, `name`, `photo`, `specialty_id`, `sub_specialty`, `experience_years`, `university`, `languages`, `recognitions`, `rating`, `patients`, `featured`, `active`, `created_at`, `updated_at`) VALUES
	('doc1', 'Dr. Alejandro Vargas', '/clinica/public/assets/doctor_avatar.jpg', 'cardiologia', 'Cardiologia Intervencionista', 15, 'Universidad de Buenos Aires', '["Espanol","Ingles"]', '["Miembro Titular de la SAC"]', 5, 3200, 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('doc2', 'Dra. Sofia Castillo', '/clinica/public/assets/doctora_avatar.png', 'pediatria', 'Neonatologia', 12, 'Universidad Nacional de Colombia', '["Espanol","Portugues"]', '["Certificada por la SCP"]', 5, 2800, 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('doc3', 'Dr. Mateo Herrera', '/clinica/public/assets/doctor_avatar.jpg', 'neurologia', 'Neurofisiologia Clinica', 18, 'Universidad de Chile', '["Espanol","Ingles"]', '["Fellowship en Clinica Mayo"]', 5, 4100, 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('doc4', 'Dra. Valentina Morales', '/clinica/public/assets/doctora_avatar.png', 'dermatologia', 'Dermatologia Cosmetica', 10, 'Universidad Autonoma de Mexico', '["Espanol"]', '["Miembro de la AAD"]', 4, 3500, 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('doc5', 'Dr. Lucas Jimenez', '/clinica/public/assets/doctor_avatar.jpg', 'traumatologia', 'Cirugia de Rodilla y Hombro', 20, 'Universidad Complutense de Madrid', '["Espanol","Ingles"]', '[]', 5, 5000, 0, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('doc6', 'Dra. Camila Rios', '/clinica/public/assets/doctora_avatar.png', 'ginecologia', 'Medicina Reproductiva', 14, 'Universidad de Sao Paulo', '["Espanol","Portugues","Ingles"]', '["Certificacion en Laparoscopia"]', 5, 3900, 0, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('doc7', 'Dr. Sebastian Pena', '/clinica/public/assets/doctor_avatar.jpg', 'oncologia', 'Oncologia Toracica', 22, 'Johns Hopkins University', '["Espanol","Ingles"]', '["Premio Nacional de Medicina"]', 5, 2100, 0, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('doc8', 'Dra. Isabella Gomez', '/clinica/public/assets/doctora_avatar.png', 'psiquiatria', 'Psicoterapia', 9, 'Universidad de Barcelona', '["Espanol","Catalan"]', '[]', 4, 1500, 0, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17');

-- Volcando estructura para tabla clinica_base.patients
CREATE TABLE IF NOT EXISTS `patients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.patients: ~11 rows (aproximadamente)
INSERT INTO `patients` (`id`, `name`, `email`, `phone`, `active`, `created_at`, `updated_at`) VALUES
	(1, 'Ana Torres', 'ana.torres@example.com', '0991112222', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(2, 'Luis Mendez', 'luis.mendez@example.com', '0992223333', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(3, 'Carla Solano', 'carla.solano@example.com', '0993334444', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(4, 'Pedro Jimenez', 'pedro.jimenez@example.com', '0994445555', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(5, 'Sofia Castro', 'sofia.castro@example.com', '0995556666', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(6, 'Elena Rojas', 'elena.rojas@example.com', '0996667777', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(7, 'Marco Vera', 'marco.vera@example.com', '0997778888', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(8, 'Julia Navarro', 'julia.navarro@example.com', '0998889999', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(9, 'Ricardo Ponce', 'ricardo.ponce@example.com', '0999990000', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(10, 'Marta Aguilar', 'marta.aguilar@example.com', '0981112233', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(11, 'Diego Molina', 'diego.molina@example.com', '0983334455', 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	(12, 'Paciente QA Automatizado', 'qa.1786984224@clinica.test', '0991234567', 1, '2026-08-17 16:30:24', '2026-08-17 16:30:24');

-- Volcando estructura para tabla clinica_base.services
CREATE TABLE IF NOT EXISTS `services` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` text DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.services: ~6 rows (aproximadamente)
INSERT INTO `services` (`id`, `title`, `description`, `icon`, `featured`, `active`, `created_at`, `updated_at`) VALUES
	('centro-quirurgico', 'Centro Quirurgico', 'Quirofanos inteligentes equipados para procedimientos de alta complejidad.', '<path d="M14.5 2.5a2.5 2.5 0 0 0-5 0V6H8a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1.5V2.5z"></path><path d="M9 14h6"></path><path d="M12 11v6"></path>', 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('consulta-externa', 'Consulta Externa', 'Acceso a especialistas de todas las areas para diagnostico y seguimiento.', '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>', 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('emergencias-24-7', 'Emergencias 24/7', 'Atencion inmediata y especializada para urgencias medicas a cualquier hora.', '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>', 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('hospitalizacion', 'Hospitalizacion', 'Habitaciones comodas y monitoreo constante para una recuperacion segura.', '<path d="M2 22h20"></path><path d="M4 14v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"></path><path d="M4 14h16"></path><path d="M12 4v6"></path><path d="M10 6h4"></path>', 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('imagen', 'Diagnostico por Imagen', 'Resonancia, tomografia y radiologia digital de ultima generacion.', '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>', 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('laboratorio', 'Laboratorio Clinico', 'Analisis precisos y resultados rapidos para un diagnostico confiable.', '<path d="M10 2v6l-5 9a3 3 0 0 0 2.6 4.5h8.8A3 3 0 0 0 19 17l-5-9V2"></path><path d="M8 2h8"></path><path d="M7 14h10"></path>', 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16');

-- Volcando estructura para tabla clinica_base.specialties
CREATE TABLE IF NOT EXISTS `specialties` (
  `id` varchar(50) NOT NULL COMMENT 'ID basado en slug, ej: cardiologia',
  `name` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` text DEFAULT NULL COMMENT 'Puede ser cÃ³digo SVG o una ruta de archivo',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.specialties: ~8 rows (aproximadamente)
INSERT INTO `specialties` (`id`, `name`, `summary`, `description`, `icon`, `featured`, `active`, `created_at`, `updated_at`) VALUES
	('cardiologia', 'Cardiologia', 'Cuidado integral de su corazon con tecnologia avanzada.', 'Cuidado integral de su corazon con tecnologia avanzada para diagnostico y tratamiento.', '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>', 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('dermatologia', 'Dermatologia', 'Soluciones avanzadas para piel, cabello y unas.', 'Soluciones avanzadas para la salud de su piel, cabello y unas, con enfoque estetico y clinico.', '<path d="M12 12.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"></path><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"></path>', 0, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('ginecologia', 'Ginecologia', 'Atencion integral de la salud femenina.', 'Atencion integral de la salud femenina en todas las etapas de la vida.', '<circle cx="12" cy="8" r="5"></circle><path d="M12 13v8"></path><path d="M8 17h8"></path>', 0, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('neurologia', 'Neurologia', 'Diagnostico y tratamiento de enfermedades del sistema nervioso.', 'Diagnostico y tratamiento de enfermedades del sistema nervioso central y periferico.', '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>', 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('oncologia', 'Oncologia', 'Tratamientos de vanguardia y apoyo continuo contra el cancer.', 'Tratamientos de vanguardia y apoyo continuo en la lucha integral contra el cancer.', '<path d="M12 2a2 2 0 0 0-2 2v2H8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2h2a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-2V4a2 2 0 0 0-2-2h-2z"></path>', 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('pediatria', 'Pediatria', 'Cuidado infantil con dedicacion, prevencion y carino.', 'Cuidamos la salud de los mas pequenos con dedicacion, carino y un enfoque preventivo.', '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>', 1, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('psiquiatria', 'Psiquiatria', 'Cuidado de la salud mental con enfoque humano.', 'Cuidado de la salud mental con terapias modernas y un enfoque humano y confidencial.', '<circle cx="12" cy="12" r="10"></circle><path d="M9 10h.01"></path><path d="M15 10h.01"></path><path d="M8 15s1.5 2 4 2 4-2 4-2"></path>', 0, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16'),
	('traumatologia', 'Traumatologia', 'Especialistas en lesiones del aparato locomotor.', 'Especialistas en lesiones del aparato locomotor, desde fracturas hasta medicina deportiva.', '<path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3z"></path>', 0, 1, '2026-08-06 21:22:16', '2026-08-06 21:22:16');

-- Volcando estructura para tabla clinica_base.testimonials
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` varchar(50) NOT NULL,
  `author` varchar(255) NOT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `text` text NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.testimonials: ~4 rows (aproximadamente)
INSERT INTO `testimonials` (`id`, `author`, `detail`, `text`, `rating`, `avatar`, `featured`, `active`, `created_at`, `updated_at`) VALUES
	('comment1', 'Carlos Rodriguez', 'Paciente de Cardiologia', 'El nivel de profesionalismo y la calidad humana del equipo son excepcionales. Me senti seguro y bien atendido durante todo mi procedimiento.', 5, 'public/assets/avatar.png', 1, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('comment2', 'Ana Sofia Paredes', 'Madre de paciente de Pediatria', 'Llevar a mi hijo recien nacido fue la mejor decision. La paciencia y el conocimiento nos dieron mucha tranquilidad.', 5, 'public/assets/avatar.png', 1, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('comment3', 'Javier Mendoza', 'Paciente de Dermatologia', 'Despues de anos buscando una solucion para mi problema de piel, recibi un diagnostico preciso y un tratamiento efectivo.', 5, 'public/assets/avatar.png', 1, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17'),
	('comment4', 'Lucia Fernandez', 'Paciente de Diagnostico por Imagen', 'La tecnologia para diagnosticos por imagen es de otro nivel. Obtuve resultados rapidos y con una claridad impresionante.', 5, 'public/assets/avatar.png', 1, 1, '2026-08-06 21:22:17', '2026-08-06 21:22:17');

-- Volcando estructura para tabla clinica_base.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'AlmacenarÃ¡ el hash de la contraseÃ±a',
  `name` varchar(255) NOT NULL,
  `role` enum('ADMINISTRATOR','SECRETARIA','DOCTOR') NOT NULL,
  `doctor_id` varchar(50) DEFAULT NULL COMMENT 'FK a doctors.id si el rol es DOCTOR',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `users_doctor_id_foreign` (`doctor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla clinica_base.users: ~4 rows (aproximadamente)
INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `doctor_id`, `active`, `created_at`, `updated_at`) VALUES
	(1, 'admin', '$2y$10$2FUxu00xj3UqEWBVp2L/QOvkM.yqDeIJe7OtKEyz3rx2If/yF25la', 'Jhonny', 'ADMINISTRATOR', NULL, 1, '2026-08-06 22:30:18', '2026-08-06 22:31:51'),
	(2, 'secretaria', '$2y$10$4uvSTyML8gcd0gJ/FoJJs.wcncEzbVdN5GMxC7Cgi57THsD7P8NDG', 'Secretaria General', 'SECRETARIA', NULL, 1, '2026-08-17 16:29:04', '2026-08-18 03:20:31'),
	(3, 'dr.herrera', '$2y$10$LBsFSL8g0o5CrP0OdHFjQukk4JEAtpegk0mUa.r5KIOh/208Lu2Xa', 'Dr. Mateo Herrera', 'DOCTOR', 'doc3', 1, '2026-08-17 16:29:04', '2026-08-18 03:20:31'),
	(4, 'dr.vargas', '$2y$10$7F3Jcc3MqF2xoTNPl8lYPOTnI3sOllgmQEGoEz/aU70aeLBWiDDyy', 'Dr. Alejandro Vargas', 'DOCTOR', 'doc1', 1, '2026-08-17 16:29:04', '2026-08-18 03:20:31');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

