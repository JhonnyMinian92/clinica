<?php

require_once __DIR__ . '/db.php';

const CLINICA_SESSION_TTL_SECONDS = 28800;

const CLINICA_GESTION_DATA_VERSION = 1;
const CLINICA_APPOINTMENTS_SEED_VERSION = 1;

// This function is now only used by the seeder script as a fallback.
function clinica_get_seed_data(): array
{
  return [
    'version' => CLINICA_GESTION_DATA_VERSION,
    'clinic' => [
      'name' => 'Clinica Estandar',
      'legal_name' => 'Clinica Estandar',
      'logo_label' => 'Clinica Estandar',
      'logo_image' => '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="logo-svg" aria-hidden="true"><path d="M12 5C12 5 9 2 6 2C3 2 2 4 2 6C2 9 5 12 12 19C19 12 22 9 22 6C22 4 21 2 18 2C15 2 12 5 12 5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" opacity="0.4" /><path d="M8 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>',
      'tagline' => 'Medicina de vanguardia con un enfoque humano y personalizado.',
      'description' => 'Centro medico privado con especialistas, tecnologia avanzada y atencion cercana.',
      'meta_title' => 'Clinica Estandar | Medicina de Vanguardia y Cuidado Humano',
      'meta_description' => 'Clinica Estandar: atencion medica especializada, tecnologia de punta y profesionales comprometidos con su bienestar.',
      'canonical_url' => 'https://www.clinicaestandar.com',
      'hero_title' => 'Medicina de Vanguardia, Cuidado Humano',
      'hero_subtitle' => 'Combinamos alta tecnologia con especialistas comprometidos con su salud y bienestar.',
      'hero_image' => 'public/assets/banner.jpg',
      'hero_mobile_image' => 'public/assets/banner_mobil.jpg',
      'about_title' => 'Su Salud, Nuestra Prioridad',
      'about_subtitle' => 'Descubra por que somos la eleccion de confianza para miles de pacientes.',
      'services_title' => 'Nuestros Servicios',
      'services_subtitle' => 'Ofrecemos una gama completa de servicios medicos para cuidar de usted en cada etapa.',
      'specialties_title' => 'Nuestras Especialidades',
      'specialties_subtitle' => 'Cobertura medica integral para todas sus necesidades de salud.',
      'doctors_title' => 'Conozca a Nuestro Equipo',
      'doctors_subtitle' => 'Profesionales que combinan conocimiento, experiencia y vocacion.',
      'comments_title' => 'La Voz de Nuestros Pacientes',
      'comments_subtitle' => 'Historias reales de quienes han confiado su salud en nuestras manos.',
      'reservation_title' => 'Agendar una Cita',
      'reservation_subtitle' => 'Siga los pasos para encontrar un horario conveniente para usted.',
      'reservation_summary_note' => 'Recibira una confirmacion por correo electronico. Por favor, llegue 15 minutos antes de su cita.',
      'reservation_success_title' => 'Cita Agendada',
      'reservation_success_text' => 'Su cita ha sido confirmada exitosamente. Recibira un correo electronico con todos los detalles. Gracias por confiar en {clinic}.',
      'reservation_back_home_label' => 'Volver al Inicio',
      'reservation_next_label' => 'Siguiente',
      'reservation_confirm_label' => 'Confirmar Cita',
      'reservation_previous_label' => 'Anterior',
      'address' => 'Buenavista y Tarqui, Machala, Ecuador',
      'email' => 'info@clinicaestandar.com',
      'phone_display' => '(07) 293-3888',
      'phone_href' => '+59372933888',
      'emergency_phone' => '911',
      'whatsapp_url' => 'https://wa.me/593999999999',
      'facebook_url' => 'https://facebook.com/clinicaestandar',
      'instagram_url' => 'https://instagram.com/clinicaestandar',
      'stats' => [
        ['label' => 'Pacientes Atendidos', 'value' => 50000, 'suffix' => '+'],
        ['label' => 'Anos de Experiencia', 'value' => 25, 'suffix' => '+'],
        ['label' => 'Especialistas Certificados', 'value' => 100, 'suffix' => '+'],
        ['label' => 'Tasa de Satisfaccion', 'value' => 98, 'suffix' => '%'],
      ],
      'about_cards' => [
        [
          'title' => 'Tecnologia de Punta',
          'text' => 'Equipos de ultima generacion para diagnosticos precisos y tratamientos efectivos.',
          'icon' => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>',
        ],
        [
          'title' => 'Especialistas de Elite',
          'text' => 'Un equipo medico de renombre, con amplia experiencia y reconocimiento internacional.',
          'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        ],
        [
          'title' => 'Atencion Humana',
          'text' => 'Cuidado calido y personalizado, porque detras de cada paciente hay una historia.',
          'icon' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>',
        ],
      ],
    ],
    'specialties' => [
      ['id' => 'cardiologia', 'name' => 'Cardiologia', 'summary' => 'Cuidado integral de su corazon con tecnologia avanzada.', 'description' => 'Cuidado integral de su corazon con tecnologia avanzada para diagnostico y tratamiento.', 'icon' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>', 'featured' => true, 'active' => true],
      ['id' => 'neurologia', 'name' => 'Neurologia', 'summary' => 'Diagnostico y tratamiento de enfermedades del sistema nervioso.', 'description' => 'Diagnostico y tratamiento de enfermedades del sistema nervioso central y periferico.', 'icon' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>', 'featured' => true, 'active' => true],
      ['id' => 'pediatria', 'name' => 'Pediatria', 'summary' => 'Cuidado infantil con dedicacion, prevencion y carino.', 'description' => 'Cuidamos la salud de los mas pequenos con dedicacion, carino y un enfoque preventivo.', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>', 'featured' => true, 'active' => true],
      ['id' => 'oncologia', 'name' => 'Oncologia', 'summary' => 'Tratamientos de vanguardia y apoyo continuo contra el cancer.', 'description' => 'Tratamientos de vanguardia y apoyo continuo en la lucha integral contra el cancer.', 'icon' => '<path d="M12 2a2 2 0 0 0-2 2v2H8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2h2a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-2V4a2 2 0 0 0-2-2h-2z"></path>', 'featured' => true, 'active' => true],
      ['id' => 'dermatologia', 'name' => 'Dermatologia', 'summary' => 'Soluciones avanzadas para piel, cabello y unas.', 'description' => 'Soluciones avanzadas para la salud de su piel, cabello y unas, con enfoque estetico y clinico.', 'icon' => '<path d="M12 12.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"></path><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"></path>', 'featured' => false, 'active' => true],
      ['id' => 'ginecologia', 'name' => 'Ginecologia', 'summary' => 'Atencion integral de la salud femenina.', 'description' => 'Atencion integral de la salud femenina en todas las etapas de la vida.', 'icon' => '<circle cx="12" cy="8" r="5"></circle><path d="M12 13v8"></path><path d="M8 17h8"></path>', 'featured' => false, 'active' => true],
      ['id' => 'traumatologia', 'name' => 'Traumatologia', 'summary' => 'Especialistas en lesiones del aparato locomotor.', 'description' => 'Especialistas en lesiones del aparato locomotor, desde fracturas hasta medicina deportiva.', 'icon' => '<path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3z"></path>', 'featured' => false, 'active' => true],
      ['id' => 'psiquiatria', 'name' => 'Psiquiatria', 'summary' => 'Cuidado de la salud mental con enfoque humano.', 'description' => 'Cuidado de la salud mental con terapias modernas y un enfoque humano y confidencial.', 'icon' => '<circle cx="12" cy="12" r="10"></circle><path d="M9 10h.01"></path><path d="M15 10h.01"></path><path d="M8 15s1.5 2 4 2 4-2 4-2"></path>', 'featured' => false, 'active' => true],
    ],
    'services' => [
      ['id' => 'consulta-externa', 'title' => 'Consulta Externa', 'description' => 'Acceso a especialistas de todas las areas para diagnostico y seguimiento.', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>', 'featured' => true, 'active' => true],
      ['id' => 'emergencias-24-7', 'title' => 'Emergencias 24/7', 'description' => 'Atencion inmediata y especializada para urgencias medicas a cualquier hora.', 'icon' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>', 'featured' => true, 'active' => true],
      ['id' => 'hospitalizacion', 'title' => 'Hospitalizacion', 'description' => 'Habitaciones comodas y monitoreo constante para una recuperacion segura.', 'icon' => '<path d="M2 22h20"></path><path d="M4 14v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"></path><path d="M4 14h16"></path><path d="M12 4v6"></path><path d="M10 6h4"></path>', 'featured' => true, 'active' => true],
      ['id' => 'centro-quirurgico', 'title' => 'Centro Quirurgico', 'description' => 'Quirofanos inteligentes equipados para procedimientos de alta complejidad.', 'icon' => '<path d="M14.5 2.5a2.5 2.5 0 0 0-5 0V6H8a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1.5V2.5z"></path><path d="M9 14h6"></path><path d="M12 11v6"></path>', 'featured' => true, 'active' => true],
      ['id' => 'imagen', 'title' => 'Diagnostico por Imagen', 'description' => 'Resonancia, tomografia y radiologia digital de ultima generacion.', 'icon' => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>', 'featured' => true, 'active' => true],
      ['id' => 'laboratorio', 'title' => 'Laboratorio Clinico', 'description' => 'Analisis precisos y resultados rapidos para un diagnostico confiable.', 'icon' => '<path d="M10 2v6l-5 9a3 3 0 0 0 2.6 4.5h8.8A3 3 0 0 0 19 17l-5-9V2"></path><path d="M8 2h8"></path><path d="M7 14h10"></path>', 'featured' => true, 'active' => true],
    ],
    'doctors' => [
      ['id' => 'doc1', 'name' => 'Dr. Alejandro Vargas', 'photo' => '/clinica/public/assets/doctor_avatar.jpg', 'specialty_id' => 'cardiologia', 'sub_specialty' => 'Cardiologia Intervencionista', 'experience_years' => 15, 'university' => 'Universidad de Buenos Aires', 'recognitions' => ['Miembro Titular de la SAC'], 'languages' => ['Espanol', 'Ingles'], 'rating' => 5, 'patients' => 3200, 'featured' => true, 'active' => true],
      ['id' => 'doc2', 'name' => 'Dra. Sofia Castillo', 'photo' => '/clinica/public/assets/doctora_avatar.png', 'specialty_id' => 'pediatria', 'sub_specialty' => 'Neonatologia', 'experience_years' => 12, 'university' => 'Universidad Nacional de Colombia', 'recognitions' => ['Certificada por la SCP'], 'languages' => ['Espanol', 'Portugues'], 'rating' => 5, 'patients' => 2800, 'featured' => true, 'active' => true],
      ['id' => 'doc3', 'name' => 'Dr. Mateo Herrera', 'photo' => '/clinica/public/assets/doctor_avatar.jpg', 'specialty_id' => 'neurologia', 'sub_specialty' => 'Neurofisiologia Clinica', 'experience_years' => 18, 'university' => 'Universidad de Chile', 'recognitions' => ['Fellowship en Clinica Mayo'], 'languages' => ['Espanol', 'Ingles'], 'rating' => 5, 'patients' => 4100, 'featured' => true, 'active' => true],
      ['id' => 'doc4', 'name' => 'Dra. Valentina Morales', 'photo' => '/clinica/public/assets/doctora_avatar.png', 'specialty_id' => 'dermatologia', 'sub_specialty' => 'Dermatologia Cosmetica', 'experience_years' => 10, 'university' => 'Universidad Autonoma de Mexico', 'recognitions' => ['Miembro de la AAD'], 'languages' => ['Espanol'], 'rating' => 4, 'patients' => 3500, 'featured' => true, 'active' => true],
      ['id' => 'doc5', 'name' => 'Dr. Lucas Jimenez', 'photo' => '/clinica/public/assets/doctor_avatar.jpg', 'specialty_id' => 'traumatologia', 'sub_specialty' => 'Cirugia de Rodilla y Hombro', 'experience_years' => 20, 'university' => 'Universidad Complutense de Madrid', 'recognitions' => [], 'languages' => ['Espanol', 'Ingles'], 'rating' => 5, 'patients' => 5000, 'featured' => false, 'active' => true],
      ['id' => 'doc6', 'name' => 'Dra. Camila Rios', 'photo' => '/clinica/public/assets/doctora_avatar.png', 'specialty_id' => 'ginecologia', 'sub_specialty' => 'Medicina Reproductiva', 'experience_years' => 14, 'university' => 'Universidad de Sao Paulo', 'recognitions' => ['Certificacion en Laparoscopia'], 'languages' => ['Espanol', 'Portugues', 'Ingles'], 'rating' => 5, 'patients' => 3900, 'featured' => false, 'active' => true],
      ['id' => 'doc7', 'name' => 'Dr. Sebastian Pena', 'photo' => '/clinica/public/assets/doctor_avatar.jpg', 'specialty_id' => 'oncologia', 'sub_specialty' => 'Oncologia Toracica', 'experience_years' => 22, 'university' => 'Johns Hopkins University', 'recognitions' => ['Premio Nacional de Medicina'], 'languages' => ['Espanol', 'Ingles'], 'rating' => 5, 'patients' => 2100, 'featured' => false, 'active' => true],
      ['id' => 'doc8', 'name' => 'Dra. Isabella Gomez', 'photo' => '/clinica/public/assets/doctora_avatar.png', 'specialty_id' => 'psiquiatria', 'sub_specialty' => 'Psicoterapia', 'experience_years' => 9, 'university' => 'Universidad de Barcelona', 'recognitions' => [], 'languages' => ['Espanol', 'Catalan'], 'rating' => 4, 'patients' => 1500, 'featured' => false, 'active' => true],
    ],
    'comments' => [
      ['id' => 'comment1', 'author' => 'Carlos Rodriguez', 'detail' => 'Paciente de Cardiologia', 'text' => 'El nivel de profesionalismo y la calidad humana del equipo son excepcionales. Me senti seguro y bien atendido durante todo mi procedimiento.', 'rating' => 5, 'avatar' => 'public/assets/avatar.png', 'featured' => true, 'active' => true],
      ['id' => 'comment2', 'author' => 'Ana Sofia Paredes', 'detail' => 'Madre de paciente de Pediatria', 'text' => 'Llevar a mi hijo recien nacido fue la mejor decision. La paciencia y el conocimiento nos dieron mucha tranquilidad.', 'rating' => 5, 'avatar' => 'public/assets/avatar.png', 'featured' => true, 'active' => true],
      ['id' => 'comment3', 'author' => 'Javier Mendoza', 'detail' => 'Paciente de Dermatologia', 'text' => 'Despues de anos buscando una solucion para mi problema de piel, recibi un diagnostico preciso y un tratamiento efectivo.', 'rating' => 5, 'avatar' => 'public/assets/avatar.png', 'featured' => true, 'active' => true],
      ['id' => 'comment4', 'author' => 'Lucia Fernandez', 'detail' => 'Paciente de Diagnostico por Imagen', 'text' => 'La tecnologia para diagnosticos por imagen es de otro nivel. Obtuve resultados rapidos y con una claridad impresionante.', 'rating' => 5, 'avatar' => 'public/assets/avatar.png', 'featured' => true, 'active' => true],
    ],
    'users' => [
      ['id' => 'admin1', 'username' => 'admin', 'name' => 'Valeria Andrade', 'role' => 'ADMINISTRATOR', 'label' => 'Administracion', 'password' => 'password123', 'doctor_id' => '', 'active' => true],
      ['id' => 'sec1', 'username' => 'secretaria', 'name' => 'Mariana Rios', 'role' => 'SECRETARIA', 'label' => 'Secretaria', 'password' => 'secretaria123', 'doctor_id' => '', 'active' => true],
      ['id' => 'doc3', 'username' => 'dr.herrera', 'name' => 'Dr. Mateo Herrera', 'role' => 'DOCTOR', 'label' => 'Doctor', 'password' => 'doctor123', 'doctor_id' => 'doc3', 'active' => true],
    ],
    'schedules' => [
      ['id' => 'sch1', 'doctor_id' => 'doc1', 'weekday' => 1, 'start_time' => '07:00', 'end_time' => '13:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch2', 'doctor_id' => 'doc1', 'weekday' => 3, 'start_time' => '07:00', 'end_time' => '13:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch3', 'doctor_id' => 'doc1', 'weekday' => 5, 'start_time' => '13:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch4', 'doctor_id' => 'doc2', 'weekday' => 2, 'start_time' => '13:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch5', 'doctor_id' => 'doc2', 'weekday' => 4, 'start_time' => '13:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch6', 'doctor_id' => 'doc2', 'weekday' => 6, 'start_time' => '07:00', 'end_time' => '13:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch7', 'doctor_id' => 'doc3', 'weekday' => 1, 'start_time' => '07:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch8', 'doctor_id' => 'doc3', 'weekday' => 2, 'start_time' => '07:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch9', 'doctor_id' => 'doc3', 'weekday' => 3, 'start_time' => '07:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch10', 'doctor_id' => 'doc3', 'weekday' => 4, 'start_time' => '07:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch11', 'doctor_id' => 'doc3', 'weekday' => 5, 'start_time' => '07:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch12', 'doctor_id' => 'doc4', 'weekday' => 1, 'start_time' => '13:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch13', 'doctor_id' => 'doc4', 'weekday' => 3, 'start_time' => '13:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch14', 'doctor_id' => 'doc4', 'weekday' => 5, 'start_time' => '13:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch15', 'doctor_id' => 'doc5', 'weekday' => 2, 'start_time' => '07:00', 'end_time' => '13:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch16', 'doctor_id' => 'doc5', 'weekday' => 4, 'start_time' => '07:00', 'end_time' => '13:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch17', 'doctor_id' => 'doc6', 'weekday' => 1, 'start_time' => '13:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch18', 'doctor_id' => 'doc6', 'weekday' => 3, 'start_time' => '07:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch19', 'doctor_id' => 'doc6', 'weekday' => 5, 'start_time' => '07:00', 'end_time' => '13:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch20', 'doctor_id' => 'doc7', 'weekday' => 2, 'start_time' => '13:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch21', 'doctor_id' => 'doc7', 'weekday' => 4, 'start_time' => '13:00', 'end_time' => '22:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch22', 'doctor_id' => 'doc8', 'weekday' => 1, 'start_time' => '07:00', 'end_time' => '13:00', 'slot_minutes' => 15, 'active' => true],
      ['id' => 'sch23', 'doctor_id' => 'doc8', 'weekday' => 4, 'start_time' => '07:00', 'end_time' => '13:00', 'slot_minutes' => 15, 'active' => true],
    ],
  ];
}

function clinica_is_active_record(array $record): bool
{
  return (bool) ($record['active'] ?? true);
}

function clinica_get_clinic_settings(): array
{
  $pdo = clinica_get_db();
  $stmt = $pdo->query("SELECT * FROM clinics ORDER BY id ASC LIMIT 1");
  $clinic = $stmt->fetch();

  if (!$clinic) {
    return []; // Or throw an exception
  }

  // Decode JSON fields
  $clinic['stats'] = json_decode($clinic['stats'] ?? '[]', true);
  $clinic['about_cards'] = json_decode($clinic['about_cards'] ?? '[]', true);

  return $clinic;
}

function clinica_get_specialties(bool $activeOnly = false): array
{
  $pdo = clinica_get_db();
  $sql = "SELECT * FROM specialties";
  if ($activeOnly) {
    $sql .= " WHERE active = 1";
  }
  $sql .= " ORDER BY name ASC";
  $stmt = $pdo->query($sql);
  return $stmt->fetchAll();
}

function clinica_get_services(bool $activeOnly = false): array
{
  $pdo = clinica_get_db();
  $sql = "SELECT * FROM services";
  if ($activeOnly) {
    $sql .= " WHERE active = 1";
  }
  $stmt = $pdo->query($sql);
  return $stmt->fetchAll();
}

function clinica_get_comments(bool $activeOnly = false): array
{
  $pdo = clinica_get_db();
  $sql = "SELECT * FROM testimonials"; // Assuming 'comments' are stored in 'testimonials' table
  if ($activeOnly) {
    $sql .= " WHERE active = 1";
  }
  $stmt = $pdo->query($sql);
  return $stmt->fetchAll();
}

function clinica_get_doctors(bool $activeOnly = false): array
{
  $pdo = clinica_get_db();
  $sql = "SELECT * FROM doctors";
  if ($activeOnly) {
    $sql .= " WHERE active = 1";
  }
  $sql .= " ORDER BY name ASC";
  $stmt = $pdo->query($sql);
  $doctors = $stmt->fetchAll();

  // Decode JSON fields
  return array_map(function ($doctor) {
    $doctor['languages'] = json_decode($doctor['languages'] ?? '[]', true);
    $doctor['recognitions'] = json_decode($doctor['recognitions'] ?? '[]', true);
    return $doctor;
  }, $doctors);
}

function clinica_get_schedules(bool $activeOnly = false): array
{
  $pdo = clinica_get_db();
  $sql = "SELECT * FROM doctor_schedules";
  if ($activeOnly) {
    $sql .= " WHERE active = 1";
  }
  $sql .= " ORDER BY doctor_id, date, weekday, start_time";
  $stmt = $pdo->query($sql);
  return $stmt->fetchAll();
}

function clinica_specialty_name_by_id(string $specialtyId): string
{
  $pdo = clinica_get_db();
  $stmt = $pdo->prepare("SELECT name FROM specialties WHERE id = ?");
  $stmt->execute([$specialtyId]);
  $result = $stmt->fetchColumn();
  return $result ?: $specialtyId;
}

function clinica_find_doctor(string $doctorId): ?array
{
  $pdo = clinica_get_db();
  $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
  $stmt->execute([$doctorId]);
  $doctor = $stmt->fetch();
  if ($doctor) {
    $doctor['languages'] = json_decode($doctor['languages'] ?? '[]', true);
    $doctor['recognitions'] = json_decode($doctor['recognitions'] ?? '[]', true);
  }
  return $doctor ?: null;
}

function clinica_schedule_shift_label(string $startTime, string $endTime): string
{
  if ($startTime <= '07:00' && $endTime >= '22:00') {
    return 'Completo';
  }

  if ($endTime <= '13:00') {
    return 'Mañana';
  }

  return 'Tarde';
}

function clinica_schedule_map_for_doctor(string $doctorId): array
{
  $map = [];

  foreach (clinica_get_schedules(true) as $schedule) {
    if (($schedule['doctor_id'] ?? '') !== $doctorId) {
      continue;
    }

    $weekday = (string) ((int) ($schedule['weekday'] ?? 0));
    if ($weekday === '0') {
      continue;
    }

    $label = clinica_schedule_shift_label((string) ($schedule['start_time'] ?? ''), (string) ($schedule['end_time'] ?? ''));
    $date = (string) ($schedule['date'] ?? '');

    if ($date !== '') {
      $map[$date] = $label;
      continue;
    }

    $map[$weekday] = $label;
  }

  return $map;
}

function clinica_schedule_rules_for_doctor(string $doctorId): array
{
  $rules = [];

  foreach (clinica_get_schedules(true) as $schedule) {
    if (($schedule['doctor_id'] ?? '') !== $doctorId) {
      continue;
    }

    $weekday = (string) ((int) ($schedule['weekday'] ?? 0));
    if ($weekday === '0') {
      continue;
    }

    $rule = [
      'start_time' => (string) ($schedule['start_time'] ?? ''),
      'end_time' => (string) ($schedule['end_time'] ?? ''),
      'slot_minutes' => max(5, (int) ($schedule['slot_minutes'] ?? 15)),
    ];

    $date = (string) ($schedule['date'] ?? '');
    if ($date !== '') {
      $rule['date'] = $date;
      $rules[$date][] = $rule;
      continue;
    }

    $rules[$weekday][] = $rule;
  }

  return $rules;
}

function clinica_get_doctor_photo_url(array $doctor): string
{
  $photo = trim((string) ($doctor['photo'] ?? ''));
  // Un campo de foto vacío, nulo o con el avatar genérico se considera "sin foto personalizada".
  if ($photo !== '' && $photo !== '/clinica/public/assets/avatar.png') {
    return $photo;
  }

  $name = trim((string) ($doctor['name'] ?? ''));
  if (str_starts_with($name, 'Dra.')) {
    return '/clinica/public/assets/doctora_avatar.png';
  }

  return '/clinica/public/assets/doctor_avatar.jpg';
}

function clinica_render_logo_element(array $clinic_settings): void
{
  $clinic_name = $clinic_settings['logo_label'] ?? $clinic_settings['name'] ?? 'Clinica Estandar';
  $logo_image = $clinic_settings['logo_image'] ?? '';

  if (trim($logo_image) !== '') {
    if (str_starts_with(trim($logo_image), '<svg')) {
      echo $logo_image; // It's SVG code, render directly
    } else {
      echo '<img src="' . htmlspecialchars($logo_image) . '" alt="' . htmlspecialchars($clinic_name) . ' logo" class="logo-img">';
    }
  } else {
    // Fallback to text if no image is provided
    echo '<span class="clinic-name-header">' . htmlspecialchars($clinic_name) . '</span>';
  }
}

function clinica_public_doctor(array $doctor): array
{
  $specialtyId = (string) ($doctor['specialty_id'] ?? '');

  return [
    'id' => (string) ($doctor['id'] ?? ''),
    'nombre' => (string) ($doctor['name'] ?? ''),
    'foto' => clinica_get_doctor_photo_url($doctor),
    'especialidad' => clinica_specialty_name_by_id($specialtyId),
    'especialidad_id' => $specialtyId,
    'subespecialidad' => (string) ($doctor['sub_specialty'] ?? ''),
    'experiencia_anos' => (int) ($doctor['experience_years'] ?? 0),
    'universidad' => (string) ($doctor['university'] ?? ''),
    'reconocimientos' => array_values($doctor['recognitions'] ?? []),
    'idiomas' => array_values($doctor['languages'] ?? []),
    'valoracion' => (int) ($doctor['rating'] ?? 0),
    'pacientes' => (int) ($doctor['patients'] ?? 0),
    'horario_semanal' => clinica_schedule_map_for_doctor((string) ($doctor['id'] ?? '')),
    'horarios_detallados' => clinica_schedule_rules_for_doctor((string) ($doctor['id'] ?? '')),
    'featured' => (bool) ($doctor['featured'] ?? false),
    'active' => (bool) ($doctor['active'] ?? true),
  ];
}

function clinica_public_doctors_for_catalog(): array
{
  return array_map('clinica_public_doctor', clinica_get_doctors(true));
}

function clinica_public_doctors_grouped_by_specialty(): array
{
  $grouped = [];

  foreach (clinica_get_doctors(true) as $doctor) {
    $specialtyId = (string) ($doctor['specialty_id'] ?? '');
    if ($specialtyId === '') continue;
    $publicDoctor = clinica_public_doctor($doctor);
    $grouped[$specialtyId][] = [
      'id' => $publicDoctor['id'],
      'nombre' => $publicDoctor['nombre'],
      'foto' => $publicDoctor['foto'],
      'valoracion' => $publicDoctor['valoracion'],
      'horario_semanal' => $publicDoctor['horario_semanal'],
      'horarios_detallados' => $publicDoctor['horarios_detallados'],
    ];
  }

  return $grouped;
}

function clinica_public_specialties_for_catalog(bool $featuredOnly = false): array
{
  $specialties = clinica_get_specialties(true);

  if ($featuredOnly) {
    $specialties = array_values(array_filter($specialties, static fn(array $item): bool => (bool) ($item['featured'] ?? false)));
  }

  return array_map(static fn(array $specialty): array => [
    'id' => (string) ($specialty['id'] ?? ''),
    'titulo' => (string) ($specialty['name'] ?? ''),
    'descripcion' => (string) ($specialty['description'] ?? $specialty['summary'] ?? ''),
    'resumen' => (string) ($specialty['summary'] ?? ''),
    'icono' => (string) ($specialty['icon'] ?? ''),
  ], $specialties);
}

function clinica_public_services_for_home(): array
{
  return array_map(static fn(array $service): array => [
    'id' => (string) ($service['id'] ?? ''),
    'titulo' => (string) ($service['title'] ?? ''),
    'descripcion' => (string) ($service['description'] ?? ''),
    'icono' => (string) ($service['icon'] ?? ''),
  ], clinica_get_services(true));
}

function clinica_public_comments_for_home(): array
{
  $comments = array_values(array_filter(clinica_get_comments(true), static fn(array $item): bool => (bool) ($item['featured'] ?? true)));

  return array_map(static fn(array $comment): array => [
    'texto' => (string) ($comment['text'] ?? ''),
    'autor' => (string) ($comment['author'] ?? ''),
    'detalle' => (string) ($comment['detail'] ?? ''),
    'valoracion' => max(0, min(5, (int) ($comment['rating'] ?? 0))),
    'avatar' => (string) ($comment['avatar'] ?? 'public/assets/avatar.png'),
  ], $comments);
}

function clinica_public_doctors_for_home(): array
{
  $doctors = array_values(array_filter(clinica_get_doctors(true), static fn(array $doctor): bool => (bool) ($doctor['featured'] ?? false)));

  return array_map(static function (array $doctor): array {
    $publicDoctor = clinica_public_doctor($doctor);
    return [
      'id' => $publicDoctor['id'],
      'nombre' => $publicDoctor['nombre'],
      'especialidad' => trim($publicDoctor['especialidad'] . ' ' . $publicDoctor['subespecialidad']),
      'experiencia' => $publicDoctor['experiencia_anos'] . '+ anos de experiencia',
      'foto' => ltrim(str_replace('/clinica/', '', $publicDoctor['foto']), '/'),
    ];
  }, $doctors);
}

function clinica_public_booked_slots(): array
{
  $pdo = clinica_get_db();
  $stmt = $pdo->query("SELECT doctor_id, date, time FROM appointments WHERE status != 'rechazada'");
  $results = $stmt->fetchAll();

  $booked = [];
  foreach ($results as $row) {
    if (!isset($booked[$row['doctor_id']])) {
      $booked[$row['doctor_id']] = [];
    }
    $booked[$row['doctor_id']][$row['date']][] = substr($row['time'], 0, 5);
  }
  return $booked;
}

function clinica_demo_users(): array
{
  $pdo = clinica_get_db();
  $stmt = $pdo->query("SELECT id, username, password, name, role, doctor_id FROM users WHERE active = 1");
  $users = $stmt->fetchAll();

  $mapped = [];

  foreach ($users as $user) {
    $username = strtolower(trim((string) ($user['username'] ?? '')));
    if ($username === '') {
      continue;
    }

    // If the user is a doctor, their session ID should be their doctor_id.
    // This is crucial for the dashboard logic to find their appointments.
    $sessionUserId = ($user['role'] === 'DOCTOR' && !empty($user['doctor_id']))
      ? (string) $user['doctor_id']
      : (string) $user['id'];

    $mapped[$username] = [
      'id' => $sessionUserId,
      'username' => $username,
      'name' => (string) ($user['name'] ?? $username),
      'role' => (string) ($user['role'] ?? ''),
      'label' => (string) ($user['role'] ?? ''), // 'label' is not in the DB, using 'role' as fallback.
      // Intentionally do NOT expose password or password hash to the public mapping.
    ];
  }

  return $mapped;
}

function clinica_dashboard_roles(): array
{
  return ['DOCTOR', 'ADMINISTRATOR', 'SECRETARIA'];
}

function clinica_can_see_all_appointments(string $role): bool
{
  return in_array($role, ['ADMINISTRATOR', 'SECRETARIA'], true);
}

function clinica_can_see_past_appointments(string $role): bool
{
  return clinica_can_see_all_appointments($role);
}

function clinica_can_access_management(string $role): bool
{
  return $role === 'ADMINISTRATOR';
}

function clinica_permissions_for_role(string $role): array
{
  return [
    'dashboard' => in_array($role, clinica_dashboard_roles(), true),
    'management' => clinica_can_access_management($role),
    'all_appointments' => clinica_can_see_all_appointments($role),
    'past_appointments' => clinica_can_see_past_appointments($role),
    'own_current_future_appointments' => $role === 'DOCTOR',
  ];
}

function clinica_default_route_for_role(string $role): string
{
  return in_array($role, clinica_dashboard_roles(), true)
    ? '/clinica/pages/dashboard/'
    : '/clinica/pages/login/';
}

function clinica_normalize_internal_redirect(?string $redirect): string
{
  $redirect = trim((string) $redirect);
  if ($redirect === '') {
    return '';
  }

  $parts = parse_url($redirect);
  if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
    return '';
  }

  $path = (string) ($parts['path'] ?? '');
  if (!str_starts_with($path, '/clinica/')) {
    return '';
  }

  $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
  $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#' . $parts['fragment'] : '';

  return $path . $query . $fragment;
}

function clinica_role_can_open_redirect(string $redirect, string $role): bool
{
  $path = (string) (parse_url($redirect, PHP_URL_PATH) ?: '');

  if (!in_array($role, clinica_dashboard_roles(), true)) {
    return false;
  }

  if (str_starts_with($path, '/clinica/pages/gestion/')) {
    return clinica_can_access_management($role);
  }

  if (str_starts_with($path, '/clinica/pages/login/') || str_starts_with($path, '/clinica/api/')) {
    return false;
  }

  return str_starts_with($path, '/clinica/');
}

function clinica_resolve_login_redirect(?string $requestedRedirect, array $user): string
{
  $role = (string) ($user['role'] ?? '');
  $fallback = clinica_default_route_for_role($role);
  $target = clinica_normalize_internal_redirect($requestedRedirect);

  if ($target !== '' && clinica_role_can_open_redirect($target, $role)) {
    return $target;
  }

  return $fallback;
}

function clinica_statuses(): array
{
  return ['pendiente', 'atendida', 'no-presentado', 'rechazada'];
}

function clinica_seed_appointments(): array
{
  $today = date('Y-m-d');
  $yesterday = date('Y-m-d', strtotime('-1 day'));
  $tomorrow = date('Y-m-d', strtotime('+1 day'));
  $nextWeek = date('Y-m-d', strtotime('+5 days'));

  return [
    ['id' => 'CITA001', 'fecha' => $today, 'hora' => '09:00', 'nombre_paciente' => 'Ana Torres', 'email_paciente' => 'ana.torres@example.com', 'telefono_paciente' => '0991112222', 'motivo_consulta' => 'Chequeo anual', 'estado' => 'pendiente', 'doctor_id' => 'doc1', 'doctor_nombre' => 'Dr. Alejandro Vargas', 'especialidad' => 'Cardiologia'],
    ['id' => 'CITA002', 'fecha' => $today, 'hora' => '09:15', 'nombre_paciente' => 'Luis Mendez', 'email_paciente' => 'luis.mendez@example.com', 'telefono_paciente' => '0992223333', 'motivo_consulta' => 'Dolor de cabeza persistente', 'estado' => 'pendiente', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera', 'especialidad' => 'Neurologia'],
    ['id' => 'CITA003', 'fecha' => $today, 'hora' => '09:30', 'nombre_paciente' => 'Carla Solano', 'email_paciente' => 'carla.solano@example.com', 'telefono_paciente' => '0993334444', 'motivo_consulta' => 'Resultados de analisis', 'estado' => 'atendida', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera', 'especialidad' => 'Neurologia'],
    ['id' => 'CITA004', 'fecha' => $today, 'hora' => '10:00', 'nombre_paciente' => 'Pedro Jimenez', 'email_paciente' => 'pedro.jimenez@example.com', 'telefono_paciente' => '0994445555', 'motivo_consulta' => 'Control post-operatorio', 'estado' => 'pendiente', 'doctor_id' => 'doc5', 'doctor_nombre' => 'Dr. Lucas Jimenez', 'especialidad' => 'Traumatologia'],
    ['id' => 'CITA005', 'fecha' => $today, 'hora' => '10:30', 'nombre_paciente' => 'Sofia Castro', 'email_paciente' => 'sofia.castro@example.com', 'telefono_paciente' => '0995556666', 'motivo_consulta' => 'Consulta de seguimiento', 'estado' => 'pendiente', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera', 'especialidad' => 'Neurologia'],
    ['id' => 'CITA006', 'fecha' => $today, 'hora' => '11:00', 'nombre_paciente' => 'Elena Rojas', 'email_paciente' => 'elena.rojas@example.com', 'telefono_paciente' => '0996667777', 'motivo_consulta' => 'No especificado', 'estado' => 'no-presentado', 'doctor_id' => 'doc1', 'doctor_nombre' => 'Dr. Alejandro Vargas', 'especialidad' => 'Cardiologia'],
    ['id' => 'CITA007', 'fecha' => $yesterday, 'hora' => '14:00', 'nombre_paciente' => 'Marco Vera', 'email_paciente' => 'marco.vera@example.com', 'telefono_paciente' => '0997778888', 'motivo_consulta' => 'Revision dermatologica', 'estado' => 'rechazada', 'doctor_id' => 'doc4', 'doctor_nombre' => 'Dra. Valentina Morales', 'especialidad' => 'Dermatologia'],
    ['id' => 'CITA008', 'fecha' => $tomorrow, 'hora' => '14:30', 'nombre_paciente' => 'Julia Navarro', 'email_paciente' => 'julia.navarro@example.com', 'telefono_paciente' => '0998889999', 'motivo_consulta' => 'Vacunacion infantil', 'estado' => 'pendiente', 'doctor_id' => 'doc2', 'doctor_nombre' => 'Dra. Sofia Castillo', 'especialidad' => 'Pediatria'],
    ['id' => 'CITA009', 'fecha' => $nextWeek, 'hora' => '15:00', 'nombre_paciente' => 'Ricardo Ponce', 'email_paciente' => 'ricardo.ponce@example.com', 'telefono_paciente' => '0999990000', 'motivo_consulta' => 'Terapia', 'estado' => 'pendiente', 'doctor_id' => 'doc8', 'doctor_nombre' => 'Dra. Isabella Gomez', 'especialidad' => 'Psiquiatria'],
    ['id' => 'CITA010', 'fecha' => $tomorrow, 'hora' => '11:45', 'nombre_paciente' => 'Marta Aguilar', 'email_paciente' => 'marta.aguilar@example.com', 'telefono_paciente' => '0981112233', 'motivo_consulta' => 'Control neurologico', 'estado' => 'pendiente', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera', 'especialidad' => 'Neurologia'],
    ['id' => 'CITA011', 'fecha' => $yesterday, 'hora' => '08:30', 'nombre_paciente' => 'Diego Molina', 'email_paciente' => 'diego.molina@example.com', 'telefono_paciente' => '0983334455', 'motivo_consulta' => 'Seguimiento de migrana', 'estado' => 'atendida', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera', 'especialidad' => 'Neurologia'],
  ];
}

function clinica_ensure_appointments(): void
{
  if (!isset($_SESSION['appointments']) || !is_array($_SESSION['appointments'])) {
    $_SESSION['appointments'] = clinica_seed_appointments();
    $_SESSION['appointments_seed_version'] = CLINICA_APPOINTMENTS_SEED_VERSION;
    return;
  }

  if (($_SESSION['appointments_seed_version'] ?? 0) < CLINICA_APPOINTMENTS_SEED_VERSION) {
    clinica_upgrade_seed_appointments();
    $_SESSION['appointments_seed_version'] = CLINICA_APPOINTMENTS_SEED_VERSION;
  }
}

function clinica_upgrade_seed_appointments(): void
{
  $seedAppointments = clinica_seed_appointments();
  $sessionById = [];

  foreach ($_SESSION['appointments'] as $index => $appointment) {
    if (isset($appointment['id'])) {
      $sessionById[$appointment['id']] = $index;
    }
  }

  foreach ($seedAppointments as $seedAppointment) {
    $seedId = $seedAppointment['id'];

    if (!array_key_exists($seedId, $sessionById)) {
      $_SESSION['appointments'][] = $seedAppointment;
      continue;
    }

    $index = $sessionById[$seedId];
    $currentStatus = $_SESSION['appointments'][$index]['estado'] ?? $seedAppointment['estado'];
    $_SESSION['appointments'][$index] = array_merge($seedAppointment, [
      'estado' => $currentStatus,
    ]);
  }
}

function clinica_public_user(array $user): array
{
  $role = (string) ($user['role'] ?? '');

  return [
    'id' => (string) ($user['id'] ?? ''),
    'name' => (string) ($user['name'] ?? ''),
    'role' => $role,
    'label' => (string) ($user['label'] ?? $role),
    'permissions' => clinica_permissions_for_role($role),
  ];
}

function clinica_current_user(): ?array
{
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }

  $user = $_SESSION['user'] ?? null;
  if (!is_array($user)) {
    return null;
  }

  $startedAt = (int) ($_SESSION['auth_started_at'] ?? 0);
  if ($startedAt <= 0 || time() - $startedAt >= CLINICA_SESSION_TTL_SECONDS) {
    clinica_destroy_session();
    return null;
  }

  return $user;
}

/**
 * Legacy function to read from JSON, only used by the seeder.
 */
function clinica_read_database(): array
{
  $path = __DIR__ . '/storage/gestion-data.json';

  // Prefer filesystem JSON if available (legacy), but fall back to in-code seed data.
  if (is_file($path)) {
    $raw = @file_get_contents($path);
    $decoded = $raw !== false ? json_decode($raw, true) : null;

    if (is_array($decoded)) {
      return $decoded;
    }
    // If file exists but can't be decoded, fall through to seed fallback.
  }

  // Fallback: return built-in seed data so seeding can run without external files.
  return clinica_get_seed_data();
}

function clinica_start_authenticated_session(array $user): void
{
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }

  session_regenerate_id(true);
  $_SESSION['user'] = $user;
  $_SESSION['auth_started_at'] = time();
  clinica_set_session_cookie_expiration($_SESSION['auth_started_at'] + CLINICA_SESSION_TTL_SECONDS);
}

function clinica_session_expires_at(): ?int
{
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }

  $startedAt = (int) ($_SESSION['auth_started_at'] ?? 0);
  return $startedAt > 0 ? $startedAt + CLINICA_SESSION_TTL_SECONDS : null;
}

function clinica_set_session_cookie_expiration(int $expiresAt): void
{
  if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) {
    return;
  }

  $params = session_get_cookie_params();
  setcookie(session_name(), session_id(), [
    'expires' => $expiresAt,
    'path' => $params['path'] ?: '/',
    'domain' => $params['domain'] ?? '',
    'secure' => (bool) ($params['secure'] ?? false),
    'httponly' => true,
    'samesite' => $params['samesite'] ?? 'Lax',
  ]);
}

function clinica_destroy_session(): void
{
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }

  $_SESSION = [];

  if (ini_get('session.use_cookies') && !headers_sent()) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
      'expires' => time() - 42000,
      'path' => $params['path'] ?: '/',
      'domain' => $params['domain'] ?? '',
      'secure' => (bool) ($params['secure'] ?? false),
      'httponly' => true,
      'samesite' => $params['samesite'] ?? 'Lax',
    ]);
  }

  session_destroy();
}

function clinica_json(array $payload, int $statusCode = 200): never
{
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit();
}

function clinica_request_json(): array
{
  $rawInput = file_get_contents('php://input');
  if ($rawInput === false || trim($rawInput) === '') {
    return [];
  }

  $rawInput = preg_replace('/^\xEF\xBB\xBF/', '', $rawInput);
  $payload = json_decode($rawInput, true);
  return is_array($payload) ? $payload : [];
}

function clinica_transform_appointment(array $appointment, array $user): array
{
  $transformed = $appointment;

  if (clinica_can_see_all_appointments($user['role'])) {
    $transformed['doctor_info'] = [
      'id' => $appointment['doctor_id'],
      'nombre' => $appointment['doctor_nombre'],
    ];
  }

  unset($transformed['doctor_id'], $transformed['doctor_nombre']);
  return $transformed;
}

function clinica_filter_appointments_for_user(array $appointments, array $user, ?string $doctorId = null): array
{
  return clinica_filter_dashboard_appointments($appointments, $user, $doctorId);
}

function clinica_is_valid_dashboard_date(?string $date): bool
{
  if ($date === null || $date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    return false;
  }

  [$year, $month, $day] = array_map('intval', explode('-', $date));
  return checkdate($month, $day, $year);
}

function clinica_normalize_date_scope(string $dateScope, string $role): string
{
  $allowedScopes = ['today', 'tomorrow', 'future', 'past', 'all'];
  $dateScope = in_array($dateScope, $allowedScopes, true) ? $dateScope : 'today';

  if (!clinica_can_see_past_appointments($role) && in_array($dateScope, ['past', 'all'], true)) {
    return 'future';
  }

  return $dateScope;
}

function clinica_matches_date_filter(array $appointment, array $user, string $dateScope = 'today', ?string $exactDate = null): bool
{
  $appointmentDate = (string) ($appointment['fecha'] ?? '');
  $today = date('Y-m-d');

  if (clinica_is_valid_dashboard_date($exactDate)) {
    if (!clinica_can_see_past_appointments((string) ($user['role'] ?? '')) && $exactDate < $today) {
      return false;
    }

    return $appointmentDate === $exactDate;
  }

  $dateScope = clinica_normalize_date_scope($dateScope, $user['role']);

  return match ($dateScope) {
    'tomorrow' => $appointmentDate === date('Y-m-d', strtotime('+1 day')),
    'future' => $appointmentDate >= $today,
    'past' => $appointmentDate < $today,
    'all' => true,
    default => $appointmentDate === $today,
  };
}

function clinica_filter_dashboard_appointment_records(array $appointments, array $user, ?string $doctorId = null, string $dateScope = 'today', ?string $exactDate = null): array
{
  $filtered = [];

  foreach ($appointments as $appointment) {
    if (!clinica_can_see_all_appointments($user['role']) && $appointment['doctor_id'] !== $user['id']) {
      continue;
    }

    if (clinica_can_see_all_appointments($user['role']) && $doctorId && $appointment['doctor_id'] !== $doctorId) {
      continue;
    }

    if (!clinica_matches_date_filter($appointment, $user, $dateScope, $exactDate)) {
      continue;
    }

    $filtered[] = $appointment;
  }

  usort($filtered, static function (array $a, array $b): int {
    $dateCompare = strcmp($a['fecha'] ?? '', $b['fecha'] ?? '');
    return $dateCompare !== 0 ? $dateCompare : strcmp($a['hora'] ?? '', $b['hora'] ?? '');
  });

  return $filtered;
}

function clinica_filter_dashboard_appointments(array $appointments, array $user, ?string $doctorId = null, string $dateScope = 'today', ?string $exactDate = null): array
{
  return array_map(
    static fn(array $appointment): array => clinica_transform_appointment($appointment, $user),
    clinica_filter_dashboard_appointment_records($appointments, $user, $doctorId, $dateScope, $exactDate),
  );
}

function clinica_normalize_dashboard_status(?string $status): string
{
  $status = trim((string) $status);
  return in_array($status, clinica_statuses(), true) ? $status : 'all';
}

function clinica_matches_dashboard_status(array $appointment, string $status): bool
{
  return $status === 'all' || ($appointment['estado'] ?? '') === $status;
}

function clinica_dashboard_search_text(array $appointment): string
{
  return strtolower(implode(' ', array_filter([
    $appointment['id'] ?? '',
    $appointment['fecha'] ?? '',
    $appointment['hora'] ?? '',
    $appointment['nombre_paciente'] ?? '',
    $appointment['email_paciente'] ?? '',
    $appointment['telefono_paciente'] ?? '',
    $appointment['motivo_consulta'] ?? '',
    $appointment['estado'] ?? '',
    $appointment['doctor_nombre'] ?? '',
    $appointment['especialidad'] ?? '',
  ], static fn(mixed $value): bool => trim((string) $value) !== '')));
}

function clinica_matches_dashboard_search(array $appointment, string $search): bool
{
  $search = strtolower(trim($search));
  if ($search === '') {
    return true;
  }

  return str_contains(clinica_dashboard_search_text($appointment), $search);
}

function clinica_dashboard_status_counts(array $appointments): array
{
  $counts = [
    'all' => count($appointments),
    'pendiente' => 0,
    'atendida' => 0,
    'no-presentado' => 0,
    'rechazada' => 0,
  ];

  foreach ($appointments as $appointment) {
    $status = (string) ($appointment['estado'] ?? '');
    if (array_key_exists($status, $counts)) {
      $counts[$status]++;
    }
  }

  return $counts;
}

function clinica_doctors_from_appointments(array $appointments): array
{
  $doctors = [];

  foreach ($appointments as $appointment) {
    $doctors[$appointment['doctor_id']] = [
      'id' => $appointment['doctor_id'],
      'nombre' => $appointment['doctor_nombre'],
    ];
  }

  usort($doctors, static fn(array $a, array $b): int => strcmp($a['nombre'], $b['nombre']));
  return array_values($doctors);
}

function clinica_get_all_doctors_for_select(): array
{
  $pdo = clinica_get_db();
  $stmt = $pdo->query("SELECT id, name AS nombre FROM doctors WHERE active = 1 ORDER BY name ASC");
  return $stmt->fetchAll();
}
