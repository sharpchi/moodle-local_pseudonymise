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

        $mform->addElement('checkbox', 'others', get_string('others', 'local_anonymise'));
        $mform->setType('others', PARAM_BOOL);

        $mform->addElement('submit', 'submitbutton', get_string('anonymise', 'local_anonymise'));
    }
}

function anonymise_activities() {

    global $DB;

    $modules = $DB->get_records('modules');

    foreach ($modules as $module) {

        echo BLOCK_CHAR . ' ';
        if (get_string_manager()->string_exists('pluginname', 'mod_' . $module->name)) {
            $modulename = get_string('pluginname', 'mod_' . $module->name);
        } else {
            $modulename = $module->name;
        }
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
        'password' => get_string('password'),
        'skype' => get_string('skypeid'),
        'yahoo' => get_string('yahooid'),
        'aim' => get_string('aimid'),
        'msn' => get_string('msnid'),
        'institution' => get_string('institution'),
        'department' => get_string('department'),
        'address' => get_string('address'),
        'description' => get_string('description'),
        'firstnamephonetic' => get_string('firstnamephonetic'),
        'lastnamephonetic' => get_string('lastnamephonetic'),
        'middlename' => get_string('middlename'),
        'alternatename' => get_string('alternatename'),
    );
    $allusers = $DB->get_recordset('user', array('deleted' => 0));

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

        // Moving here fields specially small, we need to limit their size.
        assign_if_not_null($user, 'email', $randomid . '@'. $domain);
        assign_if_not_null($user, 'icq', 'icq ' . substr($randomid, 0, 10));
        assign_if_not_null($user, 'phone1', 'phone1 ' . substr($randomid, 0, 12));
        assign_if_not_null($user, 'phone2', 'phone2 ' . substr($randomid, 0, 12));
        assign_if_not_null($user, 'url', 'http://' . $randomid . '.com');
        assign_if_not_null($user, 'lastip', 'lastip ' . substr($randomid, 0, 37));
        assign_if_not_null($user, 'secret', 'secret ' . substr($randomid, 0, 7));

        // Defaults.
        assign_if_not_null($user, 'city', $defaultcity);
        assign_if_not_null($user, 'country', $defaultcountry);
        $user->picture = 0;
        user_update_user($user, $user->username == 'admin' ? false : $password, false);
    }

    // Clear custom profile fields.
    $customfields = $DB->get_recordset('user_info_data');
    foreach ($customfields as $field) {
        $field->data = '';
        $DB->update_record('user_info_data', $field, true);
    }
}

/**
 * Here we:
 *
 * - Anonymise all database text fields (there is a list of excluded fields)
 * - Delete all non-core database tables
 * - Delete all non-core mdl_config_plugins entries
 * - Delete all core sensitive records from mdl_config_plugins and mdl_config
 * - Delete all user sessions stored data
 * - Update all ips to 1.1.1.1
 * - Delete core sensitive records that don't fall in any of the points above
 *
 * @access public
 * @return void
 */
function anonymise_others() {
    global $DB;

    // We don't want to anonymise these database table columns because the system would not work as expected
    // without them or they contain numeric or they contain data that do not need to be anonymised.
    $excludedcolumns = get_excluded_columns();

    // Iterate through all system tables and set random values to all non-excluded text fields.
    $tables = $DB->get_tables(false);
    foreach ($tables as $tablename) {

        echo BLOCK_CHAR . ' ';

        $toupdate = array();
        $columns = $DB->get_columns($tablename, false);
        foreach ($columns as $columnname => $column) {

            // Some text fields can not be cleared or the site would not make sense.
            if (!empty($excludedcolumns[$tablename]) && in_array($columnname, $excludedcolumns[$tablename])) {
                continue;
            }

            // TODO Missing mysql!
            if ($DB->get_dbfamily() === 'postgres') {
                if ($column->type === 'text') {
                    $toupdate[$columnname] = $columnname;
                }
            }
        }

        // Update all table records if there is any text column that should be cleaned.
        if (!empty($toupdate)) {
            anonymise_table_records($tablename, $toupdate);
        }
    }

    // List all non-standard plugins in the system.
    $noncoreplugins = array();
    foreach (\core_component::get_plugin_types() as $plugintype => $plugintypedir) {

        $allplugins = \core_component::get_plugin_list($plugintype);
        $standardplugins = core_plugin_manager::standard_plugins_list($plugintype);
        if (!is_array($standardplugins)) {
            $standardplugins = array();
        }
        $plugintypenoncore = array_diff(array_keys($allplugins), $standardplugins);

        foreach ($plugintypenoncore as $pluginname) {
            $name = $plugintype . '_' . $pluginname;
            $noncoreplugins[$name] = $allplugins[$pluginname];
        }
    }

    // Delete all non-core mdl_config_plugins records and tables.
    if ($noncoreplugins) {

        $dbman = $DB->get_manager();

        foreach ($noncoreplugins as $pluginname => $path) {

            $DB->delete_records('config_plugins', array('plugin' => $pluginname));

            // Also delete records stored without the plugintype part of the plugin name.
            $name = substr($pluginname, strpos($name, '_') + 1);
            $DB->delete_records('config_plugins', array('plugin' => $name));

            // All plugin tables.
            $dbfile = $path . '/db/install.xml';
            if (file_exists($dbfile)) {
                $dbman->delete_tables_from_xmldb_file($dbfile);
            }
        }
    }

    // Delete core plugins sensitive mdl_config_plugins records.
    $sensitiveplugins = array('auth_cas', 'auth_db', 'auth_fc', 'auth_imap', 'auth_ldap', 'auth_nntp', 'auth_pam', 'auth_pop3',
        'auth_shibboleth',
        'enrol_database', 'enrol_ldap', 'enrol_paypal',
        'logstore_database',
        'repository_youtube', 'repository_dropbox', 'repository_flickr_public', 'repository_boxnet', 'repository_flickr',
        'repository_googledocs', 'repository_merlot', 'repository_picasa', 'repository_s3', 'repository_skydrive',
        'search_solr'
    );
    foreach ($sensitiveplugins as $pluginname) {
        $DB->delete_records('config_plugins', array('plugin' => $pluginname));

        // Also delete records stored without the plugintype part of the plugin name.
        $name = substr($pluginname, strpos($name, '_') + 1);
        $DB->delete_records('config_plugins', array('plugin' => $name));
    }

    // Also hub, which is not a plugin but its data is stored in config_plugins.
    $DB->delete_records('config_plugins', array('plugin' => 'hub'));

    // Also delete core sensitive records in mdl_config.
    $sensitiveconfigvalues = array(
        'jabberhost', 'jabberserver', 'jabberusername', 'jabberpassword', 'jabberport',
        'airnotifierurl', 'airnotifierport', 'airnotifiermobileappname', 'airnotifierappname', 'airnotifieraccesskey',
        'BigBlueButtonBNSecuritySalt', 'bigbluebuttonbn_server_url', 'BigBlueButtonBNServerURL', 'bigbluebuttonbn_shared_secret',
        'chat_serverhost', 'chat_serverip', 'chat_serverport', 'curlsecurityallowedport', 'curlsecurityblockedhosts', 'geoip2file',
        'geoipfile', 'googlemapkey3', 'maintenance_message', 'messageinbound_domain', 'messageinbound_host',
        'messageinbound_hostpass', 'messageinbound_hostssl', 'messageinbound_hostuser', 'messageinbound_mailbox', 'noreplyaddress',
        'proxybypass', 'proxyhost', 'proxypassword', 'proxyport', 'proxytype', 'proxyuser', 'recaptchaprivatekey',
        'recaptchapublickey', 'smtphosts', 'smtppass', 'smtpsecure', 'smtpuser', 'supportemail', 'supportname', 'badges_badgesalt',
        'cronremotepassword'
    );
    foreach ($sensitiveconfigvalues as $name) {
        // We update rather than delete because there is code that relies incorrectly on CFG vars being set.
        if ($record = $DB->get_record('config', array('name' => $name))) {
            $record->value = '';
            $DB->update_record('config', $record);
        }
    }

    // Other records.
    $DB->delete_records('user_preferences', array('name' => 'login_lockout_secret'));
    $DB->delete_records('user_preferences', array('name' => 'flickr_'));
    $DB->delete_records('user_preferences', array('name' => 'flickr__nsid'));
    $DB->delete_records('user_preferences', array('name' => 'dropbox__request_secret'));

    $DB->delete_records('sessions');

    // Get rid of all ips.
    $params = array('ip' => '1.1.1.1');
    $updateips = "UPDATE {user_private_key} SET iprestriction = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {user} SET lastip = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {registry} SET ipaddress = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {register_downloads} SET ip = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {mnet_log} SET ip = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {mnet_host} SET ip_address = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {logstore_standard_log} SET ip = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {log} SET ip = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {external_tokens} SET iprestriction = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {external_services_users} SET iprestriction = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {chat_users} SET ip = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {block_spam_deletion_akismet} SET user_ip = :ip";
    $DB->execute($updateips, $params);

    purge_all_caches();
}

function anonymise_table_records($tablename, $columns) {
    global $DB;

    $records = $DB->get_recordset($tablename);
    foreach ($records as $record) {
        $updaterecord = false;

        // Set each of the text columns value to a random string with the same length.
        foreach ($columns as $columnname) {
            $len = \core_text::strlen($record->{$columnname});
            if ($len) {
                $updaterecord = true;
                $record->{$columnname} = random_string($len);
            }
        }
        if ($updaterecord) {
            $DB->update_record($tablename, $record);
        }
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

function get_excluded_columns() {
    return array(
        'config' => array('value'),
        'config_plugins' => array('value'),
        'course_modules' => array('availability'),
        'course_sections' => array('sequence', 'availability'),
        'course_format_options' => array('value'),
        'filter_config' => array('value'),
        'message' => array('contexturl', 'contexturlname'),
        'message_read' => array('contexturl', 'contexturlname'),
        'scale' => array('scale'),
        'question_statistics' => array('subquestions', 'positions'),
        'events_handlers' => array('handlerfunction'),
        'grade_items' => array('calculation'),
        'grade_items_history' => array('calculation'),
        'tag_correlation' => array('correlatedtags'),
        'groupings' => array('configdata'),
        'files_reference' => array('reference'),
        'block_instances' => array('configdata'),
        'grading_definitions' => array('options'),
        'badge_issued' => array('uniquehash'),
        'competency' => array('ruleconfig', 'scaleconfiguration'),
        'competency_framework' => array('scaleconfiguration'),
        'logstore_standard_log' => array('other'),
        'question_multianswer' => array('sequence'),
        'qtype_ddmarker_drops' => array('coords'),
        'data' => array('singletemplate', 'listtemplate', 'listtemplateheader', 'listtemplatefooter', 'addtemplate', 'rsstemplate', 'csstemplate', 'jstemplate', 'asearchtemplate', 'config'),
        'lesson' => array('conditions'),
        'page' => array('displayoptions'),
        'feedback' => array('page_after_submit'),
        'resource' => array('displayoptions'),
        'quiz_attempts' => array('layout'),
        'workshopallocation_scheduled' => array('settings'),
        'lti_types' => array('enabledcapability', 'parameter'),
        'lti_tool_settings' => array('settings'),
        'scorm_scoes' => array('launch'),
        'scorm_scoes_data' => array('value'),
        'scorm_scoes_track' => array('value'),
        'assignfeedback_editpdf_annot' => array('path'),
        'assign_plugin_config' => array('value'),
        'survey_questions' => array('options'),
        'url' => array('displayoptions', 'parameters'),
        'imscp' => array('structure')
    );
}
