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
 * Course overview block extended
 *
 * @package    block_course_overview_ext
 * @copyright  2015 Jan Eberhardt <eberhardt@math.tu-berlin.de>, Nicolas Klenert <klenert@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/blocks/course_overview/block_course_overview.php');
require_once($CFG->dirroot.'/blocks/course_overview_ext/locallib.php');
//require_once($CFG->dirroot.'/blocks/course_overview/locallib.php');

class block_course_overview_ext extends block_course_overview{
    /**
     * If this is passed as mynumber then showallcourses, irrespective of limit by user.
     */
    const SHOW_ALL_COURSES = -2;

    /**
     * Block initialization
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_course_overview_ext');
        
        //view is needed here, because it determine the block's classes
        $updateview = optional_param("myview", -1, PARAM_INT);
        if ($updateview >= 0) {
        	set_user_preference("course_overview_view", $updateview);
        	//unset params because init() function can be called twice
        	unset($_GET['myview']);
        	unset($_POST['myview']);
        }
        //End
    }

    /**
     * Return contents of course_overview block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $USER, $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/lib.php');

        if($this->content !== NULL) {
            return $this->content;
        }

        $config = get_config('block_course_overview_ext');

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $content = array();

        $updatemynumber = optional_param('mynumber', -1, PARAM_INT);
        if ($updatemynumber >= 0) {
            block_course_overview_update_mynumber($updatemynumber);
        }
        //"mytimestart" als Einstellung setzen
        $updatemytimestart = optional_param("mytimestart", -1, PARAM_INT);
        if ($updatemytimestart >= 0) {
        	set_user_preference("course_overview_timestart_for_log", $updatemytimestart);
        }

        profile_load_custom_fields($USER);

        $showallcourses = ($updatemynumber === self::SHOW_ALL_COURSES);
        list($sortedcourses, $sitecourses, $totalcourses) = block_course_overview_get_sorted_courses($showallcourses);
        $overviews = block_course_overview_ext_get_overviews($sitecourses);

        $renderer = $this->page->get_renderer('block_course_overview_ext');
        if (!empty($config->showwelcomearea)) {
            require_once($CFG->dirroot.'/message/lib.php');
            $msgcount = message_count_unread_messages();
            $this->content->text = $renderer->welcome_area($msgcount);
        }

        // Number of sites to display.
        if ($this->page->user_is_editing() && empty($config->forcedefaultmaxcourses)) {
            $this->content->text .= $renderer->editing_bar_head($totalcourses);
        }

        if (empty($sortedcourses)) {
            $this->content->text .= get_string('nocourses','my');
        } else {
            // For each course, build category cache.
            $this->content->text .= $renderer->course_overview($sortedcourses, $overviews);
            $this->content->text .= $renderer->hidden_courses($totalcourses - count($sortedcourses));
        }

        return $this->content;
    }

    /**
     * Sets block header to be hidden or visible
     *
     * @return bool if true then header will be visible.
     */
    public function hide_header() {
        // Hide header if welcome area is show.
        $config = get_config('block_course_overview_ext');
        return !empty($config->showwelcomearea);
    }
    
    /**
     * @author Nicolas Klenert
     * Return any HTML attributes that you want added to the outer <div> that
     * of the block when it is output.
     *
     * @return array attribute name => value.
     */
     function html_attributes() {
       	$attributes = parent::html_attributes();
       	if(get_user_preferences("course_overview_view",0) > 0){
       		$attributes['class'] .= ' view';
       	}
       	//deprecated
       	if(get_user_preferences("course_overview_view",0) == 2){
       		$attributes['class'] .= ' co_pics';
       	}
       	if(get_config('block_course_overview_ext','tiles')){
       		$attributes['class'] .= ' tiles';
       	}
       	return $attributes;
     }
     
     /**
      * Locations where block can be displayed
      *
      * @return array
      */
     public function applicable_formats() {
     	return array('my-index' => true, 'site' => true);
     }
}