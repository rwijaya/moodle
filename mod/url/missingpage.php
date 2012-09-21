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
 * Missing page for unloadable URL
 *
 * @package    mod
 * @subpackage url
 * @copyright  2012 onwards Rossiani Wijaya <rwijaya@moodle.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

global $DB;

$id = required_param('id', PARAM_INT);  // Course module id
$msg = optional_param('msg', null, PARAM_RAW);  // messages

$cm = get_coursemodule_from_id('url', $id, 0, false, MUST_EXIST);
$url = $DB->get_record('url', array('id' => $cm->instance), '*', MUST_EXIST);

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/url:view', $context);

add_to_log($course->id, 'url', 'missing page', 'view.php?id=' . $cm->id, $url->id, $cm->id);

echo get_string('unabletoload', 'url') . '<br />';

if (!empty($msg)) {
    echo $msg;
}