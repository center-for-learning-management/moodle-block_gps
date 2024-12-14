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
 * @package   block_gps
 * @copyright  2021 Zentrum für Lernmanagement (www.lernmanagement.at)
 * @author    Robert Schrenk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gps;

class hook_callbacks {
    public static function before_standard_head_html_generation($hook = null): void {
        global $COURSE, $PAGE;

        if (!empty($PAGE->context->contextlevel)
            && $PAGE->context->contextlevel >= CONTEXT_COURSE
            && \block_gps\locallib::get_honeypots($COURSE->id, true)) {
            $PAGE->requires->js_call_amd('block_gps/geoassist', 'getHoneypots', [ 'courseid' => $COURSE->id ]);
        }
    }
}
