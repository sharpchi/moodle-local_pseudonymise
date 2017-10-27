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
 * Pseudonymise personal identifiers
 *
 * @package    local_pseudonymise
 * @copyright  2017 Elizabeth Dalton, 2016 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/pseudonymise/locallib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'all' => false,
        'activities' => false,
        'categories' => false,
        'courses' => false,
        'site' => false,
        'files' => false,
        'users' => false,
        'password' => false,
        'admin' => false,
        'others' => false,
        'help' => false
    ), array(
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

$help =
"Pseudonymises your site

Options:
--all               Pseudonymise the whole site (includes all options below)
--activities        Pseudonymise activities
--categories        Pseudonymise categories
--courses           Pseudonymise courses
--site              Pseudonymise site home course
--files             Pseudonymise files
--users             Pseudonymise users
--password          Reset user passwords
--admin             Pseudonymise default administrator (except username and password)
--others            Pseudonymise all other potentially sensitive contents
-h, --help          Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/pseudonymise/cli/pseudonymise.php --all
";


if ($options['help']) {
    echo $help;
    exit(0);
}
if (!debugging() || (empty($CFG->maintenance_enabled) && !file_exists("$CFG->dataroot/climaintenance.html"))) {
    echo $OUTPUT->notification(get_string('nodebuggingmaintenancemodecli', 'local_pseudonymise'));
    exit(1);
}

$unique = array_unique($options);
if (count($unique) === 1 && reset($unique) === false) {
    echo $help;
    exit(0);
}

// Enable them all.
if ($options['all'] === true) {
    foreach ($options as $key => $option) {
        $options[$key] = true;
    }
}


// Allow more time for long query runs.
set_time_limit(0);

// Exectute anonmisation based on selections.
if ($options['activities']) {
    echo $OUTPUT->heading(get_string('activities', 'local_pseudonymise'), 3);
    pseudonymise_activities();
}

if ($options['categories']) {
    echo $OUTPUT->heading(get_string('categories', 'local_pseudonymise'), 3);
    pseudonymise_categories();
}

if ($options['courses']) {
    echo $OUTPUT->heading(get_string('courses', 'local_pseudonymise'), 3);
    pseudonymise_courses($options['site']);
}

if ($options['files']) {
    echo $OUTPUT->heading(get_string('files', 'local_pseudonymise'), 3);
    pseudonymise_files();
}

if ($options['users']) {
    echo $OUTPUT->heading(get_string('users', 'local_pseudonymise'), 3);
    pseudonymise_users($options['password'], $options['admin']);
}

if ($options['others']) {
    echo $OUTPUT->heading(get_string('others', 'local_pseudonymise'), 3);
    pseudonymise_others($options['activities'], $options['password']);
}

exit(0);
