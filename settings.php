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
 * course_overview_ext block settings
 *
 * @package    block_course_overview_ext
 * @copyright  2015 Nicolas Klenert <klenert@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// require_once ($CFG->dirroot .'/blocks/course_overview/settings.php'); //funktioniert nicht... -> gebe link an

global $CFG;

$link		= $CFG->wwwroot.'/admin/settings.php?section=blocksettingcourse_overview';
$string		= clean_param(get_config('block_course_overview_ext','colors'),PARAM_TEXT);
$bool 		= true;
$default = '#ffffff #f9f7f7 #dedede #787678 #edc1ad #db7e60 #fce0bb #f9c073 #c5d3cd #86aa9e #bdd3de #6ea8be';

//angenommen wir haben nur hex-code (unterstütze später noch rgb etc)
$arr = explode('#', $string);
unset($arr[0]);
$cleanarr = array();
//bereinige das array!
foreach($arr as $key => $str){
	$hex = substr($str, 0, 6);
	if(ctype_xdigit($hex)){
		$cleanarr[$key] = $hex;
	}else{
		$hex = substr($hex, 0, 3);
		if(ctype_xdigit($hex)){
			$cleanarr[$key] = $hex;
		}else{
			$bool = false;		//funktioniert nicht, da die Seite 2mal aufgerufen wird
		}
	}
}

//TODO: sortiere die Farben

//erzeuge den bereinigten string
$string = '';
foreach ($cleanarr as $str){
		$string .= ' #'.$str;		// ' #' sind die Trennzeichen für den Code
}
$string = ltrim($string);
set_config('colors',$string,'block_course_overview_ext');


defined('MOODLE_INTERNAL') || die;

if($ADMIN->fulltree) {
	$settings->add(new admin_setting_heading('block_course_overview_ext/basicsetting', new lang_string('basicsetting','block_course_overview_ext'), new lang_string('basicsettingdescr','block_course_overview_ext',$link)));
	
	$colordescr;
	if($bool){
		$colordescr = new lang_string('colorsdescr','block_course_overview_ext');
	}else{
		$colordescr = new lang_string('colorfail','block_course_overview_ext');
	}
	$settings->add(new admin_setting_configtextarea('block_course_overview_ext/colors', new lang_string('colors','block_course_overview_ext'), $colordescr, $default));
	
	$settings->add(new admin_setting_configcolourpicker('block_course_overview_ext/colorpicker', new lang_string('colorpicker','block_course_overview_ext'),
			new lang_string('colorpickerdescr','block_course_overview_ext'),null));
	
	$settings->add(new admin_setting_configtext('block_course_overview_ext/columns', new lang_string('columns','block_course_overview_ext'), new lang_string('columnsdescr','block_course_overview_ext'), '3'));
	
	$settings->add(new admin_setting_heading('block_course_overview_ext/moresetting', new lang_string('moresetting','block_course_overview_ext'), new lang_string('moresettingdescr','block_course_overview_ext',$link)));
}
