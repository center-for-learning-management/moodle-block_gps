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

namespace availability_gps;

defined('MOODLE_INTERNAL') || die;

class block_gps_lib  {
    /**
     * Checks if coordinates have been sent via get request.
     * Redirects to the page without coordinates.
    **/
    public static function check_coordinates() {
        global $SESSION;
        $longitude = optional_param('longitude', -200, PARAM_FLOAT);
        $latitude = optional_param('latitude', -200, PARAM_FLOAT);

        if ($longitude > -180 && $longitude < 180 && $latitude > -180 && $latitude < 180) {
            $SESSION->availability_gps_longitude = $longitude;
            $SESSION->availability_gps_latitude = $latitude;
            $params = $_GET;
            unset($params['longitude']);
            unset($params['latitude']);
            $url = new \moodle_url($_SERVER['PHP_SELF'], $params);
            redirect($url);
        }
    }
    /**
     * Calculates the distance between two positions
     * @param position1 object containing longitude and latitude
     * @param position2 object containing longitude and latitude
     * @param decimals (optional) decimals to round the distance, defaults to 2
     * @return distance in meters or -1 if positions are invalid
    **/
    public static function get_distance($position1, $position2, $decimals = 2) {
        if (!self::check_positions(array($position1, $position2))) {
            return -1;
        }
        $lat1 = deg2rad($position1->latitude);
        $lon1 = deg2rad($position1->longitude);
        $lat2 = deg2rad($position2->latitude);
        $lon2 = deg2rad($position2->longitude);
        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;
        $angle = 2*asin(sqrt(pow(sin($latDelta / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($lonDelta / 2), 2)));
        return round($angle * 6378.388 * 1000, 0);
    }
    /**
     * Checks a list of positions if coordinates are valid.
     * @param positions Array containing position objects
    **/
    private static function check_positions($positions) {
        foreach($positions AS $position) {
            if (!isset($position->longitude) || $position->longitude < -180 || $position->longitude > 180) return false;
            if (!isset($position->latitude) || $position->latitude < -180 || $position->latitude > 180) return false;
        }
        return true;
    }
    /**
     * Loads all conditions from sections and modules in a course and returns a list.
     * @param courseid ID of the course
     * @return array containing all positions as object
    **/
    public static function load_positions($courseid) {
        global $DB;
        $positions = array();
        $sections = $DB->get_records('course_sections', array('course' => $courseid));
        foreach($sections AS $section) {
            $has_positions = self::load_position_condition($section, 'sectionid');
            if (count($has_positions) > 0) {
                $positions = array_merge($positions, $has_positions);
            }
        }
        $modules = $DB->get_records('course_modules', array('course' => $courseid));
        foreach($modules AS $module) {
            $has_positions = self::load_position_condition($module, 'cmid');
            if (count($has_positions) > 0) {
                $positions = array_merge($positions, $has_positions);
            }
        }
        return $positions;
    }
    /**
     * Analyzes conditions for gps type.
     * @param o Object of table course_sections or course_modules
     * @param idtype specifies if id-attribute of o is sectionid or cmid
    **/
    private static function load_position_condition($o, $idtype) {
        $positions = array();
        $av = json_decode($o->availability);
        if (isset($av->c) && count($av->c) > 0) {
            foreach($av->c AS $condition) {
                if ($condition->type == 'gps') {
                    $condition->cmid = 0; $condition->sectionid = 0;
                    $condition->{$idtype} = $o->id;
                    if (!isset($condition->accuracy)) { $condition->accuracy = 5; }
                    if (!isset($condition->persistent)) { $condition->persistent = 0; }
                    if (!isset($condition->revealname)) { $condition->revealname = 0; }
                    if (!isset($condition->reveal)) { $condition->reveal = 0; }
                    $positions[] = $condition;
                }
            }
        }

        return $positions;
    }
}
