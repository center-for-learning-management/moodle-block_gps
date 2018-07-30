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

$PAGE->set_url('/blocks/gps/list.php', $urlparams); // Defined here to avoid notices on errors etc
$PAGE->set_cacheable(false);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('list', 'block_gps'));
$PAGE->set_heading(get_string('list', 'block_gps'));
$PAGE->requires->js('/blocks/gps/js/main.js');

context_helper::preload_course($course->id);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

echo $OUTPUT->header();

$userposition = (object)array(
    'longitude' => $SESSION->availability_gps_longitude,
    'latitude' => $SESSION->availability_gps_latitude,
);

?>

<div>
<a href="#" onclick="block_gps_locate()" class="btn btn-primary">
    <?php echo get_string('update_location', 'block_gps'); ?>
</a>
<a href="<?php echo $CFG->wwwroot . '/blocks/gps/map.php?id=' . $course->id; ?>" class="btn">
    <?php echo get_string('map', 'block_gps'); ?>
</a>
</div>
<table border="1" width="100%">
    <thead>
        <tr>
            <th><?php echo get_string('item', 'block_gps'); ?></th>
            <th><?php echo get_string('latitude', 'block_gps') . '<br />' . get_string('longitude', 'block_gps'); ?></th>
            <th><?php echo get_string('accuracy', 'block_gps'); ?></th>
            <th><?php echo get_string('persistent', 'block_gps'); ?></th>
            <th><?php echo get_string('distance', 'block_gps'); ?></th>
        </tr>
    </thead>
    <tbody>

<?php
foreach($locations AS $location) {
    //$cm = $cms[$location->cmid];
    $conditionposition = (object)array(
        'longitude' => $location->longitude,
        'latitude' => $location->latitude,
    );
    $distance = \availability_gps\block_gps_lib::get_distance($userposition, $conditionposition, 2);
    $chkdist = ($distance < $location->accuracy);
    $distlbl = ($distance !== -1) ? $distance . ' ' . get_string('meters', 'block_gps') : get_string('n_a', 'block_gps');

    $alt = ''; $icon = ''; $name = ''; $url = '';
    if ($location->cmid > 0) {
        $cm = $modinfo->get_cm($location->cmid);
        $alt = $cm->modname;
        $icon = (method_exists($cm, 'get_icon_url')?$cm->get_icon_url():'');
        $name = $cm->name;
        $url = $cm->url;
        $info = new \core_availability\info_module($cm);
    }
    if ($location->sectionid > 0) {
        $sec = $DB->get_record('course_sections', array('id' => $location->sectionid));
        $alt = get_string('section');
        $icon = '/pix/i/folder.svg';
        $name = $sec->name;
        $url = $CFG->wwwroot . '/course/view.php?id=' . $sec->course . '&sectionid=' . $sec->id . '#section-' . $sec->section;
        $info = new \core_availability\info_section($courseformat->get_section($sec));
    }
    $condition = new \availability_gps\condition($location);
    $available = $condition->is_available(false, $info, null, $USER->id);

    if ($location->revealname != 1 && $available != 1) {
        $name = get_string('n_a', 'block_gps');
        $url = '#';
    }

    ?>
        <tr data-available="<?php echo $available; ?>" style="<?php echo (!$available) ? 'background-color: rgba(200, 0, 0, 0.4) !important;' : ''; ?>">
            <td>
                <a href="<?php echo $url; ?>">
                    <img src="<?php echo $icon; ?>" alt="<?php echo $alt; ?>" style="max-height: 1em;" />
                    <?php echo $name; ?>
                </a>
            </td>
            <td align="center"><?php echo (($location->reveal)? $location->latitude . '<br />' . $location->longitude : get_string('n_a', 'block_gps')); ?></td>
            <td align="center"><?php echo $location->accuracy; ?></td>
            <td align="center"><?php echo $location->persistent; ?></td>
            <td align="center"><?php echo $distlbl; ?></td>
        </tr>
    <?php
}
?>
    </tbody>
</table>
<?php


echo $OUTPUT->footer();
