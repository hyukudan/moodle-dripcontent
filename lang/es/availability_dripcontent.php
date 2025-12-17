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
$string['mode_coursedays'] = 'Tiempo en el curso';
$string['mode_coursedays_help'] = 'Cuenta los días/meses desde que el usuario se inscribió por primera vez en el curso.';
$string['mode_subscriptiondays'] = 'Tiempo de suscripción activa';
$string['mode_subscriptiondays_help'] = 'Solo cuenta los días/meses cuando el usuario tiene una inscripción activa (pagada). Los períodos sin suscripción no se cuentan.';
$string['mode_daterange'] = 'Rango de fechas';
$string['mode_daterange_help'] = 'Disponible entre fechas específicas.';

// Unit selection.
$string['unit'] = 'Unidad';
$string['unit_days'] = 'Días';
$string['unit_months'] = 'Meses';

// Value input.
$string['value'] = 'Valor';
$string['aftervalue'] = 'Después de {$a->value} {$a->unit}';

// Date range.
$string['fromdate'] = 'Desde fecha';
$string['todate'] = 'Hasta fecha';
$string['betweendates'] = 'Entre {$a->from} y {$a->to}';
$string['afterdate'] = 'Después de {$a->from}';
$string['beforedate'] = 'Antes de {$a->to}';

// Descriptions.
$string['full_days_course'] = 'Disponible después de {$a} día(s) en el curso';
$string['full_months_course'] = 'Disponible después de {$a} mes(es) en el curso';
$string['full_days_subscription'] = 'Disponible después de {$a} día(s) de suscripción activa';
$string['full_months_subscription'] = 'Disponible después de {$a} mes(es) de suscripción activa';
$string['full_daterange'] = 'Disponible desde {$a->from} hasta {$a->to}';
$string['full_afterdate'] = 'Disponible después del {$a}';
$string['full_beforedate'] = 'Disponible hasta el {$a}';

$string['short_days_course'] = 'Después de {$a} días en el curso';
$string['short_months_course'] = 'Después de {$a} meses en el curso';
$string['short_days_subscription'] = 'Después de {$a} días suscrito';
$string['short_months_subscription'] = 'Después de {$a} meses suscrito';
$string['short_daterange'] = '{$a->from} - {$a->to}';

// Error messages.
$string['error_invalidvalue'] = 'Por favor, introduce un número válido.';
$string['error_invaliddate'] = 'Por favor, selecciona una fecha válida.';
$string['error_dateorder'] = 'La fecha "Desde" debe ser anterior a la fecha "Hasta".';

// Privacy.
$string['privacy:metadata'] = 'La condición de disponibilidad de Contenido gradual no almacena ningún dato personal.';
