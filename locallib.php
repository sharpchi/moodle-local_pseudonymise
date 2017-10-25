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
 * @package    local_pseudonymise
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

// Files required by uninstallation processes.
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Action form for the Pseudonymise page.
 *
 * @copyright  Gavin Henrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_pseudonymise_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'action', true);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('action', PARAM_BOOL);

        $mform->addElement('checkbox', 'activities', get_string('activities', 'local_pseudonymise'));
        $mform->setType('activities', PARAM_BOOL);

        $mform->addElement('checkbox', 'categories', get_string('categories', 'local_pseudonymise'));
        $mform->setType('categories', PARAM_BOOL);

        $mform->addElement('checkbox', 'courses', get_string('courses', 'local_pseudonymise'));
        $mform->setType('courses', PARAM_BOOL);

        $mform->addElement('checkbox', 'site', get_string('includesite', 'local_pseudonymise'));
        $mform->setType('site', PARAM_BOOL);
        $mform->disabledIf('site', 'courses', 'notchecked');

        $mform->addElement('checkbox', 'files', get_string('files', 'local_pseudonymise'));
        $mform->setType('files', PARAM_BOOL);

        $mform->addElement('checkbox', 'users', get_string('users', 'local_pseudonymise'));
        $mform->setType('users', PARAM_BOOL);

        $mform->addElement('checkbox', 'password', get_string('resetpasswords', 'local_pseudonymise'));
        $mform->setType('password', PARAM_BOOL);
        $mform->setDefault('password', 'checked');
        $mform->disabledIf('password', 'users', 'notchecked');

        $mform->addElement('checkbox', 'admin', get_string('includeadmin', 'local_pseudonymise'));
        $mform->setType('users', PARAM_BOOL);
        $mform->disabledIf('admin', 'users', 'notchecked');

        $mform->addElement('checkbox', 'others', get_string('others', 'local_pseudonymise'));
        $mform->setType('others', PARAM_BOOL);

        $mform->addElement('submit', 'submitbutton', get_string('anonymise', 'local_pseudonymise'));
    }
}

function pseudonymise_activities() {

    global $DB;

    $modules = $DB->get_records('modules');

    debugging('Pseudonymising activities', DEBUG_DEVELOPER);

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

            /* $randomid = assign_random_id(); */
            $pseudoid = assign_pseudo_id();
            $moduleinstance->name = $modulename . ' ' . $pseudoid;
            $DB->update_record($module->name, $moduleinstance, true);
        }
        $moduleinstances->close();
    }
}

function pseudoonymise_categories() {

    global $DB;

    $categoyprefix = get_string('category');
    $descriptionprefix = get_string('description');

    debugging('Pseudonymising categories', DEBUG_DEVELOPER);

    $allcategories = $DB->get_recordset('course_categories');
    foreach ($allcategories as $category) {

    /* $randomid = assign_random_id(); */
    $pseudoid = assign_pseudo_id();
        $category->name = $categoyprefix . ' ' . $pseudoid;
        assign_if_not_null($category, 'description', $descriptionprefix . $pseudoid);
        assign_if_not_null($category, 'idnumber', $pseudoid);
        $DB->update_record('course_categories', $category, true);
    }
    $allcategories->close();
}

function pseudonymise_courses($site = false) {

    global $DB;

    $courseprefix = get_string('course');
    $descriptionprefix = get_string('description');
    $sectionprefix = get_string('section');
    $sitecourse = 1;

    debugging('Pseudonymising courses');

    // Pseudonymise course data.
    $courses = $DB->get_recordset('course');
    foreach ($courses as $course) {

        if (!$site && $course->format == 'site') {
            $sitecourse = $course->id;
            continue;
        }

    /* $randomid = assign_random_id(); */
     $pseudoid = assign_pseudo_id();
        $course->fullname = $courseprefix . ' ' . $pseudoid;
        $course->shortname = $courseprefix . ' ' . $pseudoid;
        assign_if_not_null($course, 'idnumber', $pseudoid);
        assign_if_not_null($course, 'summary', $descriptionprefix . ' ' . $pseudoid);
        $DB->update_record('course', $course, true);
    }
    $courses->close();

    debugging('Pseudonymising sections');

    // Pseudonymise sections - replace with numbers
    $sections = $DB->get_recordset('course_sections');
    foreach ($sections as $section) {

        if (!$site && $section->course == $sitecourse) {
            continue;
        }

        assign_if_not_null($section, 'name', $sectionprefix . ' ' . $section->section);
        assign_if_not_null($section, 'summary', $descriptionprefix . ' ' . $section->section);

        $DB->update_record('course_sections', $section, true);
    }
    $sections->close();
}

function pseudonymise_files() {
    global $DB;

    debugging('Pseudonymising files');

    $files = $DB->get_recordset('files');
    foreach ($files as $file) {

        assign_if_not_null($file, 'author', 'user ' . $file->userid);
        assign_if_not_null($file, 'source', '');
        if ($file->filename !== '.') {
            assign_if_not_null($file, 'filename', assign_pseudo_id());
        }
        if ($file->filepath !== '/') {
            assign_if_not_null($file, 'filepath', '/' . assign_pseudo_id() . '/');
        }
        $DB->update_record('files', $file);
    }
    $files->close();
}

function pseudonymise_users($password = false, $admin = false) {

    global $CFG, $DB;

    require_once($CFG->dirroot . '/user/lib.php');

    // Delete all deleted users.
    $DB->delete_records('user', array('deleted' => 1));

    $defaultcity = get_string('defaultusercity', 'local_pseudonymise');
    $defaultcountry = get_string('defaultusercountry', 'local_pseudonymise');
    $userstring = strtolower(get_string('user'));
    $domain = get_string('defaultdomain', 'local_pseudonymise');
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

    debugging('Pseudonymising users');

    // Clear fields in the user table.
    $allusers = $DB->get_recordset('user', array('deleted' => 0));
    foreach ($allusers as $user) {

        if ($user->username == 'guest' || (!$admin && $user->username == 'admin')) {
            continue;
        }

        $pseudoid = assign_pseudo_id();
        /* this function is specific to assigning a plausible given name */
        $pseudogname = assign_pseudo_gname();
        /* this function is specific to assigning a plausible surname */
        $pseudosname = assign_pseudo_sname();
        if ($user->username != 'admin') {
            $user->username = $userstring . $pseudogname . $pseudosname;
        }
        /* assign_if_not_null($user, 'idnumber', $pseudoid); */
        assign_if_not_null($user, 'idnumber', $pseudogname . $pseudosname);
        foreach ($fields as $field => $translation) {
            assign_if_not_null($user, $field, $translation . ' ' . $pseudoid);
        }

        // Moving here fields specially small, we need to limit their size.
        assign_if_not_null($user, 'email', $pseudogname . $pseudosname . '@'. $domain);
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
        } catch (Exception $ex) {
            // No problem if there is any inconsistency just skip it.
            debugging('Skipped user ' . $user->id . ' update', DEBUG_DEVELOPER);
        }
    }
    $allusers->close();

    // Clear custom profile fields.
    $customfields = $DB->get_recordset('user_info_data');
    foreach ($customfields as $field) {
        $field->data = '';
        $DB->update_record('user_info_data', $field, true);
    }
    $customfields->close();
}

/**
 * Here we:
 *
 * - Delete all non-core database tables
 * - Delete all non-core mdl_config_plugins entries
 * - Delete all core sensitive records from mdl_config_plugins and mdl_config
 * - Delete all user sessions stored data
 * - Update all ips to 0.0.0.0
 * - Delete core sensitive records that don't fall in any of the points above
 * - Anonymise database text and varchar fields (there is a list of excluded fields)
 *
 * @access public
 * @return void
 */
function pseudonymise_others($pseudonymiseactivities, $pseudonymisepassword) {
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
            // We don't want to delete local pseudonymise.
            if ($plugintype !== 'local' && $pluginname !== 'pseudonymise') {
                $name = $plugintype . '_' . $pluginname;
                $noncoreplugins[$name] = $allplugins[$pluginname];
            }
        }
    }

    debugging('Uninstalling non-core stuff', DEBUG_DEVELOPER);

    // Delete all non-core mdl_config_plugins records and tables.
    if ($noncoreplugins) {

        $dbman = $DB->get_manager();

        foreach ($noncoreplugins as $pluginname => $path) {

            $shortname = substr($pluginname, strpos($pluginname, '_') + 1);
            $plugintype = substr($pluginname, 0, strpos($pluginname, '_'));
            $legacyname = $plugintype . '/' . $shortname;

            try {
                uninstall_plugin($plugintype, $shortname);
            } catch (moodle_exception $e) {
                // Catch any possible issue with 3rd party code. Notify it and provide a workaround.
                debugging('Not possible to complete ' . $pluginname . ' uninstall process, falling back to database tables ' .
                    'and config values removal');

                // Delete all plugin tables.
                $dbfile = $path . '/db/install.xml';
                if (file_exists($dbfile)) {
                    $dbman->delete_tables_from_xmldb_file($dbfile);
                }
            }

            // Cleanup from core tables regardless of successfull uninstall.
            $DB->delete_records('config_plugins', array('plugin' => $pluginname));

            // Also delete records stored without the plugintype part of the plugin name.
            $DB->delete_records('config_plugins', array('plugin' => $shortname));

            // And records using type/name syntax.
            $DB->delete_records('config_plugins', array('plugin' => $legacyname));
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

        $shortname = substr($pluginname, strpos($pluginname, '_') + 1);
        // e.g. auth plugins before 3.3
        $oldname = str_replace('_', '/', $pluginname);

        $sql = "DELETE FROM {config_plugins} WHERE (plugin = :pluginname OR plugin = :shortname OR plugin = :oldname) AND name != 'version'";
        $DB->execute($sql, array('pluginname' => $pluginname, 'shortname' => $shortname, 'oldname' => $oldname));
    }

    // Also hub, which is not a plugin but its data is stored in config_plugins.
    $DB->delete_records('config_plugins', array('plugin' => 'hub'));
    $DB->delete_records('config_plugins', array('plugin' => 'mnet'));
    $DB->delete_records('config_plugins', array('plugin' => 'core_plugin'));

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
        'badges_defaultissuername', 'badges_defaultissuercontact', 'cronremotepassword', 'turnitin_account_id', 'turnitin_secret',
        'turnitin_proxyurl', 'turnitin_proxyport', 'turnitin_proxyuser', 'turnitin_proxypassword'
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

    try {
        $DB->delete_records('sessions');
    } catch (dml_exception $e) {
        // Ignore.
    }
    try {
        $DB->delete_records('log');
    } catch (dml_exception $e) {
        // Ignore.
    }
    try {
        $DB->delete_records('config_log');
    } catch (dml_exception $e) {
        // Ignore.
    }
    try {
        $DB->delete_records('portfolio_log');
    } catch (dml_exception $e) {
        // Ignore.
    }
    try {
        $DB->delete_records('mnet_log');
    } catch (dml_exception $e) {
        // Ignore.
    }
    try {
        $DB->delete_records('upgrade_log');
    } catch (dml_exception $e) {
        // Ignore.
    }
    try {
        $DB->delete_records('scorm_aicc_session');
    } catch (dml_exception $e) {
        // Ignore.
    }
    try {
        $DB->delete_records('mnet_session');
    } catch (dml_exception $e) {
        // Ignore.
    }
    try {
        $DB->delete_records('user_password_history');
    } catch (dml_exception $e) {
        // Ignore.
    }
    try {
        $DB->delete_records('user_password_resets');
    } catch (dml_exception $e) {
        // Ignore.
    }
    try {
        $DB->delete_records('user_private_key');
    } catch (dml_exception $e) {
        // Ignore.
    }

    debugging('Getting rid of all ips', DEBUG_DEVELOPER);

    // Get rid of all ips.
    $params = array('ip' => '0.0.0.0');
    try {
        $updateips = "UPDATE {user_private_key} SET iprestriction = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // Ignore.
    }
    try {
        $updateips = "UPDATE {user} SET lastip = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // Ignore.
    }
    try {
        $updateips = "UPDATE {registry} SET ipaddress = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // Ignore.
    }
    try {
        $updateips = "UPDATE {register_downloads} SET ip = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // Ignore.
    }
    try {
        $updateips = "UPDATE {mnet_host} SET ip_address = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // Ignore.
    }
    try {
        $updateips = "UPDATE {external_tokens} SET iprestriction = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // Ignore.
    }
    try {
        $updateips = "UPDATE {external_services_users} SET iprestriction = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // Ignore.
    }
    try {
        $updateips = "UPDATE {chat_users} SET ip = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // Ignore.
    }
    try {
        $updateips = "UPDATE {logstore_standard_log} SET ip = :ip";
        $DB->execute($updateips, $params);
    } catch (dml_exception $ex) {
        // np, ignoring logstore_standard if not installed (although not worth the dataset if uninstalled....).
    }

    // We don't want to pseudonymise these database table columns because the system would not work as expected
    // without them or they contain numeric or they contain data that do not need to be pseudonymised.
    $excludedcolumns = get_excluded_text_columns();

    // List of varchar fields to pseudonymise, already excluded varchar fields that are required by the system
    // to work properly.
    $varchars = get_varchar_fields_to_update();

    // List of activities, we skip activity names pseudonymisation.
    $activitynamefields = get_activity_name_fields();

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

                // Skip activity names and user password if required.
                if (($pseudonymiseactivities || empty($activitynamefields[$tablename]) || empty($activitynamefields[$tablename][$columnname])) &&
                        ($pseudonymisepassword || $tablename !== 'user' || $columnname !== 'password')) {
                    $toupdate[$columnname] = $columnname;
                }
            }
        }

        // Update all table records if there is any text column that should be cleaned.
        if (!empty($toupdate)) {
            debugging('Pseudonymising ' . $tablename . ' records', DEBUG_DEVELOPER);
            pseudonymise_table_records($tablename, $toupdate);
        }
    }

    purge_all_caches();
}

function pseudonymise_table_records($tablename, $columns) {
    global $DB;

    try {
        $records = $DB->get_recordset($tablename);
    } catch (dml_exception $ex) {
        mtrace('Skipping ' . $tablename . ' table pseudonymisation process as it does not exist in this Moodle site');
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
            /* not changing this for pseudonymiser for now, but later may try to preserve some lexical markers */
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

for ($i = 1; $i <=100; $i++) {
		$j = floor(pow(1.26,$i));
		$pseudoid = assign_pseudo_id($j);
		//print "$i th string using size of $j is $pseudoid\n";
		print "$pseudoid\n";
}
function assign_pseudo_id($len) {
    // rather than just assigning a random string of junk,
    // this algorithm assembles a phrase string consisting of randomized strings categorized by type
    // accept a length parameter to determine how long the phrase needs to be for uniqueness
    // Keep track of used IDs during the running of the script.
    static $usedpseudoids = array();
    $prefixlist = explode(",", "Introduction to,Introductory,Junior,Intermediate,Elementary,Applied,General,Freshman,Senior,Integrated,Special,Independent Study in,Thesis in,Advanced,Fundamentals of,Topics in,Internship in,Seminar in,Colloquium in,Exploring,Teaching,Foundations of,Investigating,Condensed,Beginnings of,Studies in,Peer-Led");
    $adjlist = explode(",","Abnormal,Abstract,Academic,Acquired,Active,Acute,Advanced,Aerial,Algebraic,Analytical,Ancient,Anthropological,Apocalyptic,Applied,Aquatic,Aqueous,Archaeological,Archaic,Architectural,Arctic,Artificial,Artist's,Artistic,Assisted,Assistive,Athenian,Athletic,Atlantic,Auditory,Aural,Bad,Baroque,Basic,Behavioral,Biochemical,Bioenvironmental,Biological,Biomechanical,Biomedical,Brave,Broken,Buried,Canine,Cardiac,Cardiopulmonary,Cellular,Changing,Chemical,Children's,Choral,Chronic,Civic,Civil,Classical,Client-Side,Clinical,Coastal,Cognitive,Collaborative,Collective,Collegiate,Commercial,Communicative,Community-Based,Commutative,Comparative,Complex,Composite,Computational,Concrete,Condensed,Contemporary,Context,Controlled,Controversial,Conversational,Coolest,Cooperative,Core,Corporate,Creative,Criminological,Critical,Crop,Cross-Cultural,Cruel,Culinary,Cultural,Current,Daily,Dairy,Delinquent,Derivative,Developmental,Dietetic,Differential,Digital,Disabled,Discrete,Disruptive,Distributed,Do-It-Yourself,Domestic,Dystopian,Earliest,Early,East,Eastern,Ecological,Economic,Ecosystem,Educational,Eighteenth-Century,Electric,Electrical,Electrochemical,Electromagnetic,Electromechanical,Electronic,Elementary,Embedded,Endangered,Entrepreneurial,Environmental,Epic,Equine,Ergogenic,Estuarine,Ethical,Ethnic,Ethnographic,Eukaryotic,European,Evolutionary,Exceptional,Experimental,Extreme,Famous,Federal,Financial,Finite,Floral,Floricultural,Foreign,Forensic,Formal,Functional,Fundamental,Fungal,Gender-Based,Gendered,General,Genetic,Geo-Environmental,Geographic,Geological,Geotechnical,Geriatric,Glacial,Global,Golden,Good,Gothic,Governmental,Graded,Grand,Graphic,Great,Green,Growing,Happy,Hazardous,Healthy,Hellenic,Hellenistic,Herbaceous,High,High-Performance,High-Tech,Hispanic,Historic,Historical,Horticultural,Igneous,Inclusive,Independent,Indigenous,Individual,Industrial,Infectious,Inorganic,Institutional,Instrumental,Integrated,Integrating,Intellectual,Interactive,Interdisciplinary,Internal,International,Interpersonal,Invasive,Invertebrate,Investigative,Juvenile,Large,Later,Legal,Linear,Linguistic,Literary,Local,Long,Lost,Macroeconomic,Major,Mammalian,Managerial,Marine,Mass,Material,Maternal,Mathematical,Mechanical,Medical,Medieval,Mediterranean,Mental,Mesoamerican,Metamorphic,Methodological,Microbial,Microeconomic,Microscopic,Mock,Model,Modern,Molecular,Monetary,Multicultural,Multidimensional,Multidisciplinary,Multilingual,Multinational,Musculoskeletal,Musical,Mystic,Napoleonic,National,Native,Natural,Naval,Near,Networked,New,Nineteenth-Century,Non-Profit,Non-Western,Nonlinear,Nonprofit,North,Northeast,Northern,Nuclear,Nucleic,Numerical,Nutritional,Object-Oriented,Occupational,Ocean,Old,Older,One-Dimensional,Open,Organic,Organizational,Oriented,Ornamental,Outdoor,Parallel,Park,Past,Pathogenic,Pathologic,Period,Periodic,Personal,Physical,Physiological,Piano,Poisonous,Political,Pond-Less,Pop,Popular,Positive,Postcolonial,Pre-Colonial,Pre-Modern,Pre-Practicum,Pre-Stressed,Preceptorial,Predicitve,Prehospital,Prenatal,Present,Pressurized,Primary,Principle,Private,Problem,Process,Professional,Programmable,Prokaryotic,Psychological,Psychosocial,Public,Quantitative,Quantum,Rain,Random,Real,Recreational,Regional,Reinforced,Related,Remote,Renewable,Residential,Restaurant,Rhetorical,Right,Roman,Romanesque,Romantic,Rural,Scenic,Scientific,Second,Secondary,Selected,Semantic,Sensory,Small,Smart,Social,Societal,Sociological,Solar,Solid,Special,Spectroscopic,Spring,Stagewise,State,Statistical,Strategic,Stressed,Structural,Sub-Saharan,Supervised,Supreme,Surgical,Sustainable,Symphonic,Technical,Technological,Technology-Related,Terrestrial,Theatrical,Therapeutic,Thermal,Third,Top,Torn,Tropical,Tudor,Twentieth,Twentieth-Century,Ubiquitous,Ugly,Uniform,United,Unusual,Urban,Vertebrate,Veterinary,Victorian,Virtual,Visual,Vocal,Water-Borne,Western,Winter,Woody,Workshop,World,Young");
    $subjectlist = explode(",","Analysis,Analytics,Anatomy,Anesthesia,Animals,Anthropology,Aquaculture,Arabic,Arboriculture,Archaeology,Architecture,Art,Aspects,Asphalt,Astronomy,Astrophysics,Athletics,Attention,Audiology,Autism,Avatars,Babies,Bacteriology,Baking,Ballet,Band,Banking,Barbarians,Bards,Basketball,Beaches,Behavior,Behaviorism,Being,Biochemistry,Biodiversity,Bioengineering,Biogeochemistry,Biogeography,Bioinformatics,Biology,Biomaterials,Biomechanics,Bionics,Biostatistics,Biotech,Biotechnology,Botany,Bureaucracy,Business,Calculus,Camelids,Canoeing,Capital,Capitalism,Careers,Catering,Centuries,Ceramics,Change,Channel,Chaos,Characters,Chaucer,Chekhov,Chemistry,Childhood,Children,Chinese,Choreography,Cinema,Circuits,Cities,Citizenship,Civilization,Climate,Climbing,Coasts,Cognition,Combinatorics,Combustion,Comedy,Communication,Communications,Communities,Community,Competency,Competition,Compiler,Compocinema,Composition,Computation,Computers,Computing,Concepts,Concert,Condition,Conditions,Conflicts,Conformation,Congress,Conquest,Consciousness,Conservation,Constitution,Construction,Consulting,Consumer/Buyer,Contemporaries,Contexts,Contracting,Control,Controversy,Convention,Conversation,Corrosion,Cosmology,Costume,Counseling,Counterpoint,Counterterrorism,Course,Courses,Courts,Creativity,Crime,Criminology,Criticism,Crusades,Cuisine,Culture,Cultures,Curriculum,Cyberculture,Cyborgs,Dali,Dancers,Darkroom,Data,Database,Deaf,Death,Decision,Decisions,Dehumanization,Deities,Delineation,Delinquency,Democracies,Democracy,Democrats,Demography,Dendrology,Descartes,Designs,Development,Devils,Diagnostics,Dialects,Dialogue,Dictators,Diction,Diet,Dietetics,Diffraction,Dining,Diplomacy,Dirt,Disabilities,Disability,Discoveries,Discovery,Disease,Diseases,Disorders,Display,Dissent,Diversity,Diving,DNA,Documentation,Dogs,Draft,Dragons,Drama,Dramatics,Drawing,Dreaming,Dreams,Dressage,Drug,Drugs,Drum,Dying,Dynamics,Dysfunction,E-Business,Ear,Earthquakes,Ecogastronomy,Ecohydrology,Ecology,Econometrics,Economics,Ecosystems,Ecotourism,Education,Educators,Electricity,Electrocardiography,Emergencies,Emigration,Empires,Employment,Endocrinology,Energy,Engineering,Engines,English,Enlightenment,Enrollment,Enterprises,Entomology,Entrepreneurship,Environment,Environments,Epidemics,Epidemiology,Epistemology,Equality,Equations,Equipment,Equity/Venture,Espionage,Estate,Ethics,Ethnicity,Euphonium,Europe,Evaluation,Event,Evolution,Exceptionalities,Exceptionality,Exercise,Existentialism,Experiment,Expository,Extinction,Eye,Eyes,Facilitation,Facilities,Facility,Families,Family,Farm,Fate,Fatigue,Faulting,Feature,Features,Feminism,Feminist,Feminists,Festival,Fiber,Fiction,Fields,Film,Finance,Fire,Fish,Fisheries,Fishes,Fishing,Fitness,Flow,Flower,Fluid,Fluids,Flute,Folklife,Folklore,Food,Football,Forage,Forecasting,Forensics,Forestland,Forestry,Fort,Fossil,Fossils,Foundation,Foundations,Fracture,Framing,France,Franchising,Francophone,Frankenstein,Freedom,French,Frog,Fruit,Fuels,Function,Functions,Fund,Fundraising,Game,Games,Garden,Gauguin,Gender,Genes,Genetics,Genius,Genomics,Genres,Geochemistry,Geodesy,Geodynamics,Geography,Geology,Geometry,Geophysics,Geotectonics,Germ,Germs,Globalization,Goggles,Government,Grammar,Grant,Grantsmanship,Graphics,Grassland,Greek,Greenhouse,Grounds,Groundwater,Guilt,Guitar,Gymnastics,Habitat,Habitats,Happiness,Harassment,Hardware,Hazards,Headlines,Healing,Health,Heat,Hematology,Herbs,Herd,Heretics,Hertiage,Histology,Historians,Histories,Historiography,History,Hittite,Hockey,Home,Homer,Homicide,Horsemanship,Horses,Hospitality,Houses,Humanities,Humanity,Hungarian,Hydrodynamics,Hydrology,Ichthyology,Ideas,Identification,Identities,Ideology,Immersion,Immigration,Immunohematology,Immunology,Inclusion,Income,Indian,Individuals,Industry,Inequality,Infancy,Inference,Information,Infrastructure,Injuries,Innovation,Insects,Institutions,Instruction,Instrumentation,Instruments,Intaglio,Intelligence,Interaction,Interactions,Interest,Interpretation,Intervention,Interventions,Inventory,Invertebrates,Investment,Investments,Irish,Irrigation,Isotope,Italian,Japanese,Jazz,Journal,Journalism,Judgment,Justice,Kant,Kayaking,Kinesiology,Kinetics,King,Kingdom,Labor,Landforms,Landscape,Language,Languages,Latin,Law,Leadership,Learners,Learning,Lectures,Leisure,Leonardo,Liberties,Liberty,Life,Lifespan,Lighting,Linearity,Linguistics,Literacy,Literature,Literatures,Lithography,Lives,Livestock,Lodging,Logging,Logic,Loss,Madness,Magnetism,Maintenance,Makeover,Makeup,Mammalogy,Management,Manufacturing,Marching,Marketing,Markets,Masonry,Masterpieces,Materials,Mathematics,Matter,Meaning,Measurement,Measurements,Mechanics,Mechanisms,Mechanization,Media,Mediation,Medicine,Mentoring,Metabolism,Metallurgy,Metaphysics,Microbes,Microbiology,Microfluidics,Micronutrients,Microprocessors,Microscopy,Mildews,Milton,Mind,Mine,Mineralogy,Mining,Modalities,Models,Modernism,Modernization,Molds,Molecules,Mona Lisa,Money,Mood,Morality,Morphology,Motherhood,Movement,Mozart,Museum,Mushrooms,Music,Mycology,Myth,Mythology,Nanoscience,Nature,Navigation,Neotropics,Networks,Neurobiology,Neurology,Neuroscience,News,Nonfiction,Norms,Novel,Number,Nursing,Nutrition,Obesity,Occupation,Occupations,Oceanography,Opera,Operation,Operations,Opinion,Opportunities,Opportunity,Optics,Orchestration,Order,Organization,Organizations,Origins,Ornithology,Oxen,Oxygen,Painting,Paleoclimatology,Paleontology,Paradise,Parasites,Parasitology,Parenting,Participation,Parties,Pastry,Pathologies,Pathology,Paths,Pathways,Pavement,Peace,Pedagogy,People,Peoples,Perception,Percussion,Performance,Periodicals,Periods,Persistence,Personality,Persons,Perspective,Perspectives,Persuasion,Persuasive,Pet,Petrology,Pharmacology,Philosophies,Philosophy,Phlebotomy,Phonetics,Phonology,Photography,Physics,Physiology,Pioneers,Pirates,Place,Plants,Playwriting,Poetry,Pointe,Policies,Policy,Politics,Pollution,Population,Populations,Portuguese,Post-Modernism,Pots,Poultry,Poverty,Power,Powers,Prevention,Pricing,Primates,Printmaking,Privilege,Probability,Problems,Processes,Production,Products,Professions,Profits,Programs,Projects,Promotion,Proof,Propaganda,Propagation,Properties,Prose,Protection,Proteomics,Protest,Psychobiology,Psychology,Puppetry,Pursuits,Reading,Reason,Reasoning,Rebellion,Recreation,Reform,Region,Regression,Regulation,Regulations,Rehabilitation,Relations,Relationships,Relativity,Republics,Rescue,Research,Researchers,Resiliency,Resources,Responses,Responsibility,Revenue,Revolution,Revolutions,Rhetorics,Riding,Rights,Risk,Risks,Ritual,Roads,Robots,Roles,Romance,Romanticism,Roots,Rounds,Ruminants,Russian,Safety,Sales,Sanitation,Sanskrit,Schools,Scientists,Seamanship,Securities,Sedimentology,Selection,Sensation,Sense,Sensibility,Separation,Serology,Services,Settings,Shakespeare,Signals,Silviculture,Singers,Skills,Societies,Society,Sociolinguistics,Sociology,Software,Soils,Solids,Solutions,Sources,Spanish,Species,Specifications,Spectacle,Speeches,Sports,Stagecraft,States,Statistics,Status,Stereotypes,Stories,Story,Storytelling,Strategy,Strength,Stress,Structure,Structures,Struggle,Students,Supplements,Support,Surveying,Survival,Sustainability,Symmetry,Syntax,Systems,Talk,Tasks,Taste,Taxes,Teachers,Teamwork,Technicians,Techniques,Technologies,Tests,Texts,Themes,Theories,Theory,Therapies,Therapy,Thermodynamics,Thinking,Thought,Tides,Time,Times,Tissue,Tools,Topics,Topology,Tourism,Toxicology,Traditions,Tragedy,Translation,Transmission,Transportation,Travel,Treatment,Trees,Trials,Tropics,Truths,Urbanization,Use,Uses,Utopia,Values,Verification,Vertebrates,Vibration,Violence,Virology,Visions,Visualization,Voices,Volcanology,Volunteers,Voters,Waves,Ways,Wealth,Weather,Weight,Welding,Welfare,Well-Being,Wellness,Wetlands,Wine,Furniture,Woodworking,Work,Workshops,Worlds,Writers,Youth,Zoology" );
    $modlist = explode(",", "Instrumental Methods of,Properties and Production of,Design of,Behavior of,Dynamics of,Ethics of,Theory of,Evaluation of,Interactive,History of,Philosophy of,Principles of,Applications of,Analysis of,Mechanics of,Persuasion in,Psychology of");
    $modnounlist = explode(",","Action,Adventure,Air,Angle,Animal,Asset,Backcountry,Bass,Bassoon,Beverage,Blood,Brain,Brass,Bridge,Camp,Cancer,Care,Career,Catastrophe,Cattle,Cell,Chamber,Character,Child,Choir,Clarinet,Club,Combat,Community,Computer,Conflict,Content,Cooking,Cost,Court,Courtroom,Criminal,Cycle,Dance,Earth,Earthquake,Economy,Electron,Element,Emergency,Empire,Ensemble,Forest,Freshman,Freshwater,Future,Horn,Horse,Host-Microbe,Human,Human-Computer,Human-Environment,Human/Animal,Ice,Idea,Identity,Illness,Image,Injury,Internet,Lake,Land,Lifetime,Lobster,Locavore,Machine,Mammal,Market,Matrix,Microbiome,Microcomputer,Military,Motor,Multimedia,Nation,Network,Newt,Oboe,Pest,Plague,Planet,Plant,Plant-Animal,Plant-Microbe,Plasma,Play,Police,Pollock,Prescription,Presidency,Product,Profession,Protein,Quality,Quantity,Renaissance,Republic,Resort,Resource,Responder,Resume,Reward,Rock,Room,Rope,Ruminant,Satellite,Saxophone,Scene,School,Scuba,Sculpture,Sea,Security,Seed,Self,Service,Shop,Sign,Signal,Silver,Soil,Song,Soprano,Space,Spectrum,Speech,Sport,Spreadsheet,Stage,Steel,Stormwater,Stream,String,Summer,Swine,Symphony,System,Table,Tax,Teacher,Team,Technician,Technology,Theatre,Timber,Toe,Tour,Track,Transport,Tree,Trial,Trombone,Trumpet,Tuba,Turf,Underwater,University,Value,Vegetable,Video,Viola,Violin,Violoncello,Voice,Waste,Water,Watershed,Web,Weekend,Wetland,Wilderness,Wildlife,Wind,Woodwind,Worker,Workplace");
    $postfixlist = explode (",","Abundance,Acquisition,Administration,Advocacy,Affairs,Analysis,Application,Applications,Approach,Approaches,Argumentation,Articulation,Arts,Assessment,Assurance,Audiences,Auditing,Authors,Awareness,Based,Basis,Baseline,Case,Century,Challenge,Classroom,Continuing,Country,Creation,Description,Design,Directed,Emergence,Enhancing,Evolution,Examination,Examining,Excursions,Execution,Expedition,Experience,Experiences,Experiments,Exploration,Explorations,Exploring,Factors,Field,Fieldwork,Formulating,Fundamentals,Impact,Improvement,Insights,Installation,Integration,Investigations,Involvement,Issues,Lab,Laboratory,Lecture,Method,Methodology,Methods,Misconceptions,Misuse,Mitigation,Mixtures,Moving,Myths,Needs,Observation,Paradigm,Paradigms,Peopling,Practical,Practice,Practices,Practicum,Preparation,Principles,Program,Pursuit,Readings,Restoration,Review,Science,Sciences,Simulation,Speakers,Statement,Views,Works");
    $levellist = explode(",","I,II,III,IV");
    $gerundlist = explode(",", "Abstracting,Accepting,Adopting,Analyzing,Applying,Approaching,Attending to,Attributing,Balancing,Becoming Aware of,Budgeting,Building,Calssifying,Carlifying,Carrying out,Categorizing,Characterizing,Checking,Coaching,Committing to,Comparing,Computing,Conceptualizing,Concluding,Conducting,Constructing,Constructing models,Contrasting,Coordinating,Creating,Critiquing,Deconstructing,Designing,Detecting,Developing,Differentiating,Directing,Discriminating,Distinguishing,Editing,Educating,Enabling,Evaluating,Executing,Exemplifying,Explaining,Extrapolating,Finding Coherence,Focusing,Generalizing,Generating,Handling,Harvesting,Hearing,Hypothesizing,Identifying,Illustrating,Imaging,Implementing,Inferring,Instantiating,Integrating,Interpolating,Interpreting,Investigating,Judging,Living,Making,Managing,Mapping,Marketing,Matching,Modeling,Monitoring,Opening,Operating,Organizing,Outlining,Paraphrasing,Parsing,Planning,Positioning,Predicting,Preferring,Processing,Producing,Programming,Pruning,Recalling,Recognizing,Reflecting,Remaking,Remembering,Rendering,Reporting,Representing,Resolving,Responding,Responding to,Retrieving,Sampling,Scripting,Selecting,Sensing,Structuring,Subsuming,Summarizing,Supervising,Supporting,Sustaining,Taking,Teaching,Testing,Thinking,Threading,Training,Transforming,Translating,Tutoring,Understanding,Using,Valuing,Winning,Writing");
    $majorlist = explode(",","Engineering and Physical Science,Bioengineering,Chemical Engineering,Chemistry,Civil Engineering,Computer Science,Earth Science,Electrical and Computer Engineering,Environmental Engineering,Information Technology,Integrated Applied Mathematics,Materials Science,Mathematics and Statistics,Mechanical Engineering,Ocean Engineering,Physics,Technology,Liberal Arts,Anthropology,Arabic,Art and Art History,Chinese,Classics,Communication,Education,English,French,Geography,German,Greek,History,Humanities,Italian Studies,Japanese,Justice Studies,Latin,Linguistics,Music,Music Education,Philosophy,Political Science,Portuguese,Psychology,Russian,Sociology,Spanish,Theatre and Dance,Life Sciences and Agriculture,Animal Science,Biochemistry,Molecular and Cellular Biology,Biology,Biomedical Science,Community and Environmental Planning,Environmental and Resource Economics,Environmental Conservation and Sustainability,Environmental Science,Equine Studies,Forestry,Genetics,Life Sciences and Agriculture,Marine Biology,Natural Resources,Neuroscience and Behavior,Nutrition,Sustainable Agriculture and Food Systems,Tourism,Wildlife and Conservation Biology,Zoology,Health and Human Services,Athletic Training,Communication Sciences and Disorders,Health Management and Policy,Human Development and Family Studies,Kinesiology,Nursing,Occupational Therapy,Recreation Management and Policy,Social Work,Applied Science,Agricultural Mechanization,Applied Animal Science,Applied Business Management,Civil Technology,Community Leadership,Culinary Arts and Nutrition,Forest Technology,Horticultural Technology,Integrated Agriculture Management,Veterinary Technology,Business and Economics,Accounting and Finance,Business Administration,Decision Science,Ecogastronomy,Economics,Hospitality Management,Management,Marketing");

    
    $maxcount = 1;
     do {
    
     
     $id = $subjectlist[rand(0,count($subjectlist)-1)];
     $maxcount = $maxcount * count($subjectlist);
     
     if (rand(0,1)>(($len - $maxcount)/$len)*.25) {
     //insert adjective
     $id = $modnounlist[rand(0,count($modnounlist)-1)] . " " . $id;
     		 $maxcount =  $maxcount * count($modnounlist);
     }

     if (rand(0,1)>(($len - $maxcount)/$len)*.25) {
     //insert adjective
     $id = $adjlist[rand(0,count($adjlist)-1)] . " " . $id;
     		 $maxcount =  $maxcount * count($adjlist);
     }

     if (rand(0,1)>(($len - $maxcount)/$len)*.25) {
     		 // prepend modifier
    //$id = $modlist[rand(0,count($modlist))];
     $id = $modlist[rand(0,count($modlist)-1)] . " " . $id;
    		 $maxcount =  $maxcount * count($modlist);
     }

     if (rand(0,1)>(($len - $maxcount)/$len)*.25) {
     // prepend level prefix
      $id = $prefixlist[rand(0,count($prefixlist)-1)] . " " . $id;
    		 $maxcount =  $maxcount * count($prefixlist);
     }
    
     
     if (rand(0,1)>(($len - $maxcount)/$len)*.25) {
     		 // combo courses
     		 $id = $id . " and " . $subjectlist[rand(0,count($subjectlist)-1)];
     		 $maxcount =  $maxcount * count($subjectlist);
     }
     
     if (rand(0,1)>(($len - $maxcount)/$len)*.25) {
     //posftix
       $id = $id  . " " . $postfixlist[rand(0,count($postfixlist)-1)];
     		 $maxcount =  $maxcount * count($postfixlist);
     }
          
/*     if (rand(0,1)>(($len - $maxcount)/$len)*100000000000000000) {
     		 // for subject majors
     		 $id = $id  . " for " . $majorlist[rand(0,count($majorlist)-1)] . " Majors";
     		 $maxcount =  $maxcount * count($majorlist);
     }
 */    
     if (rand(0,1)>(($len - $maxcount)/$len)*100000) {
    //level I II III IV
       $id = $id  . " " . $levellist[rand(0,count($levellist)-1)];
     		 $maxcount =  $maxcount * count($levellist);
     }
     if (rand(0,1)>(($len - $maxcount)/$len)) {
     // prepend gerund
      $id = $gerundlist[rand(0,count($gerundlist)-1)] . " " . $id;
    		 $maxcount =  $maxcount * count($gerundlist);
     }

          
 
    // add modulus (as string) to end of string if there is anything left
  if ($len > $maxcount) {
      $id = $id . " " . substr(strval($len-$maxcount),-3);
  } // if
     } while (array_search($id, $usedpseudoids) !== false);
    $usedpseudoids[] = $id;
    return $id;
}

function assign_serial_pseudo_id($len) {
    // rather than just assigning a random string of junk,
    // this algorithm assembles a phrase string consisting of serialized words in base 26
    // accept a length parameter to determine how long the phrase needs to be for uniqueness
    // 1 word: animal 26 possibilities
    // 2 words: animal with fruit 26*26 = 676
    // 3 words: color animal with fruit 26*676 = 17,576
    // 4 words: adjective, color, animal with fruit 26*17576 = 456,976
    // 5 words: verb, adjective, color, animal with fruit 26*456976 = 11,881,376
    // 6 words: adverb, verb, adjective, color, animal with fruit 26*11881376 = 308,915,776
    // 7 words: adverb, verb, adjective, color, animal with fruit and vegetable 26*308915776 = 8,031,810,176
    // if > 7, add a number at the end of the string containing modulus
    // Keep track of used IDs during the running of the script.
    static $usedpseudoids = array();
     do {
    
    // pick animal//
    // 1 word: animal 26 possibilities
    $thisrand = rand(1,26);
    switch ($thisrand) {
        case 1: 
            $id = "Armadillos";
            break;
        case 2:
            $id = "Buffaloes";
                break;         
        case 3:
            $id = "Cats";
            break;
        case 4:
            $id = "Dogs";
            break;
        case 5:
            $id = "Elephants";
            break;
        case 6:
            $id = "Foxes";
            break;
        case 7:
            $id = "Giraffes";
            break;
        case 8:
            $id = "Horses";
            break;
        case 9:
            $id = "Iguanas";
            break;
        case 10:
            $id = "Jaguars";
            break;
        case 11:
            $id = "Koalas";
            break;
        case 12:
            $id = "Leopards";
            break;
        case 13:
            $id = "Monkeys";
            break;
        case 14:
            $id = "Nightingales";
            break;
        case 15:
            $id = "Ostriches";
            break;
        case 16:
            $id = "Penguins";
            break;
        case 17:
            $id = "Quails";
            break;
        case 18:
            $id = "Rhinoceros";
            break;
        case 19:
            $id = "Sharks";
            break;
        case 20:
            $id = "Turtles";
            break;
        case 21:
            $id = "Unicorns";
            break;
        case 22:
            $id = "Vultures";
            break;
        case 23:
            $id = "Whales";
            break;
        case 24:
            $id = "Xeruses";
            break;
        case 25:
            $id = "Yaks";
            break;
        case 26:
            $id = "Zebras";
            break;
    } //switch
     
          
    // add "with" and fruit//
    // 2 words: animal with fruit 26*26 = 676
  if ($len > 26) { 
    $thisrand = rand(1,26);
    switch ($thisrand) {
        case 1: 
            $id = $id . " with " . "Apples";
            break;
        case 2:
            $id = $id . " with " . "Bananas";
                break;         
        case 3:
            $id = $id . " with " . "Cherries";
            break;
        case 4:
            $id = $id . " with " . "Dates";
            break;
        case 5:
            $id = $id . " with " . "Elderberries";
            break;
        case 6:
            $id = $id . " with " . "Figs";
            break;
        case 7:
            $id = $id . " with " . "Grapes";
            break;
        case 8:
            $id = $id . " with " . "Honeydew";
            break;
        case 9:
            $id = $id . " with " . "Inga";
            break;
        case 10:
            $id = $id . " with " . "Jackfruit";
            break;
        case 11:
            $id = $id . " with " . "Kumquats";
            break;
        case 12:
            $id = $id . " with " . "Lemons";
            break;
        case 13:
            $id = $id . " with " . "Mangoes";
            break;
        case 14:
            $id = $id . " with " . "Nectarines";
            break;
        case 15:
            $id = $id . " with " . "Oranges";
            break;
        case 16:
            $id = $id . " with " . "Papayas";
            break;
        case 17:
            $id = $id . " with " . "Quinces";
            break;
        case 18:
            $id = $id . " with " . "Raspberries";
            break;
        case 19:
            $id = $id . " with " . "Strawberries";
            break;
        case 20:
            $id = $id . " with " . "Tangerines";
            break;
        case 21:
            $id = $id . " with " . "Ugni";
            break;
        case 22:
            $id = $id . " with " . "Vanilla";
            break;
        case 23:
            $id = $id . " with " . "Watermelon";
            break;
        case 24:
            $id = $id . " with " . "Ximenia";
            break;
        case 25:
            $id = $id . " with " . "Yangmei";
            break;
        case 26:
            $id = $id . " with " . "Zucchini";
            break;
    } //switch
  } //if
    
    // prepend color
    // 3 words: color animal with fruit 26*676 = 17,576
  if ($len > 676) { 
    $thisrand = rand(1,26);
    switch ($thisrand) {
        case 1: 
            $id = "Amber" . " " . $id;
            break;
        case 2:
            $id = "Blue" . " " . $id;
                break;         
        case 3:
            $id = "Celadon" . " " . $id;
            break;
        case 4:
            $id = "Damask" . " " . $id;
            break;
        case 5:
            $id = "Ecru" . " " . $id;
            break;
        case 6:
            $id = "Fuchsia" . " " . $id;
            break;
        case 7:
            $id = "Grey" . " " . $id;
            break;
        case 8:
            $id = "Heliotrope" . " " . $id;
            break;
        case 9:
            $id = "Indigo" . " " . $id;
            break;
        case 10:
            $id = "Jade" . " " . $id;
            break;
        case 11:
            $id = "Khaki" . " " . $id;
            break;
        case 12:
            $id = "Lavender" . " " . $id;
            break;
        case 13:
            $id = "Maroon" . " " . $id;
            break;
        case 14:
            $id = "Navy" . " " . $id;
            break;
        case 15:
            $id = "Ochre" . " " . $id;
            break;
        case 16:
            $id = "Platinum" . " " . $id;
            break;
        case 17:
            $id = "Quartz" . " " . $id;
            break;
        case 18:
            $id = "Ruby" . " " . $id;
            break;
        case 19:
            $id = "Saffron" . " " . $id;
            break;
        case 20:
            $id = "Teal" . " " . $id;
            break;
        case 21:
            $id = "Ultraviolet" . " " . $id;
            break;
        case 22:
            $id = "Violet" . " " . $id;
            break;
        case 23:
            $id = "White" . " " . $id;
            break;
        case 24:
            $id = "Xanthic" . " " . $id;
            break;
        case 25:
            $id = "Yellow" . " " . $id;
            break;
        case 26:
            $id = "Zaffre" . " " . $id;
            break;
    } //switch
  } // if
    
    // prepend adjective
    // 4 words: adjective, color, animal with fruit 26*17576 = 456,976
  if ($len > 17576) { 
    $thisrand = rand(1,26);
    switch ($thisrand) {
        case 1: 
            $id = "Able" . " " . $id;
            break;
        case 2:
            $id = "Brainy" . " " . $id;
                break;         
        case 3:
            $id = "Cheerful" . " " . $id;
            break;
        case 4:
            $id = "Diligent" . " " . $id;
            break;
        case 5:
            $id = "Eccentric" . " " . $id;
            break;
        case 6:
            $id = "Fiery" . " " . $id;
            break;
        case 7:
            $id = "Gallant" . " " . $id;
            break;
        case 8:
            $id = "Humble" . " " . $id;
            break;
        case 9:
            $id = "Idyllic" . " " . $id;
            break;
        case 10:
            $id = "Jovial" . " " . $id;
            break;
        case 11:
            $id = "Kinetic" . " " . $id;
            break;
        case 12:
            $id = "Lithe" . " " . $id;
            break;
        case 13:
            $id = "Mellow" . " " . $id;
            break;
        case 14:
            $id = "Nimble" . " " . $id;
            break;
        case 15:
            $id = "Orderly" . " " . $id;
            break;
        case 16:
            $id = "Poetic" . " " . $id;
            break;
        case 17:
            $id = "Quirky" . " " . $id;
            break;
        case 18:
            $id = "Radical" . " " . $id;
            break;
        case 19:
            $id = "Shiny" . " " . $id;
            break;
        case 20:
            $id = "Thrifty" . " " . $id;
            break;
        case 21:
            $id = "Ultimate" . " " . $id;
            break;
        case 22:
            $id = "Vibrant" . " " . $id;
            break;
        case 23:
            $id = "Winsome" . " " . $id;
            break;
        case 24:
            $id = "Xeric" . " " . $id;
            break;
        case 25:
            $id = "Yogic" . " " . $id;
            break;
        case 26:
            $id = "Zesty" . " " . $id;
            break;
    } //switch
  }// if
    // prepend verb
    // 5 words: verb, adjective, color, animal with fruit 26*456976 = 11,881,376
  if ($len > 456976) { 
    $thisrand = rand(1,26);
    switch ($thisrand) {
        case 1: 
            $id = "Activating" . " " . $id;
            break;
        case 2:
            $id = "Blending" . " " . $id;
                break;         
        case 3:
            $id = "Creating" . " " . $id;
            break;
        case 4:
            $id = "Developing" . " " . $id;
            break;
        case 5:
            $id = "Educating" . " " . $id;
            break;
        case 6:
            $id = "Forming" . " " . $id;
            break;
        case 7:
            $id = "Grouping" . " " . $id;
            break;
        case 8:
            $id = "Honoring" . " " . $id;
            break;
        case 9:
            $id = "Instantiating" . " " . $id;
            break;
        case 10:
            $id = "Joining" . " " . $id;
            break;
        case 11:
            $id = "Kindling" . " " . $id;
            break;
        case 12:
            $id = "Lassoing" . " " . $id;
            break;
        case 13:
            $id = "Moderating" . " " . $id;
            break;
        case 14:
            $id = "Naming" . " " . $id;
            break;
        case 15:
            $id = "Ordering" . " " . $id;
            break;
        case 16:
            $id = "Pacifying" . " " . $id;
            break;
        case 17:
            $id = "Qualifying" . " " . $id;
            break;
        case 18:
            $id = "Renewing" . " " . $id;
            break;
        case 19:
            $id = "Sampling" . " " . $id;
            break;
        case 20:
            $id = "Teaching" . " " . $id;
            break;
        case 21:
            $id = "Understanding" . " " . $id;
            break;
        case 22:
            $id = "Verifying" . " " . $id;
            break;
        case 23:
            $id = "Winning" . " " . $id;
            break;
        case 24:
            $id = "Xenografting" . " " . $id;
            break;
        case 25:
            $id = "Yoking" . " " . $id;
            break;
        case 26:
            $id = "Zeroing" . " " . $id;
            break;
    } //switch
  } //if
    
    // 6 words: adverb, verb, adjective, color, animal with fruit 26*11881376 = 308,915,776
  if ($len > 11881376) { 
    $thisrand = rand(1,26);
    switch ($thisrand) {
        case 1: 
            $id = "Absolutely" . " " . $id;
            break;
        case 2:
            $id = "Brilliantly" . " " . $id;
                break;         
        case 3:
            $id = "Charismatically" . " " . $id;
            break;
        case 4:
            $id = "Deeply" . " " . $id;
            break;
        case 5:
            $id = "Excellently" . " " . $id;
            break;
        case 6:
            $id = "Fabulously" . " " . $id;
            break;
        case 7:
            $id = "Graphically" . " " . $id;
            break;
        case 8:
            $id = "Honestly" . " " . $id;
            break;
        case 9:
            $id = "Intently" . " " . $id;
            break;
        case 10:
            $id = "Justly" . " " . $id;
            break;
        case 11:
            $id = "Keenly" . " " . $id;
            break;
        case 12:
            $id = "Legitimately" . " " . $id;
            break;
        case 13:
            $id = "Mostly" . " " . $id;
            break;
        case 14:
            $id = "Nearly" . " " . $id;
            break;
        case 15:
            $id = "Oddly" . " " . $id;
            break;
        case 16:
            $id = "Perfectly" . " " . $id;
            break;
        case 17:
            $id = "Quaintly" . " " . $id;
            break;
        case 18:
            $id = "Really" . " " . $id;
            break;
        case 19:
            $id = "Sharply" . " " . $id;
            break;
        case 20:
            $id = "Truly" . " " . $id;
            break;
        case 21:
            $id = "Utterly" . " " . $id;
            break;
        case 22:
            $id = "Very" . " " . $id;
            break;
        case 23:
            $id = "Wholly" . " " . $id;
            break;
        case 24:
            $id = "Xtremely" . " " . $id;
            break;
        case 25:
            $id = "Yearly" . " " . $id;
            break;
        case 26:
            $id = "Zealously" . " " . $id;
            break;
    } //switch
  } //if
    
    // 7 words: adverb, verb, adjective, color, animal with fruit and vegetable 26*308915776 = 8,031,810,176
  if ($len > 308915776) {
      $thisrand = rand(1,26);
    switch ($thisrand) {
        case 1: 
            $id = $id . " and " . "Artichokes";
            break;
        case 2:
            $id =  $id . " and " . "Beets";
                break;         
        case 3:
            $id =  $id . " and " . "Celery";
            break;
        case 4:
            $id =  $id . " and " . "Daikon";
            break;
        case 5:
            $id =  $id . " and " . "Eggplant";
            break;
        case 6:
            $id =  $id . " and " . "Fennel";
            break;
        case 7:
            $id =  $id . " and " . "Garlic";
            break;
        case 8:
            $id =  $id . " and " . "Horseradish";
            break;
        case 9:
            $id =  $id . " and " . "Ivy";
            break;
        case 10:
            $id =  $id . " and " . "Jícama";
            break;
        case 11:
            $id =  $id . " and " . "Kale";
            break;
        case 12:
            $id =  $id . " and " . "Lettuce";
            break;
        case 13:
            $id =  $id . " and " . "Mustard";
            break;
        case 14:
            $id =  $id . " and " . "Napa";
            break;
        case 15:
            $id =  $id . " and " . "Okra";
            break;
        case 16:
            $id =  $id . " and " . "Parsnip";
            break;
        case 17:
            $id =  $id . " and " . "Quandong";
            break;
        case 18:
            $id =  $id . " and " . "Radicchio";
            break;
        case 19:
            $id =  $id . " and " . "Shallots";
            break;
        case 20:
            $id =  $id . " and " . "Turnips";
            break;
        case 21:
            $id =  $id . " and " . "Ulluco";
            break;
        case 22:
            $id =  $id . " and " . "Vegetables";
            break;
        case 23:
            $id =  $id . " and " . "Watercress";
            break;
        case 24:
            $id =  $id . " and " . "Xocolatl";
            break;
        case 25:
            $id =  $id . " and " . "Yams";
            break;
        case 26:
            $id =  $id . " and " . "Ziti";
            break;
    } //switch
  } //if
    // add modulus (as string) to end of string if there is anything left
  if ($len > 8031810176) {
      $id = $id . " " . strval($len-8031810176);
  } // if
     } while (array_search($id, $usedpseudoids) !== false);
    $usedpseudoids[] = $id;
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
        'user' => array('address', 'aim', 'alternatename', 'city', 'department', 'email', 'firstname', 'firstnamephonetic', 'icq', 'idnumber', 'imagealt', 'institution', 'lastip', 'lastname', 'lastnamephonetic', 'middlename', 'msn', 'password', 'phone1', 'phone2', 'secret', 'skype', 'url', 'yahoo'),
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

function get_activity_name_fields() {
    $activities = array(
        'assign' => array('name'),
        'assignment' => array('name'),
        'book' => array('name'),
        'chat' => array('name'),
        'choice' => array('name'),
        'data' => array('name'),
        'feedback' => array('name'),
        'folder' => array('name'),
        'forum' => array('name'),
        'glossary' => array('name'),
        'imscp' => array('name'),
        'label' => array('name'),
        'lesson' => array('name'),
        'lti' => array('name'),
        'page' => array('name'),
        'quiz' => array('name'),
        'resource' => array('name'),
        'scorm' => array('name'),
        'survey' => array('name'),
        'url' => array('name'),
        'wiki' => array('name'),
        'workshop' => array('name'),
    );

    foreach ($activities as $tablename => $columns) {
        $activities[$tablename] = array_combine($columns, $columns);
    }

    return $activities;

}
