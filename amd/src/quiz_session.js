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
 * AMD module: UX enhancements during a quiz attempt.
 *
 * Handles keyboard shortcuts (Enter = submit), auto-focus on first answer
 * input, and confirm-leave guard.
 *
 * @module     mod_eledialeitnerflow/quiz_session
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/log'], function(Log) {
    'use strict';

    return {
        init: function() {
            const form = document.getElementById('eledialeitnerflow-question-form');
            if (!form) {
                return;
            }

            // Auto-focus first interactive element
            const firstInput = form.querySelector('input[type="radio"], input[type="text"], textarea, select');
            if (firstInput) {
                firstInput.focus();
            }

            // Enter key submits the form (when not in textarea)
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                    const btn = document.getElementById('eledialeitnerflow-check-btn');
                    if (btn && !btn.disabled) {
                        btn.click();
                    }
                }
            });

            // Warn before leaving mid-question
            let submitted = false;
            form.addEventListener('submit', function() {
                submitted = true;
            });
            window.addEventListener('beforeunload', function(e) {
                if (!submitted) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            Log.debug('mod_eledialeitnerflow: quiz_session initialised');
        }
    };
});
