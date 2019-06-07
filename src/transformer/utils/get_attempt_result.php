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

function get_attempt_result(array $config, $attempt, $quiz, $gradeitem, $attemptgrade) {
    // https://github.com/xAPI-vle/moodle-logstore_xapi/pull/489/files
    // $gradesum = floatval(isset($attempt->sumgrades) ? $attempt->sumgrades : 0);
    // this was the 'fix' on GH.. however it uses the grade across all attempts,
    // not the current attempt's grade despite the name of the variable
    // $gradesum = floatval(isset($attemptgrade->rawgrade) ? $attemptgrade->rawgrade : 0);

    $gradesum = $attempt->sumgrades;

    $graderesults = new \stdClass();
    if ($quiz->grade != $quiz->sumgrades) {
        $graderesults->grademin = 0;
        $graderesults->grademax = $quiz->sumgrades;
        $graderesults->gradepass = $gradeitem->gradepass;
    } else {
        $graderesults->grademin = $gradeitem->grademin;
        $graderesults->grademax = $gradeitem->grademax;
        $graderesults->gradepass = $gradeitem->gradepass;
    }

    $minscore = floatval($graderesults->grademin ?: 0);
    $maxscore = floatval($graderesults->grademax ?: 0);
    $passscore = floatval($graderesults->gradepass ?: 0);
    if ($passscore > 1) {
        // scale it to between 0 & 1
        $passscore = get_scaled_score($passscore, $gradeitem->grademin, $gradeitem->grademax);
    }

    $rawscore = cap_raw_score($gradesum, $minscore, $maxscore);
    $scaledscore = get_scaled_score($rawscore, $minscore, $maxscore);

    $completed = isset($attempt->state) ? $attempt->state === 'finished' : false;
    $success = $gradesum >= $passscore;
    $duration = get_attempt_duration($attempt);

    $result = [
        'score' => [
            'raw' => $rawscore,
            'min' => $minscore,
            'max' => $maxscore,
            'scaled' => $scaledscore,
        ],
        'completion' => $completed,
        'success' => $success,
        'extensions' => [
            'https://veracity.it/xapi/result/extensions' => [
                'scaledPassingScore' => $passscore
            ]
        ]
    ];

    if ($duration != null) {
        $result['duration'] = $duration;
    }

    return $result;
}
