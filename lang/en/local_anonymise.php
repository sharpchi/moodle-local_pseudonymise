<?php
// This file is part of the mee_cycles plugin for Moodle
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
 * @package    local_anonymise
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activities'] = 'Anonymise activities';
$string['anonymise'] = 'Anonymise';
$string['categories'] = 'Anonymise categories';
$string['courses'] = 'Anonymise courses';
$string['defaultdomain'] = 'example.com';
$string['defaultusercity'] = 'Perth';
$string['defaultusercountry'] = 'AU';
$string['done'] = 'Done';
$string['files'] = 'Anonymise files';
$string['includeadmin'] = 'Anonymise default administrator (except username and password)';
$string['includesite'] = 'Anonymise site home course';
$string['nodebuggingmaintenancemode'] = '<a href="{$a->debugging}" target="_blank">\'Debugging mode\'</a> setting must be set to \'DEVELOPER\' and <a href="{$a->maintenance}" target="_blank">\'Maintenance mode\'</a> should be enabled to run the anonymise plugin. This protects production sites from being changed unintentionally';
$string['nodebuggingmaintenancemodecli'] = '\'Debugging mode\' setting must be set to \'DEVELOPER\' and \'Maintenance mode\' should be enabled to run the anonymise plugin. This protects production sites from being changed unintentionally';
$string['password'] = 'plain password';
$string['pluginname'] = 'Anonymise';
$string['purgelink'] = 'purge caches';
$string['purgeprompt'] = 'In order to finalise the anonymisation, you should';
$string['resetpasswords'] = 'Reset passwords';
$string['others'] = 'Anonymise all other potentially sensitive contents (except activity names, only anonymised when \'Anonymise activities\' is selected)';
$string['users'] = 'Anonymise users';
$string['warning'] = '<strong>WARNING:</strong> This will alter data across your whole site. <br/><br/>The web interface is not recommended, this is a heavy process and it can get stucked if your site is big. Please, use the CLI interface (local/anonymise/cli/anonymise.php).';
