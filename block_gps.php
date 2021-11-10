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
        global $SESSION;
        $this->title = get_string('pluginname', 'block_gps');
    }
    public function get_content() {
        global $CFG, $COURSE, $OUTPUT, $PAGE, $SESSION;

        if ($this->content !== null) {
          return $this->content;
        }
        $this->content         =  new stdClass;
        $this->content->text = $OUTPUT->render_from_template(
            'block_gps/block',
            (object)array(
                'altitude' => round(self::get_location('altitude'), 0) . ' ' . get_string('meters', 'block_gps'),
                'courseid' => $COURSE->id,
                'is_https' => self::is_https(),
                'latitude' => round(self::get_location('latitude'), 5),
                'longitude' => round(self::get_location('longitude'), 5),
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
    public static function is_https() {
        global $CFG;
        return substr($CFG->wwwroot, 0, 6) == 'https:';
    }
    public static function get_location($type) {
        global $SESSION;
        switch($type) {
            case 'altitude':
                if (!isset($SESSION->availability_gps_altitude)) return false;
                else return $SESSION->availability_gps_altitude;
            break;
            case 'latitude':
                if (!isset($SESSION->availability_gps_latitude)) return false;
                else return $SESSION->availability_gps_latitude;
            break;
            case 'longitude':
                if (!isset($SESSION->availability_gps_longitude)) return false;
                else return $SESSION->availability_gps_longitude;
            break;
        }
    }
}
