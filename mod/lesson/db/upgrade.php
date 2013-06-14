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
 * This file keeps track of upgrades to
 * the lesson module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package    mod
 * @subpackage lesson
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 o
 */

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $oldversion
 * @return bool
 */
function xmldb_lesson_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();


    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this


    // Moodle v2.4.0 release upgrade line
    // Put any upgrade step following this


    // Moodle v2.5.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2013050101) {
        // Fixed page order for missing page in lesson
        upgrade_set_timeout(600);  // increase excution time for large sites

        $lessons = $DB->get_records('lesson');

        foreach ($lessons as $lesson) {
            $pages = $DB->get_records('lesson_pages', array('lessonid' => $lesson->id));

            // Make sure the prev/next id for each page refers to other pages within the lesson.
            // Otherwise set it to -1. This will prevent breakage during the update process.
            $iscorrupt = false;

            // Validate page link is exist within the lesson.
            foreach ($pages as $id => $page) {
                if ($page->prevpageid != 0 && !isset($pages[$page->prevpageid])) {
                    $page->prevpageid = -1;
                    $iscorrupt = true;
                }
                if ($page->nextpageid != 0 && !isset($pages[$page->nextpageid])) {
                    $page->nextpageid = -1;
                    $iscorrupt = true;
                }
            }

            // Check for duplicate end page ids.
            // If there is any multiple end page ids, set the last one as end page.
            $params = array();
            $params['lessonid'] = $lesson->id;
            $params['nextpageid'] = 0;
            $duplicateendpage = $DB->get_records('lesson_pages', $params);
            if (array_key_exists($page->id, $duplicateendpage) && count($duplicateendpage) > 1) {
                $iscorrupt = true;
                array_pop($duplicateendpage);
                foreach ($duplicateendpage as $duplicate) {
                    $pages[$duplicate->id]->nextpageid = -1;
                }
            }

            // Process the update
            $continue = true;
            $orderedpages = array();
            $lastpageid = 0;
            while($continue && $iscorrupt) {
                foreach($pages as $page) {
                    // Reset prevpageid if it is corrupted.
                    if ($lastpageid != 0 && $page->prevpageid == -1) {
                        $page->prevpageid = $lastpageid;
                    }
                    $orderedpages[$page->id] = $page;
                    $DB->set_field(lesson_pages, 'prevpageid', $lastpageid, array('id' => $page->id));
                    unset($pages[$page->id]);

                    // Reset nextpageid if it is corrupted.
                    if ($lastpageid !=0 && isset($orderedpages[$lastpageid]) && $orderedpages[$lastpageid]->nextpageid == -1) {
                        $orderedpages[$lastpageid]->nextpageid = $page->id;
                        $DB->set_field(lesson_pages, 'nextpageid', $page->id, array('id' => $orderedpages[$lastpageid]->id));
                    }
                    $lastpageid = $page->id;

                    if ((int)$page->nextpageid===0) {
                        $orderedpages[$page->id] = $page;
                        $continue = false;
                    }
                }
            }
        }
        upgrade_mod_savepoint(true, 2013050101, 'lesson');
    }
    return true;
}
