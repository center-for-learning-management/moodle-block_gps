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

$functions = array(
    'block_gps_locate' => array(
        'classname'   => 'block_gps_ws',
        'methodname'  => 'locate',
        'classpath'   => 'blocks/gps/externallib.php',
        'description' => 'Stores the current geo location',
        'type'        => 'read',
        'ajax'        => 1,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ),
    'block_gps_setinterval' => array(
        'classname'   => 'block_gps_ws',
        'methodname'  => 'setinterval',
        'classpath'   => 'blocks/gps/externallib.php',
        'description' => 'Let moodle know that we are using an interval',
        'type'        => 'read',
        'ajax'        => 1,
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ),
);
