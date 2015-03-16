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
 * Helper functions for course_overview block
 *
 * @package    block_course_overview
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Display overview for courses
 *
 * @param array $courses courses for which overview needs to be shown
 * @return array html overview
 */
function block_course_overview_ext_get_overviews($courses) {
    $htmlarray = array();
    if ($modules = get_plugin_list_with_function('mod', 'print_overview')) {
        // Split courses list into batches with no more than MAX_MODINFO_CACHE_SIZE courses in one batch.
        // Otherwise we exceed the cache limit in get_fast_modinfo() and rebuild it too often.
        if (defined('MAX_MODINFO_CACHE_SIZE') && MAX_MODINFO_CACHE_SIZE > 0 && count($courses) > MAX_MODINFO_CACHE_SIZE) {
            $batches = array_chunk($courses, MAX_MODINFO_CACHE_SIZE, true);
        } else {
            $batches = array($courses);
        }
        foreach ($batches as $courses) {
            foreach ($modules as $fname) {
                $fname($courses, $htmlarray);
            }
        	//ISIS2 Neue/veränderte Aktivitäten anzeigen $JE 2014/06/12
        	//ISIS2 Es ist wichtig, dass unsere Funktion NACH dem Aufruf von $fname ausgeführt wird!!!
        	//ISIS2 Ich baue in der Erzeugung der Einträge von $htmlarray, dass die Infos zu den Modulen schon aufgebaut sind.
        	//ISIS2 Update 02/09/2014: Bug - Ein(ige) Nutzer konnte(n) nicht mehr auf die my-Startseite zugreifen, sobald der Block
        	//ISIS2                    über die Navigation verschoben wurde (local/isis/lib.php wurde dann nicht eingebunden).
        	global $CFG; //ISIS2 Bugfix $JE 02/09/2014
        	if (file_exists($CFG->dirroot . '/local/isis/lib.php')) //ISIS2 Bugfix $JE 02/09/2014
        	{ //ISIS2 Bugfix $JE 02/09/2014
        		require_once $CFG->dirroot . '/local/isis/lib.php';
        		foreach ($courses as $course)
        			block_course_overview_ext_structural_changes($course, $htmlarray);
        	//ISIS2 Endfix $JE 2014/06/12
        	} //ISIS2 Bugfix $JE 02/09/2014
        }
    }
    return $htmlarray;
}

/**
 * Bis wohin soll das log in der Kursübersicht durchgesucht werden?
 *
 * @param int $courseid
 *
 * @see block_recent_activity::get_timestart()
 * @package block_course_overview
 * @author Jan Eberhardt <eberhardt@math.tu-berlin.de>
 */
function block_course_overview_ext_get_timestart($courseid = SITEID)
{
	global $USER, $DB;

	switch (get_user_preferences("course_overview_timestart_for_log", 0)) {
		case 1:
			$timestart = strtotime("now -1 day");
			break;
		case 2:
			$timestart = strtotime("now -1 week");
			break;
		case 3:
			$timestart = strtotime("now -1 month");
			break;
		case 4:
			$timestart = $USER->lastlogin;
			break;

		default:
			$timestart = round(time() - COURSE_MAX_RECENT_PERIOD, -2);
			if (!isguestuser() && !empty($USER->lastcourseaccess[$courseid])) {
				$timestart = $DB->get_field("logstore_standard_log",
						"MAX(timecreated)",
						array("userid" => $USER->id,
								"contextinstanceid" => (int)$courseid,
								"contextlevel" => CONTEXT_COURSE)); //durch Verwendung des Kontextes nutzen wir einen Index aus
				if ($USER->lastcourseaccess[$courseid] > $timestart) {
					$timestart = $USER->lastcourseaccess[$courseid];
				}
			}
	}

	return $timestart;
}

/**
 * Findet die neuesten Aktivitäten / Aktivitätsupdates eines Kurses
 *
 * @param stdClass $course
 * @param array<string> $htmlarray Änderungen werden dort abgelegt
 *
 * @package block_course_overview
 * @see block_recent_activity::get_structural_changes()
 * @author Jan Eberhardt <eberhardt@math.tu-berlin.de>
 */
function block_course_overview_ext_structural_changes($course, &$htmlarray)
{
	global $USER, $DB;

	$context = context_course::instance($course->id);
	$canviewdeleted = has_capability('block/recent_activity:viewdeletemodule', $context);
	$canviewupdated = has_capability('block/recent_activity:viewaddupdatemodule', $context);
	if (!$canviewdeleted && !$canviewupdated) {
		return;
	}

	$sql = "SELECT
	cmid, MIN(action) AS minaction, MAX(action) AS maxaction, MAX(modname) AS modname, MAX(timecreated) AS timemodified
	FROM {block_recent_activity}
	WHERE timecreated > :tc AND courseid = :cid
	GROUP BY cmid
	ORDER BY timemodified ASC";
	$params = array("tc" => block_course_overview_ext_get_timestart($course->id), "cid" => $course->id);
	$logs = $DB->get_records_sql($sql, $params);

	if (isset($logs[0])) {
		// If special record for this course and cmid=0 is present, migrate logs.
		self::migrate_logs($course);
		$logs = $DB->get_records_sql($sql, $params);
	}

	if ($logs) {
		$changelist = array();
		$modinfo = get_fast_modinfo($course);
		$modnames = get_module_types_names();
		foreach ($logs as $log) {
			// We used aggregate functions since constants CM_CREATED, CM_UPDATED and CM_DELETED have ascending order (0,1,2).
			$wasdeleted = ($log->maxaction == block_recent_activity_observer::CM_DELETED);
			$wascreated = ($log->minaction == block_recent_activity_observer::CM_CREATED);


			if ($wasdeleted && $wascreated) {
				// Activity was created and deleted within this interval. Do not show it.
				continue;
			} else if ($wasdeleted && $canviewdeleted) {
				if (plugin_supports('mod', $log->modname, FEATURE_NO_VIEW_LINK, false)) {
					// Better to call cm_info::has_view() because it can be dynamic.
					// But there is no instance of cm_info now.
					continue;
				}
				// Unfortunately we do not know if the mod was visible.
				$info = html_writer::div(get_string("deleted") . ": " . userdate($log->timemodified), "details");
				$changelist[$log->cmid] = array(
						'info' => array(
								"action" => "deletedactivity",
								"infotext" => html_writer::div($info, $log->modname . " overview")),
						'module' => $log->modname
				); // Die Struktur von $changelist wurde gegenüber der Vorlage geändert.

			} else if (!$wasdeleted && isset($modinfo->cms[$log->cmid]) && $canviewupdated) {
				// Module was either added or updated during this interval and it currently exists.
				// If module was both added and updated show only "add" action.
				$cm = $modinfo->cms[$log->cmid];
				$info = html_writer::div(get_string("name") . ": " . html_writer::link($cm->url, $cm->name), "name")
				. html_writer::div(get_string("modified") . ": " . userdate($log->timemodified), "info");
				if ($cm->has_view() && $cm->uservisible) {
					$changelist[$log->cmid] = array(
							"info" => array(
									"action" => $wascreated ? "added" : "updated",
									"infotext" => html_writer::div($info, $cm->modname . " overview")),
							"module" => $cm->modname
					);
				}
			}
		}

		if (!empty($changelist)) {
			if (!isset($htmlarray[$course->id]))
				$htmlarray[$course->id] = array();
			foreach ($changelist as $change) {
				$module = $change["module"];
				$action = $change["info"]["action"];
				if (isset($htmlarray[$course->id][$module])) {
					//div block davor entfernt, da die Änderungen am Anfang des blocks und nicht am anfang der Änderung steht
					$htmlarray[$course->id][$module] = //html_writer::div(html_writer::div(get_string($action . "_course_overview_info", "block_course_overview_ext"), "info"), $module . " overview")
					$htmlarray[$course->id][$module]; // add/update VOR der "normalen" Nachricht anzeigen
				} else {
					$htmlarray[$course->id]["isis2".$module] = $change["info"];
					//dieser String wird woanders aufgebaut
					//@see block_course_overview_renderer::activity_display
				}
			}
		}
	}
}

/**
 * Startzeit-Selektor für Kursübersicht
 *
 * @param moodle_url $url URL der Weiterleitung, nach dem Einstellen der Option
 * @param string $name Name des Objektes
 * @return single_select
 *
 * @see block_course_overview_renderer::editing_bar_head
 * @package block_course_overview
 * @author Jan Eberhardt <eberhardt@math.tu-berlin.de>
 */
function block_course_overview_ext_timestart_select(moodle_url $url, $name = "mytimestart")
{
	$modes = array(
			"0" => get_string("lastcourseaccess", "block_course_overview_ext"),
			"1" => get_string("lastday", "block_course_overview_ext"),
			"2" => get_string("lastweek", "block_course_overview_ext"),
			"3" => get_string("lastmonth", "block_course_overview_ext"),
			"4" => get_string("lastlogin", "block_course_overview_ext")
	);
	$select = new single_select(new moodle_url("/my/index.php"), $name, $modes, get_user_preferences("course_overview_timestart_for_log", "0"));
	$select->set_label(get_string("co_timestart_label", "block_course_overview_ext"));

	return $select;
}

/**
 * Neues Aussehen für die Kursübersicht
 *
 * @param moodle_url $url URL der Weiterleitung, nach dem Einstellen der Option (params werden überschrieben!)
 * @param string $name Name des Parameters
 * @return single_button
 *
 * @see block_course_overview_renderer::editing_bar_head
 * @package block_course_overview
 * @author Nicolas Klenert <klenert@math.tu-berlin.de>
 */
function block_course_overview_ext_view(moodle_url $url, $name = "myview")
{
	$modes = array(
			0 => get_string("view_standard", "block_course_overview_ext"),
			2 =>get_string("view_tiles_without_pictures", "block_course_overview_ext")
			//get_string("view_tiles_with_pictures", "block_course_overview_ext")
	);
	if(isset($url) && $url instanceof moodle_url){
		$select = new single_select($url, $name, $modes, get_user_preferences("course_overview_view","0"));

	}else{
		$select = new single_select(new moodle_url("/my/index.php"), $name, $modes, get_user_preferences("course_overview_view", "0"));

	}
	$select->set_label(get_string("co_view", "block_course_overview_ext"));
	return $select;
}

function block_course_overview_ext_saveColors(moodle_url $url, $name = 'saveColor'){
	
	if(isset($url) && $url instanceof moodle_url){
		$url->params(array($name=>"true"));
		$select = new single_button($url, get_string('co_save_colors','block_course_overview_ext'));
	}else{
		$select = new single_button(new moodle_url("/my/index.php",array($name => true)), get_string('co_save_colors','block_course_overview_ext'));
	}
	return $select;
}

function block_course_overview_ext_resetColors(moodle_url $url, $name = 'resetColor'){
	if(isset($url) && $url instanceof moodle_url){
		//$url->params(array($name=>"true"));
		$select = new single_button($url, get_string('co_reset_colors','block_course_overview_ext'));
	}else{
		$select = new single_button(new moodle_url("/my/index.php",array()), get_string('co_reset_colors','block_course_overview_ext'));	//$name => true
	}
	return $select;
}

function block_course_overview_ext_update_coursecolor($courselist, $colorlist){
	
	$arr = array_combine($courselist, $colorlist);
	$string = serialize($arr);
	
	set_user_preference("course_overview_coursecolor", $string);
		
}
