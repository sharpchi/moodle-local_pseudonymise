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
 * Anonymise personal identifiers
 *
 * @package    local_anonymise
 * @copyright  2016 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/anonymise/locallib.php');

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
"Anonymises your site

Options:
--all               Anonymise the whole site (includes all options below)
--activities        Anonymise activities
--categories        Anonymise categories
--courses           Anonymise courses
--site              Anonymise site home course
--files             Anonymise files
--users             Anonymise users
--password          Reset user passwords
--admin             Anonymise default administrator (except username and password)
--others            Anonymise all other potentially sensitive contents
-h, --help          Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/anonymise/cli/anonymise.php --all
";


if ($options['help']) {
    echo $help;
    exit(0);
}
if (!debugging() || (empty($CFG->maintenance_enabled) && !file_exists("$CFG->dataroot/climaintenance.html"))) {
    echo $OUTPUT->notification(get_string('nodebuggingmaintenancemodecli', 'local_anonymise'));
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
    echo $OUTPUT->heading(get_string('activities', 'local_anonymise'), 3);
    anonymise_activities();
}

if ($options['categories']) {
    echo $OUTPUT->heading(get_string('categories', 'local_anonymise'), 3);
    anonymise_categories();
}

if ($options['courses']) {
    echo $OUTPUT->heading(get_string('courses', 'local_anonymise'), 3);
    anonymise_courses($options['site']);
}

if ($options['files']) {
    echo $OUTPUT->heading(get_string('files', 'local_anonymise'), 3);
    anonymise_files();
}

if ($options['users']) {
    echo $OUTPUT->heading(get_string('users', 'local_anonymise'), 3);
    anonymise_users($options['password'], $options['admin']);
}

if ($options['others']) {
    echo $OUTPUT->heading(get_string('others', 'local_anonymise'), 3);
    anonymise_others($options['activities'], $options['password']);
}

exit(0);
