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
//php admin/tool/task/cli/schedule_task.php --execute=\\block_homework\\task\\delete_task --showsql --showdebugging
namespace block_homework\task;
/**
 * Scheduled task to delete assignments a set number of days after due date
 * @package    block_homework
 * @copyright  2017 Titus Learning by Marcus Green (@link http://www.tituslearning.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/homework/edulink_classes/homework.php');

class delete_task extends \core\task\scheduled_task {

     /**
     * Must be included as it is abstract in the base class 
     * @return string
     */
    public function get_name() {
        return get_string('delete', 'block_homework');
    }

    /**
     * This is required to be called execute for cron to automatically find it
     * @global moodle_database $DB
     */
    public function execute() {
       if(!get_config('block_homework', 'enablearchiving')){
            return;
       }
       mtrace('homework_block_delete_task start');
        /** find all courses that include the homework block */
        global $DB;
        $sql = "SELECT crs.id,crs.shortname FROM mdl_block_instances bi 
        join mdl_context ctx on bi.parentcontextid = ctx.id
        join mdl_course crs on ctx.instanceid=crs.id
        and bi.blockname='homework'";        
        $courses = $DB->get_records_sql($sql);
        $deleteafterdays = get_config('block_homework')->deleteafterdays;
        /* 60 seconds in a minute, 60 minutes in an hour 24 hours in a day */
        $daysagostamp = time() - ((60 * 60 * 24) * $deleteafterdays);
        foreach ($courses as $course) {
            /* find assignments on this course that are listed in the homework block */
            $sql="select cm.id,cm.section,cm.course,a.name,a.duedate from mdl_course_modules cm
                    join mdl_block_homework_assignment bha on cm.id=bha.coursemoduleid
                    join mdl_assign a on a.id=cm.instance
                    where cm.course=?";            
            $assigns = $DB->get_records_sql($sql, array($course->id));
            foreach ($assigns as $assign) {
                /* 60 seconds in a minute, 60 minutes in an hour 24 hours in a day */
                $daysagostamp = time() - ((60 * 60 * 24) * $deleteafterdays);
                /* if the duedate is more than x days ago then delete assignment */
                if ($assign->duedate < $daysagostamp) {
                    mtrace('Deleting assignment:' . $assign->id);
                    course_delete_module($assign->id);                   
                }
            }
            mtrace('homework_block_delete_task end');
        }
    }
}
    