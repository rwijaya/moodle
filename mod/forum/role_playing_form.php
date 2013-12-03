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
 * Chapter edit form
 *
 * @package    mod_forum
 * @copyright  2013 Rossiani Wijaya
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class forum_role_playing_form extends moodleform {

    function definition() {

        $mform = $this->_form;
        $userid = $this->_customdata->userid;
        $forum = $this->_customdata->forum;
        $cmid = $this->_customdata->cmid;
        $rolename = $this->_customdata->rolename;

        $mform->addElement('header', 'general', get_string('editingroleplaying', 'forum'));

        $mform->addElement('text', 'rolename', get_string('rolename', 'forum'), array('size'=>'30'));
        $mform->setType('rolename', PARAM_TEXT);
        $mform->setDefault('rolename', $rolename);


        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);;

        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'forum', $forum);
        $mform->setType('forum', PARAM_INT);

        $this->add_action_buttons(true);

    }

}
