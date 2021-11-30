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

defined('MOODLE_INTERNAL') || die;

class locallib {
    private static $caches = [];
    /**
     * Retrieve a key from cache.
     * @param cache cache object to use (application or session)
     * @param key the key.
     * @return whatever is in the cache.
     */
    public static function cache_get($cache, $key) {
        if (!in_array($cache, [ 'application', 'request', 'session'])){
            throw new \moodle_exception('invalid cache type requested');
        }
        if (empty(self::$caches[$cache])) {
            self::$caches[$cache] = \cache::make('block_gps', $cache);
        }
        $value = self::$caches[$cache]->get($key);
        return $value;
    }
    /**
     * Set a cache object.
     * @param cache cache object to use (application or session)
     * @param key the key.
     * @param value the value.
     * @param delete whether or not the key should be removed from cache.
     */
    public static function cache_set($cache, $key, $value, $delete = false) {
        if (!in_array($cache, [ 'application', 'request', 'session'])) {
            throw new \moodle_exception('invalid cache type requested');
        }
        if (empty(self::$caches[$cache])) {
            self::$caches[$cache] = \cache::make('block_gps', $cache);
        }

        if ($delete) {
            self::$caches[$cache]->delete($key);
        } else {
            self::$caches[$cache]->set($key, $value);
        }
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
        return round($angle * 6378.388 * 1000, $decimals);
    }
    public static function get_location($type = "", $default = null) {

        if (empty($type)) {
            return (object) [
                'altitude' => self::get_location('altitude'),
                'latitude' => self::get_location('latitude'),
                'longitude' => self::get_location('longitude'),
            ];
        } else {
            $var = \block_gps\locallib::cache_get("session", $type);
            if (!empty($var)) return $var;
            elseif (isset($default)) return $default;
            else return false;
        }

    }

    public static function is_https() {
        global $CFG;
        return substr($CFG->wwwroot, 0, 6) == 'https:';
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
     * @return array containing positions
    **/
    public static function load_position_condition($o, $idtype) {
        $positions = array();
        // Make sure it has a type and object contains valid data.
        if (!empty($idtype) && isset($o->availability)) {
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
        }

        return $positions;
    }

    public static function set_location($latitude, $longitude, $altitude) {
        self::cache_set("session", "latitude", $latitude);
        self::cache_set("session", "longitude", $longitude);
        self::cache_set("session", "altitude", $altitude);
    }

    /**
     * Return all honeypots of a specific course or check if condition is used.
     * @param courseid the courseid.
     * @param onlycheck only return true after first found condition.
     */
    public static function get_honeypots($courseid, $onlycheck = false) {
        require_login();
        if ($courseid > 1) {
            require_course_login($courseid, true, null, true, true);
        }

        $honeypots = [];
        $courseinfo = \get_fast_modinfo($courseid);
        $cms = $courseinfo->get_instances();
        foreach($cms as $type => $modlist) {
            foreach ($modlist as $modinfo) {
                $conditions = json_decode($modinfo->availability);
                if (empty($conditions->c)) continue;
                foreach ($conditions->c as $condition) {
                    if (!empty($condition->type) && $condition->type == 'gps') {
                        if ($onlycheck) return true;
                        $condition->available = $modinfo->available;
                        $condition->availableinfo = $modinfo->availableinfo;
                        $condition->cmid = $modinfo->id;
                        $condition->cmtype = $type;
                        $condition->name = $modinfo->name;
                        $condition->url = $modinfo->url->__toString();
                        $condition->uservisible = $modinfo->uservisible;
                        $condition->visible = $modinfo->visible;
                        $condition->visibleold = $modinfo->visibleold;
                        $condition->visibleoncoursepage = $modinfo->visibleoncoursepage;
                        $honeypots[] = $condition;
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
                    if ($onlycheck) return true;
                    $condition->available = $section->available;
                    $condition->availableinfo = $section->availableinfo;
                    $condition->name = (empty($section->name)) ? get_string('section') . ' ' . $section->section : $section->name;
                    $condition->sectionid = $section->id;
                    $condition->sectionno = $section->section;
                    $condition->url = (new \moodle_url('/course/view.php', [ 'id' => $section->course], 'section-' . $section->section))->__toString();
                    $condition->visible = $section->visible;
                    $honeypots[] = $condition;
                }
            }
        }
        return $honeypots;
    }
}
