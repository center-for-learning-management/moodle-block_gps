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
 * @package    block_gps
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 *             2020 onwards Zentrum für Lernmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function block_gps_before_standard_html_head() {
    global $CFG, $CONTEXT, $COURSE, $DB, $OUTPUT, $PAGE, $USER;

    // Protect question banks on course level.
    if (!empty($PAGE->context->contextlevel) && $PAGE->context->contextlevel >= CONTEXT_COURSE) {
        $courseinfo = \get_fast_modinfo($COURSE->id);
        $cms = $courseinfo->get_instances();
        foreach($cms as $type => $modlist) {
            foreach ($modlist as $modinfo) {
                $conditions = json_decode($modinfo->availability);
                if (empty($conditions->c)) continue;
                foreach ($conditions->c as $condition) {
                    if (!empty($condition->type) && $condition->type == 'gps') {
                        $condition->cmid = $modinfo->id;
                        $condition->cmtype = $type;
                        $condition->name = $modinfo->name;
                        $condition->url = $modinfo->url->__toString();
                        $condition->visible = $modinfo->visible;
                        $condition->visibleoncoursepage = $modinfo->visibleoncoursepage;
                        $PAGE->requires->js_call_amd('block_gps/geoassist', 'pushHoneypot', [ 'location' => $condition ]);
                    }
                }
            }
        }

        $sections = $courseinfo->get_section_info_all();
        foreach ($sections as $section) {
            $conditions = json_decode($section->availability);
            if (empty($conditions->c)) continue;
            foreach ($conditions->c as $condition) {
                if (!empty($condition->type) && $condition->type == 'gps') {
                    $condition->name = (empty($section->name)) ? get_string('section') . ' ' . $section->section : $section->name;
                    $condition->sectionid = $section->id;
                    $condition->sectionno = $section->section;
                    $condition->url = (new \moodle_url('/course/view.php', [ 'id' => $section->course], 'section-' . $section->section))->__toString();
                    $condition->visible = $section->visible;
                    $PAGE->requires->js_call_amd('block_gps/geoassist', 'pushHoneypot', [ 'location' => $condition ]);
                }
            }
        }

    }

}
