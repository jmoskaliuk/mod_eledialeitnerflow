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
 * AMD module: card transition animation between Leitner boxes.
 *
 * Shows feedback on the current question page after answering,
 * highlights which box the card moved to, then redirects to next question.
 *
 * @module     mod_eledialeitnerflow/card_transition
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    'use strict';

    return {
        /**
         * Animate the box-flow pills, show feedback, then redirect.
         *
         * @param {number} fromBox       - The box the card was in before.
         * @param {number} toBox         - The box the card moved to.
         * @param {number} correct       - 1 if the answer was correct, 0 if not.
         * @param {number} learned       - 1 if the card is now learned, 0 if not.
         * @param {string} nextUrl       - URL to redirect to (empty = no auto-redirect).
         * @param {number} feedbackstyle - 1=minimal, 2=animated, 3=detailed, 4=gamified.
         * @param {number} delay         - Milliseconds before redirect.
         */
        init: function(fromBox, toBox, correct, learned, nextUrl, feedbackstyle, delay) {
            // Default delay if not provided.
            if (!delay) {
                delay = 1000;
            }

            // Find the pill for the target box and briefly highlight it.
            var pills = document.querySelectorAll('[data-box]');

            pills.forEach(function(pill) {
                var boxNum = parseInt(pill.getAttribute('data-box'), 10);
                if (!boxNum) {
                    return;
                }
                if (boxNum === toBox && toBox !== fromBox) {
                    // Highlight target box with glow.
                    pill.classList.add('lf-anim-pulse-in');
                    if (correct) {
                        pill.style.boxShadow = '0 0 12px rgba(102, 153, 51, 0.6)';
                    } else {
                        pill.style.boxShadow = '0 0 12px rgba(249, 128, 18, 0.6)';
                    }
                }
            });

            // Animate gamified points float-up.
            if (feedbackstyle === 4) {
                var pointsEl = document.querySelector('.lf-points-float');
                if (pointsEl) {
                    pointsEl.classList.add('lf-points-animate');
                }
            }

            // Auto-redirect after delay (not for detailed mode — button handles it).
            if (nextUrl) {
                setTimeout(function() {
                    window.location.href = nextUrl;
                }, delay);
            }
        }
    };
});
