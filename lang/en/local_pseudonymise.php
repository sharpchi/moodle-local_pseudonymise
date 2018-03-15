<?php
// This file is based on part of the mee_cycles plugin for Moodle
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
 * @package    local_pseudonymise
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activities'] = 'Pseudonymise activities';
$string['anonymise'] = 'Pseudonymise';
$string['categories'] = 'Pseudonymise categories';
$string['courses'] = 'Pseudonymise courses';
$string['defaultdomain'] = 'example.com';
$string['defaultusercity'] = 'Perth';
$string['defaultusercountry'] = 'AU';
$string['done'] = 'Done';
$string['files'] = 'Pseudonymise files';
$string['includeadmin'] = 'Pseudonymise default administrator (except username and password)';
$string['includesite'] = 'Pseudonymise site home course';
$string['nodebuggingmaintenancemode'] = '<a href="{$a->debugging}" target="_blank">\'Debugging mode\'</a> setting must be set to \'DEVELOPER\' and <a href="{$a->maintenance}" target="_blank">\'Maintenance mode\'</a> should be enabled to run the pseuonymise plugin. This protects production sites from being changed unintentionally';
$string['nodebuggingmaintenancemodecli'] = '\'Debugging mode\' setting must be set to \'DEVELOPER\' and \'Maintenance mode\' should be enabled to run the pseudonymise plugin. This protects production sites from being changed unintentionally';
$string['password'] = 'plain password';
$string['pluginname'] = 'Pseudonymise';
$string['purgelink'] = 'purge caches';
$string['purgeprompt'] = 'In order to finalise the pseudonymisation, you should';
$string['resetpasswords'] = 'Reset passwords';
$string['others'] = 'Pseudonymise all other potentially sensitive contents (except activity names, only pseudonymised when \'Pseudonymise activities\' is selected)';
$string['users'] = 'Pseudonymise users';
$string['noncore'] = 'Delete non-core plugins and their data.';
$string['sensitive'] = 'Delete sensitive data.';
$string['warning'] = '<strong>WARNING:</strong> This will alter data across your whole site. <br/><br/>The web interface is not recommended, this is a heavy process and it can get stuck if your site is big. Please, use the CLI interface (local/pseudonymise/cli/pseudonymise.php).';
$string['settings'] = 'Pseudonymise settings';
$string['keepplugins'] = 'Keep non-core plugins';
$string['keeppluginsdesc'] = 'When deleting non-core plugins, still keep these ones. One plugin per line e.g. mod_questionnaire';
$string['excludetextcolumns'] = 'Exclude text columns';
$string['excludetextcolumnsdesc'] = 'Append these table => fieldname values to get_excluded_text_columns array. Insert as JSON.';
$string['updatevarchars'] = 'Update varchar fields';
$string['updatevarcharsdesc'] = 'Append these table => fieldname values to get_varchar_fields_to_update array. Insert as JSON.';
$string['namefields'] = 'Activity name fields';
$string['namefieldsdesc'] = 'Skip activity name fields, if required. Append the name fields from these activites to the exclusion list.<br/><small>(Hold ctrl to select more than one activity.)</small>';
$string['truncatetables'] = 'Truncate tables';
$string['truncatetablesdesc'] = 'This will delete all the content from the selected tables, but not the tables themselves.<br/><small>(Hold ctrl to select more than one activity.)</small>';
$string['runpseudonymise'] = 'Run Pseudonymise';
