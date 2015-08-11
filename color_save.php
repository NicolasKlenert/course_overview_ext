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
 * Save course order in course_overview block
 *
 * @package    block_course_overview
 * @copyright  2015 Nicolas Klenert <klenert@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

require_sesskey();
require_login();

$courselist = required_param_array('courselist', PARAM_INT);
$colorlist = required_param_array('colorlist', PARAM_TEXT);
//$coursecolor = required_param('coursecolor', PARAM_STRING);

//testen ob colorlist auch nur hex-codes enthält.
foreach ($colorlist as $key => $color){
	if(!ctype_xdigit(ltrim($color,"#"))){
		//falls es kein hex-code ist, lösche den eintrag
		unset ($colorlist[$key]);
	}
}

block_course_overview_ext_update_coursecolor($courselist, $colorlist);
