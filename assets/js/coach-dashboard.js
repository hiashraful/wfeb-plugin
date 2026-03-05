/**
 * WFEB Plugin - Coach Dashboard JavaScript
 * Handles all coach dashboard interactions: exam scoring, player management,
 * settings, credits, radar charts, and UI controls.
 *
 * @package WFEB_Plugin
 * @since 1.0.0
 */
'use strict';

(function ($) {
    $(document).ready(function () {
        console.log('[WFEB] Coach Dashboard JS initialized');

        // Fade in content after CSS is ready (prevents flash of unstyled content)
        $('.wfeb-content').animate({ opacity: 1 }, 250);

        // =====================================================================
        // UTILITY FUNCTIONS
        // =====================================================================

        /**
         * Escape HTML to prevent XSS.
         */
        function escapeHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        /**
         * Simple debounce utility.
         *
         * @param {Function} func     - Function to debounce.
         * @param {number}   delay    - Delay in milliseconds.
         * @returns {Function}
         */
        function debounce(func, delay) {
            var timer;
            return function () {
                var context = this;
                var args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    func.apply(context, args);
                }, delay);
            };
        }

        /**
         * Build a jQuery iOS spinner element (12 blade divs inside a container).
         */
        function createSpinner() {
            var $s = $('<div class="wfeb-ios-spinner" aria-hidden="true"></div>');
            for (var i = 0; i < 12; i++) {
                $s.append('<div class="wfeb-spinner-blade"></div>');
            }
            return $s;
        }

        /**
         * Put a button into / out of loading state.
         * Injects the iOS spinner on load, removes it on unload.
         *
         * @param {jQuery}  $btn
         * @param {boolean} isLoading
         */
        function setLoading($btn, isLoading) {
            if (isLoading) {
                $btn.addClass('wfeb-btn--loading').prop('disabled', true).append(createSpinner());
            } else {
                $btn.removeClass('wfeb-btn--loading').prop('disabled', false)
                    .find('.wfeb-ios-spinner').remove();
            }
        }

        // =====================================================================
        // TABLE SORTING
        // =====================================================================

        $(document).on('click', '.wfeb-sortable', function () {
            var $th = $(this);
            var $table = $th.closest('table');
            var colIndex = $th.index();
            var isAsc = $th.hasClass('sort-asc');

            // Reset all headers in this table.
            $table.find('.wfeb-sortable').removeClass('sort-asc sort-desc');

            // Toggle direction.
            var dir = isAsc ? 'desc' : 'asc';
            $th.addClass('sort-' + dir);

            // Sort rows.
            var $tbody = $table.find('tbody');
            var rows = $tbody.find('tr').get();

            rows.sort(function (a, b) {
                var aVal = $(a).children('td').eq(colIndex).text().trim().toLowerCase();
                var bVal = $(b).children('td').eq(colIndex).text().trim().toLowerCase();

                // Try numeric comparison first.
                var aNum = parseFloat(aVal.replace(/[^0-9.\-]/g, ''));
                var bNum = parseFloat(bVal.replace(/[^0-9.\-]/g, ''));
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return dir === 'asc' ? aNum - bNum : bNum - aNum;
                }

                // Try date comparison (dd Mon yyyy).
                var aDate = Date.parse(aVal);
                var bDate = Date.parse(bVal);
                if (!isNaN(aDate) && !isNaN(bDate)) {
                    return dir === 'asc' ? aDate - bDate : bDate - aDate;
                }

                // String comparison.
                if (aVal < bVal) return dir === 'asc' ? -1 : 1;
                if (aVal > bVal) return dir === 'asc' ? 1 : -1;
                return 0;
            });

            $.each(rows, function (i, row) {
                $tbody.append(row);
            });
        });

        // =====================================================================
        // TAB SKELETON LOADING
        // =====================================================================

        $(document).on('click', '.wfeb-tab', function (e) {
            var $tab = $(this);
            if ($tab.hasClass('wfeb-tab--active')) {
                e.preventDefault();
                return;
            }

            // Prevent immediate navigation so skeleton is visible
            e.preventDefault();
            var href = $tab.attr('href');

            // Show skeleton in place of the table
            var $card = $tab.closest('.wfeb-card');
            if ($card.length) {
                var cols = $card.find('.wfeb-table thead th').length || 6;
                var rows = '';
                for (var i = 0; i < 6; i++) {
                    rows += '<div class="wfeb-skeleton-row">';
                    for (var j = 0; j < cols; j++) {
                        var cls = j === 3 ? 'wfeb-skeleton-cell wfeb-skeleton-cell--badge' :
                                  (j === cols - 1 ? 'wfeb-skeleton-cell wfeb-skeleton-cell--short' : 'wfeb-skeleton-cell');
                        rows += '<div class="' + cls + '"></div>';
                    }
                    rows += '</div>';
                }
                $card.find('.wfeb-card-body').html('<div class="wfeb-table-skeleton">' + rows + '</div>');
            }

            // Update active tab visually
            $tab.siblings('.wfeb-tab').removeClass('wfeb-tab--active');
            $tab.addClass('wfeb-tab--active');

            // Navigate after skeleton is visible
            setTimeout(function () {
                window.location.href = href;
            }, 80);
        });

        // =====================================================================
        // 1. THEME TOGGLE
        // =====================================================================

        function wfebSetThemeCookie(theme) {
            document.cookie = 'wfeb_theme=' + theme + ';path=/;max-age=31536000;SameSite=Lax';
        }

        // Theme fallback
        if (!$('body').attr('data-theme')) {
            var stored = localStorage.getItem('wfeb_theme');
            if (!stored) {
                var m = document.cookie.match(/(?:^|; )wfeb_theme=([^;]*)/);
                if (m) stored = m[1];
            }
            stored = stored || 'light';
            $('html').attr('data-theme', stored);
            $('body').attr('data-theme', stored);
        }

        $('#wfeb-theme-toggle').on('click', function () {
            var current = $('body').attr('data-theme') || 'light';
            var next = current === 'dark' ? 'light' : 'dark';
            $('html').attr('data-theme', next);
            $('body').attr('data-theme', next);
            localStorage.setItem('wfeb_theme', next);
            wfebSetThemeCookie(next);
        });

        // =====================================================================
        // 2. MOBILE SIDEBAR
        // =====================================================================

        $('#wfeb-hamburger').on('click', function () {
            $('.wfeb-sidebar').toggleClass('open');
            $('#wfeb-sidebar-overlay').toggleClass('active');
        });

        $('#wfeb-sidebar-overlay').on('click', function () {
            $('.wfeb-sidebar').removeClass('open');
            $(this).removeClass('active');
        });

        // =====================================================================
        // 3. EXAM AUTO-CALCULATION (MOST IMPORTANT FEATURE)
        // =====================================================================

        /**
         * Sprint time-to-score conversion.
         *
         * @param {number} time - Sprint time in seconds.
         * @returns {number} Score 0-10.
         */
        function sprintTimeToScore(time) {
            if (isNaN(time) || time <= 0) return 0;
            if (time < 5.5) return 10;
            if (time < 6) return 9;
            if (time < 6.5) return 8;
            if (time < 7) return 7;
            if (time < 7.5) return 6;
            if (time < 8) return 5;
            if (time < 8.5) return 4;
            if (time < 9) return 3;
            if (time < 9.5) return 2;
            if (time < 10) return 1;
            return 0;
        }

        /**
         * Dribble time-to-score conversion.
         *
         * @param {number} time - Dribble time in seconds.
         * @returns {number} Score 0-10.
         */
        function dribbleTimeToScore(time) {
            if (isNaN(time) || time <= 0) return 0;
            if (time < 4) return 10;
            if (time < 4.5) return 9;
            if (time < 5) return 8;
            if (time < 5.5) return 7;
            if (time < 6) return 6;
            if (time < 6.5) return 5;
            if (time < 7) return 4;
            if (time < 7.5) return 3;
            if (time < 8) return 2;
            if (time < 8.5) return 1;
            return 0;
        }

        /**
         * Kickups count-to-score conversion (best of 3 attempts).
         *
         * @param {number} count - Best kickup count.
         * @returns {number} Score 0-10.
         */
        function kickupsToScore(count) {
            if (isNaN(count) || count <= 0) return 0;
            if (count >= 100) return 10;
            if (count >= 90) return 9;
            if (count >= 75) return 8;
            if (count >= 60) return 7;
            if (count >= 45) return 6;
            if (count >= 30) return 5;
            if (count >= 15) return 4;
            if (count >= 10) return 3;
            if (count >= 5) return 2;
            if (count >= 3) return 1;
            return 0;
        }

        /**
         * Determine achievement level from total score.
         *
         * @param {number} total - Total score across all categories.
         * @returns {string} Achievement level name.
         */
        function getAchievementLevel(total) {
            if (total >= 80) return 'MASTERY';
            if (total >= 70) return 'DIAMOND';
            if (total >= 60) return 'GOLD';
            if (total >= 50) return 'SILVER';
            if (total >= 40) return 'BRONZE';
            if (total >= 30) return 'MERIT+';
            if (total >= 20) return 'MERIT';
            if (total >= 15) return 'MERIT-';
            if (total >= 10) return 'PASS+';
            if (total >= 5) return 'PASS';
            return 'UNGRADED';
        }

        /**
         * Get a CSS class suffix for a given achievement level.
         *
         * @param {string} level - The achievement level string.
         * @returns {string} CSS-safe class suffix.
         */
        function getLevelClass(level) {
            return level.toLowerCase().replace(/[^a-z0-9]/g, '-');
        }

        /**
         * Animate a badge element with a bounce effect.
         *
         * @param {jQuery} $el - The element to animate.
         */
        function animateBadge($el) {
            $el.addClass('wfeb-animate-bounce');
            setTimeout(function () {
                $el.removeClass('wfeb-animate-bounce');
            }, 300);
        }

        /**
         * Get a numeric value from a form input, defaulting to 0.
         *
         * @param {string} selector - jQuery selector.
         * @returns {number}
         */
        function getVal(selector) {
            var val = parseFloat($(selector).val());
            return isNaN(val) ? 0 : val;
        }

        /**
         * Get the badge colour for a given achievement level.
         */
        function wfebLevelColor(level) {
            var map = {
                'MASTERY': '#FF0000', 'DIAMOND': '#B9F2FF', 'GOLD': '#FFD700',
                'SILVER': '#C0C0C0', 'BRONZE': '#CD7F32', 'MERIT+': '#4CAF50',
                'MERIT': '#66BB6A', 'MERIT-': '#81C784', 'PASS+': '#2196F3',
                'PASS': '#42A5F5', 'UNGRADED': '#9E9E9E'
            };
            return map[level] || '#9E9E9E';
        }

        /**
         * Get the playing level string for a given achievement level.
         */
        function getPlayingLevel(level) {
            var map = {
                'MASTERY': 'World Class',
                'DIAMOND': 'Professional',
                'GOLD': 'Semi-Professional',
                'SILVER': 'Advanced Amateur',
                'BRONZE': 'Amateur',
                'MERIT+': 'Intermediate',
                'MERIT': 'Developing',
                'MERIT-': 'Foundation Plus',
                'PASS+': 'Foundation',
                'PASS': 'Beginner',
                'UNGRADED': 'Ungraded'
            };
            return map[level] || 'Ungraded';
        }

        /**
         * Update a category breakdown bar and its value label.
         */
        function updateBar(category, score, max) {
            var pct = max > 0 ? Math.round((score / max) * 100) : 0;
            $('#wfeb-bar-' + category).css('width', pct + '%');
            $('#wfeb-val-' + category).text(score + '/' + max);
        }

        /**
         * Main recalculation function. Called whenever any exam score input changes.
         */
        function recalculateExam() {
            // Helper to read value by input name attribute.
            function nv(name) {
                var val = parseFloat($('[name="' + name + '"]').val());
                return isNaN(val) ? 0 : val;
            }

            // --- a. Short Passing (max 10) ---
            var shortPassTotal = nv('short_passing_left') + nv('short_passing_right');
            $('#wfeb-short-passing-badge').text(shortPassTotal + '/10');

            // --- b. Long Passing (max 10) ---
            var longPassTotal = nv('long_passing_left') + nv('long_passing_right');
            $('#wfeb-long-passing-badge').text(longPassTotal + '/10');

            // --- c. Shooting (max 20) ---
            var shootingTotal = nv('shooting_tl') + nv('shooting_tr') + nv('shooting_bl') + nv('shooting_br');
            $('#wfeb-shooting-badge').text(shootingTotal + '/20');

            // --- d. Sprint (max 10) ---
            var sprintScore = sprintTimeToScore(nv('sprint_time'));
            $('#wfeb-sprint-badge').text(sprintScore + '/10');

            // --- e. Dribble (max 10) ---
            var dribbleScore = dribbleTimeToScore(nv('dribble_time'));
            $('#wfeb-dribble-badge').text(dribbleScore + '/10');

            // --- f. Kickups (max 10) ---
            var kickupsBest = Math.max(nv('kickups_attempt1'), nv('kickups_attempt2'), nv('kickups_attempt3'));
            var kickupsScore = kickupsToScore(kickupsBest);
            $('#wfeb-kickups-badge').text(kickupsScore + '/10');

            // --- g. Volley (max 10) ---
            var volleyTotal = nv('volley_left') + nv('volley_right');
            $('#wfeb-volley-badge').text(volleyTotal + '/10');

            // --- h. Total Score ---
            var totalScore = shortPassTotal + longPassTotal + shootingTotal + sprintScore + dribbleScore + kickupsScore + volleyTotal;
            $('#wfeb-total-score').text(totalScore);

            // --- i. Achievement Level ---
            var level = getAchievementLevel(totalScore);
            $('#wfeb-award-level')
                .text(level)
                .css('background-color', wfebLevelColor(level));

            // --- j. Playing Level ---
            $('#wfeb-playing-level').text(getPlayingLevel(level));

            // --- k. Category Breakdown Bars ---
            updateBar('short-passing', shortPassTotal, 10);
            updateBar('long-passing', longPassTotal, 10);
            updateBar('shooting', shootingTotal, 20);
            updateBar('sprint', sprintScore, 10);
            updateBar('dribble', dribbleScore, 10);
            updateBar('kickups', kickupsScore, 10);
            updateBar('volley', volleyTotal, 10);
        }

        // Bind recalculation to all exam form score inputs
        if ($('#wfeb-exam-form').length) {
            // Listen on input change for all number/text inputs inside the exam form
            $('#wfeb-exam-form').on('input change', 'input[type="number"], input[type="text"], input.wfeb-score-input', function () {
                recalculateExam();
            });

            // Stepper buttons
            $(document).on('click', '.wfeb-stepper-minus', function () {
                var $input = $(this).siblings('input');
                var min = parseFloat($input.attr('min')) || 0;
                var step = parseFloat($input.attr('step')) || 1;
                var current = parseFloat($input.val()) || 0;
                var newVal = current - step;
                if (newVal >= min) {
                    $input.val(newVal).trigger('change');
                }
            });

            $(document).on('click', '.wfeb-stepper-plus', function () {
                var $input = $(this).siblings('input');
                var max = parseFloat($input.attr('max')) || 999;
                var step = parseFloat($input.attr('step')) || 1;
                var current = parseFloat($input.val()) || 0;
                var newVal = current + step;
                if (newVal <= max) {
                    $input.val(newVal).trigger('change');
                }
            });

            // Initial calculation on page load
            recalculateExam();
        }

        // =====================================================================
        // 4. PLAYER SELECT (Conduct Exam Page)
        // =====================================================================

        var $playerSelect = $('#wfeb-player-select');
        if ($playerSelect.length) {
            var $trigger   = $('#wfeb-player-trigger');
            var $dropdown  = $('#wfeb-player-dropdown');
            var $search    = $('#wfeb-player-search');
            var $options   = $('#wfeb-player-options');
            var $hiddenId  = $('#wfeb-exam-player-id');
            var isOpen     = false;

            var $parentCard = $playerSelect.closest('.wfeb-card');

            function openDropdown() {
                if (isOpen) return;
                isOpen = true;
                $playerSelect.addClass('wfeb-select--open');
                $parentCard.addClass('wfeb-card--select-open');
                $dropdown.show();
                $search.val('').trigger('focus');
                filterOptions('');
            }

            function closeDropdown() {
                if (!isOpen) return;
                isOpen = false;
                $playerSelect.removeClass('wfeb-select--open');
                $parentCard.removeClass('wfeb-card--select-open');
                $dropdown.hide();
                $options.find('.wfeb-select-option').removeClass('active');
            }

            function selectPlayer(id, name) {
                $hiddenId.val(id);
                $trigger.find('.wfeb-select-placeholder').remove();
                $trigger.find('.wfeb-select-chip').remove();
                var chip = '<span class="wfeb-select-chip" data-id="' + id + '">' +
                    escapeHtml(name) +
                    '<button type="button" class="wfeb-select-chip-remove" aria-label="Remove">&times;</button>' +
                '</span>';
                $trigger.find('.wfeb-select-arrow').before(chip);
                closeDropdown();
            }

            function clearSelection() {
                $hiddenId.val('');
                $trigger.find('.wfeb-select-chip').remove();
                if (!$trigger.find('.wfeb-select-placeholder').length) {
                    $trigger.find('.wfeb-select-arrow').before('<span class="wfeb-select-placeholder">Choose a player...</span>');
                }
            }

            function filterOptions(query) {
                var q = query.toLowerCase();
                var visible = 0;
                $options.find('.wfeb-select-option').each(function () {
                    var name = ($(this).data('name') || '').toLowerCase();
                    var email = ($(this).data('email') || '').toLowerCase();
                    var match = !q || name.indexOf(q) !== -1 || email.indexOf(q) !== -1;
                    $(this).toggle(match);
                    if (match) visible++;
                });
                $options.find('.wfeb-select-no-results').remove();
                if (visible === 0 && q) {
                    $options.append('<div class="wfeb-select-no-results">No players match "' + escapeHtml(query) + '"</div>');
                }
            }

            // Toggle dropdown on trigger click.
            $trigger.on('click', function (e) {
                if ($(e.target).closest('.wfeb-select-chip-remove').length) return;
                isOpen ? closeDropdown() : openDropdown();
            });

            // Remove chip.
            $trigger.on('click', '.wfeb-select-chip-remove', function (e) {
                e.stopPropagation();
                clearSelection();
            });

            // Filter on search input.
            $search.on('input', function () {
                filterOptions($(this).val().trim());
            });

            // Select an option.
            $options.on('click', '.wfeb-select-option', function () {
                selectPlayer($(this).data('id'), $(this).data('name'));
            });

            // Keyboard navigation.
            $search.on('keydown', function (e) {
                var $visible = $options.find('.wfeb-select-option:visible');
                if (!$visible.length) return;

                var $active = $visible.filter('.active');
                var idx = $visible.index($active);

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    $visible.removeClass('active');
                    $visible.eq(Math.min(idx + 1, $visible.length - 1)).addClass('active').get(0).scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    $visible.removeClass('active');
                    $visible.eq(Math.max(idx - 1, 0)).addClass('active').get(0).scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if ($active.length) $active.trigger('click');
                } else if (e.key === 'Escape') {
                    closeDropdown();
                }
            });

            // Close when clicking outside.
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#wfeb-player-select').length) {
                    closeDropdown();
                }
            });
        }

        // =====================================================================
        // 5. SAVE EXAM
        // =====================================================================

        /**
         * Collect all exam form data into a plain object.
         *
         * @returns {object} Key-value pairs of all exam fields.
         */
        function collectExamData() {
            var data = {};
            $('#wfeb-exam-form').find('input, select, textarea').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    data[name] = $(this).val();
                }
            });
            return data;
        }

        // Save as draft
        $('#wfeb-save-draft').on('click', function (e) {
            e.preventDefault();
            console.log('[WFEB] Saving exam as draft');

            var $btn = $(this);
            $btn.addClass('wfeb-btn--loading').prop('disabled', true);

            var examData = collectExamData();
            examData.action = 'wfeb_save_exam';
            examData.security = wfeb_coach.nonce;
            examData.status = 'draft';

            $.ajax({
                url: wfeb_coach.ajax_url,
                type: 'POST',
                data: examData,
                success: function (response) {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    if (response.success) {
                        showNotification('Exam saved as draft.', 'success');
                        console.log('[WFEB] Exam draft saved');
                    } else {
                        showNotification(response.data.message || 'Failed to save draft.', 'error');
                    }
                },
                error: function () {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    showNotification('Failed to save draft. Please try again.', 'error');
                }
            });
        });

        // Complete exam
        $('#wfeb-complete-exam').on('click', function (e) {
            e.preventDefault();
            console.log('[WFEB] Complete exam button clicked');

            var $btn = $(this);
            var credits = $btn.data('credits') || '?';

            // Validate required fields before showing confirmation
            var playerId = $('#wfeb-exam-player-id').val();
            var assistantExaminer = $('#wfeb-assistant-examiner').val();

            if (!playerId) {
                showNotification('Please select a player before completing the exam.', 'error');
                $('#wfeb-exam-player-id').focus();
                return;
            }

            if (!assistantExaminer || !assistantExaminer.trim()) {
                showNotification('Assistant Examiner is required to complete the exam.', 'error');
                $('#wfeb-assistant-examiner').focus();
                return;
            }

            showModal(
                '<div class="wfeb-modal-confirm">' +
                '<h3>Complete Exam</h3>' +
                '<p>This will use 1 credit. Credits remaining: <strong>' + escapeHtml(String(credits)) + '</strong></p>' +
                '<p>Generate certificate?</p>' +
                '<div class="wfeb-modal-actions">' +
                '<button class="wfeb-btn wfeb-btn--secondary" id="wfeb-modal-cancel">Cancel</button>' +
                '<button class="wfeb-btn wfeb-btn--primary" id="wfeb-modal-confirm-complete">Complete & Generate</button>' +
                '</div>' +
                '</div>'
            );

            // Cancel
            $(document).off('click', '#wfeb-modal-cancel').on('click', '#wfeb-modal-cancel', function () {
                hideModal();
            });

            // Confirm
            $(document).off('click', '#wfeb-modal-confirm-complete').on('click', '#wfeb-modal-confirm-complete', function () {
                hideModal();

                $btn.addClass('wfeb-btn--loading').prop('disabled', true);

                var examData = collectExamData();
                examData.action = 'wfeb_save_exam';
                examData.security = wfeb_coach.nonce;
                examData.status = 'completed';

                $.ajax({
                    url: wfeb_coach.ajax_url,
                    type: 'POST',
                    data: examData,
                    success: function (response) {
                        $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                        if (response.success) {
                            console.log('[WFEB] Exam completed successfully');
                            showModal(
                                '<div class="wfeb-modal-success">' +
                                '<h3>Exam Completed!</h3>' +
                                '<p>Certificate Number: <strong>' + escapeHtml(response.data.certificate_number || '') + '</strong></p>' +
                                '<div class="wfeb-modal-actions">' +
                                (response.data.view_url ? '<a href="' + response.data.view_url + '" class="wfeb-btn wfeb-btn--secondary">View Certificate</a>' : '') +
                                (response.data.download_url ? '<a href="' + response.data.download_url + '" class="wfeb-btn wfeb-btn--primary" download>Download Certificate</a>' : '') +
                                '<button class="wfeb-btn wfeb-btn--secondary" data-modal-close>Close</button>' +
                                '</div>' +
                                '</div>'
                            );
                        } else {
                            showNotification(response.data.message || 'Failed to complete exam.', 'error');
                        }
                    },
                    error: function () {
                        $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                        showNotification('Failed to complete exam. Please try again.', 'error');
                    }
                });
            });
        });

        // =====================================================================
        // 6. PLAYER CRUD
        // =====================================================================

        // Add / Update Player
        $('#wfeb-player-form').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var playerId = $form.find('input[name="player_id"]').val();
            var action = playerId ? 'wfeb_update_player' : 'wfeb_add_player';

            // Validate: email required when create account is checked.
            var $createAccountCb = $form.find('#wfeb-create-account');
            if ($createAccountCb.is(':checked') && !$.trim($form.find('#wfeb-player-email').val()).length) {
                showNotification('Email is required when creating a login account.', 'error');
                $form.find('#wfeb-player-email').focus();
                return;
            }

            console.log('[WFEB] Player form submit, action:', action);

            $btn.addClass('wfeb-btn--loading').prop('disabled', true);

            var formData = {};
            $form.find('input, select, textarea').each(function () {
                var $el = $(this);
                var name = $el.attr('name');
                if (name) {
                    if ($el.is(':checkbox') || $el.is(':radio')) {
                        if ($el.is(':checked')) {
                            formData[name] = $el.val();
                        }
                    } else if ($el.attr('type') !== 'file') {
                        formData[name] = $el.val();
                    }
                }
            });
            formData.action = action;
            formData.security = wfeb_coach.nonce;

            $.ajax({
                url: wfeb_coach.ajax_url,
                type: 'POST',
                data: formData,
                success: function (response) {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    if (response.success) {
                        var hasAccountWarning = response.data.account_warning;
                        var notifType = hasAccountWarning ? 'error' : 'success';
                        showNotification(response.data.message || 'Player saved.', notifType);
                        if (response.data.redirect_url) {
                            var delay = hasAccountWarning ? 3000 : 1000;
                            setTimeout(function () {
                                window.location.href = response.data.redirect_url;
                            }, delay);
                        }
                    } else {
                        showNotification(response.data.message || 'Failed to save player.', 'error');
                    }
                },
                error: function () {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        });

        // Delete Player
        $(document).on('click', '.wfeb-delete-player', function (e) {
            e.preventDefault();

            var playerId = $(this).data('player-id');
            var playerName = $(this).data('player-name') || 'this player';

            showModal(
                '<div class="wfeb-modal-confirm">' +
                '<h3>Delete Player</h3>' +
                '<p>Are you sure you want to delete <strong>' + escapeHtml(playerName) + '</strong>? This action cannot be undone.</p>' +
                '<div class="wfeb-modal-actions">' +
                '<button class="wfeb-btn wfeb-btn--secondary" data-modal-close>Cancel</button>' +
                '<button class="wfeb-btn wfeb-btn--danger" id="wfeb-confirm-delete-player" data-player-id="' + playerId + '">Delete</button>' +
                '</div>' +
                '</div>'
            );
        });

        $(document).on('click', '#wfeb-confirm-delete-player', function () {
            var playerId = $(this).data('player-id');
            var $btn = $(this);
            $btn.addClass('wfeb-btn--loading').prop('disabled', true);

            console.log('[WFEB] Deleting player:', playerId);

            $.ajax({
                url: wfeb_coach.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_delete_player',
                    player_id: playerId,
                    security: wfeb_coach.nonce
                },
                success: function (response) {
                    hideModal();
                    if (response.success) {
                        showNotification('Player deleted.', 'success');
                        // Remove the player row from the table
                        $('[data-player-row="' + playerId + '"]').fadeOut(300, function () {
                            $(this).remove();
                        });
                    } else {
                        showNotification(response.data.message || 'Failed to delete player.', 'error');
                    }
                },
                error: function () {
                    hideModal();
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        });

        // Player search on my-players page (client-side table filter)
        $('#wfeb-my-players-search').on('keyup', function () {
            var query = $(this).val().toLowerCase();
            $('.wfeb-players-table tbody tr').each(function () {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(query) !== -1);
            });
        });

        // Create Account checkbox UX
        (function () {
            var $emailInput = $('#wfeb-player-email');
            var $emailLabel = $emailInput.closest('.wfeb-form-group').find('.wfeb-form-label');
            var $accountRow = $('#wfeb-create-account-row');
            var $checkbox = $('#wfeb-create-account');
            var $helpText = $('.wfeb-create-account-help');
            var isEditNoAccount = $accountRow.data('edit-no-account') === 1;

            if ($emailInput.length && $accountRow.length) {

                // Toggle email required state based on checkbox.
                function toggleEmailRequired(required) {
                    if (required) {
                        $emailInput.prop('required', true);
                        if (!$emailLabel.find('.required').length) {
                            $emailLabel.append(' <span class="required">*</span>');
                        }
                    } else {
                        $emailInput.prop('required', false);
                        $emailLabel.find('.required').remove();
                    }
                }

                if (isEditNoAccount) {
                    // Edit mode without account: checkbox always visible, email required when checked.
                    $checkbox.on('change', function () {
                        if ($(this).is(':checked')) {
                            $helpText.slideDown(150);
                            toggleEmailRequired(true);
                        } else {
                            $helpText.slideUp(150);
                            toggleEmailRequired(false);
                        }
                    });
                } else {
                    // Add mode: show checkbox only when email is entered.
                    function toggleAccountRow() {
                        var hasEmail = $.trim($emailInput.val()).length > 0;
                        if (hasEmail) {
                            $accountRow.slideDown(200);
                        } else {
                            $accountRow.slideUp(200);
                            $checkbox.prop('checked', false);
                            $helpText.slideUp(150);
                        }
                    }

                    $emailInput.on('input change', toggleAccountRow);

                    // Initial state check.
                    toggleAccountRow();

                    $checkbox.on('change', function () {
                        if ($(this).is(':checked')) {
                            $helpText.slideDown(150);
                        } else {
                            $helpText.slideUp(150);
                        }
                    });
                }
            }
        })();

        // =====================================================================
        // 7. DELETE EXAM (Draft Only)
        // =====================================================================

        $(document).on('click', '.wfeb-delete-exam', function (e) {
            e.preventDefault();

            var examId = $(this).data('exam-id');

            showModal(
                '<div class="wfeb-modal-confirm">' +
                '<h3>Delete Draft Exam</h3>' +
                '<p>Are you sure you want to delete this draft exam? This cannot be undone.</p>' +
                '<div class="wfeb-modal-actions">' +
                '<button class="wfeb-btn wfeb-btn--secondary" data-modal-close>Cancel</button>' +
                '<button class="wfeb-btn wfeb-btn--danger" id="wfeb-confirm-delete-exam" data-exam-id="' + examId + '">Delete</button>' +
                '</div>' +
                '</div>'
            );
        });

        $(document).on('click', '#wfeb-confirm-delete-exam', function () {
            var examId = $(this).data('exam-id');
            var $btn = $(this);
            $btn.addClass('wfeb-btn--loading').prop('disabled', true);

            console.log('[WFEB] Deleting exam:', examId);

            $.ajax({
                url: wfeb_coach.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_delete_exam',
                    exam_id: examId,
                    security: wfeb_coach.nonce
                },
                success: function (response) {
                    hideModal();
                    if (response.success) {
                        showNotification('Draft exam deleted.', 'success');
                        $('[data-exam-row="' + examId + '"]').fadeOut(300, function () {
                            $(this).remove();
                        });
                    } else {
                        showNotification(response.data.message || 'Failed to delete exam.', 'error');
                    }
                },
                error: function () {
                    hideModal();
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 8. COACH SETTINGS
        // =====================================================================

        // Personal info + Professional details forms
        $('#wfeb-settings-personal, #wfeb-settings-professional').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            $btn.addClass('wfeb-btn--loading').prop('disabled', true);

            var formData = new FormData(this);
            formData.append('action', 'wfeb_update_coach_settings');
            formData.append('security', wfeb_coach.nonce);

            console.log('[WFEB] Updating coach profile settings');

            $.ajax({
                url: wfeb_coach.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    if (response.success) {
                        showNotification('Settings updated successfully.', 'success');
                    } else {
                        showNotification(response.data.message || 'Failed to update settings.', 'error');
                    }
                },
                error: function () {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        });

        // Password visibility toggle — inject SVG eye icon
        var eyeIcon = '<svg class="wfeb-icon-eye" viewBox="0 0 24 24">' +
            '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>' +
            '<circle cx="12" cy="12" r="3"/>' +
            '<line class="wfeb-eye-slash" x1="4" y1="4" x2="20" y2="20"/>' +
            '</svg>';

        $('.wfeb-password-toggle').each(function () {
            if (!$(this).find('svg').length) {
                $(this).html(eyeIcon);
            }
        });

        $(document).on('click', '.wfeb-password-toggle', function () {
            var $toggle = $(this);
            var $input = $toggle.siblings('input');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $toggle.attr('aria-pressed', 'true');
            } else {
                $input.attr('type', 'password');
                $toggle.attr('aria-pressed', 'false');
            }
        });

        // Password strength indicator
        $('#wfeb-settings-new-password').on('input', function () {
            var pw = $(this).val();
            var score = 0;
            if (pw.length >= 6) score++;
            if (pw.length >= 10) score++;
            if (/[A-Z]/.test(pw)) score++;
            if (/[0-9]/.test(pw)) score++;
            if (/[^A-Za-z0-9]/.test(pw)) score++;

            var pct, color, label;
            if (pw.length === 0) { pct = 0; color = ''; label = ''; }
            else if (score <= 1) { pct = 20; color = '#dc2626'; label = 'Very Weak'; }
            else if (score === 2) { pct = 40; color = '#f59e0b'; label = 'Weak'; }
            else if (score === 3) { pct = 60; color = '#f59e0b'; label = 'Fair'; }
            else if (score === 4) { pct = 80; color = '#16a34a'; label = 'Strong'; }
            else { pct = 100; color = '#16a34a'; label = 'Very Strong'; }

            $('#wfeb-password-strength-fill').css({ width: pct + '%', backgroundColor: color });
            $('#wfeb-password-strength-text').text(label).css('color', color);
        });

        // Password change form
        $('#wfeb-settings-password').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var currentPw = $form.find('#wfeb-settings-current-password').val();
            var newPw = $form.find('#wfeb-settings-new-password').val();
            var confirmPw = $form.find('#wfeb-settings-confirm-password').val();

            if (!currentPw || !newPw || !confirmPw) {
                showNotification('All password fields are required.', 'error');
                return;
            }
            if (newPw !== confirmPw) {
                showNotification('New passwords do not match.', 'error');
                return;
            }

            $btn.addClass('wfeb-btn--loading').prop('disabled', true);

            console.log('[WFEB] Changing coach password');

            $.ajax({
                url: wfeb_coach.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_change_password',
                    current_password: currentPw,
                    new_password: newPw,
                    confirm_password: confirmPw,
                    security: wfeb_coach.nonce
                },
                success: function (response) {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    if (response.success) {
                        showNotification('Password changed successfully.', 'success');
                        $form[0].reset();
                    } else {
                        showNotification(response.data.message || 'Failed to change password.', 'error');
                    }
                },
                error: function () {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        });

        // Profile picture (avatar) form
        $('#wfeb-settings-avatar').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var pictureId = $form.find('#wfeb-profile-picture').val();

            if (!pictureId) {
                showNotification('Please upload a profile picture first.', 'error');
                return;
            }

            $btn.addClass('wfeb-btn--loading').prop('disabled', true);

            console.log('[WFEB] Saving profile picture, attachment_id:', pictureId);

            $.ajax({
                url: wfeb_coach.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_update_coach_settings',
                    profile_picture: pictureId,
                    security: wfeb_coach.nonce
                },
                success: function (response) {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    if (response.success) {
                        showNotification('Profile picture saved successfully.', 'success');

                        // Update sidebar avatar dynamically.
                        var $preview = $form.find('.wfeb-upload-preview img');
                        if ($preview.length) {
                            var newSrc = $preview.attr('src');
                            var $sidebarAvatar = $('#wfeb-sidebar-avatar-img');
                            if ($sidebarAvatar.is('img')) {
                                $sidebarAvatar.attr('src', newSrc);
                            } else {
                                // Replace initials div with img.
                                $sidebarAvatar.replaceWith(
                                    '<img class="wfeb-sidebar-avatar" id="wfeb-sidebar-avatar-img" src="' + newSrc + '" alt="">'
                                );
                            }
                        }
                    } else {
                        showNotification(response.data.message || 'Failed to save profile picture.', 'error');
                    }
                },
                error: function () {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        });

        // Delete account
        $(document).on('click', '#wfeb-delete-account-btn', function (e) {
            e.preventDefault();

            showModal(
                '<div class="wfeb-modal-confirm wfeb-modal-confirm--danger">' +
                '<h3>Delete Account</h3>' +
                '<p>This action is permanent and cannot be undone. All your data, exams, and certificates will be permanently deleted.</p>' +
                '<p>Type <strong>DELETE</strong> to confirm:</p>' +
                '<input type="text" id="wfeb-delete-confirm-input" class="wfeb-input" placeholder="Type DELETE" />' +
                '<div class="wfeb-modal-actions">' +
                '<button class="wfeb-btn wfeb-btn--secondary" data-modal-close>Cancel</button>' +
                '<button class="wfeb-btn wfeb-btn--danger" id="wfeb-confirm-delete-account" disabled>Delete My Account</button>' +
                '</div>' +
                '</div>'
            );

            // Enable delete button only when user types DELETE
            $(document).on('input', '#wfeb-delete-confirm-input', function () {
                var val = $(this).val().trim();
                $('#wfeb-confirm-delete-account').prop('disabled', val !== 'DELETE');
            });
        });

        $(document).on('click', '#wfeb-confirm-delete-account', function () {
            var $btn = $(this);
            $btn.addClass('wfeb-btn--loading').prop('disabled', true);

            console.log('[WFEB] Deleting coach account');

            $.ajax({
                url: wfeb_coach.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_delete_coach_account',
                    confirm: 'DELETE',
                    security: wfeb_coach.nonce
                },
                success: function (response) {
                    if (response.success) {
                        showNotification('Account deleted. Redirecting...', 'success');
                        setTimeout(function () {
                            window.location.href = response.data.redirect_url || '/';
                        }, 2000);
                    } else {
                        hideModal();
                        showNotification(response.data.message || 'Failed to delete account.', 'error');
                    }
                },
                error: function () {
                    hideModal();
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 9. RADAR CHART (Exam Details Page)
        // =====================================================================

        (function initRadarChart() {
            var $canvas = $('#wfeb-radar-chart');
            if (!$canvas.length || typeof Chart === 'undefined') {
                return;
            }

            console.log('[WFEB] Initializing radar chart');

            var labels, scores, maxValues;
            try {
                labels    = JSON.parse($canvas.attr('data-labels') || '[]');
                scores    = JSON.parse($canvas.attr('data-scores') || '[]');
                maxValues = JSON.parse($canvas.attr('data-max') || '[]');
            } catch (err) {
                console.log('[WFEB] Failed to parse radar chart data:', err);
                return;
            }

            if (!labels.length || !scores.length) {
                console.log('[WFEB] No radar chart data found');
                return;
            }

            // Normalize scores to percentage (0-100) for even radar scaling
            var normalized = scores.map(function (s, i) {
                var m = maxValues[i] || 10;
                return Math.round((s / m) * 100);
            });

            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            var gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
            var labelColor = isDark ? '#cbd5e1' : '#334155';

            new Chart($canvas[0].getContext('2d'), {
                type: 'radar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Score %',
                        data: normalized,
                        backgroundColor: 'rgba(204, 51, 102, 0.15)',
                        borderColor: 'rgba(204, 51, 102, 0.8)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(204, 51, 102, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 20,
                                display: false
                            },
                            grid: {
                                color: gridColor
                            },
                            angleLines: {
                                color: gridColor
                            },
                            pointLabels: {
                                font: { size: 12, weight: '600' },
                                color: labelColor
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var idx = ctx.dataIndex;
                                    return scores[idx] + '/' + (maxValues[idx] || 10);
                                }
                            }
                        }
                    }
                }
            });
        })();

        // =====================================================================
        // 9b. OVERVIEW DONUT CHART
        // =====================================================================

        // =====================================================================
        // 9b-helper. OVERVIEW DONUT CHART (called after content is visible)
        // =====================================================================

        function initOverviewDonut() {
            var $canvas = $('#wfeb-overview-donut');
            if (!$canvas.length || typeof Chart === 'undefined') {
                return;
            }

            console.log('[WFEB] Initializing overview donut chart');

            var labels = [];
            var values = [];
            var colors = [];
            var total = parseInt($canvas.attr('data-total'), 10) || 0;

            try {
                labels = JSON.parse($canvas.attr('data-labels')) || [];
                values = JSON.parse($canvas.attr('data-values')) || [];
                colors = JSON.parse($canvas.attr('data-colors')) || [];
            } catch (e) {
                console.log('[WFEB] Failed to parse donut chart data');
                return;
            }

            var isDark = $('body').attr('data-theme') !== 'light';

            new Chart($canvas[0].getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderColor: isDark ? '#1A1A1F' : '#FFFFFF',
                        borderWidth: 3,
                        borderRadius: 4,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDark ? '#222228' : '#FFFFFF',
                            titleColor: isDark ? '#F0F0F2' : '#1A1714',
                            bodyColor: isDark ? '#8A8A94' : '#6B6560',
                            borderColor: isDark ? '#2A2A30' : '#E8E3DD',
                            borderWidth: 1,
                            cornerRadius: 8,
                            padding: 10,
                            bodyFont: {
                                family: 'Inter',
                                size: 13
                            },
                            titleFont: {
                                family: 'Inter',
                                size: 13,
                                weight: '600'
                            }
                        }
                    }
                },
                plugins: [{
                    id: 'centerText',
                    beforeDraw: function(chart) {
                        var ctx = chart.ctx;
                        var centerX = chart.chartArea.left + (chart.chartArea.right - chart.chartArea.left) / 2;
                        var centerY = chart.chartArea.top + (chart.chartArea.bottom - chart.chartArea.top) / 2;

                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';

                        // Total number
                        ctx.font = '700 28px Inter';
                        ctx.fillStyle = isDark ? '#F0F0F2' : '#1A1714';
                        ctx.fillText(total, centerX, centerY - 8);

                        // Label
                        ctx.font = '500 12px Inter';
                        ctx.fillStyle = isDark ? '#8A8A94' : '#6B6560';
                        ctx.fillText('Exams', centerX, centerY + 14);

                        ctx.restore();
                    }
                }]
            });
        }

        // =====================================================================
        // 9c. OVERVIEW SKELETON LOADING
        // =====================================================================

        (function initSkeleton() {
            var $skeleton = $('#wfeb-overview-skeleton');
            var $content = $('#wfeb-overview-content');

            if (!$skeleton.length || !$content.length) {
                return;
            }

            // Show skeleton briefly then reveal content, init donut after visible.
            setTimeout(function () {
                $skeleton.fadeOut(200, function () {
                    $content.css('display', '').hide().fadeIn(300, function () {
                        initOverviewDonut();
                    });
                });
            }, 400);
        })();

        // =====================================================================
        // 9c. PLAYER SCORE PROGRESS CHART
        // =====================================================================

        (function initPlayerScoreChart() {
            var $canvas = $('#wfeb-player-score-chart');
            if (!$canvas.length || typeof Chart === 'undefined') {
                return;
            }

            var labels = JSON.parse($canvas.attr('data-labels') || '[]');
            var scores = JSON.parse($canvas.attr('data-scores') || '[]');

            if (labels.length < 2) {
                return;
            }

            var isDark = $('body').attr('data-theme') !== 'light';
            var gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
            var labelColor = isDark ? '#94a3b8' : '#64748b';
            var lineColor = isDark ? '#4A7FBF' : '#0056A7';
            var pointColor = isDark ? '#4A7FBF' : '#0056A7';
            var fillColor = isDark
                ? 'rgba(0, 86, 167, 0.15)'
                : 'rgba(0, 86, 167, 0.08)';

            new Chart($canvas[0].getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Score',
                        data: scores,
                        borderColor: lineColor,
                        backgroundColor: fillColor,
                        pointBackgroundColor: pointColor,
                        pointBorderColor: isDark ? '#1a1a2e' : '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 2.5,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDark ? '#1e293b' : '#ffffff',
                            titleColor: isDark ? '#e2e8f0' : '#1e293b',
                            bodyColor: isDark ? '#cbd5e1' : '#475569',
                            borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function (ctx) {
                                    return 'Score: ' + ctx.parsed.y + '/80';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 80,
                            grid: { color: gridColor },
                            ticks: {
                                color: labelColor,
                                font: { size: 12 },
                                stepSize: 10,
                                callback: function (v) { return v + '/80'; }
                            },
                            border: { display: false }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: labelColor,
                                font: { size: 11 },
                                maxRotation: 45
                            },
                            border: { display: false }
                        }
                    }
                }
            });
        })();

        // =====================================================================
        // 10. MODAL SYSTEM
        // =====================================================================

        /**
         * Show a modal dialog with the provided HTML content.
         *
         * @param {string} content - HTML content for the modal body.
         */
        function showModal(content) {
            // Remove any existing modal
            hideModal();

            var $overlay = $('<div class="wfeb-modal-overlay"></div>');
            var $modal = $(
                '<div class="wfeb-modal">' +
                '<button class="wfeb-modal-close" aria-label="Close">&times;</button>' +
                '<div class="wfeb-modal-body">' + content + '</div>' +
                '</div>'
            );

            $overlay.append($modal);
            $('body').append($overlay);

            // Trigger CSS transition by adding class after DOM paint
            setTimeout(function () {
                $overlay.addClass('active');
            }, 10);

            // Prevent body scroll
            $('body').addClass('wfeb-modal-open');

            console.log('[WFEB] Modal opened');
        }

        /**
         * Hide and remove the active modal.
         */
        function hideModal() {
            $('.wfeb-modal-overlay').remove();
            $('body').removeClass('wfeb-modal-open');
        }

        // Close on overlay click (outside modal)
        $(document).on('click', '.wfeb-modal-overlay', function (e) {
            if ($(e.target).hasClass('wfeb-modal-overlay')) {
                hideModal();
            }
        });

        // Close on close button (X icon) or data-modal-close buttons (Cancel/Close)
        $(document).on('click', '.wfeb-modal-close, [data-modal-close]', function () {
            hideModal();
        });

        // Close on Escape key
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                hideModal();
            }
        });

        // Make modal functions globally accessible within this scope
        window.wfebShowModal = showModal;
        window.wfebHideModal = hideModal;

        // =====================================================================
        // 11. NOTIFICATIONS (Toast-style)
        // =====================================================================

        /**
         * Show a toast-style notification that auto-dismisses after 3 seconds.
         *
         * @param {string} message - The notification message.
         * @param {string} type    - 'success', 'error', 'info', or 'warning'.
         */
        function showNotification(message, type) {
            type = type || 'info';

            // Create container if it does not exist
            if (!$('#wfeb-notification-container').length) {
                $('body').append('<div id="wfeb-notification-container" class="wfeb-notification-container"></div>');
            }

            var $toast = $('<div class="wfeb-notification wfeb-notification--' + type + '">' + escapeHtml(message) + '</div>');
            $('#wfeb-notification-container').append($toast);

            // Trigger show animation
            setTimeout(function () {
                $toast.addClass('wfeb-notification--visible');
            }, 10);

            // Auto dismiss after 3 seconds
            setTimeout(function () {
                $toast.removeClass('wfeb-notification--visible');
                setTimeout(function () {
                    $toast.remove();
                }, 300);
            }, 3000);
        }

        // Make notification function globally accessible
        window.wfebShowNotification = showNotification;
        window.WFEB_showToast = showNotification;

        // =====================================================================
        // 12. CREDITS PAGE
        // =====================================================================

        $(document).on('click', '#wfeb-buy-credits-btn, .wfeb-buy-credits', function (e) {
            e.preventDefault();
            var url = $(this).data('product-url') || $(this).attr('href');
            if (url) {
                console.log('[WFEB] Redirecting to WooCommerce product:', url);
                window.location.href = url;
            }
        });

	// =====================================================================
	// Buy Credits: +/- quantity selector + live price + AJAX form submit
	// =====================================================================
	(function initBuyCredits() {
		var $qty = $( '#wfeb-credit-qty' );
		if ( ! $qty.length ) return; // Not on the buy-credits section.

		var $hiddenQty = $( '#wfeb-buy-credits-qty-hidden' );
		var $summary   = $( '#wfeb-credits-summary-line' );
		var $total     = $( '#wfeb-credits-total-amount' );
		var $form      = $( '#wfeb-buy-credits-form' );
		var price      = parseFloat( $qty.data( 'price' ) ) || 1;
		var sym        = ( typeof wfeb_coach !== 'undefined' && wfeb_coach.currency_symbol ) ? wfeb_coach.currency_symbol : '\u00a3';

		function updatePriceDisplay() {
			var qty   = parseInt( $qty.val(), 10 ) || 1;
			if ( qty < 1 ) qty = 1;
			if ( qty > 200 ) qty = 200;
			$qty.val( qty );
			$hiddenQty.val( qty );

			var total = ( qty * price ).toFixed( 2 );
			var word  = qty === 1 ? 'credit' : 'credits';
			$summary.text( qty + ' ' + word + ' \u00d7 ' + sym + price.toFixed( 2 ) );
			$total.text( sym + total );
		}

		// +/- buttons.
		$( document ).on( 'click', '.wfeb-qty-minus', function () {
			var v = parseInt( $qty.val(), 10 ) || 1;
			if ( v > 1 ) {
				$qty.val( v - 1 );
				updatePriceDisplay();
			}
		} );

		$( document ).on( 'click', '.wfeb-qty-plus', function () {
			var v = parseInt( $qty.val(), 10 ) || 1;
			if ( v < 200 ) {
				$qty.val( v + 1 );
				updatePriceDisplay();
			}
		} );

		$qty.on( 'input change', function () {
			updatePriceDisplay();
		} );

		// Form submit: AJAX → set cart → redirect to checkout.
		$form.on( 'submit', function ( e ) {
			e.preventDefault();
			var $btn        = $( '#wfeb-pay-now-btn' );
			var checkoutUrl = $( 'input[name="checkout_url"]', $form ).val();

			setLoading( $btn, true );

			$.post(
				wfeb_coach.ajax_url,
				{
					action:                 'wfeb_setup_credit_cart',
					product_id:             $( 'input[name="product_id"]', $form ).val(),
					quantity:               $hiddenQty.val(),
					checkout_url:           checkoutUrl,
					wfeb_buy_credits_nonce: $( 'input[name="wfeb_buy_credits_nonce"]', $form ).val(),
					_wpnonce:               $( 'input[name="_wpnonce"]', $form ).val(),
				},
				function ( response ) {
					if ( response.success && response.data.redirect ) {
						window.location.href = response.data.redirect;
					} else {
						setLoading( $btn, false );
						var msg = ( response.data && response.data.message )
							? response.data.message
							: 'An error occurred. Please try again.';
						alert( msg );
					}
				}
			).fail( function () {
				setLoading( $btn, false );
				alert( 'Request failed. Please try again.' );
			} );
		} );
	}() );

    }); // end document.ready
})(jQuery);
