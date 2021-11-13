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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

class block_gps extends block_base {
    public $content;

    public function init() {
        $this->title = get_string('pluginname', 'block_gps');
    }
    public function get_content() {
        global $CFG, $COURSE, $OUTPUT, $PAGE;

        $PAGE->requires->css('/blocks/gps/style/main.css');

        if ($this->content !== null) {
          return $this->content;
        }
        //print_r(\block_gps\locallib::get_location());die();
        $this->content         =  new stdClass;
        $this->content->text = $OUTPUT->render_from_template(
            'block_gps/block',
            (object)array(
                'altitude' => round(\block_gps\locallib::get_location('altitude'), 0) . ' ' . get_string('meters', 'block_gps'),
                'courseid' => $COURSE->id,
                'is_https' => \block_gps\locallib::is_https(),
                'latitude' => \block_gps\locallib::get_location('latitude'),
                'longitude' => \block_gps\locallib::get_location('longitude'),
                'setinterval' => \block_gps\locallib::cache_get('session', 'setinterval'),
                'wwwroot' => $CFG->wwwroot,
            )
        );

        return $this->content;
    }
    public function hide_header() {
        return false;
    }
    public function has_config() {
        return false;
    }
}
