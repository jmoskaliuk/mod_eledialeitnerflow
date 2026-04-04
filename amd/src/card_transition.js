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
 * Shows a brief animation on the box-flow pills when arriving at
 * the next question, then fades out the feedback banner.
 *
 * @module     mod_leitnerflow/card_transition
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    'use strict';

    return {
        /**
         * Animate the box-flow pills and fade out feedback banner.
         *
         * @param {number} fromBox - The box the card was in before.
         * @param {number} toBox - The box the card moved to.
         * @param {boolean} correct - Whether the answer was correct.
         * @param {boolean} learned - Whether the card is now learned.
         */
        init: function(fromBox, toBox, correct, learned) {
            // Find the pill for the target box and briefly highlight it.
            var pills = document.querySelectorAll('.lf-transition-box, [data-box]');
            if (!pills.length) {
                // Fallback: find pills by class in the box-flow.
                pills = document.querySelectorAll('.badge.rounded-pill');
            }

            pills.forEach(function(pill) {
                var boxNum = parseInt(pill.getAttribute('data-box'), 10);
                if (!boxNum) {
                    return;
                }
                if (boxNum === toBox && toBox !== fromBox) {
                    // Highlight target box.
                    setTimeout(function() {
                        pill.classList.add('lf-anim-pulse-in');
                        if (correct) {
                            pill.style.boxShadow = '0 0 12px rgba(102, 153, 51, 0.6)';
                        } else {
                            pill.style.boxShadow = '0 0 12px rgba(249, 128, 18, 0.6)';
                        }
                        // Remove highlight after animation.
                        setTimeout(function() {
                            pill.classList.remove('lf-anim-pulse-in');
                            pill.style.boxShadow = '';
                        }, 1000);
                    }, 200);
                }
            });

            // Fade out feedback banner after 2.5 seconds.
            var banner = document.querySelector('.lf-feedback-banner');
            if (banner) {
                setTimeout(function() {
                    banner.style.transition = 'opacity 0.5s ease-out';
                    banner.style.opacity = '0';
                    setTimeout(function() {
                        banner.style.display = 'none';
                    }, 500);
                }, 2500);
            }
        }
    };
});
