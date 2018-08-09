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

namespace block_gps\privacy;

class provider implements \core_privacy\local\metadata\provider {
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'block_gps_reached',
             array(
                 'cmid' => 'privacy:reached:cmid',
                 'sectionid' => 'privacy:reached:sectionid',
                 'userid' => 'privacy:reached:userid',
                 'firstreach' => 'privacy:reached:firstreach',
             ),
            'privacy:reached'
        );

        return $collection;
    }
}

/**
 * Get the list of contexts that contain user information for the specified user.
 *
 * @param   int           $userid       The user to search.
 * @return  contextlist   $contextlist  The list of contexts used in this plugin.
*/
public static function get_contexts_for_userid(int $userid) : contextlist {
    $contextlist = new \core_privacy\local\request\contextlist();

    $sql = "SELECT * FROM {block_gps_reached} WHERE userid=?";
    $params = ['userid' => $userid ];
    $contextlist->add_from_sql($sql, $params);

    return $contextlist;
}
