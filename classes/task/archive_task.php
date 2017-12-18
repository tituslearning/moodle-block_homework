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
//This can be tested on the command line with 

 //php admin/tool/task/cli/schedule_task.php --execute=\\block_homework\\task\\archive_task --showsql --showdebugging

namespace block_homework\task;

/**
 * Scheduled task to move assignments to the arcive area of a course
 * @package    block_homework
 * @copyright  2017 Titus Learning by Marcus Green (@link http://www.tituslearning.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/homework/edulink_classes/homework.php');

class archive_task extends \core\task\scheduled_task {
    
    /**
     * Must be included as it is abstract in the base class 
     * @return string
     */
    public function get_name() {
        return get_string('archive', 'block_homework');
    }

    /**
     * Check if the duedate is older than (smaller than)
     * the archive after date. If so move the assignment
     * to the end topic which has been set to be hidden
     * from students.
     * 
     * @global moodle_database $DB
     * @param object $assign
     * @param object $coursesection
     * @param number $archiveafter
     */
 public function archive($assign, $coursesection, $archiveafter) {
        global $DB;
        /*modinfo does not contain the due date */
       // $sql = "select duedate from {assign} where id =?";
       // $deadline = $DB->get_record_sql($sql, array($assign->instance));
        if ($assign->duedate < $archiveafter) {
             /* defined in core api */
             moveto_module($assign, $coursesection);
        }
    }
    public function execute() {        
        if(!get_config('block_homework', 'enablearchiving')){
            return;
        }
        mtrace('homework_block_archive_task start');
        /** find all courses that include the homework block */
        global $DB;
        $sql = "SELECT crs.id,crs.shortname,ctx.instanceid FROM {block_instances} bi 
        join {context} ctx on bi.parentcontextid = ctx.id
        join mdl_course crs on ctx.instanceid=crs.id
        and bi.blockname='homework'";        
        $courses = $DB->get_records_sql($sql);
        foreach ($courses as $course) {
            $modinfo = get_fast_modinfo($course->instanceid);
            /* find the biggest course section (furthest from the top) */
            $sql= "select max(section) as section from {course_sections} where course=?"; 
            $maxsection = $DB->get_record_sql($sql, array($course->id));      
            $coursesection = get_fast_modinfo($course->id)->get_section_info($maxsection->section);
            /*if the end section is visible, hide it (from students) */
            if($coursesection->visible==1){ 
               set_section_visible($course->id, $maxsection->section, false); 
           }
           $assigns = get_fast_modinfo($course->id)->get_instances_of('assign');
            /* find assignments on this course that are listed in the homework block */
            $sql="select cm.id,cm.section,cm.course,cm.visible,a.name,a.duedate,bha.archiveafterdays from {course_modules} cm
                    join {block_homework_assignment} bha on cm.id=bha.coursemoduleid
                    join {assign} a on a.id=cm.instance
                    where cm.course=?";   
            $assigns = $DB->get_records_sql($sql, array($course->id));    
            foreach ($assigns as $assign) {
                /* 60 seconds in a minute, 60 minutes in an hour 24 hours in a day */
                $daysagostamp = time() - ((60 * 60 * 24) * $assign->archiveafterdays);
                if ($assign->section !== $coursesection->id) {
                   $this->archive($assign, $coursesection, $daysagostamp);
                }
            }
        }
        mtrace('homework_block_archive_task end');
    }

}