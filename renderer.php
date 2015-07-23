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
 * course_overview block rendrer
 *
 * @package    block_course_overview_ext
 * @copyright  2015 Jan Eberhardt <eberhardt@math.tu-berlin.de>, Nicolas Klenert <klenert@math.tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once ($CFG->dirroot .'/blocks/course_overview/renderer.php');

class block_course_overview_ext_renderer extends block_course_overview_renderer {

    /**
     * Construct contents of course_overview block
     *
     * @param array $courses list of courses in sorted order
     * @param array $overviews list of course overviews
     * @return string html to be displayed in course_overview block
     */
    public function course_overview($courses, $overviews) {
    	global $DB;
        $html = '';
        $config = get_config('block_course_overview');
        if ($config->showcategories != BLOCKS_COURSE_OVERVIEW_SHOWCATEGORIES_NONE) {
            global $CFG;
            require_once($CFG->libdir.'/coursecatlib.php');
        }
        $ismovingcourse = false;
        $courseordernumber = 0;
        $maxcourses = count($courses);
        $userediting = false;
                
        // Intialise string/icon etc if user is editing and courses > 1
        if ($this->page->user_is_editing() && (count($courses) > 1)) {
            $userediting = true;
            $this->page->requires->js_init_call('M.block_course_overview.add_handles');

            // Check if course is moving
            $ismovingcourse = optional_param('movecourse', FALSE, PARAM_BOOL);
            $movingcourseid = optional_param('courseid', 0, PARAM_INT);
        }

        // Render first movehere icon.
        if ($ismovingcourse) {
            // Remove movecourse param from url.
            $this->page->ensure_param_not_in_url('movecourse');

            // Show moving course notice, so user knows what is being moved.
            $html .= $this->output->box_start('notice');
            $a = new stdClass();
            $a->fullname = $courses[$movingcourseid]->fullname;
            $a->cancellink = html_writer::link($this->page->url, get_string('cancel'));
            $html .= get_string('movingcourse', 'block_course_overview_ext', $a);
            $html .= $this->output->box_end();

            $moveurl = new moodle_url('/blocks/course_overview/move.php',
                        array('sesskey' => sesskey(), 'moveto' => 0, 'courseid' => $movingcourseid));
            // Create move icon, so it can be used.
            $movetofirsticon = html_writer::empty_tag('img',
                    array('src' => $this->output->pix_url('movehere'),
                        'alt' => get_string('movetofirst', 'block_course_overview_ext', $courses[$movingcourseid]->fullname),
                        'title' => get_string('movehere')));
            $moveurl = html_writer::link($moveurl, $movetofirsticon);
            $html .= html_writer::tag('div', $moveurl, array('class' => 'movehere'));
        }

        foreach ($courses as $key => $course) {
            // If moving course, then don't show course which needs to be moved.
            if ($ismovingcourse && ($course->id == $movingcourseid)) {
                continue;
            }
            //finde heraus welcher Kurs zur welchen Fakultät gehört
            $temp = $DB->get_field('course_categories', 'path', array('id'=> $course->category));
            $temp = explode('/', $temp);
            $html .= $this->output->box_start('coursebox fak-'. $temp[1], "course-{$course->id}");
            //End
            $html .= html_writer::start_tag('div', array('class' => 'course_title'));
            // If user is editing, then add move icons.
            if ($userediting && !$ismovingcourse) {
                $moveicon = html_writer::empty_tag('img',
                        array('src' => $this->pix_url('t/move')->out(false),
                            'alt' => get_string('movecourse', 'block_course_overview', $course->fullname),
                            'title' => get_string('move')));
                $moveurl = new moodle_url($this->page->url, array('sesskey' => sesskey(), 'movecourse' => 1, 'courseid' => $course->id));
                $moveurl = html_writer::link($moveurl, $moveicon);
                $html .= html_writer::tag('div', $moveurl, array('class' => 'move'));
                
                //Farbpalette 
                if (get_user_preferences("course_overview_view",-1) == 2) {

                	$coloricon = html_writer::empty_tag('img',
                			array('src' => $this->pix_url('e/text_highlight_picker')->out(false),
                			'alt' => get_string('colorcourse','block_course_overview_ext',$course->fullname),
                			'title' => get_string('selectcolor','editor'))
                	);
                	
                	$width = clean_param(get_config('block_course_overview_ext','colorcolumns'),PARAM_INT) * 32;
                	
                	$html .= $this->popup_region($this->get_colors($course->id),'colorpicker_'.$course->id ,$course->id, $coloricon, 'changecolor',array('style' => 'width: '.$width.'px;'));
                	//option true, falls sofort bei jedem Schritt die Änderung gespeichert werden soll
                	$this->page->requires->js_call_amd('block_course_overview_ext/tiles', 'isColor', array('colorpicker_'.$course->id.'_popup',true));	//das popup kommt von der funktion popup_region
                }
                
            }
            
            //Hintergrundfarbe einstellen
            if(get_user_preferences("course_overview_view",-1) == 2 && ($string = get_user_preferences('course_overview_coursecolor')) != null){
            	$arr = unserialize($string);
            	$courselist = array_keys($arr);
            	$colorlist = array_values($arr);
            	
            	$this->page->requires->js_call_amd('block_course_overview_ext/tiles', 'setColor',array($courselist,$colorlist));
            }          

            // No need to pass title through s() here as it will be done automatically by html_writer.
            $attributes = array('title' => $course->fullname);
            if ($course->id > 0) {
                if (empty($course->visible)) {
                    $attributes['class'] = 'dimmed';
                }
                $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                $coursefullname = format_string(get_course_display_name_for_list($course), true, $course->id);
                $link = html_writer::link($courseurl, $coursefullname, $attributes);
                $html .= $this->output->heading($link, 2, 'title');
            } else {
                $html .= $this->output->heading(html_writer::link(
                    new moodle_url('/auth/mnet/jump.php', array('hostid' => $course->hostid, 'wantsurl' => '/course/view.php?id='.$course->remoteid)),
                    format_string($course->shortname, true), $attributes) . ' (' . format_string($course->hostname) . ')', 2, 'title');
            }
            
            $html .= $this->output->box('', 'flush');
            $html .= html_writer::end_tag('div');

            if (!empty($config->showchildren) && ($course->id > 0)) {
                // List children here.
                if ($children = block_course_overview_ext_get_child_shortnames($course->id)) {
                    $html .= html_writer::tag('span', $children, array('class' => 'coursechildren'));
                }
            }

            // If user is moving courses, then don't show overview.
            if (isset($overviews[$course->id]) && !$ismovingcourse) {
                $html .= $this->activity_display($course->id, $overviews[$course->id]);
            }

            if ($config->showcategories != BLOCKS_COURSE_OVERVIEW_SHOWCATEGORIES_NONE) {
                // List category parent or categories path here.
                $currentcategory = coursecat::get($course->category, IGNORE_MISSING);
                if ($currentcategory !== null) {
                    $html .= html_writer::start_tag('div', array('class' => 'categorypath'));
                    if ($config->showcategories == BLOCKS_COURSE_OVERVIEW_SHOWCATEGORIES_FULL_PATH) {
                        foreach ($currentcategory->get_parents() as $categoryid) {
                            $category = coursecat::get($categoryid, IGNORE_MISSING);
                            if ($category !== null) {
                                $html .= $category->get_formatted_name().' / ';
                            }
                        }
                    }
                    $html .= $currentcategory->get_formatted_name();
                    $html .= html_writer::end_tag('div');
                }
            }

            $html .= $this->output->box('', 'flush');
            $html .= $this->output->box_end();
            $courseordernumber++;
            if ($ismovingcourse) {
                $moveurl = new moodle_url('/blocks/course_overview/move.php',
                            array('sesskey' => sesskey(), 'moveto' => $courseordernumber, 'courseid' => $movingcourseid));
                $a = new stdClass();
                $a->movingcoursename = $courses[$movingcourseid]->fullname;
                $a->currentcoursename = $course->fullname;
                $movehereicon = html_writer::empty_tag('img',
                        array('src' => $this->output->pix_url('movehere'),
                            'alt' => get_string('moveafterhere', 'block_course_overview_ext', $a),
                            'title' => get_string('movehere')));
                $moveurl = html_writer::link($moveurl, $movehereicon);
                $html .= html_writer::tag('div', $moveurl, array('class' => 'movehere'));
            }
        }
        
        //initialise js for view if neededs
        if(get_user_preferences("course_overview_view",-1) > 0){
        	if(get_config('block_course_overview_ext','tiles')){
        		//$this->page->requires->js_init_call('M.block_course_overview_ext.addresize');
        		$this->page->requires->js_call_amd('block_course_overview_ext/tiles', 'init');
        	}
        	//$this->page->requires->js_init_call('M.block_course_overview_ext.resetPop');
        	$this->page->requires->js_call_amd('block_course_overview_ext/tiles', 'closeAllPopups');
        }
        
        // Wrap course list in a div and return.
        return html_writer::tag('div', $html, array('class' => 'course_list clearfix'));		//ISIS2 clearfix hinzugefügt
    }

    /**
     * Construct activities overview for a course
     *
     * @param int $cid course id
     * @param array $overview overview of activities in course
     * @return string html of activities overview
     */
    protected function activity_display($cid, $overview) {
        $output = html_writer::start_tag('div', array('class' => 'activity_info'));
        foreach (array_keys($overview) as $module) {
        	//Prüfen, ob der Schlüssel besondere Infos enthält, die wir in block_course_overview_ext_structural_changes eingefügt haben $JE 2014/06/12
        	//Danach alles wieder "normal" machen, damit der alte Code genutzt werden kann
        	$onlystructural = (substr($module, 0, 5) === "isis2"); // Dieser Schlüssel existiert nur, wenn es keine weitere Info zu dem Modul gibt
        	$action = "";
        	if ($onlystructural) {
        		//$overview[isis2modname] = array(0 => "add"|"update"|"delete", 1 => Detailtext zum ausklappen)
        		$action = $overview[$module]["action"];
        		$infotext = $overview[$module]["infotext"];
        		$module = substr($module, 5);
        	}
            $output .= html_writer::start_tag('div', array('class' => 'activity_overview'));
            $url = new moodle_url("/mod/$module/index.php", array('id' => $cid));
            $modulename = get_string('modulename', $module);
            //statt $modulename die ganze text-info in den link reinschreiben
            $textinfo = '';
            if (get_string_manager()->string_exists("activityoverview", $module)) {
            	$textinfo .= get_string("activityoverview", $module);
            } else {
            	$textinfo .= get_string("activityoverview", 'block_course_overview', $modulename);
            }
            $icontext = html_writer::link($url, $this->output->pix_icon('icon', $textinfo, 'mod_'.$module, array('class'=>'iconlarge')));
			
            //NUR Aktualisierungen der Aktivitäten anzeigen (sonst ist die Meldung in $overview[$module] eingebettet) $JE 2014/06/12
            if ($onlystructural) {
	           	//$icontext wird neu gesetzt -> das hat den Vorteil, dass wir nur Code hinzufügen und der alte erhalten bleibt
	           	$textinfo = get_string($action, "moodle", $modulename);
				$icontext = $this->output->pix_icon("i/" . $action, $textinfo, "local_isis", array("class" => "iconextra"))
							. $this->output->pix_icon('icon', $modulename, "mod_" . $module, array("class" => "iconlarge"));
            	$icontext = html_writer::link($url,$icontext);

	           	//setzen von $overview[$module] um den "Abschluss" des alten Codes zu verwenden
	           	//zur Erinnerung: In $module wurde "isis2" oben entfernt, d.h. $module ist jetzt wieder der Modulkurzname
	           	$overview[$module] = $infotext;
            }

            if(get_user_preferences("course_overview_view",-1) < 1){
            	// Add collapsible region with overview text in it.
            	$output .= $this->collapsible_region($overview[$module], '', 'region_'.$cid.'_'.$module, $icontext . $textinfo, '', true);
           		//$output .= $overview[$module];
            }else{
				//Add Popup
				$textinfo = '<div class="assign overview bold">'.$textinfo.'</div>';
            	$output .= $this->popup_region($textinfo . $overview[$module],'region_'.$cid.'_'.$module,$cid, $icontext);
            }
            
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('div');
        
        return $output;
    }

    /**
     * Constructs header in editing mode
     *
     * @param int $max maximum number of courses
     * @return string html of header bar.
     */
    public function editing_bar_head($max = 0) {
        $output = $this->output->box_start('notice');

        $options = array('0' => get_string('alwaysshowall', 'block_course_overview'));
        for ($i = 1; $i <= $max; $i++) {
            $options[$i] = $i;
        }
        $url = new moodle_url('/my/index.php');
        $select = new single_select($url, 'mynumber', $options, block_course_overview_get_max_user_courses(), array());
        $select->set_label(get_string('numtodisplay', 'block_course_overview'));
        $output .= $this->output->render($select);
        //Startzeitselektor für "neueste Aktivitäten" $JE 2014/06/18
        $select = block_course_overview_ext_timestart_select($url);
        $output .= $this->output->render($select);
        //Selektor für Lookänderung
        $select = block_course_overview_ext_view($url);
        $output .= $this->output->render($select);
        
		//Button zur Speicherung der Farben (Link wird von Javascript überschrieben und verändert!)
		//wurde entfernt(jede Änderung wird sofort gespeichert). Steht hier nur nur, falls der Wunsch existiert, wieder Buttons zu nutzen
    	if (get_user_preferences("course_overview_view",-1) == 2){
//     		$select = block_course_overview_ext_saveColors($url, ' btn-disabled');
//     		$select = $this->output->render($select) 
//     		//only if you want mid-screen
//     		. $this->mid_screen('Erfolgreich gespeichert!');
    		
//     		$button = block_course_overview_ext_resetColors($url);
//     		$button = $this->output->render($button);
    		
//     		$output .= html_writer::div($select . $button,'co_saveColor');
//     		//last paremeter only if you want mid-screen => option?
//     		$this->page->requires->js_call_amd('block_course_overview_ext/tiles','saveColor',array('.co_saveColor .singlebutton:first-child','.co_midscreen_container'));
    	}
        
        $output .= $this->output->box_end();
        return $output;
    }
    
    /**
     * @param string $content which will be displayed in the popup
     * @param string $id of the div which links will be editet to triggering the popup
     * @param string $cid id of the div of the whole course
     * @param string $caption which will be displayed (poreferable the clickable content)
     * @param string $class the classes the span have to hold
     * @author Nicolas Klenert
     */
    protected function popup_region($content, $id, $cid, $caption, $class = null, $array = null){
   	
    	if($array){
    		$array = array('id'=>$id.'_popup') + $array;
    	}else{
    		$array = array('id'=>$id.'_popup');
    	}
    	
    	$output = html_writer::span($caption . html_writer::div($content,'co_popup',$array),$class,array('id'=>$id));
    	
    	//$this->page->requires->js_init_call('M.block_course_overview_ext.pop', array($id,$cid));
    	$this->page->requires->js_call_amd('block_course_overview_ext/tiles', 'pop',array($id));
    	return $output;
    }
    
    protected function get_colors($cid){
    	$output = '';
    	$string = get_config('block_course_overview_ext','colors');
    	$colors = explode(' ', $string);
    	foreach ($colors as $key => $color){
    		$output .= html_writer::div(null,'color',array('style' => 'background-color:'.$color.';','id' => 'colorpicker_'.$cid.'_'.$key));
    		//$this->page->requires->js_init_call('M.block_course_overview_ext.isColor', array('colorpicker_'.$cid.'_'.$key,$color,'course-'.$cid, '.co_saveColor .singlebutton'));
    	}
    	return $output;
    }
    
    protected function mid_screen($content){
    	$output = html_writer::div(html_writer::div(html_writer::tag('strong', $content),'co_notification alert alert-success'),'co_midscreen_container');
    	return $output;
    }

}
