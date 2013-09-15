<?php

/**
 * course_rating block class.
 *
 * The block is strictly accessable only to retrieve/insert information for current logged in user
 * and current view course.
 *
 * @package   block_course_rating
 * @copyright 2013 Rossiani Wijaya
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_course_rating extends block_base {
    function init() {
        $this->title = get_string('pluginname','block_course_rating');
    }

    function has_config() {
        return false;
    }

    public function applicable_formats() {
        return array('course-view' => true);
    }

    public function instance_allow_multiple() {
        return false;
    }

    function get_required_javascript() {
        global $PAGE;

        parent::get_required_javascript();

        $this->page->requires->jquery();
        $PAGE->requires->js('/blocks/course_rating/javascript/jRating.jquery.js', true);
    }

    /**
     * Display the block content
     */
    public function get_content() {
        global $USER, $COURSE;

        $disablevoting = $this->user_has_voted();

        $jscode = '<script>
                    $(function() {
                        $(".display_rating").jRating({
                            isDisabled: "'.$disablevoting.'"
                        });
                    });
                  </script>';

        $attributes = '';
        if (!$disablevoting) {
            $attributes = 'courseid="'.$COURSE->id.'"';
            $attributes .= ' userid="'.$USER->id.'"';
            $attributes .= ' sesskey="'.sesskey().'"';
        }

        $currentaverage = $this->get_rating_average($COURSE->id);
        $this->content = new stdClass();
        $this->content->text  = '<div class="display_rating" data-average="'.$currentaverage.'"'.$attributes.' ></div>';
        $this->content->text .= '<div class="average_display">'. get_string('averagedisplay', 'block_course_rating');
        $this->content->text .= '<span class="average_rating">'.$currentaverage.'</span></div>';
        $this->content->text .= '<div class="your_rating_display">'. get_string('yourrating', 'block_course_rating');
        $this->content->text .= '<span class="your_rating">'.$this->get_user_rating().'</span></div>';
        $this->content->text .= '<div class="rating_error"></div>';

        $this->content->text .= $jscode;

        return $this->content;

    }

    /**
     * Get the current course rating average
     */
    public function get_rating_average($courseid) {
        global $DB;

        if ($records = $DB->get_records('course_rating', array ('courseid' => $courseid), '', 'rate')) {
            $total = 0;
            foreach ($records as $record) {
                $total += $record->rate;
            }
            $average = $total / count($records);

            return $average;
        }
        return 0;
    }

    /**
     * Get the current course rating average
     */
    public function user_has_voted() {
        global $DB, $USER, $COURSE;

        $conditions = array('courseid' => $COURSE->id, 'userid' =>$USER->id);
        return ($DB->record_exists('course_rating', $conditions));
    }

    /**
     * Get the login user rating for the current view course
     */
    public function get_user_rating() {
        global $DB, $USER, $COURSE;

        $hasvoted = $this->user_has_voted();
        if ($hasvoted && ($record = $DB->get_record('course_rating', array ('userid' => $USER->id, 'courseid' => $COURSE->id), 'rate', MUST_EXIST))) {
            return $record->rate;
        }
        return '-';

    }
}

