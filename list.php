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
require_once($CFG->dirroot . '/blocks/gps/lib.php');
require_once($CFG->dirroot . '/course/format/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/availability/condition/gps/classes/condition.php');
require_once($CFG->dirroot . '/availability/classes/info_module.php');
require_once($CFG->dirroot . '/availability/classes/info_section.php');

\availability_gps\block_gps_lib::check_coordinates();

$id = optional_param('id', 0, PARAM_INT);
$params = array();
if (!empty($id)) {
    $params = array('id' => $id);
} else {
    print_error('unspecifycourseid', 'error');
}
$course = $DB->get_record('course', $params, '*', MUST_EXIST);
$urlparams = array('id' => $course->id);
$courseformat = course_get_format($course);
$modinfo = get_fast_modinfo($course);
$locations = \availability_gps\block_gps_lib::load_positions($course->id);

context_helper::preload_course($course->id);
$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);
require_login($course);

$PAGE->set_url(new moodle_url($CFG->wwwroot . '/blocks/gps/list.php'), $urlparams); // Defined here to avoid notices on errors etc
$PAGE->set_cacheable(false);
$PAGE->set_pagelayout('course');
$course->format = course_get_format($course)->get_format();
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_title(get_string('list', 'block_gps'));
$PAGE->set_heading(get_string('list', 'block_gps'));

echo $OUTPUT->header();

$userposition = (object)array(
    'longitude' => block_gps::get_location('longitude'),
    'latitude' => block_gps::get_location('latitude'),
);

echo $OUTPUT->render_from_template(
    'block_gps/nav-buttons',
    (object)array(
        'courseid' => $course->id,
        'goto' => 'map',
        'gotostr' => get_string('map', 'block_gps'),
        'is_https' => \block_gps::is_https(),
        'wwwroot' => $CFG->wwwroot,
    )
);

$unrevealed = array();
foreach($locations AS &$location) {
    $conditionposition = (object)array(
        'longitude' => $location->longitude,
        'latitude' => $location->latitude,
    );
    $location->distance = \availability_gps\block_gps_lib::get_distance($userposition, $conditionposition, 2);
    $chkdist = ($location->distance < $location->accuracy);
    $location->distlbl = ($location->distance !== -1) ? number_format($location->distance, 0, ',', ' ') . ' ' . get_string('meters', 'block_gps') : get_string('n_a', 'block_gps');

    $location->alt = ''; $location->icon = ''; $location->name = ''; $location->url = '';
    if ($location->cmid > 0) {
        $cm = $modinfo->get_cm($location->cmid);
        $location->alt = $cm->modname;
        $location->icon = (method_exists($cm, 'get_icon_url')?$cm->get_icon_url():'');
        $location->name = $cm->name;
        $location->url = $cm->url;
        $info = new \core_availability\info_module($cm);
    }
    if ($location->sectionid > 0) {
        $sec = $DB->get_record('course_sections', array('id' => $location->sectionid));
        $location->alt = get_string('section');
        $location->icon = $CFG->wwwroot . '/pix/i/folder.svg';
        $location->name = $sec->name;
        $location->url = $CFG->wwwroot . '/course/view.php?id=' . $sec->course . '&sectionid=' . $sec->id . '#section-' . $sec->section;
        $info = new \core_availability\info_section($courseformat->get_section($sec));
    }
    $condition = new \availability_gps\condition($location);
    $location->available = $condition->is_available(false, $info, null, $USER->id);

    if ($location->revealname != 1 && $location->available != 1) {
        $location->name = get_string('n_a', 'block_gps');
        $location->url = '#';
    }
    if ($location->reveal != 1 && $location->available != 1) {
        $location->longitude = '';
        $location->latitude = get_string('n_a', 'block_gps');
    }
    $location->accuracy .= ' ' . get_string('meters', 'block_gps');
    $location->persistent = ($location->persistent) ? get_string('yes') : get_string('no');
}

echo $OUTPUT->render_from_template(
    'block_gps/list',
    (object) array(
        'items' => $locations,
    )
);

if (count($unrevealed) > 0) {
    echo $OUTPUT->render_from_template(
        'block_gps/unrevealed-' . ((count($unrevealed) == 1)?'single':'multiple'),
        (object) array(
            'amount' => count($unrevealed),
            'items' => $unrevealed,
        )
    );
}

echo $OUTPUT->footer();
