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
    public function get_name() {
        return get_string('archive', 'block_homework');
    }

    public function archive($assign, $coursesection, $archiveafter) {
        global $DB;
        $sql = "select duedate from {assign} where id =?";
        $deadline = $DB->get_record_sql($sql, array($assign->instance));
       if ($deadline->duedate > $archiveafter) {
      // if(1==1){
             /* defined in core api */
             moveto_module($assign, $coursesection);
        }
    }

    public function execute() {
        mtrace('homework_block_archive_task start');
        /** find all courses that include the homework block */
        global $DB;
        $sql = "select ctx.instanceid as courseid from {context} ctx 
            where contextlevel=50 and ctx.id in 
            (select parentcontextid from {block_instances} b 
            where blockname='homework')";
        $courses = $DB->get_records_sql($sql);
        /* 60 seconds in a minute, 60 minutes in an hour 24 hours in a day */
        $archiveafterdays = get_config('block_homework')->archiveafterdays;
        $daysagostamp = time() - ((60 * 60 * 24) * $archiveafterdays);
        foreach ($courses as $course) {
            $cms = get_fast_modinfo($course->courseid)->get_cms();
            $modinfo = get_fast_modinfo($course->courseid);
            /* find the biggest course section (furthest from the top) */
            $sql = "SELECT section FROM {course_sections} WHERE section=(select max(section) from {course_sections}) and course=?";
            $maxsection = $DB->get_record_sql($sql, array($course->courseid));
            $coursesection = get_fast_modinfo($course->courseid)->get_section_info($maxsection->section);
            $assigns = get_fast_modinfo($course->courseid)->get_instances_of('assign');
            foreach ($assigns as $assign) {
                if ($assign->section !== $coursesection->id) {
                    $this->archive($assign, $coursesection, $daysagostamp);
                }
            }
        }
        mtrace('homework_block_archive_task end');
    }
}