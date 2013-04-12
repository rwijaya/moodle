<?php

if ($hassiteconfig) { // speedup for non-admins, add all caps used on this page

    // "locations" settingpage
    $temp = new admin_settingpage('locationsettings', new lang_string('locationsettings', 'admin'));
    $options = get_list_of_timezones();
    $options[99] = new lang_string('serverlocaltime');
    $temp->add(new admin_setting_configselect('timezone', new lang_string('timezone','admin'), new lang_string('configtimezone', 'admin'), 99, $options));
    $options[99] = new lang_string('timezonenotforced', 'admin');
    $temp->add(new admin_setting_configselect('forcetimezone', new lang_string('forcetimezone', 'admin'), new lang_string('helpforcetimezone', 'admin'), 99, $options));

    $options = array('0' => new lang_string('default', 'calendar'),
                     DATEFORMAT_DDMMYYYY => new lang_string('dateformatddmmyyyy'),
                     DATEFORMAT_MMDDYYYY => new lang_string('dateformatmmddyyyy'),
                     DATEFORMAT_YYYYMMDD => new lang_string('dateformatyyyymmdd'));
    $temp->add(new admin_setting_configselect('site_dateformat', new lang_string('pref_dateformat', 'calendar'), new lang_string('explain_site_dateformat', 'calendar'), '0', $options));


    $options = array('0' => new lang_string('default', 'calendar'),
                     TIMEFORMAT_12 => new lang_string('timeformat12'),
                     TIMEFORMAT_24 => new lang_string('timeformat24'));
    $temp->add(new admin_setting_configselect('site_timeformat', new lang_string('pref_timeformat', 'calendar'), new lang_string('explain_site_timeformat', 'calendar'), '0', $options));

    $temp->add(new admin_settings_country_select('country', new lang_string('country', 'admin'), new lang_string('configcountry', 'admin'), 0));
    $temp->add(new admin_setting_configtext('defaultcity', new lang_string('defaultcity', 'admin'), new lang_string('defaultcity_help', 'admin'), ''));

    $temp->add(new admin_setting_heading('iplookup', new lang_string('iplookup', 'admin'), new lang_string('iplookupinfo', 'admin')));
    $temp->add(new admin_setting_configfile('geoipfile', new lang_string('geoipfile', 'admin'), new lang_string('configgeoipfile', 'admin', $CFG->dataroot.'/geoip/'), $CFG->dataroot.'/geoip/GeoLiteCity.dat'));
    $temp->add(new admin_setting_configtext('googlemapkey3', new lang_string('googlemapkey3', 'admin'), new lang_string('googlemapkey3_help', 'admin'), '', PARAM_RAW, 60));

    $temp->add(new admin_setting_configtext('allcountrycodes', new lang_string('allcountrycodes', 'admin'), new lang_string('configallcountrycodes', 'admin'), '', '/^(?:\w+(?:,\w+)*)?$/'));

    $ADMIN->add('location', $temp);

} // end of speedup
