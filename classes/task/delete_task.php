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
    public function get_name() {
        return get_string('delete', 'block_homework');
    }

    public function execute() {
        mtrace('homework_block_delete_task start');
        mtrace('homework_block_delete_task end');
    }
}