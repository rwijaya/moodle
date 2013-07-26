<?php
/**
 * Created by JetBrains PhpStorm.
 * User: rossianiw
 * Date: 26/07/13
 * Time: 12:04 PM
 * To change this template use File | Settings | File Templates.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/questionlib.php');
require_once($CFG->libdir. '/coursecatlib.php');

class delete_category_bulk_form extends moodleform {

    var $_categories;
    var $_category;

    function definition() {

        $mform = & $this->_form;
        $this->_categories = $this->_customdata;
        foreach ($this->_categories as $category) {
            $this->_category = $category;
            //$categorycontext = context_coursecat::instance($this->_category->id);
            $categorycontext = context_coursecat::instance($category->id);

            // Check permissions, to see if it OK to give the option to delete
            // the contents, rather than move elsewhere.
            $candeletecontent = $this->_category->can_delete_full();

            // Get the list of categories we might be able to move to.
            $displaylist = $this->_category->move_content_targets_list();

            // Now build the options.
            $options = array();
            if ($displaylist) {
                $options[0] = get_string('movecontentstoanothercategory');
            }
            if ($candeletecontent) {
                $options[1] = get_string('deleteallcannotundo');
            }
            if (empty($options)) {
                print_error('youcannotdeletecategory', 'error', 'index.php', $this->_category->get_formatted_name());
            }

            // Now build the form.
            $mform->addElement('header','general', get_string('categorycurrentcontents', '', $this->_category->get_formatted_name()));

            // Describe the contents of this category.
            $contents = '';
            if ($this->_category->has_children()) {
                $contents .= '<li>' . get_string('subcategories') . '</li>';
            }
            if ($this->_category->has_courses()) {
                $contents .= '<li>' . get_string('courses') . '</li>';
            }
            if (question_context_has_any_questions($categorycontext)) {
                $contents .= '<li>' . get_string('questionsinthequestionbank') . '</li>';
            }
            if (!empty($contents)) {
                $mform->addElement('static', 'emptymessage', get_string('thiscategorycontains'), html_writer::tag('ul', $contents));
            } else {
                $mform->addElement('static', 'emptymessage', '', get_string('deletecategoryempty'));
            }

            // Give the options for what to do.
            $mform->addElement('select', 'fulldelete'.$category->id, get_string('whattodo'), $options);
            if (count($options) == 1) {
                $optionkeys = array_keys($options);
                $option = reset($optionkeys);
                $mform->hardFreeze('fulldelete');
                $mform->setConstant('fulldelete', $option);
            }
            if ($displaylist) {
                $mform->addElement('select', 'newparent'.$category->id, get_string('movecategorycontentto'), $displaylist);
                if (in_array($this->_category->parent, $displaylist)) {
                    $mform->setDefault('newparent', $this->_category->parent);
                }
                $mform->disabledIf('newparent', 'fulldelete', 'eq', '1');
            }

            $mform->addElement('hidden', 'cat'.$category->id);
            $mform->setType('cat'.$category->id, PARAM_INT);
            $mform->setDefault('cat'.$category->id, $category->id);
            $mform->addElement('hidden', 'sure'.$category->id);
            $mform->setType('sure'.$category->id, PARAM_ALPHANUM);
            $mform->setDefault('sure'.$category->id, md5(serialize($this->_category)));

        }
        //--------------------------------------------------------------------------------
        $this->add_action_buttons(true, get_string('delete'));
        $mform->addElement('hidden', 'bulkcataction');
        $mform->setType('bulkcataction', PARAM_ALPHANUM);
        $this->set_data(array('bulkcataction' => 'bulkcatdelete'));
    }

}