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
 * AMD module: confirm dialog for reset buttons in the teacher report.
 *
 * @module     mod_eledialeitnerflow/confirm_reset
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/notification'], function(Notification) {
    'use strict';

    return {
        /**
         * Initialize confirm dialogs on all elements with data-confirm attribute.
         */
        init: function() {
            document.querySelectorAll('[data-confirm]').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var message = btn.getAttribute('data-confirm');
                    var href = btn.getAttribute('href');

                    Notification.confirm(
                        btn.textContent.trim(),
                        message,
                        btn.textContent.trim(),
                        null,
                        function() {
                            window.location.href = href;
                        }
                    );
                });
            });
        }
    };
});
