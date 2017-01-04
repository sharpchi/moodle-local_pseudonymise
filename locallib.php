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

if (defined('CLI_SCRIPT') && CLI_SCRIPT == true) {
    define('BLOCK_CHAR', '.');
} else {
    define('BLOCK_CHAR', '&#9608;');
}

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

    debugging('Anonymising activities', DEBUG_DEVELOPER);

    foreach ($modules as $module) {

        if (!debugging('', DEBUG_DEVELOPER)) {
            echo BLOCK_CHAR . ' ';
        }

        if (get_string_manager()->string_exists('pluginname', 'mod_' . $module->name)) {
            $modulename = get_string('pluginname', 'mod_' . $module->name);
        } else {
            $modulename = $module->name;
        }
        $moduleinstances = $DB->get_recordset($module->name);

        foreach ($moduleinstances as $moduleinstance) {

            $randomid = assign_random_id();
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

    debugging('Anonymising categories', DEBUG_DEVELOPER);

    foreach ($allcategories as $category) {

        if (!debugging('', DEBUG_DEVELOPER)) {
            echo BLOCK_CHAR . ' ';
        }

        $randomid = assign_random_id();
        $category->name = $categoyprefix . ' ' . $randomid;
        assign_if_not_null($category, 'description', $descriptionprefix . $randomid);
        assign_if_not_null($category, 'idnumber', $randomid);
        $DB->update_record('course_categories', $category, true);
    }
}

function anonymise_courses($site = false) {

    global $DB;

    $courseprefix = get_string('course');
    $descriptionprefix = get_string('description');
    $sectionprefix = get_string('section');
    $sitecourse = 1;

    debugging('Anonymising courses');

    // Anonymise course data.
    $courses = $DB->get_recordset('course');
    foreach ($courses as $course) {

        if (!debugging('', DEBUG_DEVELOPER)) {
            echo BLOCK_CHAR . ' ';
        }

        if (!$site && $course->format == 'site') {
            $sitecourse = $course->id;
            continue;
        }

        $randomid = assign_random_id();
        $course->fullname = $courseprefix . ' ' . $randomid;
        $course->shortname = $courseprefix . ' ' . $randomid;
        assign_if_not_null($course, 'idnumber', $randomid);
        assign_if_not_null($course, 'summary', $descriptionprefix . ' ' . $randomid);
        $DB->update_record('course', $course, true);
    }

    debugging('Anonymising sections');

    // Anonymise sections.
    $sections = $DB->get_recordset('course_sections');
    foreach ($sections as $section) {

        if (!debugging('', DEBUG_DEVELOPER)) {
            echo BLOCK_CHAR . ' ';
        }

        if (!$site && $section->course == $sitecourse) {
            continue;
        }

        assign_if_not_null($section, 'name', $sectionprefix . ' ' . $section->section);
        assign_if_not_null($section, 'summary', $descriptionprefix . ' ' . $section->section);

        $DB->update_record('course_sections', $section, true);
    }
}

function anonymise_files() {
    global $DB;

    debugging('Anonymising files');

    $files = $DB->get_recordset('files');
    foreach ($files as $file) {

        if (!debugging('', DEBUG_DEVELOPER)) {
            echo BLOCK_CHAR . ' ';
        }

        assign_if_not_null($file, 'author', 'user ' . $file->userid);
        assign_if_not_null($file, 'source', '');
        if ($file->filename !== '.') {
            assign_if_not_null($file, 'filename', assign_random_id());
        }
        if ($file->filepath !== '/') {
            assign_if_not_null($file, 'filepath', '/' . assign_random_id() . '/');
        }
        $DB->update_record('files', $file);
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

    debugging('Anonymising users');

    // Clear fields in the user table.
    foreach ($allusers as $user) {

        if (!debugging('', DEBUG_DEVELOPER)) {
            echo BLOCK_CHAR . ' ';
        }

        if ($user->username == 'guest' || (!$admin && $user->username == 'admin')) {
            continue;
        }

        $randomid = assign_random_id();
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
        try {
            user_update_user($user, $user->username == 'admin' ? false : $password, false);
        } catch (moodle_exception $ex) {
            // No problem if there is any inconsistency just skip it.
            debugging('Skipped user ' . $user->id . ' update', DEBUG_DEVELOPER);
        }
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
 * - Delete all non-core database tables
 * - Delete all non-core mdl_config_plugins entries
 * - Delete all core sensitive records from mdl_config_plugins and mdl_config
 * - Delete all user sessions stored data
 * - Update all ips to 1.1.1.1
 * - Delete core sensitive records that don't fall in any of the points above
 * - Anonymise database text and varchar fields (there is a list of excluded fields)
 *
 * @access public
 * @return void
 */
function anonymise_others() {
    global $DB;

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

    debugging('Deleting non-core db tables', DEBUG_DEVELOPER);

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

    debugging('Deleting config_plugins data', DEBUG_DEVELOPER);

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

    debugging('Deleting config sensitive data', DEBUG_DEVELOPER);

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

    debugging('Deleting other stuff', DEBUG_DEVELOPER);

    // Other records.
    $DB->delete_records('user_preferences', array('name' => 'login_lockout_secret'));
    $DB->delete_records('user_preferences', array('name' => 'flickr_'));
    $DB->delete_records('user_preferences', array('name' => 'flickr__nsid'));
    $DB->delete_records('user_preferences', array('name' => 'dropbox__request_secret'));

    $DB->delete_records('sessions');
    $DB->delete_records('log');
    $DB->delete_records('config_log');
    $DB->delete_records('portfolio_log');
    $DB->delete_records('mnet_log');
    $DB->delete_records('upgrade_log');
    $DB->delete_records('scorm_aicc_session');
    $DB->delete_records('mnet_session');
    $DB->delete_records('user_password_history');
    $DB->delete_records('user_password_resets');
    $DB->delete_records('user_private_key');

    debugging('Getting rid of all ips', DEBUG_DEVELOPER);

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
    $updateips = "UPDATE {external_tokens} SET iprestriction = :ip";
    $DB->execute($updateips, $params);
    $updateips = "UPDATE {external_services_users} SET iprestriction = :ip";
    $DB->execute($updateips, $params);
    try {
        $updateips = "UPDATE {chat_users} SET ip = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // np, ignoring chat if not installed.
    }
    try {
        $updateips = "UPDATE {logstore_standard_log} SET ip = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // np, ignoring logstore_standard if not installed (although not worth the dataset if uninstalled....).
    }

    // We don't want to anonymise these database table columns because the system would not work as expected
    // without them or they contain numeric or they contain data that do not need to be anonymised.
    $excludedcolumns = get_excluded_text_columns();

    // List of varchar fields to anonymise, already excluded varchar fields that are required by the system
    // to work properly.
    $varchars = get_varchar_fields_to_update();

    // Iterate through all system tables and set random values to text and varchar fields.
    $tables = $DB->get_tables(false);
    foreach ($tables as $tablename) {

        if (!debugging('', DEBUG_DEVELOPER)) {
            echo BLOCK_CHAR . ' ';
        }

        $toupdate = array();
        $columns = $DB->get_columns($tablename, false);
        foreach ($columns as $columnname => $column) {

            // Some text fields can not be cleared or the site would not make sense.
            if (!empty($excludedcolumns[$tablename]) && in_array($columnname, $excludedcolumns[$tablename])) {
                continue;
            }

            // Text fields, all of them but the excluded ones.
            if (($DB->get_dbfamily() === 'postgres' && $column->type === 'text') ||
                ($DB->get_dbfamily() === 'mysql' && $column->type === 'longtext')) {
                $toupdate[$columnname] = $columnname;
            }

            // All listed varchars.
            if (!empty($varchars[$tablename]) && !empty($varchars[$tablename][$columnname])) {
                $toupdate[$columnname] = $columnname;
            }
        }

        // Update all table records if there is any text column that should be cleaned.
        if (!empty($toupdate)) {
            debugging('Anonymising ' . $tablename . ' records', DEBUG_DEVELOPER);
            anonymise_table_records($tablename, $toupdate);
        }
    }

    purge_all_caches();
}

function anonymise_table_records($tablename, $columns) {
    global $DB;

    try {
        $records = $DB->get_recordset($tablename);
    } catch (dml_exception $ex) {
        mtrace('Skipping ' . $tablename . ' table anonymisation process as it does not exist in this Moodle site');
        return;
    }

    foreach ($records as $record) {
        $updaterecord = false;

        // Set each of the text columns value to a random string with the same length.
        foreach ($columns as $columnname) {

            // Skip unexisting columns.
            if (!isset($record->{$columnname})) {
                continue;
            }

            $len = \core_text::strlen($record->{$columnname});
            if ($len) {
                $updaterecord = true;
                $record->{$columnname} = assign_random_string($len);
            }
        }
        if ($updaterecord) {
            $DB->update_record($tablename, $record);
        }
    }
    $records->close();
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

function assign_random_string($len) {
    $random = random_string($len);

    // Add some spacing so texts don't appear in 1 single line.
    $random = preg_replace("/\d/"," ", $random);

    return $random;
}

function assign_random_id() {

    // Keep track of used IDs during the running of the script.
    static $usedids = array();

    do {
        $id = rand(1, PHP_INT_MAX);
    } while (array_search($id, $usedids) !== false);

    $usedids[] = $id;

    return $id;
}

function get_excluded_text_columns() {
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

function get_varchar_fields_to_update() {

    // I've left role names in db as they are although not 100% sure.
    // I've left tag as they are.
    $varchars = array(
        'assign' => array('name'),
        'assignment' => array('name'),
        'badge' => array('name', 'issuername', 'issuerurl', 'issuercontact'),
        'badge_backpack' => array('email', 'backpackurl', 'password'),
        'block_community' => array('coursename', 'courseurl', 'imageurl'),
        'block_rss_client' => array('preferredtitle', 'url'),
        'blog_external' => array('name'),
        'book' => array('name'),
        'book_chapters' => array('title', 'importsrc'),
        'chat' => array('name'),
        'chat_users' => array('sid'),
        'choice' => array('name'),
        'cohort' => array('name', 'idnumber'),
        'course_categories' => array('name', 'idnumber'),
        'course_published' => array('huburl'),
        'course_request' => array('fullname', 'shortname', 'password'),
        'course_sections' => array('name'),
        'course_modules' => array('idnumber'),
        'course' => array('fullname', 'shortname', 'idnumber'),
        'enrol' => array('name', 'password'),
        'enrol_paypal' => array('business', 'receiver_email', 'receiver_id', 'item_name', 'memo', 'tax', 'pending_reason', 'reason_code', 'txn_id', 'parent_txn_id'),
        'feedback' => array('name'),
        'feedback_item' => array('name', 'label', 'dependvalue'),
        'feedback_template' => array('name'),
        'files' => array('filename', 'author'),
        'forum_posts' => array('subject'),
        'glossary' => array('name'),
        'glossary_entries' => array('concept'),
        'grade_categories' => array('fullname'),
        'forum_discussions' => array('name'),
        'forum' => array('name'),
        'grade_categories_history' => array('fullname'),
        'grade_import_newitem' => array('itemname'),
        'grade_items' => array('itemname'),
        'grade_items_history' => array('itemname'),
        'grade_outcomes' => array('shortname'),
        'grade_outcomes_history' => array('shortname'),
        'grading_definitions' => array('name'),
        'gradingform_guide_criteria' => array('shortname'),
        'groupings' => array('name', 'idnumber'),
        'groups' => array('name', 'idnumber', 'enrolmentkey'),
        'imscp' => array('name'),
        'label' => array('name'),
        'lesson' => array('name', 'password'),
        'lesson_overrides' => array('password'),
        'lesson_pages' => array('title'),
        'lti' => array('name', 'instructorcustomparameters', 'resourcekey', 'password', 'servicesalt'),
        'lti_tool_proxies' => array('name', 'secret', 'vendorcode', 'name'),
        'lti_types' => array('name', 'tooldomain'),
        'messageinbound_datakeys' => array('datakey'),
        'mnet_application' => array('display_name', 'name', 'sso_jump_url', 'sso_land_url', 'xmlrpc_server_url'),
        'mnet_host' => array('ip_address', 'name', 'wwwroot'),
        'mnet_remote_rpc' => array('functionname', 'xmlrpcpath'),
        'mnet_rpc' => array('functionname', 'xmlrpcpath'),
        'mnet_service' => array('description', 'name'),
        'mnet_sso_access_control' => array('accessctrl', 'username'),
        'mnetservice_enrol_courses' => array('categoryname', 'fullname', 'idnumber', 'rolename', 'shortname'),
        'mnetservice_enrol_enrolments' => array('rolename'),
        'page' => array('name'),
        'portfolio_instance' => array('name'),
        'portfolio_mahara_queue' => array('token'),
        'post' => array('subject'),
        'profiling' => array('url'),
        'qtype_match_subquestions' => array('answertext'),
        'question' => array('name'),
        'question_categories' => array('name'),
        'question_dataset_definitions' => array('name'),
        'quiz' => array('name', 'password', 'subnet'),
        'quiz_overrides' => array('password'),
        'quiz_sections' => array('heading'),
        'registration_hubs' => array('hubname', 'huburl', 'secret', 'token'),
        'repository_instances' => array('name', 'password', 'username'),
        'resource' => array('name'),
        'resource_old' => array('name'),
        'scale' => array('name'),
        'scale_history' => array('name'),
        'scorm' => array('name', 'sha1hash', 'md5hash'),
        'scorm_scoes' => array('identifier', 'manifest', 'organization', 'title'),
        'survey' => array('name'),
        'tool_monitor_rules' => array('name'),
        'tool_recyclebin_category' => array('fullname', 'shortname'),
        'tool_recyclebin_course' => array('name'),
        'tool_usertours_tours' => array('name'),
        'url' => array('name'),
        'user' => array('address', 'aim', 'alternatename', 'city', 'country', 'department', 'email', 'firstname', 'firstnamephonetic', 'icq', 'idnumber', 'imagealt', 'institution', 'lastip', 'lastname', 'lastnamephonetic', 'middlename', 'msn', 'password', 'phone1', 'phone2', 'secret', 'skype', 'url', 'yahoo'),
        'user_devices' => array('appid', 'model', 'name', 'platform', 'pushid', 'uuid', 'version'),
        'user_info_category' => array('name'),
        'wiki' => array('firstpagetitle', 'name'),
        'wiki_links' => array('tomissingpage'),
        'wiki_locks' => array('sectionname'),
        'wiki_pages' => array('title'),
        'wiki_synonyms' => array('pagesynonym'),
        'workshop' => array('name'),
        'workshop_old' => array('name', 'password'),
        'workshop_submissions' => array('title'),
        'workshopallocation_scheduled' => array('resultmessage')
    );

    foreach ($varchars as $tablename => $columns) {
        $varchars[$tablename] = array_combine($columns, $columns);
    }

    return $varchars;
}
