<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings - Spanish.
 *
 * @package    availability_dripcontent
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Contenido gradual';
$string['title'] = 'Contenido gradual';
$string['description'] = 'Controla el acceso según el tiempo en el curso, tiempo de suscripción activa o fechas específicas.';

// Condition title.
$string['conditiontitle'] = 'Contenido gradual';

// Mode selection.
$string['mode'] = 'Modo';
$string['mode_coursedays'] = 'Tiempo desde inscripción';
$string['mode_coursedays_help'] = 'Cuenta los días/semanas/meses desde que el usuario se inscribió por primera vez en el curso.';
$string['mode_coursestartdays'] = 'Tiempo desde inicio del curso';
$string['mode_coursestartdays_help'] = 'Cuenta los días/semanas/meses desde la fecha de inicio del curso. Igual para todos los usuarios.';
$string['mode_subscriptiondays'] = 'Tiempo de suscripción activa';
$string['mode_subscriptiondays_help'] = 'Solo cuenta los días/semanas/meses cuando el usuario tiene una inscripción activa (pagada). Los períodos sin suscripción no se cuentan.';
$string['mode_daterange'] = 'Rango de fechas';
$string['mode_daterange_help'] = 'Disponible entre fechas específicas.';

// Unit selection.
$string['unit'] = 'Unidad';
$string['unit_days'] = 'Días';
$string['unit_weeks'] = 'Semanas';
$string['unit_months'] = 'Meses';

// Value input.
$string['value'] = 'Valor';
$string['aftervalue'] = 'Después de {$a->value} {$a->unit}';

// Enrolment methods filter.
$string['enrolmentmethods'] = 'Métodos de inscripción';
$string['enrolmentmethods_help'] = 'Solo contar tiempo de estos métodos de inscripción. Dejar vacío para contar todos los métodos.';
$string['allenrolmentmethods'] = 'Todos los métodos de inscripción';

// Date range.
$string['fromdate'] = 'Desde fecha';
$string['todate'] = 'Hasta fecha';
$string['betweendates'] = 'Entre {$a->from} y {$a->to}';
$string['afterdate'] = 'Después de {$a->from}';
$string['beforedate'] = 'Antes de {$a->to}';

// Full descriptions - Enrolment mode.
$string['full_days_enrolment'] = 'Disponible después de {$a} día(s) desde la inscripción';
$string['full_weeks_enrolment'] = 'Disponible después de {$a} semana(s) desde la inscripción';
$string['full_months_enrolment'] = 'Disponible después de {$a} mes(es) desde la inscripción';

// Full descriptions - Course start mode.
$string['full_days_coursestart'] = 'Disponible después de {$a} día(s) desde el inicio del curso';
$string['full_weeks_coursestart'] = 'Disponible después de {$a} semana(s) desde el inicio del curso';
$string['full_months_coursestart'] = 'Disponible después de {$a} mes(es) desde el inicio del curso';

// Full descriptions - Subscription mode.
$string['full_days_subscription'] = 'Disponible después de {$a} día(s) de suscripción activa';
$string['full_weeks_subscription'] = 'Disponible después de {$a} semana(s) de suscripción activa';
$string['full_months_subscription'] = 'Disponible después de {$a} mes(es) de suscripción activa';

// Full descriptions - Date range.
$string['full_daterange'] = 'Disponible desde {$a->from} hasta {$a->to}';
$string['full_afterdate'] = 'Disponible después del {$a}';
$string['full_beforedate'] = 'Disponible hasta el {$a}';

// Short descriptions - Enrolment mode.
$string['short_days_enrolment'] = 'Después de {$a} días inscrito';
$string['short_weeks_enrolment'] = 'Después de {$a} semanas inscrito';
$string['short_months_enrolment'] = 'Después de {$a} meses inscrito';

// Short descriptions - Course start mode.
$string['short_days_coursestart'] = 'Después de {$a} días del inicio';
$string['short_weeks_coursestart'] = 'Después de {$a} semanas del inicio';
$string['short_months_coursestart'] = 'Después de {$a} meses del inicio';

// Short descriptions - Subscription mode.
$string['short_days_subscription'] = 'Después de {$a} días suscrito';
$string['short_weeks_subscription'] = 'Después de {$a} semanas suscrito';
$string['short_months_subscription'] = 'Después de {$a} meses suscrito';

// Short descriptions - Date range.
$string['short_daterange'] = '{$a->from} - {$a->to}';
$string['short_afterdate'] = 'Después del {$a}';
$string['short_beforedate'] = 'Hasta el {$a}';

// Remaining time strings.
$string['remaining_months'] = 'Faltan {$a} mes(es)';
$string['remaining_months_days'] = 'Faltan {$a->months} mes(es) y {$a->days} día(s)';
$string['remaining_days'] = 'Faltan {$a} día(s)';
$string['remaining_days_hours'] = 'Faltan {$a->days} día(s) y {$a->hours} hora(s)';
$string['remaining_hours'] = 'Faltan {$a} hora(s)';
$string['remaining_weeks'] = 'Faltan {$a} semana(s)';

// Error messages.
$string['error_invalidvalue'] = 'Por favor, introduce un número válido.';
$string['error_invaliddate'] = 'Por favor, selecciona una fecha válida.';
$string['error_dateorder'] = 'La fecha "Desde" debe ser anterior a la fecha "Hasta".';

// Admin settings.
$string['settings'] = 'Configuración de Contenido gradual';
$string['settings_notifications'] = 'Configuración de notificaciones';
$string['settings_notifications_desc'] = 'Configura cómo se notifica a los usuarios cuando el contenido está disponible.';
$string['notify_enabled'] = 'Activar notificaciones de desbloqueo';
$string['notify_enabled_desc'] = 'Enviar notificaciones a los usuarios cuando el contenido esté disponible para ellos.';
$string['notify_method'] = 'Método de notificación';
$string['notify_method_desc'] = 'Elige cómo notificar a los usuarios cuando se desbloquea el contenido.';
$string['notify_method_none'] = 'Sin notificaciones';
$string['notify_method_email'] = 'Solo correo electrónico';
$string['notify_method_popup'] = 'Solo notificación en la plataforma';
$string['notify_method_both'] = 'Correo electrónico y notificación en la plataforma';

// Notification messages.
$string['notification_subject'] = 'Nuevo contenido disponible: {$a->activityname}';
$string['notification_body'] = 'Hola {$a->username},

El siguiente contenido ya está disponible en el curso "{$a->coursename}":

{$a->activityname}

Haz clic aquí para acceder: {$a->url}

Un saludo,
{$a->sitename}';
$string['notification_small'] = 'Contenido "{$a->activityname}" ya disponible';

// Privacy.
$string['privacy:metadata:availability_dripcontent_ntf'] = 'El plugin de Contenido gradual almacena información sobre qué usuarios han sido notificados de los desbloqueos de contenido.';
$string['privacy:metadata:availability_dripcontent_ntf:userid'] = 'El ID del usuario que fue notificado.';
$string['privacy:metadata:availability_dripcontent_ntf:cmid'] = 'El ID del módulo del curso que fue desbloqueado.';
$string['privacy:metadata:availability_dripcontent_ntf:timecreated'] = 'El momento en que se envió la notificación.';

// Maintenance settings.
$string['settings_maintenance'] = 'Configuración de mantenimiento';
$string['settings_maintenance_desc'] = 'Configura la limpieza automática de registros de notificaciones antiguos.';
$string['notification_retention'] = 'Retención de notificaciones (días)';
$string['notification_retention_desc'] = 'Número de días para mantener los registros de notificaciones. Establece 0 para mantener todos los registros indefinidamente. Por defecto son 90 días.';

// Task.
$string['task_check_unlocks'] = 'Comprobar desbloqueos de contenido y enviar notificaciones';
$string['task_cleanup_notifications'] = 'Limpiar registros de notificaciones antiguos';
