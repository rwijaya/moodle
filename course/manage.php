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
 * Allows the admin to create, delete and rename course categories rearrange courses
 *
 * @package   core
 * @copyright 2013 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../config.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/coursecatlib.php');

// Category id.
$id = optional_param('categoryid', 0, PARAM_INT);
// Which page to show.
$page = optional_param('page', 0, PARAM_INT);
// How many per page.
$perpage = optional_param('perpage', $CFG->coursesperpage, PARAM_INT);

$search    = optional_param('search', '', PARAM_RAW);  // search words
$blocklist = optional_param('blocklist', 0, PARAM_INT);
$modulelist= optional_param('modulelist', '', PARAM_PLUGIN);
if (!$id && !empty($search)) {
    $searchcriteria = array('search' => $search);
} else if (!$id && !empty($blocklist)) {
    $searchcriteria = array('blocklist' => $blocklist);
} else if (!$id && !empty($modulelist)) {
    $searchcriteria = array('modulelist' => $modulelist);
} else {
    $searchcriteria = array();
}

// Actions to manage courses.
$hide = optional_param('hide', 0, PARAM_INT);
$show = optional_param('show', 0, PARAM_INT);
$moveup = optional_param('moveup', 0, PARAM_INT);
$movedown = optional_param('movedown', 0, PARAM_INT);
$moveto = optional_param('moveto', 0, PARAM_INT);
$resort = optional_param('resort', 0, PARAM_BOOL);
$action = optional_param('action', 0, PARAM_ALPHANUM);

// Actions to manage categories.
$deletecat = optional_param('deletecat', 0, PARAM_INT);
$hidecat = optional_param('hidecat', 0, PARAM_INT);
$showcat = optional_param('showcat', 0, PARAM_INT);
$movecat = optional_param('movecat', 0, PARAM_INT);
$movetocat = optional_param('movetocat', -1, PARAM_INT);
$moveupcat = optional_param('moveupcat', 0, PARAM_INT);
$movedowncat = optional_param('movedowncat', 0, PARAM_INT);
$bulkcataction = optional_param('bulkcataction', 0, PARAM_ALPHANUM);

require_login();

// Retrieve coursecat object
// This will also make sure that category is accessible and create default category if missing
$coursecat = coursecat::get($id);

if ($id) {
    $PAGE->set_category_by_id($id);
    $PAGE->set_url(new moodle_url('/course/manage.php', array('categoryid' => $id)));
    // This is sure to be the category context.
    $context = $PAGE->context;
    if (!can_edit_in_category($coursecat->id)) {
        redirect(new moodle_url('/course/index.php', array('categoryid' => $coursecat->id)));
    }
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/course/manage.php'));
    if (!can_edit_in_category()) {
        redirect(new moodle_url('/course/index.php'));
    }
}

$canmanage = has_capability('moodle/category:manage', $context);
$candelete = has_capability('moodle/course:delete', $context);
$canreset = has_capability('moodle/course:reset', $context);

// Process any category actions.
if (!empty($deletecat) and confirm_sesskey()) {
    // Delete a category.
    $cattodelete = coursecat::get($deletecat);
    $context = context_coursecat::instance($deletecat);
    require_capability('moodle/category:manage', $context);
    require_capability('moodle/category:manage', get_category_or_system_context($cattodelete->parent));

    $heading = get_string('deletecategory', 'moodle', format_string($cattodelete->name, true, array('context' => $context)));

    require_once($CFG->dirroot.'/course/delete_category_form.php');
    $mform = new delete_category_form(null, $cattodelete);
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/course/manage.php'));
    }

    // Start output.
    echo $OUTPUT->header();
    echo $OUTPUT->heading($heading);

    if ($data = $mform->get_data()) {
        // The form has been submit handle it.
        if ($data->fulldelete == 1 && $cattodelete->can_delete_full()) {
            $cattodeletename = $cattodelete->get_formatted_name();
            $deletedcourses = $cattodelete->delete_full(true);
            foreach ($deletedcourses as $course) {
                echo $OUTPUT->notification(get_string('coursedeleted', '', $course->shortname), 'notifysuccess');
            }
            echo $OUTPUT->notification(get_string('coursecategorydeleted', '', $cattodeletename), 'notifysuccess');
            echo $OUTPUT->continue_button(new moodle_url('/course/manage.php'));

        } else if ($data->fulldelete == 0 && $cattodelete->can_move_content_to($data->newparent)) {
            $cattodelete->delete_move($data->newparent, true);
            echo $OUTPUT->continue_button(new moodle_url('/course/manage.php'));
        } else {
            // Some error in parameters (user is cheating?)
            $mform->display();
        }
    } else {
        // Display the form.
        $mform->display();
    }
    // Finish output and exit.
    echo $OUTPUT->footer();
    exit();
}

if (!empty($movecat) and ($movetocat >= 0) and confirm_sesskey()) {
    // Move a category to a new parent if required.
    $cattomove = coursecat::get($movecat);
    if ($cattomove->parent != $movetocat) {
        if ($cattomove->can_change_parent($movetocat)) {
            $cattomove->change_parent($movetocat);
        } else {
            print_error('cannotmovecategory');
        }
    }
}

// Hide or show a category.
if ($hidecat and confirm_sesskey()) {
    $cattohide = coursecat::get($hidecat);
    require_capability('moodle/category:manage', get_category_or_system_context($cattohide->parent));
    $cattohide->hide();
} else if ($showcat and confirm_sesskey()) {
    $cattoshow = coursecat::get($showcat);
    require_capability('moodle/category:manage', get_category_or_system_context($cattoshow->parent));
    $cattoshow->show();
}

// bulk hide or show category
if (($bulkcataction === 'bulkcathide' || $bulkcataction === 'bulkcatshow') && ($data = data_submitted()) && confirm_sesskey()) {

    $categories = get_data_cat_ids($data);
    foreach ($categories as $category) {
        $cat = coursecat::get($category->id);
        require_capability('moodle/category:manage', get_category_or_system_context($cat->parent));
        if ($bulkcataction === 'bulkcathide') {
            $cat->hide();
        } else {
            $cat->show();
        }
    }
    redirect(new moodle_url('/course/manage.php', array('categoryid' => $category->id)));
}

// bulk delete category
if ($bulkcataction === 'bulkcatdelete' && ($data = data_submitted()) && confirm_sesskey()) {
    $catids = get_data_cat_ids($data);
    $coursecategories = array();
    foreach ($catids as $cat) {
        $cattodelete = coursecat::get($cat->id);
        $context = context_coursecat::instance($cat->id);
        require_capability('moodle/category:manage', $context);
        require_capability('moodle/category:manage', get_category_or_system_context($cattodelete->parent));
        array_push($coursecategories, $cattodelete);
    }
    // Start output.
    //$heading = get_string('deletecategory', 'moodle', null, true, array('context' => $context));

    //echo $OUTPUT->heading($heading);

    require_once($CFG->dirroot.'/course/delete_category_bulk_form.php');
    $mform = new delete_category_bulk_form(null, $coursecategories);

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/course/manage.php'));
    } else if ($data = $mform->get_data()) {
        $continuebutton = false;
        foreach ($coursecategories as $cattodelete) {

            // The form has been submit handle it.
            $fulldelete = 'fulldelete'.$cattodelete->id;
            $newparent = 'newparent'.$cattodelete->id;

            if ($data->$fulldelete == 1 && $cattodelete->can_delete_full()) {
                $cattodeletename = $cattodelete->get_formatted_name();
                $deletedcourses = $cattodelete->delete_full(true);

                foreach ($deletedcourses as $course) {
                    echo $OUTPUT->notification(get_string('coursedeleted', '', $course->shortname), 'notifysuccess');
                }
                echo $OUTPUT->notification(get_string('coursecategorydeleted', '', $cattodeletename), 'notifysuccess');
                echo $OUTPUT->continue_button(new moodle_url('/course/manage.php'));

            } else if ($data->$fulldelete == 0 && $cattodelete->can_move_content_to((int)$data->$newparent)) {
                $cattodelete->delete_move($data->$newparent, true);
                $continuebutton = true;
            } else {
                // Some error in parameters (user is cheating?)
                $mform->display();
            }
        }
        if ($continuebutton) {
            echo $OUTPUT->continue_button(new moodle_url('/course/manage.php'));
        }
        //redirect(new moodle_url('/course/manage.php'));
    } else {
        // Display the form.
        $mform->display();
    }

}

if ((!empty($moveupcat) or !empty($movedowncat)) and confirm_sesskey()) {
    // Move a category up or down.
    fix_course_sortorder();
    $swapcategory = null;

    if (!empty($moveupcat)) {
        require_capability('moodle/category:manage', context_coursecat::instance($moveupcat));
        if ($movecategory = $DB->get_record('course_categories', array('id' => $moveupcat))) {
            $params = array($movecategory->sortorder, $movecategory->parent);
            if ($swapcategory = $DB->get_records_select('course_categories', "sortorder<? AND parent=?", $params, 'sortorder DESC', '*', 0, 1)) {
                $swapcategory = reset($swapcategory);
            }
        }
    } else {
        require_capability('moodle/category:manage', context_coursecat::instance($movedowncat));
        if ($movecategory = $DB->get_record('course_categories', array('id' => $movedowncat))) {
            $params = array($movecategory->sortorder, $movecategory->parent);
            if ($swapcategory = $DB->get_records_select('course_categories', "sortorder>? AND parent=?", $params, 'sortorder ASC', '*', 0, 1)) {
                $swapcategory = reset($swapcategory);
            }
        }
    }
    if ($swapcategory and $movecategory) {
        $DB->set_field('course_categories', 'sortorder', $swapcategory->sortorder, array('id' => $movecategory->id));
        $DB->set_field('course_categories', 'sortorder', $movecategory->sortorder, array('id' => $swapcategory->id));
        cache_helper::purge_by_event('changesincoursecat');
        add_to_log(SITEID, "category", "move", "editcategory.php?id=$movecategory->id", $movecategory->id);
    }

    // Finally reorder courses.
    fix_course_sortorder();
}

if ($coursecat->id && $canmanage && $resort && confirm_sesskey()) {
    // Resort the category.
    if ($courses = get_courses($coursecat->id, '', 'c.id,c.fullname,c.sortorder')) {
        collatorlib::asort_objects_by_property($courses, 'fullname', collatorlib::SORT_NATURAL);
        $i = 1;
        foreach ($courses as $course) {
            $DB->set_field('course', 'sortorder', $coursecat->sortorder + $i, array('id' => $course->id));
            $i++;
        }
        // This should not be needed but we do it just to be safe.
        fix_course_sortorder();
        cache_helper::purge_by_event('changesincourse');
    }
}

if (!empty($moveto) && (($data = data_submitted()) && confirm_sesskey())) {
    // Move a specified course to a new category.
    // User must have category update in both cats to perform this.
    require_capability('moodle/category:manage', $context);
    require_capability('moodle/category:manage', context_coursecat::instance($moveto));

    if (!$destcategory = $DB->get_record('course_categories', array('id' => $data->moveto))) {
        print_error('cannotfindcategory', '', '', $data->moveto);
    }

    $courses = array();
    $coursestomove = get_data_course_ids($data);
    foreach ($coursestomove as $course) {
        if ($id && $course->category != $id) {
            print_error('coursedoesnotbelongtocategory');
        } else {
            array_push($courses, $course->id);
        }
    }

    move_courses($courses, $data->moveto);
}

if ((!empty($hide) or !empty($show)) && confirm_sesskey()) {
    // Hide or show a course.
    if (!empty($hide)) {
        $course = $DB->get_record('course', array('id' => $hide), '*', MUST_EXIST);
        $visible = 0;
    } else {
        $course = $DB->get_record('course', array('id' => $show), '*', MUST_EXIST);
        $visible = 1;
    }
    $coursecontext = context_course::instance($course->id);
    require_capability('moodle/course:visibility', $coursecontext);
    showhide_course ($course, $visible);
    cache_helper::purge_by_event('changesincourse');

}

if (($action === 'bulkhide' || $action === 'bulkshow') && ($data = data_submitted()) && confirm_sesskey()) {
    // Hide or show  courses.

    if ($action === 'bulkhide') {
        $visible = 0;
    } else {
        $visible = 1;
    }

    $coursecontext = context_course::instance($course->id);
    require_capability('moodle/course:visibility', $coursecontext);

    $courses = get_data_course_ids($data);
    bulk_showhide_courses($courses, $visible);
    cache_helper::purge_by_event('changesincourse');
}

if ($action === 'bulkdelete' && $candelete && ($data = data_submitted()) && confirm_sesskey()) {
    // Remove a specified course.
    $courses = array();

    $courses = get_data_course_ids($data);
    foreach($courses as $course) {
        if (!can_delete_course($course->id)) {
            print_error('cannotdeletecourse');
        }
    }

    delete_courses($courses);
}

if ($action === 'reset' && $canreset && ($data = data_submitted()) && confirm_sesskey()) {
    if (!isset($data->selectdefault) && !isset($data->deselectall)) {
        require_once('reset_form.php');

        $resetform = new course_reset_form();

        if ($resetform->is_cancelled()) {
            redirect($CFG->wwwroot.'/course/manage.php?categoryid='.$id);
        } else if ($resetdata = $resetform->get_data()) { // no magic quotes
            if (!isset($resetdata->selectdefault) && !isset($resetdata->deselectall)) {
                $courses = get_data_course_ids($data);
                foreach($courses as $course) {
                    $resetdata->courseid = $course->id;
                    $resetdata->id = $course->id;
                    $resetdata->reset_start_date_old = $course->startdate;
                    $status = reset_course_userdata($resetdata);
                }
                redirect(new moodle_url('/course/manage.php', array('categoryid' => $id)));
            }
        }
    }
}

if ((!empty($moveup) or !empty($movedown)) && confirm_sesskey()) {
    // Move a course up or down.
    require_capability('moodle/category:manage', $context);

    // Ensure the course order has continuous ordering.
    fix_course_sortorder();
    $swapcourse = null;

    if (!empty($moveup)) {
        if ($movecourse = $DB->get_record('course', array('id' => $moveup))) {
            $swapcourse = $DB->get_record('course', array('sortorder' => $movecourse->sortorder - 1));
        }
    } else {
        if ($movecourse = $DB->get_record('course', array('id' => $movedown))) {
            $swapcourse = $DB->get_record('course', array('sortorder' => $movecourse->sortorder + 1));
        }
    }
    if ($swapcourse and $movecourse) {
        // Check course's category.
        if ($movecourse->category != $id) {
            print_error('coursedoesnotbelongtocategory');
        }
        $DB->set_field('course', 'sortorder', $swapcourse->sortorder, array('id' => $movecourse->id));
        $DB->set_field('course', 'sortorder', $movecourse->sortorder, array('id' => $swapcourse->id));
        cache_helper::purge_by_event('changesincourse');
        add_to_log($movecourse->id, "course", "move", "edit.php?id=$movecourse->id", $movecourse->id);
    }
}

// Prepare the standard URL params for this page. We'll need them later.
$urlparams = array('categoryid' => $id);
if ($page) {
    $urlparams['page'] = $page;
}
if ($perpage) {
    $urlparams['perpage'] = $perpage;
}
$urlparams += $searchcriteria;

$PAGE->set_pagelayout('coursecategory');
$courserenderer = $PAGE->get_renderer('core', 'course');

if (can_edit_in_category()) {
    // Integrate into the admin tree only if the user can edit categories at the top level,
    // otherwise the admin block does not appear to this user, and you get an error.
    require_once($CFG->libdir . '/adminlib.php');
    if ($id) {
        navigation_node::override_active_url(new moodle_url('/course/index.php', array('categoryid' => $id)));
    }
    admin_externalpage_setup('coursemgmt', '', $urlparams, $CFG->wwwroot . '/course/manage.php');
    $settingsnode = $PAGE->settingsnav->find_active_node();
    if ($id && $settingsnode) {
        $settingsnode->make_inactive();
        $settingsnode->force_open();
        $PAGE->navbar->add($settingsnode->text, $settingsnode->action);
    }
} else {
    $site = get_site();
    $PAGE->set_title("$site->shortname: $coursecat->name");
    $PAGE->set_heading($site->fullname);
    $PAGE->set_button($courserenderer->course_search_form('', 'navbar'));
}

// Start output.
echo $OUTPUT->header();

if (!empty($searchcriteria)) {
    echo $OUTPUT->heading(new lang_string('searchresults'));

} else if ($action === 'resetoption' && $canreset && ($data = data_submitted()) && confirm_sesskey()) {
    require_once('reset_form.php');

    $resetform = new course_reset_form();
    $coursenames = array();

    $courses = get_data_course_ids($data);
    foreach($courses as $course) {
        $resetform->add_course_to_bulk_reset($course->id);
        array_push($coursenames, $course->fullname);
    }

    $listofcourses = get_string('listofcourses') . ': '. implode(', ', $coursenames);
    echo $OUTPUT->heading(get_string('resetcourses'));
    echo html_writer::tag('div', $listofcourses, array('class' => 'resetcourses'));
    $resetform->add_bulk_action($id);
    $resetform->display();
    echo $OUTPUT->footer();
    exit;

} else if ($action === 'reset' && $canreset && ($data = data_submitted()) && confirm_sesskey()) {
    require_once('reset_form.php');

    $resetform = new course_reset_form();
    if ($resetdata = $resetform->get_data()) {

        $_POST = array(); // clear up post data
        $mform = new course_reset_form();
        if (isset($resetdata->selectdefault)) { print "here";
            $mform->load_defaults();
        }

        $mform->add_bulk_action($id);
        $coursenames = array();

        $courses = get_data_course_ids($data);
        foreach($courses as $course) {
            $mform->add_course_to_bulk_reset($course->id);
            array_push($coursenames, $course->fullname);
        }

        $listofcourses = get_string('listofcourses') . ': '. implode(', ', $coursenames);
        echo $OUTPUT->heading(get_string('resetcourses'));
        echo html_writer::tag('div', $listofcourses, array('class' => 'resetcourses'));

        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }
}else if ($bulkcataction === 'bulkcatdeleteoption' && ($data = data_submitted()) && confirm_sesskey()) {
    $categories = get_data_cat_ids($data);
    $coursecategories = array();
    // Start output.
    $heading = get_string('deletecategory', 'moodle', null, true, array('context' => $context));

    foreach ($categories as $deletecat) {
        $cattodelete = coursecat::get($deletecat->id);
        $context = context_coursecat::instance($cattodelete->id);
        require_capability('moodle/category:manage', $context);
        require_capability('moodle/category:manage', get_category_or_system_context($cattodelete->parent));
        array_push($coursecategories, $cattodelete);
    }

        require_once($CFG->dirroot.'/course/delete_category_bulk_form.php');
        $mform = new delete_category_bulk_form(null, $coursecategories);
        if ($mform->is_cancelled()) {
            redirect(new moodle_url('/course/manage.php'));
        }

        // Display the form.
        $mform->display();
        //$mform->delete_bulk();

        // Finish output and exit.

    echo $OUTPUT->footer();
    exit();


} else if (!$coursecat->id) {
    // Print out the categories with all the knobs.
    $table = new html_table;
    $table->id = 'coursecategories';
    $table->attributes['class'] = 'admintable generaltable editcourse';
    $table->head = array(
        get_string('categories'),
        get_string('courses'),
        get_string('edit'),
        get_string('movecategoryto'),
        get_string('select')
    );
    $table->colclasses = array(
        'leftalign name',
        'centeralign count',
        'centeralign icons',
        'leftalign actions',
        'centeralign bulkcataction'
    );
    $table->data = array();

    $actionurl = new moodle_url('/course/manage.php');
    echo html_writer::start_tag('form', array('id' => 'bulkcataction', 'action' => $actionurl, 'method' => 'post'));
    echo html_writer::start_tag('div');
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

    print_category_edit($table, $coursecat);

    echo html_writer::table($table);
    print_bulk_actions();

    echo html_writer::end_tag('div');
    echo html_writer::end_tag('form');

} else {
    // Print the category selector.
    $displaylist = coursecat::make_categories_list();
    $select = new single_select(new moodle_url('/course/manage.php'), 'categoryid', $displaylist, $coursecat->id, null, 'switchcategory');
    $select->set_label(get_string('categories').':');

    echo html_writer::start_tag('div', array('class' => 'categorypicker'));
    echo $OUTPUT->render($select);
    echo html_writer::end_tag('div');
}

if ($canmanage && empty($searchcriteria)) {
    echo $OUTPUT->container_start('buttons');
    // Print button to update this category.
    if ($id) {
        $url = new moodle_url('/course/editcategory.php', array('id' => $id));
        echo $OUTPUT->single_button($url, get_string('editcategorythis'), 'get');
    }

    // Print button for creating new categories.
    $url = new moodle_url('/course/editcategory.php', array('parent' => $id));
    if ($id) {
        $title = get_string('addsubcategory');
    } else {
        $title = get_string('addnewcategory');
    }
    echo $OUTPUT->single_button($url, $title, 'get');
    echo $OUTPUT->container_end();
}

if (!empty($searchcriteria)) {
    $courses = coursecat::get(0)->search_courses($searchcriteria, array('recursive' => true,
        'offset' => $page * $perpage, 'limit' => $perpage, 'sort' => array('fullname' => 1)));
    $numcourses = count($courses);
    $totalcount = coursecat::get(0)->search_courses_count($searchcriteria, array('recursive' => true));
} else if ($coursecat->id) {
    // Print out all the sub-categories (plain mode).
    // In order to view hidden subcategories the user must have the viewhiddencategories.
    // capability in the current category..
    if (has_capability('moodle/category:viewhiddencategories', $context)) {
        $categorywhere = '';
    } else {
        $categorywhere = 'AND cc.visible = 1';
    }
    // We're going to preload the context for the subcategory as we know that we
    // need it later on for formatting.
    $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
    $sql = "SELECT cc.*, $ctxselect
              FROM {course_categories} cc
              JOIN {context} ctx ON cc.id = ctx.instanceid
             WHERE cc.parent = :parentid AND
                   ctx.contextlevel = :contextlevel
                   $categorywhere
          ORDER BY cc.sortorder ASC";
    $subcategories = $DB->get_recordset_sql($sql, array('parentid' => $coursecat->id, 'contextlevel' => CONTEXT_COURSECAT));
    // Prepare a table to display the sub categories.
    $table = new html_table;
    $table->attributes = array(
        'border' => '0',
        'cellspacing' => '2',
        'cellpadding' => '4',
        'class' => 'generaltable boxaligncenter category_subcategories'
    );
    //$table->head = array(new lang_string('subcategories'));
    $table->head = array(
        get_string('categories'),
        get_string('courses'),
        get_string('edit'),
        get_string('movecategoryto'),
        get_string('select')
    );
    $table->colclasses = array(
        'leftalign name',
        'centeralign count',
        'centeralign icons',
        'leftalign actions',
        'centeralign bulkcataction'
    );
    $table->data = array();
    $actionurl = new moodle_url('/course/manage.php');

    echo html_writer::start_tag('form', array('id' => 'bulkcataction', 'action' => $actionurl, 'method' => 'post'));
    echo html_writer::start_tag('div');
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));


    $baseurl = new moodle_url('/course/manage.php');
    foreach ($subcategories as $subcategory) {
        // Preload the context we will need it to format the category name shortly.
        context_helper::preload_from_record($subcategory);
        $context = context_coursecat::instance($subcategory->id);
        // Prepare the things we need to create a link to the subcategory.
        $attributes = $subcategory->visible ? array() : array('class' => 'dimmed');
        $text = format_string($subcategory->name, true, array('context' => $context));
        // Add the subcategory to the table.
        $baseurl->param('categoryid', $subcategory->id);
        $catobj = coursecat::get($subcategory->id);

        print_category_edit($table, $catobj, 0);
    }

    $subcategorieswereshown = (count($table->data) > 0);
    if ($subcategorieswereshown) {
        echo html_writer::table($table);
    }
    print_bulk_actions();
    echo html_writer::end_tag('form');

    $courses = get_courses_page($coursecat->id, 'c.sortorder ASC',
            'c.id,c.sortorder,c.shortname,c.fullname,c.summary,c.visible',
            $totalcount, $page*$perpage, $perpage);
    $numcourses = count($courses);
} else {
    $subcategorieswereshown = true;
    $courses = array();
    $numcourses = $totalcount = 0;
}

if (!$courses) {
    // There is no course to display.
    if (empty($subcategorieswereshown)) {
        echo $OUTPUT->heading(get_string("nocoursesyet"));
    }
} else {
    // Display a basic list of courses with paging/editing options.
    $table = new html_table;
    $table->attributes = array('border' => 0, 'cellspacing' => 0, 'cellpadding' => '4', 'class' => 'generaltable boxaligncenter');
    $table->head = array(
        get_string('courses'),
        get_string('edit'),
        get_string('select')
    );
    $table->colclasses = array(null, null, 'mdl-align');
    if (!empty($searchcriteria)) {
        // add 'Category' column
        array_splice($table->head, 1, 0, array(get_string('category')));
        array_splice($table->colclasses, 1, 0, array(null));
    }
    $table->data = array();

    $count = 0;

    // Checking if we are at the first or at the last page, to allow courses to
    // be moved up and down beyond the paging border.
    if ($totalcount > $perpage) {
        $atfirstpage = ($page == 0);
        if ($perpage > 0) {
            $atlastpage = (($page + 1) == ceil($totalcount / $perpage));
        } else {
            $atlastpage = true;
        }
    } else {
        $atfirstpage = true;
        $atlastpage = true;
    }

    $baseurl = new moodle_url('/course/manage.php', $urlparams + array('sesskey' => sesskey()));
    foreach ($courses as $acourse) {
        $coursecontext = context_course::instance($acourse->id);

        $count++;
        $up = ($count > 1 || !$atfirstpage);
        $down = ($count < $numcourses || !$atlastpage);

        $courseurl = new moodle_url('/course/view.php', array('id' => $acourse->id));
        $attributes = array();
        $attributes['class'] = $acourse->visible ? '' : 'dimmed';
        $coursename = get_course_display_name_for_list($acourse);
        $coursename = format_string($coursename, true, array('context' => $coursecontext));
        $coursename = html_writer::link($courseurl, $coursename, $attributes);

        $icons = array();
        // Update course icon.
        if (has_capability('moodle/course:update', $coursecontext)) {
            $url = new moodle_url('/course/edit.php', array('id' => $acourse->id, 'category' => $id, 'returnto' => 'catmanage'));
            $icons[] = $OUTPUT->action_icon($url, new pix_icon('t/edit', get_string('settings')));
        }

        // Role assignment icon.
        if (has_capability('moodle/course:enrolreview', $coursecontext)) {
            $url = new moodle_url('/enrol/users.php', array('id' => $acourse->id));
            $icons[] = $OUTPUT->action_icon($url, new pix_icon('t/enrolusers', get_string('enrolledusers', 'enrol')));
        }

        // Delete course icon.
        if (can_delete_course($acourse->id)) {
            $url = new moodle_url('/course/delete.php', array('id' => $acourse->id));
            $icons[] = $OUTPUT->action_icon($url, new pix_icon('t/delete', get_string('delete')));
        }

        // Change visibility.
        // Users with no capability to view hidden courses, should not be able to lock themselves out.
        if (has_any_capability(array('moodle/course:visibility', 'moodle/course:viewhiddencourses'), $coursecontext)) {
            if (!empty($acourse->visible)) {
                $url = new moodle_url($baseurl, array('hide' => $acourse->id));
                $icons[] = $OUTPUT->action_icon($url, new pix_icon('t/hide', get_string('hide')));
            } else {
                $url = new moodle_url($baseurl, array('show' => $acourse->id));
                $icons[] = $OUTPUT->action_icon($url, new pix_icon('t/show', get_string('show')));
            }
        }

        // Backup course icon.
        if (has_capability('moodle/backup:backupcourse', $coursecontext)) {
            $url = new moodle_url('/backup/backup.php', array('id' => $acourse->id));
            $icons[] = $OUTPUT->action_icon($url, new pix_icon('t/backup', get_string('backup')));
        }

        // Restore course icon.
        if (has_capability('moodle/restore:restorecourse', $coursecontext)) {
            $url = new moodle_url('/backup/restorefile.php', array('contextid' => $coursecontext->id));
            $icons[] = $OUTPUT->action_icon($url, new pix_icon('t/restore', get_string('restore')));
        }

        if ($canmanage) {
            if ($up && empty($searchcriteria)) {
                $url = new moodle_url($baseurl, array('moveup' => $acourse->id));
                $icons[] = $OUTPUT->action_icon($url, new pix_icon('t/up', get_string('moveup')));
            }
            if ($down && empty($searchcriteria)) {
                $url = new moodle_url($baseurl, array('movedown' => $acourse->id));
                $icons[] = $OUTPUT->action_icon($url, new pix_icon('t/down', get_string('movedown')));
            }
        }

        $table->data[] = new html_table_row(array(
            new html_table_cell($coursename),
            new html_table_cell(join('', $icons)),
            new html_table_cell(html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'c'.$acourse->id)))
        ));

        if (!empty($searchcriteria)) {
            // add 'Category' column
            $category = coursecat::get($acourse->category, IGNORE_MISSING, true);
            $cell = new html_table_cell($category->get_formatted_name());
            $cell->attributes['class'] = $category->visible ? '' : 'dimmed_text';
            array_splice($table->data[count($table->data) - 1]->cells, 1, 0, array($cell));
        }
    }

    $actionurl = new moodle_url('/course/manage.php', array('coursecategory' => $id, 'ccseskey' => sesskey()));
    $pagingurl = new moodle_url('/course/manage.php', array('categoryid' => $id, 'perpage' => $perpage) + $searchcriteria);

    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $pagingurl);
    echo html_writer::start_tag('form', array('id' => 'movecourses', 'action' => $actionurl, 'method' => 'post'));
    echo html_writer::start_tag('div');
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    foreach ($searchcriteria as $key => $value) {
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
    }
    echo html_writer::table($table);


    if ($canmanage) {
        echo html_writer::start_tag('div', array('class' => 'buttons text-right'));
        $movetocategories = coursecat::make_categories_list('moodle/category:manage');
        $movetocategories[$id] = get_string('moveselectedcoursesto');

        echo html_writer::label(get_string('moveselectedcoursesto'), 'movetoid', false, array('class' => 'accesshide'));
        echo html_writer::select($movetocategories, 'moveto', $id, null, array('id' => 'movetoid', 'class' => 'autosubmit'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'categoryid', 'value' => $id));

        $PAGE->requires->yui_module('moodle-core-formautosubmit',
            'M.core.init_formautosubmit',
            array(array('selectid' => 'movetoid', 'nothing' => $id))
        );

        // Bulk action
        $actions = array();
        $actions[0] = get_string('selectbulkcourseaction');
        $actions['bulkdelete'] = get_string('delete');
        $actions['resetoption'] = get_string('reset');
        $actions['bulkhide'] = get_string('hide');
        $actions['bulkshow'] = get_string('show');

        echo html_writer::label(get_string('selectbulkcourseaction'), 'action', false, array('class' => 'accesshide'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'categoryid', 'value' => $id));
        echo html_writer::select($actions, 'action', $actions, null, array('id' => 'action', 'class' => 'autosubmit'));

        $PAGE->requires->yui_module('moodle-core-formautosubmit',
            'M.core.init_formautosubmit',
            array(array('selectid' => 'action', 'nothing' => ''))
        );

        echo html_writer::end_tag('div');
    }


    echo html_writer::end_tag('div');
    echo html_writer::end_tag('form');
    echo html_writer::empty_tag('br');
}

echo html_writer::start_tag('div', array('class' => 'buttons'));
if ($canmanage and $numcourses > 1 && empty($searchcriteria)) {
    // Print button to re-sort courses by name.
    $url = new moodle_url('/course/manage.php', array('categoryid' => $id, 'resort' => 'name', 'sesskey' => sesskey()));
    echo $OUTPUT->single_button($url, get_string('resortcoursesbyname'), 'get');
}

if (has_capability('moodle/course:create', $context) && empty($searchcriteria)) {
    // Print button to create a new course.
    $url = new moodle_url('/course/edit.php');
    if ($coursecat->id) {
        $url->params(array('category' => $coursecat->id, 'returnto' => 'catmanage'));
    } else {
        $url->params(array('category' => $CFG->defaultrequestcategory, 'returnto' => 'topcatmanage'));
    }
    echo $OUTPUT->single_button($url, get_string('addnewcourse'), 'get');
}

if (!empty($CFG->enablecourserequests) && $id == $CFG->defaultrequestcategory) {
    print_course_request_buttons(context_system::instance());
}
echo html_writer::end_tag('div');

echo $courserenderer->course_search_form();

echo $OUTPUT->footer();

/**
 * Recursive function to print all the categories ready for editing.
 *
 * @param html_table $table The table to add data to.
 * @param coursecat $category The category to render
 * @param int $depth The depth of the category.
 * @param bool $up True if this category can be moved up.
 * @param bool $down True if this category can be moved down.
 */
function print_category_edit(html_table $table, coursecat $category, $depth = -1, $up = false, $down = false) {
    global $OUTPUT;

    static $str = null;

    if (is_null($str)) {
        $str = new stdClass;
        $str->edit = new lang_string('edit');
        $str->delete = new lang_string('delete');
        $str->moveup = new lang_string('moveup');
        $str->movedown = new lang_string('movedown');
        $str->edit = new lang_string('editthiscategory');
        $str->hide = new lang_string('hide');
        $str->show = new lang_string('show');
        $str->cohorts = new lang_string('cohorts', 'cohort');
        $str->spacer = $OUTPUT->spacer().' ';
    }

    if ($category->id) {

        $categorycontext = context_coursecat::instance($category->id);

        $attributes = array();
        $attributes['class'] = $category->visible ? '' : 'dimmed';
        $attributes['title'] = $str->edit;
        $categoryurl = new moodle_url('/course/manage.php', array('categoryid' => $category->id, 'sesskey' => sesskey()));
        $categoryname = $category->get_formatted_name();
        $categorypadding = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
        $categoryname = $categorypadding . html_writer::link($categoryurl, $categoryname, $attributes);

        $icons = array();
        if (has_capability('moodle/category:manage', $categorycontext)) {
            // Edit category.
            $icons[] = $OUTPUT->action_icon(
                new moodle_url('/course/editcategory.php', array('id' => $category->id)),
                new pix_icon('t/edit', $str->edit, 'moodle', array('class' => 'iconsmall')),
                null, array('title' => $str->edit)
            );
            // Delete category.
            $icons[] = $OUTPUT->action_icon(
                new moodle_url('/course/manage.php', array('deletecat' => $category->id, 'sesskey' => sesskey())),
                new pix_icon('t/delete', $str->delete, 'moodle', array('class' => 'iconsmall')),
                null, array('title' => $str->delete)
            );
            // Change visibility.
            if (!empty($category->visible)) {
                $icons[] = $OUTPUT->action_icon(
                    new moodle_url('/course/manage.php', array('hidecat' => $category->id, 'sesskey' => sesskey())),
                    new pix_icon('t/hide', $str->hide, 'moodle', array('class' => 'iconsmall')),
                    null, array('title' => $str->hide)
                );
            } else {
                $icons[] = $OUTPUT->action_icon(
                    new moodle_url('/course/manage.php', array('showcat' => $category->id, 'sesskey' => sesskey())),
                    new pix_icon('t/show', $str->show, 'moodle', array('class' => 'iconsmall')),
                    null, array('title' => $str->show)
                );
            }
            // Cohorts.
            if (has_any_capability(array('moodle/cohort:manage', 'moodle/cohort:view'), $categorycontext)) {
                $icons[] = $OUTPUT->action_icon(
                    new moodle_url('/cohort/index.php', array('contextid' => $categorycontext->id)),
                    new pix_icon('t/cohort', $str->cohorts, 'moodle', array('class' => 'iconsmall')),
                    null, array('title' => $str->cohorts)
                );
            }
            // Move up/down.
            if ($up) {
                $icons[] = $OUTPUT->action_icon(
                    new moodle_url('/course/manage.php', array('moveupcat' => $category->id, 'sesskey' => sesskey())),
                    new pix_icon('t/up', $str->moveup, 'moodle', array('class' => 'iconsmall')),
                    null, array('title' => $str->moveup)
                );
            } else {
                $icons[] = $str->spacer;
            }
            if ($down) {
                $icons[] = $OUTPUT->action_icon(
                    new moodle_url('/course/manage.php', array('movedowncat' => $category->id, 'sesskey' => sesskey())),
                    new pix_icon('t/down', $str->movedown, 'moodle', array('class' => 'iconsmall')),
                    null, array('title' => $str->movedown)
                );
            } else {
                $icons[] = $str->spacer;
            }
        }

        $actions = '';
        if (has_capability('moodle/category:manage', $categorycontext)) {
            $popupurl = new moodle_url('/course/manage.php', array('movecat' => $category->id, 'sesskey' => sesskey()));
            $tempdisplaylist = array(0 => get_string('top')) + coursecat::make_categories_list('moodle/category:manage', $category->id);
            $select = new single_select($popupurl, 'movetocat', $tempdisplaylist, $category->parent, null, "moveform$category->id");
            $select->set_label(get_string('frontpagecategorynames'), array('class' => 'accesshide'));
            $actions = $OUTPUT->render($select);

            $bulkcataction = html_writer::empty_tag('input', array('type' => 'checkbox', 'name' => 'cat'.$category->id));
        }

        $table->data[] = new html_table_row(array(
            // Category name.
            new html_table_cell($categoryname),
            // Course count.
            new html_table_cell($category->coursecount),
            // Icons.
            new html_table_cell(join(' ', $icons)),
            // Actions.
            new html_table_cell($actions),
            // Bulk actions
            new html_table_cell ($bulkcataction)
        ));
    }

    if ($categories = $category->get_children()) {
        // Print all the children recursively.
        $countcats = count($categories);
        $count = 0;
        $first = true;
        $last = false;
        foreach ($categories as $cat) {
            $count++;
            if ($count == $countcats) {
                $last = true;
            }
            $up = $first ? false : true;
            $down = $last ? false : true;
            $first = false;

            print_category_edit($table, $cat, $depth+1, $up, $down);
        }
    }
}

/**
 * Get the courseid from submitted form
 *
 * @param array $data The submitted form data
 * @return array
 */
Function get_data_course_ids($data) {
    global $DB;

    $courses = array();
    foreach ($data as $key => $value) {
        if (preg_match('/^c\d+$/', $key)) {
            $courseid = substr($key, 1);
            // Get the course
            if ($course = $DB->get_record('course', array('id' => $courseid))) {
                array_push($courses, $course);
            } else {
                print_error('cannotfindcourse');
            }
        }
    }
    return $courses;
}

/**
 * Get the catids from submitted form
 *
 * @param array $data The submitted form data
 * @return array
 */
Function get_data_cat_ids($data) {
    global $DB;

    $categories = array();
    foreach ($data as $key => $value) {
        if (preg_match('/^cat\d+$/', $key)) {
            $catid = substr($key, 3);
            if ($category = $DB->get_record('course_categories', array('id' => $catid))) {
                array_push($categories, $category);
            } else {
                print_error('cannotfindcategory', 'error', '', $catid);
            }

        }
    }
    return $categories;
}

function print_bulk_actions () {
    global $PAGE;

    $actions = array();
    $actions[0] = get_string('selectbulkcataction');
    $actions['bulkcatdeleteoption'] = get_string('delete');
    $actions['bulkcathide'] = get_string('hide');
    $actions['bulkcatshow'] = get_string('show');

    echo html_writer::start_tag('div', array('class' => 'buttons text-right'));
    echo html_writer::label(get_string('selectbulkcataction'), 'bulkcataction', false, array('class' => 'accesshide'));
    echo html_writer::select($actions, 'bulkcataction', $actions, null, array('id' => 'bulkcataction', 'class' => 'autosubmit'));
    $PAGE->requires->yui_module('moodle-core-formautosubmit',
        'M.core.init_formautosubmit',
        array(array('selectid' => 'bulkcataction', 'nothing' => ''))
    );
    echo html_writer::end_tag('div');
}