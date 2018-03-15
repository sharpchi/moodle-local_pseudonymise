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
 * Add page to admin menu.
 *
 * @package    local_pseudonymise
 * @copyright  Gavin Henrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    $ADMIN->add('development', new admin_category('pseudonymise', get_string('pluginname', 'local_pseudonymise')));

    $page = new admin_settingpage('local_pseudonymise', get_string('settings', 'local_pseudonymise'));

    $setting = new admin_externalpage('local_pseudonymise_run',
            get_string('runpseudonymise', 'local_pseudonymise'),
            new moodle_url('/local/pseudonymise/index.php'));

    $ADMIN->add('pseudonymise', $setting);

    $alltables = array_combine($DB->get_tables(), $DB->get_tables());

    // Keep these plugins.
    $name = 'local_pseudonymise/keepplugins';
    $title = get_string('keepplugins', 'local_pseudonymise');
    $desc = get_string('keeppluginsdesc', 'local_pseudonymise');
    $default = '';
    $setting = new admin_setting_configtextarea($name,
                   $title, $desc, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Exclude text columns.
    $name = 'local_pseudonymise/excludetextcolumns';
    $title = get_string('excludetextcolumns', 'local_pseudonymise');
    $description = get_string('excludetextcolumnsdesc', 'local_pseudonymise');
    $default = json_encode(array(
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
    ));
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Show warning for hidden resources/activities.
    $name = 'local_pseudonymise/updatevarchars';
    $title = get_string('updatevarchars', 'local_pseudonymise');
    $description = get_string('updatevarcharsdesc', 'local_pseudonymise');
    $setting = new admin_setting_configtextarea($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $modules = $DB->get_records('modules', [], null, 'name');
    $activitytables = [];
    foreach($modules as $module) {
        $activitytables[$module->name] = $module->name;
    }
    $name = 'local_pseudonymise/namefields';
    $title = get_string('namefields', 'local_pseudonymise');
    $description = get_string('namefieldsdesc', 'local_pseudonymise');
    $default = ['assign', 'assignment', 'book', 'chat', 'choice', 'data',
        'feedback', 'folder', 'forum', 'glossary', 'imscp', 'label',
        'lesson', 'lti', 'page', 'quiz', 'resource', 'scorm', 'survey',
        'url', 'wiki', 'workshop'];
    $setting = new admin_setting_configmultiselect($name, $title, $description, $default, $activitytables);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $name = 'local_pseudonymise/truncatetables';
    $title = get_string('truncatetables', 'local_pseudonymise');
    $description = get_string('truncatetablesdesc', 'local_pseudonymise');
    $default = ['sessions', 'log', 'config_log',
        'portfolio_log', 'mnet_log', 'upgrade_log',
        'scorm_aicc_session', 'mnet_session', 'user_password_history',
        'user_password_resets', 'user_private_key'];
    $setting = new admin_setting_configmultiselect($name, $title, $description, $default, $alltables);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $ADMIN->add('pseudonymise', $page);
}
