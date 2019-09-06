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

namespace src\transformer\events\mod_scorm;

defined('MOODLE_INTERNAL') || die();

use src\transformer\utils as utils;

function sco_launched(array $config, \stdClass $event) {
    $repo = $config['repo'];
    $user = $repo->read_record_by_id('user', $event->userid);
    $course = $repo->read_record_by_id('course', $event->courseid);

    $scorm = null;
    $sco = null;
    try {
        $scorm = $repo->read_record_by_id('scorm', $event->objectid);
    } catch (\Throwable $th) {
        if ($event->objecttable == "scorm_scoes") {
            $sco = $repo->read_record_by_id($event->objecttable, $event->objectid);
            $scorm = $repo->read_record_by_id('scorm', $sco->scorm);
        }  
    }
    $lang = utils\get_course_lang($course);
    
    $object = utils\get_activity\scorm_sco(
        $config, 
        $event->contextinstanceid, 
        $scorm, 
        $lang,
        $sco
    );

    $sco_attempt_obj = [
        'id' => utils\attemptid($object, utils\sco_attempt($event->userid, $scorm)),
        'definition' => [
            'type' => 'http://adlnet.gov/expapi/activities/attempt'
        ]
    ];


    $timestamp = utils\get_event_timestamp($event);
    $extensions = utils\extensions\base($config, $event, $course);
    $ctxsite = utils\get_activity\site($config);
    $ctxmoodlecourse = utils\get_activity\course($config, $course);
    $ctxscormcourse = utils\get_activity\course_scorm($config, $event->contextinstanceid, $scorm, $lang);
    $ctxsource = utils\get_activity\source($config);
    $ctxscormprofile = utils\get_activity\scorm_profile();


    return [[
        'actor' => utils\get_user($config, $user),
        'verb' => [
            'id' => 'http://adlnet.gov/expapi/verbs/initialized',
            'display' => [
                $lang => 'initialized'
            ],
        ],
        'object' => $object,
        'timestamp' => $timestamp,
        'context' => [
            'platform' => $config['source_name'],
            'language' => $lang,
            'extensions' => $extensions,
            'contextActivities' => [
                'grouping' => [
                    $ctxsite,
                    $ctxmoodlecourse,
                    $ctxscormcourse,
                    $sco_attempt_obj,
                ],
                'category' => [
                    $ctxsource,
                    $ctxscormprofile,
                ]
            ],
        ]
    ]];
}
