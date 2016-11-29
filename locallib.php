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
 * Code checker library code.
 *
 * @package    local_anonymise
 * @copyright  Gavin Henrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
define('BLOCK_CHAR', '&#9608;');

require_once($CFG->libdir . '/formslib.php');

/**
 * Action form for the Anonmise page.
 *
 * @copyright  Gavin Henrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_anonymise_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'action', true);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('action', PARAM_BOOL);

        $mform->addElement('checkbox', 'activities', get_string('activities', 'local_anonymise'));
        $mform->setType('activities', PARAM_BOOL);

        $mform->addElement('checkbox', 'categories', get_string('categories', 'local_anonymise'));
        $mform->setType('categories', PARAM_BOOL);

        $mform->addElement('checkbox', 'courses', get_string('courses', 'local_anonymise'));
        $mform->setType('courses', PARAM_BOOL);

        $mform->addElement('checkbox', 'site', get_string('includesite', 'local_anonymise'));
        $mform->setType('site', PARAM_BOOL);
        $mform->disabledIf('site', 'courses', 'notchecked');

        $mform->addElement('checkbox', 'files', get_string('files', 'local_anonymise'));
        $mform->setType('files', PARAM_BOOL);

        $mform->addElement('checkbox', 'users', get_string('users', 'local_anonymise'));
        $mform->setType('users', PARAM_BOOL);

        $mform->addElement('checkbox', 'password', get_string('resetpasswords', 'local_anonymise'));
        $mform->setType('password', PARAM_BOOL);
        $mform->setDefault('password', 'checked');
        $mform->disabledIf('password', 'users', 'notchecked');

        $mform->addElement('checkbox', 'admin', get_string('includeadmin', 'local_anonymise'));
        $mform->setType('users', PARAM_BOOL);
        $mform->disabledIf('admin', 'users', 'notchecked');

        $mform->addElement('submit', 'submitbutton', get_string('anonymise', 'local_anonymise'));
    }
}

function anonymise_activities() {

    global $DB;

    $modules = $DB->get_records('modules');

    foreach ($modules as $module) {

        echo BLOCK_CHAR . ' ';

        $modulename = get_string('pluginname', 'mod_' . $module->name);
        $moduleinstances = $DB->get_recordset($module->name);

        foreach ($moduleinstances as $moduleinstance) {

            $randomid = random_id();
            $moduleinstance->name = $modulename . ' ' . $randomid;
            $DB->update_record($module->name, $moduleinstance, true);
        }
    }
}

function anonymise_categories() {

    global $DB;

    $allcategories = $DB->get_recordset('course_categories');
    $categoyprefix = get_string('category');
    $descriptionprefix = get_string('description');

    foreach ($allcategories as $category) {

        echo BLOCK_CHAR . ' ';

        $randomid = random_id();
        $category->name = $categoyprefix . ' ' . $randomid;
        assign_if_not_null($category, 'description', $descriptionprefix . $randomid);
        assign_if_not_null($category, 'idnumber', $randomid);
        $DB->update_record('course_categories', $category, true);
    }
}

function anonymise_files() {
    global $DB;

    $files = $DB->get_recordset('files');
    foreach ($files as $file) {

        echo BLOCK_CHAR . ' ';

        assign_if_not_null($file, 'author', 'user ' . $file->userid);
        assign_if_not_null($file, 'source', '');
        if ($file->filename !== '.') {
            assign_if_not_null($file, 'filename', random_id());
        }
        if ($file->filepath !== '/') {
            assign_if_not_null($file, 'filepath', '/' . random_id() . '/');
        }
        $DB->update_record('files', $file);
    }
}

function anonymise_courses($site = false) {

    global $DB;

    $courseprefix = get_string('course');
    $descriptionprefix = get_string('description');
    $sectionprefix = get_string('section');
    $sitecourse = 1;

    // Anonymise course data.
    $courses = $DB->get_recordset('course');
    foreach ($courses as $course) {

        echo BLOCK_CHAR . ' ';

        if (!$site && $course->format == 'site') {
            $sitecourse = $course->id;
            continue;
        }

        $randomid = random_id();
        $course->fullname = $courseprefix . ' ' . $randomid;
        $course->shortname = $courseprefix . ' ' . $randomid;
        assign_if_not_null($course, 'idnumber', $randomid);
        assign_if_not_null($course, 'summary', $descriptionprefix . ' ' . $randomid);
        $DB->update_record('course', $course, true);
    }

    // Anonymise sections.
    $sections = $DB->get_recordset('course_sections');
    foreach ($sections as $section) {

        echo BLOCK_CHAR . ' ';

        if (!$site && $section->course == $sitecourse) {
            continue;
        }

        assign_if_not_null($section, 'name', $sectionprefix . ' ' . $section->section);
        assign_if_not_null($section, 'summary', $descriptionprefix . ' ' . $section->section);

        $DB->update_record('course_sections', $section, true);
    }
}

function anonymise_users($password = false, $admin = false) {

    global $CFG, $DB;

    require_once($CFG->dirroot . '/user/lib.php');

    $defaultcity = get_string('defaultusercity', 'local_anonymise');
    $defaultcountry = get_string('defaultusercountry', 'local_anonymise');
    $userstring = strtolower(get_string('user'));
    $domain = get_string('defaultdomain', 'local_anonymise');
    $fields = array(
        'firstname' => get_string('firstname'),
        'lastname' => get_string('lastname'),
        'skype' => get_string('skypeid'),
        'yahoo' => get_string('yahooid'),
        'aim' => get_string('aimid'),
        'msn' => get_string('msnid'),
        'phone1' => get_string('phone1'),
        'phone2' => get_string('phone2'),
        'institution' => get_string('institution'),
        'department' => get_string('department'),
        'address' => get_string('address'),
        'description' => get_string('description'),
        'firstnamephonetic' => get_string('firstnamephonetic'),
        'lastnamephonetic' => get_string('lastnamephonetic'),
        'middlename' => get_string('middlename'),
        'alternatename' => get_string('alternatename'),
    );
    $allusers = $DB->get_recordset('user');

    // Clear fields in the user table.
    foreach ($allusers as $user) {

        echo BLOCK_CHAR . ' ';

        if ($user->username == 'guest' || (!$admin && $user->username == 'admin')) {
            continue;
        }

        $randomid = random_id();
        if ($user->username != 'admin') {
            $user->username = $userstring . $randomid;
        }
        assign_if_not_null($user, 'idnumber', $randomid);
        foreach ($fields as $field => $translation) {
            assign_if_not_null($user, $field, $translation . ' ' . $randomid);
        }
        assign_if_not_null($user, 'email', $randomid . '@'. $domain);
        assign_if_not_null($user, 'icq', $randomid);
        assign_if_not_null($user, 'url', 'http://' . $randomid . '.com');
        assign_if_not_null($user, 'city', $defaultcity);
        assign_if_not_null($user, 'country', $defaultcountry);
        $user->picture = 0;
        user_update_user($user, $user->username == 'admin' ? false : $password);
    }

    // Clear custom profile fields.
    $customfields = $DB->get_recordset('user_info_data');
    foreach ($customfields as $field) {
        $field->data = '';
        $DB->update_record('user_info_data', $field, true);
    }
}

function assign_if_not_null(&$object, $field, $newvalue) {
    if (
        property_exists($object, $field) &&
        isset($object->$field) &&
        !empty($object->$field)
    ) {
        $object->$field = $newvalue;
    }
}

function random_id() {

    // Keep track of used IDs during the running of the script.
    static $usedids = array();

    do {
        $id = rand(1, PHP_INT_MAX);
    } while (array_search($id, $usedids) !== false);

    $usedids[] = $id;

    return $id;
}
