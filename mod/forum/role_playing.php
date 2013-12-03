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
 * Edit role playing name
 *
 * @package    mod_forum
 * @copyright  2013 Rossiani Wijaya
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/role_playing_form.php');

$cmid       = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('forum', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$forum = $DB->get_record('forum', array('id'=>$cm->instance), '*', MUST_EXIST);
$roleplaying = $DB->get_record('forum_role_playing', array('forum'=>$forum->id, 'userid'=>$USER->id));

require_login($course, false, $cm);

$context = context_module::instance($cmid);
require_capability('mod/forum:roleplaying', $context);

$PAGE->set_url('/mod/forum/role_playing.php', array('cmid'=>$cmid));
$PAGE->set_pagelayout('admin'); // TODO: Something. This is a bloody hack!

$roleplaying->cmid = $cmid;
$mform = new forum_role_playing_form(null, $roleplaying);

// If data submitted, then process and store.
if ($mform->is_cancelled()) {
    redirect("view.php?cmid=$cm->id");

} else if ($data = $mform->get_data()) {

    if ($data->id) {
        // store the files
        $DB->update_record('forum_role_playing', $data);
        //$DB->set_field('forum_role_playing', 'rolename', $forumroleplaying->rolename, array('id'=>$forumroleplaying->id));

        add_to_log($course->id, 'forum', 'update mod', '../mod/forum/role_playing.php?id='.$cm->id, '', $id);
        $params = array(
            'context' => $context,
            'objectid' => $data->id
        );
        /*
        $event = \mod_forum\event\chapter_updated::create($params);
        $event->add_record_snapshot('book_chapters', $data);
        $event->trigger();
        */
    } else {
        // adding new chapter
        $roleplayying = new stdClass;
        $roleplayying->forum = $data->forum;
        $roleplayying->userid = $data->userid;
        $roleplayying->rolename = $data->rolename;
        $roleplayying->cmid = $data->cmid;

        $roleid = $DB->insert_record('forum_role_playing', $roleplayying);

    }

    redirect("view.php?id=$cm->id");
}

// Otherwise fill and print the form.
$PAGE->set_title($forum->name);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($forum->name);

$mform->display();

echo $OUTPUT->footer();
