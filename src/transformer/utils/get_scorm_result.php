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

namespace src\transformer\utils;
defined('MOODLE_INTERNAL') || die();

function get_scorm_result($scormscoestracks, $rawscore) {
    $result = null;
    // error_log("in get scorm result....");
    // error_log(print_r($scormscoestracks, true));

    foreach ($scormscoestracks as $st) {
        // error_log("$st->element: $st->value");
        if ($st->element == 'cmi.core.lesson_status' ||
            $st->element == 'cmi.completion_status' ||
            $st->element == 'cmi.success_status') {
            // i'm ignoring stuff like 'unknown' or 'not attempted'
            if ($st->value == 'passed') {
                $result['success'] = TRUE;
            }
            if ($st->value == 'failed') {
                $result['success'] = FALSE;
            }
            if ($st->value == 'completed') {
                $result['completion'] = TRUE;
            }
            if ($st->value == 'incomplete') {
                $result['completion'] = FALSE;
            }
        } 
    }

    if ($rawscore !== null) {
        // this returns ['score' => [...]]
        $scoreobj = get_scorm_score($scormscoestracks, $rawscore);
    
        if ($scoreobj !== null) {
            $result['score'] = $scoreobj["score"];
        }
    }

    return $result;
}

