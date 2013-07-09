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

    if ($oldversion < 2012112901) {
        // Fixed page order for missing page in lesson
        upgrade_set_timeout(600);  // increase excution time for large sites
        // Validation test:
        // 1. Prev/next page id is exist within the lesson.
        // 2. Duplicate prev/next page id.
        // 3. No existence of first and/or last page.
        $sql = "SELECT lessonid FROM mdl_lesson_pages lp
                WHERE prevpageid != 0 AND
                      prevpageid NOT IN (SELECT id FROM mdl_lesson_pages WHERE lessonid = lp.lessonid)
                UNION
                    SELECT lessonid FROM mdl_lesson_pages lp
                    WHERE nextpageid != 0 AND
                          nextpageid NOT IN (SELECT id FROM mdl_lesson_pages WHERE lessonid = lp.lessonid)
                UNION
                     SELECT distinct(lessonid) FROM mdl_lesson_pages lp  group by lessonid, prevpageid HAVING COUNT(prevpageid) > 1
                UNION
                     SELECT distinct(lessonid) FROM mdl_lesson_pages lp  group by lessonid, nextpageid HAVING COUNT(nextpageid) > 1
                UNION
                    SELECT lessonid FROM mdl_lesson_pages lp WHERE nextpageid != 0 group by lessonid
                    HAVING COUNT(lessonid) = (SELECT COUNT(id) FROM mdl_lesson_pages WHERE lessonid = lp.lessonid)
                UNION
                    SELECT lessonid FROM mdl_lesson_pages lp WHERE prevpageid != 0 group by lessonid
                    HAVING COUNT(lessonid) = (SELECT COUNT(id) FROM mdl_lesson_pages WHERE lessonid = lp.lessonid)";

        $lessons = $DB->get_records_sql($sql);
        foreach ($lessons as $lesson) {
            $pages = $DB->get_records('lesson_pages', array('lessonid' => $lesson->lessonid));
            // Fix the corrupted prev and next id for all pages
            $count = 0;
            $lastpageid = 0;
            foreach($pages as $page) {
                $count++;
                if ($lastpageid == 0) {  // First page
                    $page->prevpageid = 0;
                    $page->nextpageid = 0;
                } elseif (count($pages) == $count) {  // Last page
                    $page->prevpageid = $lastpageid;
                    $page->nextpageid = 0;
                    $pages[$lastpageid]->nextpageid = $page->id;
                } else {
                    $page->prevpageid = $lastpageid;
                    $pages[$lastpageid]->nextpageid = $page->id;
                }
                $lastpageid = $page->id;
            }

            // Process the update for the corrupted lesson pages.
            foreach($pages as $fp) {
                $DB->update_record('lesson_pages', $fp);
            }
        }
        upgrade_mod_savepoint(true, 2012112901, 'lesson');
    }

    return true;
}


