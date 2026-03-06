/**
 * WFEB Plugin - Frontend JavaScript
 * Handles login, registration, verification, and forgot password forms.
 *
 * @package WFEB_Plugin
 * @since 1.0.0
 */
'use strict';

(function ($) {
    $(document).ready(function () {
        console.log('[WFEB] Frontend JS initialized');

        // =====================================================================
        // UTILITY / HELPER FUNCTIONS
        // =====================================================================

        /**
         * Show a field-level error message below a specific field.
         */
        function showFieldError(fieldId, message) {
            var $field = $('#' + fieldId);
            $field.addClass('wfeb-field--error');
            // Place error after the wrapping container (e.g. .wfeb-password-wrap) if present,
            // otherwise after the field itself.
            var $target = $field.closest('.wfeb-password-wrap');
            if (!$target.length) {
                $target = $field;
            }
            $target.siblings('.wfeb-field-error').remove();
            $target.parent().find('.wfeb-field-error').remove();
            $target.after('<span class="wfeb-field-error">' + escapeHtml(message) + '</span>');
        }

        /**
         * Clear all field-level error states and messages.
         */
        function clearFieldErrors() {
            $('.wfeb-field--error').removeClass('wfeb-field--error');
            $('.wfeb-field-error').remove();
        }

        /**
         * Show a notice message inside a specific notice element.
         */
        function showNotice(elementId, message, type) {
            var $notice = $('#' + elementId);
            $notice
                .removeClass('wfeb-notice--success wfeb-notice--error wfeb-notice--warning wfeb-notice--info')
                .addClass('wfeb-notice--' + type)
                .html(escapeHtml(message))
                .show();
        }

        /**
         * Escape HTML entities to prevent XSS in displayed messages.
         */
        function escapeHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        /**
         * Set a button to its loading state.
         */
        function setButtonLoading($btn) {
            $btn.addClass('wfeb-btn--loading').prop('disabled', true);
        }

        /**
         * Remove the loading state from a button.
         */
        function removeButtonLoading($btn) {
            $btn.removeClass('wfeb-btn--loading').prop('disabled', false);
        }

        /**
         * Validate password strength.
         * Must be at least 8 characters, contain at least one letter and one number.
         */
        function isPasswordStrong(password) {
            if (password.length < 8) return false;
            if (!/[a-zA-Z]/.test(password)) return false;
            if (!/[0-9]/.test(password)) return false;
            return true;
        }

        // =====================================================================
        // 1. COACH LOGIN
        // =====================================================================

        $('#wfeb-coach-login-form').on('submit', function (e) {
            e.preventDefault();
            console.log('[WFEB] Coach login form submitted');

            clearFieldErrors();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var email = $form.find('#wfeb-login-email').val().trim();
            var password = $form.find('#wfeb-login-password').val();

            // Validate
            if (!email) {
                showFieldError('wfeb-login-email', 'Please enter your email address.');
                return;
            }
            if (!password) {
                showFieldError('wfeb-login-password', 'Please enter your password.');
                return;
            }

            setButtonLoading($btn);

            $.ajax({
                url: wfeb_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_coach_login',
                    security: wfeb_frontend.nonce,
                    email: email,
                    password: password
                },
                success: function (response) {
                    removeButtonLoading($btn);
                    if (response.success) {
                        console.log('[WFEB] Coach login successful, redirecting');
                        window.location.href = response.data.redirect_url;
                    } else {
                        var msg = response.data && response.data.message
                            ? response.data.message
                            : 'Login failed. Please check your credentials.';
                        showNotice('wfeb-login-notice', msg, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    removeButtonLoading($btn);
                    console.log('[WFEB] Coach login AJAX error:', status, error);
                    showNotice('wfeb-login-notice', 'An unexpected error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 2. PLAYER LOGIN
        // =====================================================================

        $('#wfeb-player-login-form').on('submit', function (e) {
            e.preventDefault();
            console.log('[WFEB] Player login form submitted');

            clearFieldErrors();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var email = $form.find('#wfeb-login-email').val().trim();
            var password = $form.find('#wfeb-login-password').val();

            // Validate
            if (!email) {
                showFieldError('wfeb-login-email', 'Please enter your email address.');
                return;
            }
            if (!password) {
                showFieldError('wfeb-login-password', 'Please enter your password.');
                return;
            }

            setButtonLoading($btn);

            $.ajax({
                url: wfeb_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_player_login',
                    security: wfeb_frontend.nonce,
                    email: email,
                    password: password
                },
                success: function (response) {
                    removeButtonLoading($btn);
                    if (response.success) {
                        console.log('[WFEB] Player login successful, redirecting');
                        window.location.href = response.data.redirect_url;
                    } else {
                        var msg = response.data && response.data.message
                            ? response.data.message
                            : 'Login failed. Please check your credentials.';
                        showNotice('wfeb-login-notice', msg, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    removeButtonLoading($btn);
                    console.log('[WFEB] Player login AJAX error:', status, error);
                    showNotice('wfeb-login-notice', 'An unexpected error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 3. COACH REGISTRATION
        // =====================================================================

        $('#wfeb-coach-register-form').on('submit', function (e) {
            e.preventDefault();
            console.log('[WFEB] Coach registration form submitted');

            clearFieldErrors();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var valid = true;

            // Gather field values (matching template IDs)
            var fullName = $form.find('#wfeb-reg-fullname').val().trim();
            var dob = $form.find('#wfeb-reg-dob').val().trim();
            var email = $form.find('#wfeb-reg-email').val().trim();
            var phone = $form.find('#wfeb-reg-phone').val().trim();
            var address = $form.find('#wfeb-reg-address').val().trim();
            var ngbNumber = $form.find('#wfeb-reg-ngb').val().trim();
            var password = $form.find('#wfeb-reg-password').val();
            var confirmPassword = $form.find('#wfeb-reg-password-confirm').val();
            var certFile = $form.find('#wfeb-reg-certificate')[0];
            var terms = $form.find('#wfeb-reg-terms').is(':checked');

            // Required field validation
            if (!fullName) {
                showFieldError('wfeb-reg-fullname', 'Full name is required.');
                valid = false;
            }
            if (!dob) {
                showFieldError('wfeb-reg-dob', 'Date of birth is required.');
                valid = false;
            }
            if (!email) {
                showFieldError('wfeb-reg-email', 'Email is required.');
                valid = false;
            }
            if (!phone) {
                showFieldError('wfeb-reg-phone', 'Phone number is required.');
                valid = false;
            }
            if (!address) {
                showFieldError('wfeb-reg-address', 'Address is required.');
                valid = false;
            }
            if (!ngbNumber) {
                showFieldError('wfeb-reg-ngb', 'NGB number is required.');
                valid = false;
            }
            if (!password) {
                showFieldError('wfeb-reg-password', 'Password is required.');
                valid = false;
            }
            if (!confirmPassword) {
                showFieldError('wfeb-reg-password-confirm', 'Please confirm your password.');
                valid = false;
            }

            // Password match
            if (password && confirmPassword && password !== confirmPassword) {
                showFieldError('wfeb-reg-password-confirm', 'Passwords do not match.');
                valid = false;
            }

            // Password strength
            if (password && !isPasswordStrong(password)) {
                showFieldError('wfeb-reg-password', 'Password must be at least 8 characters with at least one letter and one number.');
                valid = false;
            }

            // Terms check
            if (!terms) {
                showNotice('wfeb-register-notice', 'You must agree to the terms and conditions.', 'error');
                valid = false;
            }

            // File validation (coaching certificate)
            if (certFile && certFile.files && certFile.files.length > 0) {
                var file = certFile.files[0];
                var allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                var maxSize = 5 * 1024 * 1024; // 5MB

                if (allowedTypes.indexOf(file.type) === -1) {
                    showFieldError('wfeb-reg-certificate', 'Certificate must be a PDF, JPG, or PNG file.');
                    valid = false;
                }
                if (file.size > maxSize) {
                    showFieldError('wfeb-reg-certificate', 'Certificate file must be less than 5MB.');
                    valid = false;
                }
            } else {
                showNotice('wfeb-register-notice', 'Please upload your coaching certificate.', 'error');
                valid = false;
            }

            if (!valid) {
                console.log('[WFEB] Coach registration validation failed');
                return;
            }

            // Build FormData for file upload
            var formData = new FormData();
            formData.append('action', 'wfeb_coach_register');
            formData.append('security', wfeb_frontend.nonce);
            formData.append('full_name', fullName);
            formData.append('dob', dob);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('address', address);
            formData.append('ngb_number', ngbNumber);
            formData.append('password', password);
            formData.append('password_confirm', confirmPassword);

            if (certFile && certFile.files && certFile.files.length > 0) {
                formData.append('coaching_certificate', certFile.files[0]);
            }

            setButtonLoading($btn);

            $.ajax({
                url: wfeb_frontend.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    removeButtonLoading($btn);
                    if (response.success) {
                        console.log('[WFEB] Coach registration successful');
                        var loginUrl = response.data.login_url || '/coach-login/';

                        // Auto-approved: redirect to login directly.
                        if (response.data.auto_approved) {
                            showNotice('wfeb-register-notice', response.data.message || 'Registration successful! Redirecting...', 'success');
                            setTimeout(function () {
                                window.location.href = loginUrl;
                            }, 1500);
                            return;
                        }

                        // Pending approval: show modal.
                        var $overlay = $('<div class="wfeb-modal-overlay"></div>');
                        var $modal = $(
                            '<div class="wfeb-modal">' +
                                '<div class="wfeb-modal-icon">&#10003;</div>' +
                                '<h3 class="wfeb-modal-title">Registration Submitted</h3>' +
                                '<p class="wfeb-modal-text">Your registration is pending approval. Once an admin approves your account, you will receive an email notification and can log in to your dashboard.</p>' +
                                '<button type="button" class="wfeb-btn wfeb-btn--primary wfeb-btn--full wfeb-modal-ok">OK</button>' +
                            '</div>'
                        );
                        $('body').append($overlay).append($modal);
                        setTimeout(function () {
                            $overlay.addClass('wfeb-modal-overlay--visible');
                            $modal.addClass('wfeb-modal--visible');
                        }, 10);
                        $modal.on('click', '.wfeb-modal-ok', function () {
                            window.location.href = loginUrl;
                        });
                        $overlay.on('click', function () {
                            window.location.href = loginUrl;
                        });
                    } else {
                        // Handle field-specific errors
                        if (response.data && response.data.errors && typeof response.data.errors === 'object') {
                            $.each(response.data.errors, function (fieldId, errorMsg) {
                                showFieldError(fieldId, errorMsg);
                            });
                        }
                        var msg = response.data && response.data.message
                            ? response.data.message
                            : 'Registration failed. Please check your details and try again.';
                        showNotice('wfeb-register-notice', msg, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    removeButtonLoading($btn);
                    console.log('[WFEB] Coach registration AJAX error:', status, error);
                    showNotice('wfeb-register-notice', 'An unexpected error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 4. FORGOT PASSWORD
        // =====================================================================

        $('#wfeb-forgot-password-form').on('submit', function (e) {
            e.preventDefault();
            console.log('[WFEB] Forgot password form submitted');

            clearFieldErrors();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var email = $form.find('#wfeb-forgot-email').val().trim();

            if (!email) {
                showFieldError('wfeb-forgot-email', 'Please enter your email address.');
                return;
            }

            setButtonLoading($btn);

            $.ajax({
                url: wfeb_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_forgot_password',
                    security: wfeb_frontend.nonce,
                    email: email
                },
                success: function (response) {
                    removeButtonLoading($btn);
                    if (response.success) {
                        console.log('[WFEB] Forgot password email sent');
                        $form.hide();
                        $('#wfeb-reset-success').show();
                    } else {
                        var msg = response.data && response.data.message
                            ? response.data.message
                            : 'Unable to process your request. Please try again.';
                        showNotice('wfeb-forgot-notice', msg, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    removeButtonLoading($btn);
                    console.log('[WFEB] Forgot password AJAX error:', status, error);
                    showNotice('wfeb-forgot-notice', 'An unexpected error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 5. CERTIFICATE VERIFICATION
        // =====================================================================

        $('#wfeb-verify-form').on('submit', function (e) {
            e.preventDefault();
            console.log('[WFEB] Certificate verification form submitted');

            clearFieldErrors();

            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var name = $form.find('#wfeb-verify-name').val().trim();
            var certNumber = $form.find('#wfeb-verify-cert-number').val().trim();
            var dob = $form.find('#wfeb-verify-dob').val().trim();

            if (!name) {
                showFieldError('wfeb-verify-name', 'Please enter the player name.');
                return;
            }
            if (!certNumber) {
                showFieldError('wfeb-verify-cert-number', 'Please enter the certificate number.');
                return;
            }
            if (!dob) {
                showFieldError('wfeb-verify-dob', 'Please enter the date of birth.');
                return;
            }

            setButtonLoading($btn);

            $.ajax({
                url: wfeb_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_verify_certificate',
                    security: wfeb_frontend.nonce,
                    name: name,
                    cert_number: certNumber,
                    dob: dob
                },
                success: function (response) {
                    removeButtonLoading($btn);

                    var $resultsWrap = $('#wfeb-verify-results');
                    var $found = $('#wfeb-verify-found');
                    var $notFound = $('#wfeb-verify-not-found');

                    $resultsWrap.show();

                    if (response.success && response.data && response.data.data) {
                        console.log('[WFEB] Certificate verified successfully');

                        var certData = response.data.data;

                        $found.show();
                        $notFound.hide();

                        // Populate result fields
                        $('#wfeb-result-name').text(certData.player_name || certData.name || '--');
                        $('#wfeb-result-score').text(certData.total_score || certData.score || '--');
                        $('#wfeb-result-date').text(certData.exam_date || certData.date || '--');
                        $('#wfeb-result-cert').text(certData.certificate_number || certData.cert_number || '--');
                        $('#wfeb-result-examiner').text(certData.examiner_name || certData.examiner || '--');

                        // Level badge
                        var level = certData.achievement_level || certData.level || '';
                        var $badge = $('#wfeb-result-badge');
                        $badge.text(level);
                        $badge.attr('class', 'wfeb-verify-badge wfeb-verify-badge--' + level.toLowerCase().replace(/[^a-z0-9]/g, ''));
                    } else {
                        console.log('[WFEB] Certificate not found');

                        $found.hide();
                        $notFound.show();
                    }
                },
                error: function (xhr, status, error) {
                    removeButtonLoading($btn);
                    console.log('[WFEB] Certificate verification AJAX error:', status, error);

                    var $resultsWrap = $('#wfeb-verify-results');
                    var $found = $('#wfeb-verify-found');
                    var $notFound = $('#wfeb-verify-not-found');

                    $resultsWrap.show();
                    $found.hide();
                    $notFound.show();
                }
            });
        });

        // =====================================================================
        // 5b. QR AUTO-VERIFY
        // =====================================================================

        if (typeof wfebAutoVerify !== 'undefined' && wfebAutoVerify.cert && wfebAutoVerify.sig) {
            (function () {
                console.log('[WFEB] Auto-verifying certificate from QR code');

                var $form       = $('#wfeb-verify-form');
                var $resultsWrap = $('#wfeb-verify-results');
                var $found      = $('#wfeb-verify-found');
                var $notFound   = $('#wfeb-verify-not-found');

                $form.hide();
                $resultsWrap.show();

                $.ajax({
                    url: wfebAutoVerify.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wfeb_verify_certificate',
                        security: wfebAutoVerify.nonce,
                        qr_cert: wfebAutoVerify.cert,
                        qr_sig: wfebAutoVerify.sig
                    },
                    success: function (response) {
                        if (response.success && response.data && response.data.data) {
                            var certData = response.data.data;
                            $found.show();
                            $notFound.hide();

                            $('#wfeb-result-name').text(certData.player_name || certData.name || '--');
                            $('#wfeb-result-score').text(certData.total_score || certData.score || '--');
                            $('#wfeb-result-date').text(certData.exam_date || certData.date || '--');
                            $('#wfeb-result-cert').text(certData.certificate_number || certData.cert_number || '--');
                            $('#wfeb-result-examiner').text(certData.examiner_name || certData.examiner || '--');

                            var level = certData.achievement_level || certData.level || '';
                            var $badge = $('#wfeb-result-badge');
                            $badge.text(level);
                            $badge.attr('class', 'wfeb-verify-badge wfeb-verify-badge--' + level.toLowerCase().replace(/[^a-z0-9]/g, ''));
                        } else {
                            $found.hide();
                            $notFound.show();
                        }
                    },
                    error: function () {
                        $found.hide();
                        $notFound.show();
                    }
                });
            })();
        }

        // =====================================================================
        // 6. PASSWORD TOGGLE (Eye Icon)
        // =====================================================================

        // Inject SVG eye icon + slash line into all password toggle buttons.
        // Single eye that stays in place; a diagonal line appears/disappears on toggle.
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

        // =====================================================================
        // 7. FILE UPLOAD ZONE (Drag and Drop)
        // =====================================================================

        var $fileZone = $('.wfeb-form-file');
        if ($fileZone.length) {
            $fileZone.on('dragover dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('wfeb-form-file--dragover');
            });

            $fileZone.on('dragleave drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('wfeb-form-file--dragover');
            });

            $fileZone.on('drop', function (e) {
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    var $fileInput = $(this).find('input[type="file"]');
                    try {
                        var dt = new DataTransfer();
                        dt.items.add(files[0]);
                        $fileInput[0].files = dt.files;
                    } catch (err) {
                        console.log('[WFEB] DataTransfer not supported, falling back');
                    }

                    // Update the preview text
                    var $preview = $(this).find('.wfeb-form-file__preview');
                    if ($preview.length) {
                        $preview.text(files[0].name);
                    }

                    $fileInput.trigger('change');
                    console.log('[WFEB] File dropped:', files[0].name);
                }
            });

            // Update preview on normal file input change
            $fileZone.find('input[type="file"]').on('change', function () {
                var $preview = $(this).closest('.wfeb-form-file').find('.wfeb-form-file__preview');
                if (this.files && this.files.length > 0 && $preview.length) {
                    $preview.text(this.files[0].name);
                }
            });
        }

        // =====================================================================
        // 8. PASSWORD STRENGTH INDICATOR
        // =====================================================================

        $('#wfeb-reg-password').on('input', function () {
            var password = $(this).val();
            var $strength = $('#wfeb-password-strength');

            if (!password) {
                $strength.text('').css('color', '');
                return;
            }

            if (password.length < 8) {
                $strength.text('Too short').css('color', 'var(--wfeb-error)');
            } else if (!isPasswordStrong(password)) {
                $strength.text('Needs letters and numbers').css('color', 'var(--wfeb-warning)');
            } else {
                $strength.text('Strong password').css('color', 'var(--wfeb-success)');
            }
        });

    }); // end document.ready
})(jQuery);
