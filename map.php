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

require_once('../../config.php');
require_once($CFG->dirroot . '/course/format/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/availability/condition/gps/classes/condition.php');
require_once($CFG->dirroot . '/availability/classes/info_module.php');
require_once($CFG->dirroot . '/availability/classes/info_section.php');

$id = optional_param('id', 0, PARAM_INT);
$params = array();
if (!empty($id)) {
    $params = array('id' => $id);
} else {
    throw new \moodle_exception('unspecifycourseid', 'error');
}
$course = $DB->get_record('course', $params, '*', MUST_EXIST);
$urlparams = array('id' => $course->id);
$modinfo = get_fast_modinfo($course);
$locations = \block_gps\locallib::load_positions($course->id);

if (!empty(\block_gps\locallib::get_location('longitude'))) {
    $locations[] = (object) array(
        'longitude' => \block_gps\locallib::get_location('longitude'),
        'latitude' => \block_gps\locallib::get_location('latitude'),
        'type' => 'self',
        'cmid' => 0,
        'sectionid' => 0,
        'reveal' => 1
    );
}
context_helper::preload_course($course->id);
$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);
require_login($course);

$PAGE->set_url(new moodle_url($CFG->wwwroot . '/blocks/gps/map.php', $urlparams)); // Defined here to avoid notices on errors etc!
$PAGE->set_cacheable(false);
$PAGE->set_pagelayout('course');
$courseformat = course_get_format($course);
$course->format = $courseformat->get_format();
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_title(get_string('map', 'block_gps'));
$PAGE->set_heading(get_string('map', 'block_gps'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/gps/css/leaflet.css'));

echo $OUTPUT->header();

$userposition = (object)array(
    'longitude' => \block_gps\locallib::get_location('longitude'),
    'latitude' => \block_gps\locallib::get_location('latitude'),
);

echo $OUTPUT->render_from_template(
    'block_gps/nav-buttons',
    (object)array(
        'courseid' => $course->id,
        'goto' => 'list',
        'gotomarker' => 'fa-list',
        'gotostr' => get_string('list', 'block_gps'),
        'is_https' => \block_gps\locallib::is_https(),
        'wwwroot' => $CFG->wwwroot,
    )
);

$unrevealed = [];
$markers = array();

$smallestlon = 200;
$smallestlat = 200;
$biggestlon = -200;
$biggestlat = -200;

foreach ($locations as &$location) {
    if ($smallestlon > $location->longitude) {
        $smallestlon = $location->longitude;
    }
    if ($smallestlat > $location->latitude) {
        $smallestlat = $location->latitude;
    }
    if ($biggestlon < $location->longitude) {
        $biggestlon = $location->longitude;
    }
    if ($biggestlat < $location->latitude) {
        $biggestlat = $location->latitude;
    }
    $location->reveal = (isset($location->reveal)) ? $location->reveal : false;
    $location->revealname = (isset($location->revealname)) ? $location->revealname : false;
    $location->accuracy = (isset($location->accuracy)) ? $location->accuracy : 5;

    $conditionposition = (object)array(
        'longitude' => $location->longitude,
        'latitude' => $location->latitude,
    );
    $location->distance = \block_gps\locallib::get_distance($userposition, $conditionposition, 2);
    $chkdist = ($location->distance < $location->accuracy);
    $location->distlbl = ($location->distance !== -1) ? number_format($location->distance, 0, ',', ' ')
        . ' ' . get_string('meters', 'block_gps') : get_string('n_a', 'block_gps');

    if (isset($location->type) && $location->type == 'self') {
        $location->marker = $CFG->wwwroot . '/blocks/gps/pix/google-maps-pin-orange.svg';
        $pic = new user_picture($USER);
        $location->icon = $pic->get_url($PAGE); // Use this as marker!
        $location->name = get_string('you', 'block_gps');
        $location->alt = $location->name;
        $location->url = $CFG->wwwroot . '/user/profile.php?id=' . $USER->id;
        $location->available = true;
    } else {
        if ($location->cmid > 0) {
            $cm = $modinfo->get_cm($location->cmid);
            $location->alt = $cm->modname;
            $location->icon = (method_exists($cm, 'get_icon_url') ? $cm->get_icon_url() : '');
            $location->name = $cm->name;
            $location->url = $cm->url;
            $info = new \core_availability\info_module($cm);
        } else if ($location->sectionid > 0) {
            $sec = $DB->get_record('course_sections', array('id' => $location->sectionid));
            $location->alt = get_string('section');
            $location->icon = $CFG->wwwroot . '/pix/i/folder.svg';
            $location->name = (!empty($sec->name) ? $sec->name : get_string('section') . ' ' . $sec->section);
            $location->url = $CFG->wwwroot . '/course/view.php?id='
                . $sec->course . '&sectionid=' . $sec->id . '#section-' . $sec->section;
            $info = new \core_availability\info_section($courseformat->get_section($sec));
        }
        $condition = new \availability_gps\condition($location);
        $location->available = $condition->is_available(false, $info, null, $USER->id);
    }
    if ($location->revealname != 1 && $location->available != 1) {
        $location->name = get_string('n_a', 'block_gps');
    }
    if ($location->reveal != 1 && $location->available != 1) {
        $location->longitude = '';
        $location->latitude = get_string('n_a', 'block_gps');
    }

    if ($location->longitude != '') {
        $markers[] = $location;
    } else {
        $unrevealed[] = $location;
    }
}
$centerlat = ($smallestlat + $biggestlat) / 2;
$centerlon = ($smallestlon + $biggestlon) / 2;

echo $OUTPUT->render_from_template(
    'block_gps/map',
    (object) array(
        'bounds' => "[$smallestlat, $smallestlon], [$biggestlat, $biggestlon ]",
        'center' => "$centerlat, $centerlon",
        'markers' => $markers,
        'wwwroot' => $CFG->wwwroot,
    )
);

if (count($unrevealed) > 0) {
    echo $OUTPUT->render_from_template(
        'block_gps/unrevealed-' . ((count($unrevealed) == 1) ? 'single' : 'multiple'),
        (object) array(
            'amount' => count($unrevealed),
            'items' => $unrevealed,
        )
    );
}

echo $OUTPUT->footer();
