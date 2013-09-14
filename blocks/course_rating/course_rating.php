<?php
/**
 * course_rating Ajax request process.
 *
 * @package   block_course_rating
 * @copyright 2013 Rossiani Wijaya
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once('../../lib/enrollib.php');
require_once('course_rating.php');

$courseid = required_param('courseid', PARAM_INT);
$action = required_param('action', PARAM_ALPHANUMEXT);
$userid = required_param('userid', PARAM_INT);
$rate = required_param('rate', PARAM_INT);

$action = htmlentities($action, ENT_QUOTES, 'UTF-8');

if ($action == 'save') {
    require_login();
    require_sesskey();
    // check current logged in user is the same as voter.
    if ($USER->id == $userid) {
        $enrolledcourses = enrol_get_my_courses();
        if (array_key_exists($courseid, $enrolledcourses)) {
            $response = array();
            $conditions = array('courseid' => $courseid, 'userid' =>$userid);
            // make sure user hasn't voted
            if (!$voted = $DB->record_exists('course_rating', $conditions)) {
                // check for course and user existence
                $data = new stdClass();
                $data->userid = $userid;
                $data->courseid = $courseid;
                $data->rate = $rate;
                $voteid = $DB->insert_record('course_rating', $data);

                $blockrating = new block_course_rating();
                $newaverage = $blockrating->get_rating_average();

                echo json_encode(array('message' => $newaverage));
            } else {
                echo json_encode('error'=> true, array('message' => 'already exist'));
            }
        } else {
            echo json_encode(array('error'=> true, 'message' => 'You are not enrol in this course'));
        }
    } else {
        echo json_encode(array('error'=> true, 'message' => 'Invalid user'));
    }
} else {
    echo json_encode(array('error'=> true, 'message' => 'Invalid action'));
}