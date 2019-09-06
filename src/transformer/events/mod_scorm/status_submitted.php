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

function status_submitted(array $config, \stdClass $event) {
    $repo = $config['repo'];
    $user = $repo->read_record_by_id('user', $event->userid);
    $course = $repo->read_record_by_id('course', $event->courseid);
    $sco = $repo->read_record_by_id("scorm_scoes", $event->objectid);
    $scorm = $repo->read_record_by_id('scorm', $sco->scorm);

    $lang = utils\get_course_lang($course);
    
    $object = utils\get_activity\scorm_sco(
        $config, 
        $event->contextinstanceid, 
        $scorm, 
        $lang,
        $sco
    );

    $unserializedcmi = unserialize($event->other);

    $attempt = $unserializedcmi['attemptid'];
    $scormscoestracks = $repo->read_records('scorm_scoes_track', [
        'userid' => $user->id,
        'scormid' => $event->objectid,
        'scoid' => $event->contextinstanceid,
        'attempt' => $unserializedcmi['attemptid']
    ]);


    $sco_attempt_obj = [
        'id' => utils\attemptid($object, utils\sco_attempt($event->userid, $scorm)),
        'definition' => [
            'type' => 'http://adlnet.gov/expapi/activities/attempt'
        ]
    ];

    $ctxscormprofile = utils\get_activity\scorm_profile();
    
    return [[
        'actor' => utils\get_user($config, $user),
        'verb' => utils\get_scorm_verb($scormscoestracks, $lang),
        // 'object' => utils\get_activity\course_scorm($config, $event->contextinstanceid, $scorm, $lang),
        'object' => $object,
        'timestamp' => utils\get_event_timestamp($event),
        'context' => [
            'platform' => $config['source_name'],
            'language' => $lang,
            'extensions' => utils\extensions\base($config, $event, $course),
            'contextActivities' => [
                'grouping' => [
                    utils\get_activity\site($config),
                    utils\get_activity\course($config, $course),
                    $sco_attempt_obj,
                ],
                'category' => [
                    utils\get_activity\source($config),
                    $ctxscormprofile,
                ]
            ],
        ]
    ]];
}