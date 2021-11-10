<?php

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
 * External Web Service Template
 *
 * @package    block_gps
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class block_gps_ws extends external_api {
    /**
     * Store current location to session
     * @return checked parameters
    **/
    public static function locate_parameters() {
        return new external_function_parameters(
                array(
                    'lat' => new external_value(PARAM_FLOAT, 'Latitude', VALUE_DEFAULT, -200),
                    'lon' => new external_value(PARAM_FLOAT, 'Longitude', VALUE_DEFAULT, -200),
                    'alt' => new external_value(PARAM_FLOAT, 'Altitude', VALUE_DEFAULT, 0),
                )
        );
    }
    public static function locate($lat, $lon, $alt) {
        global $CFG,$SESSION;
        $params = self::validate_parameters(
            self::locate_parameters(),
            array(
                'lat' => $lat,
                'lon' => $lon,
                'alt' => $alt
            )
        );

        if (empty($SESSION->availability_gps_longitude)) {
            $position1 = array(
                'longitude' => 0,
                'latitude' => 0,
            );
        } else {
            $position1 = array(
                'longitude' => $SESSION->availability_gps_longitude,
                'latitude' => $SESSION->availability_gps_latitude,
            );
        }

        $position2 = array(
            'longitude' => $params['lon'],
            'latitude' => $params['lat'],
        );

        require_once("$CFG->dirroot/blocks/gps/lib.php");
        $distance = \availability_gps\block_gps_lib::get_distance(
            (object)$position1,
            (object)$position2,
            0
        );

        if ($distance > 5 && $params['lat'] > -200 && $params['lon'] > -200) {
            $SESSION->availability_gps_latitude = $params['lat'];
            $SESSION->availability_gps_longitude = $params['lon'];
            $SESSION->availability_gps_altitude = $params['alt'];
            return 'coordinates_set';
        } else if($distance < 5) {
            return 'moved_less_than_5m';
        } else {
            return 'invalid_coordinates';
        }
    }
    public static function locate_returns() {
        return new external_value(PARAM_TEXT, 'Error-Messages if occured');
    }
}
