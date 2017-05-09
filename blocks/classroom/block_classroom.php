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
 * Block for displaying earned local badges to users
 *
 * @package    block_classroom
 * @copyright  2012 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Yuliya Bozhko <yuliya.bozhko@totaralms.com>
 */
class block_classroom extends block_base {
    function init() {
        $this->title = get_string('formaltitle', 'block_classroom');
        $this->version = 2008050600;
    }

    function get_content() {
        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';

        $timenow = time();
        $startyear  = strftime('%Y', $timenow);
        $startmonth = strftime('%m', $timenow);
        $startday   = strftime('%d', $timenow);

        $this->content->text = '';
        $this->content->text .= "<ul>\n";
        $this->content->text .= '<li><a href="'.$CFG->wwwroot.'/blocks/classroom/mysessions.php">'.get_string('upcomingsessions', 'block_classroom')."</a></li>\n";
        $this->content->text .= '<li><a href="'.$CFG->wwwroot.'/blocks/classroom/mysessions.php?startday='.$startday.'&amp;startmonth='.$startmonth.'&amp;startyear='.$startyear.'&amp;endday=1&amp;endmonth=1&amp;endyear=2020">'.get_string('allfuturesessions', 'block_classroom')."</a></li>\n";
        $this->content->text .= '<li><a href="'.$CFG->wwwroot.'/blocks/classroom/mysessions.php?startday=1&amp;startmonth=1&amp;startyear=2000&amp;endday=1&amp;endmonth=1&amp;endyear=2020">'.get_string('allsessions', 'block_classroom')."</a></li>\n";
        $this->content->text .= "</ul>\n";

        return $this->content;
    }
}

