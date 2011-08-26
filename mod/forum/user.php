<?php // $Id$

// Display user activity reports for a course

    require_once('../../config.php');
    require_once('lib.php');
    // Course ID
    $course  = required_param('course', PARAM_INT);
    // User ID
    $id      = optional_param('id', 0, PARAM_INT);
    $mode    = optional_param('mode', 'posts', PARAM_ALPHA);
    $page    = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 5, PARAM_INT);

    if (empty($id)) {         // See your own profile by default
        require_login();
        $id = $USER->id;
    }

    if (! $user = get_record("user", "id", $id)) {
        error("User ID is incorrect");
    }

    if (! $course = get_record("course", "id", $course)) {
        error("Course id is incorrect.");
    }

    // Make sure the current user is allowed to see this user
    if (empty($USER->id)) {
        $currentuser = false;
    } else {
        $currentuser = ($user->id == $USER->id);
    }

    $issiteadmin = is_siteadmin($USER->id);

    if ($course->id == SITEID) {
        $coursecontext = get_context_instance(CONTEXT_SYSTEM);   // SYSTEM context
    } else {
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);   // Course context
    }

    $syscontext = get_context_instance(CONTEXT_SYSTEM);
    $usercontext   = get_context_instance(CONTEXT_USER, $id);

    if (!empty($CFG->forcelogin) || $course->id != SITEID) {
        // do not force parents to enrol
        if (!get_record('role_assignments', 'userid', $USER->id, 'contextid', $usercontext->id)) {
            require_course_login($course);
        }
    }

    if (!empty($CFG->forceloginforprofiles)) {
        require_login();
        if (isguest()) {
            $loginurl ="$CFG->wwwroot/login/index.php";
            if (!empty($CFG->loginhttps)) {
                $loginurl = str_replace("http://", "https://", $loginurl);
            }
            redirect($loginurl);
        }
    }

    $strviewerror    = get_string('cannotviewdiscussionpost', 'error');
    $strparticipants = get_string('participants');
    $struser         = get_string('user');
    $strforumposts   = get_string('forumposts', 'forum');
    $strmode         = get_string($mode, 'forum');
    $fullname        = fullname($user, has_capability('moodle/site:viewfullnames', $syscontext));

    if ($course->id == SITEID) {
        if (empty($CFG->forceloginforprofiles) || isloggedin()) {
            // Search throughout the whole site.
            $searchcourse = 0;
        } else {
            $searchcourse = SITEID;
        }
    } else {
        // Search only for posts the user made in this course.
        $searchcourse = $course->id;
    }

    switch ($mode) {
        case 'posts' :
            $searchterms = array('userid:'.$user->id);
            $extrasql = '';
            break;

        default:
            $searchterms = array('userid:'.$user->id);
            $extrasql = 'AND p.parent = 0';
            break;
    }

    $posts = forum_search_posts($searchterms, $searchcourse, $page*$perpage, $perpage, $totalcount, $extrasql);
    $commoncourses = array();
    if (!$currentuser && !$issiteadmin) {
        $allmycourses = get_my_courses($USER->id);
        $allyourcourses = get_my_courses($user->id);
        if (empty($posts)) {
            $commoncourses = array_intersect_key($allmycourses, $allyourcourses);
        } else {
            $commoncourses = array_merge($allmycourses, $allyourcourses);
        }
    }

    /// If the user being shown is not ourselves, then make sure we are allowed to see them!
    if (!$currentuser && empty($commoncourses)) {
        if ($course->id == SITEID) {  // Reduce possibility of "browsing" userbase at site level
            if ($CFG->forceloginforprofiles && !$issiteadmin
                    && !has_capability('mod/forum:viewdiscussion', $usercontext)
                    && !has_capability('moodle/user:viewdetails', $usercontext)) {
                $navlinks[] = array('name' => $struser, 'link' => null, 'type' => 'misc');
                $navlinks[] = array('name' => $strforumposts, 'link' => '', 'type' => 'title');
                $navlinks[] = array('name' => $strmode, 'link' => '', 'type' => 'title');
                $navigation = build_navigation($navlinks);

                print_header($strviewerror, "", $navigation, "", "", true, "&nbsp;", navmenu($course));
                print_error('cannotviewdiscussionpost');
                print_footer($course);
            }
        } else {   // Normal course
            // check capabilities
            if (!has_capability('moodle/user:viewdetails', $coursecontext) &&
                !has_capability('moodle/user:viewdetails', $usercontext)) {
                print_error('cannotviewdiscussionpost');
            }

            if (!has_capability('moodle/course:view', $coursecontext, $user->id, false)) {
                if (has_capability('moodle/role:assign', $coursecontext)) {
                    $navlinks[] = array('name' => $fullname, 'link' => null, 'type' => 'misc');
                    $navigation = build_navigation($navlinks);
                    print_header($strviewerror, "", $navigation, "", "", true, "&nbsp;", navmenu($course));
                    print_heading(get_string('notenrolled', '', $fullname));

                } else {
                    $navlinks[] = array('name' => $struser, 'link' => null, 'type' => 'misc');
                    $navlinks[] = array('name' => $strforumposts, 'link' => '', 'type' => 'title');
                    $navlinks[] = array('name' => $strmode, 'link' => '', 'type' => 'title');
                    $navigation = build_navigation($navlinks);
                    print_header($strviewerror, "", $navigation, "", "", true, "&nbsp;", navmenu($course));
                    print_heading($strviewerror);
                }
                //print_continue($_SERVER['HTTP_REFERER']);
                print_footer($course);
                exit;
            }
        }

        // If groups are in use, make sure we can see that group
        if (groups_get_course_groupmode($course) == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups', $coursecontext)) {
            require_login();

            ///this is changed because of mygroupid
            $hasgroup = (bool)groups_get_all_groups($course->id, $user->id);
            if (!$hasgroup) {
                $navigation = build_navigation($navlinks);
                print_header($strviewerror, ": ", $navigation, "", "", true, "&nbsp;", navmenu($course));
                print_error("groupnotamember", '', "../course/view.php?id=$course->id");
            }
        }
    }

    if ($user->deleted) {
        print_header();
        print_heading(get_string('userdeleted'));
        print_footer($course);
        die;
    }

    add_to_log($course->id, "forum", "user report",
            "user.php?course=$course->id&amp;id=$user->id&amp;mode=$mode", "$user->id");

    if (empty($posts) && !$currentuser && !$issiteadmin) {
        $allmycourses = get_my_courses($USER->id);
        $allyourcourses = get_my_courses($user->id);

        $hascommoncourse = array_intersect_key($allmycourses, $allyourcourses);
        if (empty($hascommoncourse)) {
            $navlinks[] = array('name' => $struser, 'link' => null, 'type' => 'misc');
            $navigation = build_navigation($navlinks);
            print_header($strviewerror, "", $navigation, "", "", true, "&nbsp;", navmenu($course));
            print_heading($strviewerror . "boo ");
            print_footer($course);
            die;
        }
    }

    $navlinks = array();
    if (has_capability('moodle/course:viewparticipants', get_context_instance(CONTEXT_COURSE, $course->id)) || 
            has_capability('moodle/site:viewparticipants', $syscontext)) {
        $navlinks[] = array('name' => $strparticipants, 'link' => "$CFG->wwwroot/user/index.php?id=$course->id", 'type' => 'core');
    }
    $navlinks[] = array('name' => $fullname, 'link' => "$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$course->id", 'type' => 'title');
    $navlinks[] = array('name' => $strforumposts, 'link' => '', 'type' => 'title');
    $navlinks[] = array('name' => $strmode, 'link' => '', 'type' => 'title');

    $navigation = build_navigation($navlinks);

    print_header("$course->shortname: $fullname: $strmode", $course->fullname, $navigation);

    $currenttab = $mode;
    $showroles = 1;
    include($CFG->dirroot . '/user/tabs.php');   /// Prints out tabs as part of user page

    echo '<div class="user-content">';
    // Get the posts.
    if (!empty($posts)) {

        print_paging_bar($totalcount, $page, $perpage,
                         "user.php?id=$user->id&amp;course=$course->id&amp;mode=$mode&amp;perpage=$perpage&amp;");

        $discussions = array();
        $forums      = array();
        $cms         = array();

        foreach ($posts as $post) {

            if (!isset($discussions[$post->discussion])) {
                if (! $discussion = get_record('forum_discussions', 'id', $post->discussion)) {
                    error('Discussion ID was incorrect');
                }
                $discussions[$post->discussion] = $discussion;
            } else {
                $discussion = $discussions[$post->discussion];
            }

            if (!isset($forums[$discussion->forum])) {
                if (! $forum = get_record('forum', 'id', $discussion->forum)) {
                    error("Could not find forum $discussion->forum");
                }
                $forums[$discussion->forum] = $forum;
            } else {
                $forum = $forums[$discussion->forum];
            }

            $ratings = null;
            if ($forum->assessed) {
                if ($scale = make_grades_menu($forum->scale)) {
                    $ratings =new object();
                    $ratings->scale = $scale;
                    $ratings->assesstimestart = $forum->assesstimestart;
                    $ratings->assesstimefinish = $forum->assesstimefinish;
                    $ratings->allow = false;
                }
            }

            if (!isset($cms[$forum->id])) {
                if (!$cm = get_coursemodule_from_instance('forum', $forum->id)) {
                    error('Course Module ID was incorrect');
                }
                $cms[$forum->id] = $cm;
                unset($cm); // do not use cm directly, it would break caching
            }

            $fullsubject = "<a href=\"view.php?f=$forum->id\">".format_string($forum->name,true)."</a>";
            if ($forum->type != 'single') {
                $fullsubject .= " -> <a href=\"discuss.php?d=$discussion->id\">".format_string($discussion->name,true)."</a>";
                if ($post->parent != 0) {
                    $fullsubject .= " -> <a href=\"discuss.php?d=$post->discussion&amp;parent=$post->id\">".format_string($post->subject,true)."</a>";
                }
            }

            if ($course->id == SITEID && has_capability('moodle/site:config', $syscontext)) {
                $postcoursename = get_field('course', 'shortname', 'id', $forum->course);
                $fullsubject = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$forum->course.'">'.$postcoursename.'</a> -> '. $fullsubject;
            }

            $post->subject = $fullsubject;

            $fulllink = "<a href=\"discuss.php?d=$post->discussion#p$post->id\">".
                         get_string("postincontext", "forum")."</a>";

            forum_print_post($post, $discussion, $forum, $cms[$forum->id], $course, false, false, false, $ratings, $fulllink);
            echo "<br />";
        }

        print_paging_bar($totalcount, $page, $perpage,
                         "user.php?id=$user->id&amp;course=$course->id&amp;mode=$mode&amp;perpage=$perpage&amp;");
    } else {
        if ($mode == 'posts') {
            print_heading(get_string('noposts', 'forum'));
        } else {
            print_heading(get_string('nodiscussionsstartedby', 'forum'));
        }
    }
    echo '</div>';
    print_footer($course);

?>
