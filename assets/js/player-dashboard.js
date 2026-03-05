/**
 * WFEB Plugin - Player Dashboard JavaScript
 * Handles player dashboard interactions: profile, certificates, score charts,
 * radar charts, password management, and UI controls.
 *
 * @package WFEB_Plugin
 * @since 1.0.0
 */
'use strict';

(function ($) {
    $(document).ready(function () {
        console.log('[WFEB] Player Dashboard JS initialized');

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

        // =====================================================================
        // 1. THEME TOGGLE
        // =====================================================================

        function wfebSetThemeCookie(theme) {
            document.cookie = 'wfeb_theme=' + theme + ';path=/;max-age=31536000;SameSite=Lax';
        }

        (function initTheme() {
            var saved = localStorage.getItem('wfeb_theme');
            if (!saved) {
                var m = document.cookie.match(/(?:^|; )wfeb_theme=([^;]*)/);
                if (m) saved = m[1];
            }
            saved = saved || 'light';
            document.documentElement.setAttribute('data-theme', saved);
            document.body.setAttribute('data-theme', saved);
        })();

        $('#wfeb-theme-toggle').on('click', function () {
            var current = document.documentElement.getAttribute('data-theme') || 'light';
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            document.body.setAttribute('data-theme', next);
            localStorage.setItem('wfeb_theme', next);
            wfebSetThemeCookie(next);
            console.log('[WFEB] Theme switched to', next);
        });

        // =====================================================================
        // 2. MOBILE SIDEBAR
        // =====================================================================

        $('#wfeb-hamburger').on('click', function () {
            var $sidebar = $('.wfeb-sidebar');
            var $overlay = $('#wfeb-sidebar-overlay');
            $sidebar.toggleClass('open');
            if ($sidebar.hasClass('open')) {
                $overlay.css('display', 'block');
                setTimeout(function () { $overlay.addClass('active'); }, 10);
            } else {
                $overlay.removeClass('active');
                setTimeout(function () { $overlay.css('display', 'none'); }, 300);
            }
        });

        $('#wfeb-sidebar-overlay').on('click', function () {
            var $overlay = $(this);
            $('.wfeb-sidebar').removeClass('open');
            $overlay.removeClass('active');
            setTimeout(function () { $overlay.css('display', 'none'); }, 300);
        });

        // =====================================================================
        // 2b. CONTENT FADE-IN
        // =====================================================================

        (function fadeInContent() {
            var $content = $('.wfeb-content');
            if ($content.length) {
                $content.css('opacity', '1');
            }
        })();

        // =====================================================================
        // 2c. SIDEBAR AVATAR POPUP (click-based for mobile compatibility)
        // =====================================================================

        $(document).on('click', '.wfeb-sidebar-avatar-area', function (e) {
            var $popup = $(this).find('.wfeb-sidebar-popup');
            // If clicking a link inside the popup, let it navigate
            if ($(e.target).closest('.wfeb-sidebar-popup-link').length) {
                return;
            }
            $popup.toggleClass('wfeb-sidebar-popup--active');
        });

        // Close popup on click outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.wfeb-sidebar-avatar-area').length) {
                $('.wfeb-sidebar-popup').removeClass('wfeb-sidebar-popup--active');
            }
        });

        // =====================================================================
        // 3. CERTIFICATE VIEW TOGGLE (Grid / List)
        // =====================================================================

        $(document).on('click', '.wfeb-view-toggle-grid', function () {
            $(this).addClass('active').siblings().removeClass('active');
            $('.wfeb-cert-grid').show();
            $('.wfeb-table').hide();
            console.log('[WFEB] Certificate view switched to grid');
        });

        $(document).on('click', '.wfeb-view-toggle-list', function () {
            $(this).addClass('active').siblings().removeClass('active');
            $('.wfeb-cert-grid').hide();
            $('.wfeb-table').show();
            console.log('[WFEB] Certificate view switched to list');
        });

        // =====================================================================
        // 4. SCORE PROGRESSION CHART (Overview Page)
        // =====================================================================

        (function initScoreChart() {
            var $canvas = $('#wfeb-score-chart');
            if (!$canvas.length || typeof Chart === 'undefined') {
                return;
            }

            // Data should be set in the template as a global variable
            if (typeof wfebScoreData === 'undefined' || !wfebScoreData) {
                console.log('[WFEB] No score progression data available');
                return;
            }

            console.log('[WFEB] Initializing score progression chart');

            var ctx = $canvas[0].getContext('2d');

            // Create gradient fill
            var gradient = ctx.createLinearGradient(0, 0, 0, $canvas[0].height);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');   // Accent color with opacity
            gradient.addColorStop(1, 'rgba(16, 185, 129, 0.02)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: wfebScoreData.labels || [],
                    datasets: [{
                        label: 'Total Score',
                        data: wfebScoreData.scores || [],
                        backgroundColor: gradient,
                        borderColor: 'rgba(16, 185, 129, 1)',    // Accent color
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 13
                            },
                            bodyFont: {
                                size: 12
                            },
                            padding: 10,
                            cornerRadius: 6
                        }
                    }
                }
            });
        })();

        // =====================================================================
        // 5. RADAR CHART (Score Detail Page)
        // =====================================================================

        (function initRadarChart() {
            var $canvas = $('#wfeb-radar-chart');
            if (!$canvas.length || typeof Chart === 'undefined') {
                return;
            }

            console.log('[WFEB] Initializing player radar chart');

            var chartData;
            var jsonStr = $canvas.attr('data-scores');
            if (jsonStr) {
                try {
                    chartData = JSON.parse(jsonStr);
                } catch (err) {
                    console.log('[WFEB] Failed to parse radar chart data:', err);
                    return;
                }
            } else if (typeof wfebRadarData !== 'undefined') {
                chartData = wfebRadarData;
            } else {
                console.log('[WFEB] No radar chart data found');
                return;
            }

            var labels = ['Short Pass', 'Long Pass', 'Shooting', 'Sprint', 'Dribble', 'Kickups', 'Volley'];
            var scores = [
                chartData.short_passing || 0,
                chartData.long_passing || 0,
                chartData.shooting || 0,
                chartData.sprint || 0,
                chartData.dribble || 0,
                chartData.kickups || 0,
                chartData.volley || 0
            ];

            new Chart($canvas[0].getContext('2d'), {
                type: 'radar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Scores',
                        data: scores,
                        backgroundColor: 'rgba(0, 0, 128, 0.2)',     // Navy fill
                        borderColor: 'rgba(16, 185, 129, 1)',         // Emerald border
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
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
                            ticks: {
                                stepSize: 2
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            pointLabels: {
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        })();

        // =====================================================================
        // 6. PROFILE FORM
        // =====================================================================

        $('#wfeb-profile-form').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            $btn.addClass('wfeb-btn--loading').prop('disabled', true);

            var formData = {};
            $form.find('input, select, textarea').each(function () {
                var name = $(this).attr('name');
                if (name && $(this).attr('type') !== 'file') {
                    formData[name] = $(this).val();
                }
            });
            formData.action = 'wfeb_update_player_profile';
            formData.security = wfeb_player.nonce;

            console.log('[WFEB] Updating player profile');

            $.ajax({
                url: wfeb_player.ajax_url,
                type: 'POST',
                data: formData,
                success: function (response) {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    if (response.success) {
                        showNotification('Profile updated successfully.', 'success');
                    } else {
                        showNotification(response.data.message || 'Failed to update profile.', 'error');
                    }
                },
                error: function () {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 7. AVATAR UPLOAD
        // =====================================================================

        $(document).on('click', '.wfeb-avatar-overlay', function () {
            $('#wfeb-avatar-input').trigger('click');
        });

        $('#wfeb-avatar-input').on('change', function () {
            var file = this.files[0];
            if (!file) return;

            // Validate file type
            var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (allowedTypes.indexOf(file.type) === -1) {
                showNotification('Please select a valid image file (JPG, PNG, GIF, WEBP).', 'error');
                return;
            }

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                showNotification('Image file must be less than 2MB.', 'error');
                return;
            }

            // Preview the image
            var reader = new FileReader();
            reader.onload = function (e) {
                $('.wfeb-avatar-img').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);

            // Upload via AJAX
            var formData = new FormData();
            formData.append('action', 'wfeb_upload_player_avatar');
            formData.append('security', wfeb_player.nonce);
            formData.append('avatar', file);

            console.log('[WFEB] Uploading player avatar');

            $.ajax({
                url: wfeb_player.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        showNotification('Avatar updated successfully.', 'success');
                        if (response.data.avatar_url) {
                            $('.wfeb-avatar-img').attr('src', response.data.avatar_url);
                        }
                    } else {
                        showNotification(response.data.message || 'Failed to upload avatar.', 'error');
                    }
                },
                error: function () {
                    showNotification('An error occurred uploading avatar. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 8. PASSWORD FORM + STRENGTH METER
        // =====================================================================

        $('#wfeb-password-form').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var currentPw = $form.find('#current-password').val();
            var newPw = $form.find('#new-password').val();
            var confirmPw = $form.find('#confirm-new-password').val();

            if (!currentPw || !newPw || !confirmPw) {
                showNotification('All password fields are required.', 'error');
                return;
            }
            if (newPw !== confirmPw) {
                showNotification('New passwords do not match.', 'error');
                return;
            }
            if (newPw.length < 8) {
                showNotification('Password must be at least 8 characters.', 'error');
                return;
            }

            $btn.addClass('wfeb-btn--loading').prop('disabled', true);

            console.log('[WFEB] Changing player password');

            $.ajax({
                url: wfeb_player.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_player_change_password',
                    current_password: currentPw,
                    new_password: newPw,
                    confirm_password: confirmPw,
                    security: wfeb_player.nonce
                },
                success: function (response) {
                    $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
                    if (response.success) {
                        showNotification('Password changed successfully.', 'success');
                        $form[0].reset();
                        // Reset strength meter
                        updateStrengthMeter('');
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

        // Password strength meter
        $(document).on('input', '#new-password', function () {
            updateStrengthMeter($(this).val());
        });

        /**
         * Calculate password strength and update the strength meter bars and label.
         *
         * Criteria:
         *  - Length >= 8
         *  - Contains numbers
         *  - Contains special characters
         *  - Contains uppercase letters
         *
         * @param {string} password - The current password value.
         */
        function updateStrengthMeter(password) {
            var $bars = $('.wfeb-strength-bar');
            var $label = $('.wfeb-strength-label');

            if (!$bars.length) return;

            var strength = 0;

            if (password.length >= 8) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;

            // Reset all bars
            $bars.removeClass('wfeb-strength-bar--weak wfeb-strength-bar--fair wfeb-strength-bar--good wfeb-strength-bar--strong');

            var label = '';
            var colorClass = '';

            if (password.length === 0) {
                label = '';
                colorClass = '';
            } else if (strength <= 1) {
                label = 'Weak';
                colorClass = 'wfeb-strength-bar--weak';
            } else if (strength === 2) {
                label = 'Fair';
                colorClass = 'wfeb-strength-bar--fair';
            } else if (strength === 3) {
                label = 'Good';
                colorClass = 'wfeb-strength-bar--good';
            } else {
                label = 'Strong';
                colorClass = 'wfeb-strength-bar--strong';
            }

            // Fill bars up to the strength level
            $bars.each(function (index) {
                if (index < strength) {
                    $(this).addClass(colorClass);
                }
            });

            $label.text(label);
        }

        // =====================================================================
        // 9. PASSWORD TOGGLE (Eye Icon)
        // =====================================================================

        $(document).on('click', '.wfeb-password-toggle', function () {
            var $toggle = $(this);
            var $input = $toggle.siblings('input');

            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $toggle.addClass('wfeb-password-toggle--visible');
            } else {
                $input.attr('type', 'password');
                $toggle.removeClass('wfeb-password-toggle--visible');
            }
        });

        // =====================================================================
        // 10. MODAL SYSTEM
        // =====================================================================

        /**
         * Show a modal dialog with the provided HTML content.
         *
         * @param {string} content - HTML content for the modal body.
         */
        function showModal(content) {
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

        // Close on close button
        $(document).on('click', '.wfeb-modal-close', function () {
            hideModal();
        });

        // Close on Escape key
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                hideModal();
            }
        });

        // Make modal functions globally accessible
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

    }); // end document.ready
})(jQuery);
