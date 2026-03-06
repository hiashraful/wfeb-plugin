/**
 * WFEB Plugin - Admin JavaScript
 * Handles wp-admin interactions: coach approval, certificates, credits,
 * settings, and analytics charts.
 *
 * @package WFEB_Plugin
 * @since 1.0.0
 */
'use strict';

(function ($) {
    $(document).ready(function () {
        console.log('[WFEB] Admin JS initialized');

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

        /**
         * Update coach UI immediately after a status action, without a page reload.
         *
         * Handles three contexts:
         * - Dashboard pending section: removes the row
         * - Coaches list table: updates badge + swaps row action buttons
         * - Coach detail page: updates all badges + swaps buttons in header & sidebar
         *
         * @param {jQuery}  $triggerBtn — The button that triggered the action.
         * @param {string}  newStatus   — 'approved' | 'rejected' | 'suspended'
         */
        function updateCoachRowAfterAction($triggerBtn, newStatus) {
            var coachId   = $triggerBtn.data('coach-id');
            var coachName = $triggerBtn.data('coach-name') || '';
            var statusLabel = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);

            // --- Coach detail page ---
            var $detailPage = $('.wfeb-page-actions');
            if ($detailPage.length && !$triggerBtn.closest('tr').length) {
                // Update all status badges on the page
                $('.wfeb-wrap .wfeb-badge').each(function () {
                    var $b = $(this);
                    // Only update status badges (not level badges etc.)
                    if ($b.is('[class*="badge--approved"], [class*="badge--pending"], [class*="badge--rejected"], [class*="badge--suspended"]')) {
                        $b.text(statusLabel).attr('class', 'wfeb-badge wfeb-badge--' + newStatus);
                    }
                });

                // Helper to rebuild buttons inside a container
                function rebuildButtons($container, btnSize) {
                    var sm = btnSize ? ' wfeb-btn--sm' : '';
                    var $removeBtn = $container.find('.wfeb-remove-coach');
                    $container.find('.wfeb-approve-coach, .wfeb-reject-coach, .wfeb-suspend-coach').remove();

                    if (newStatus === 'approved') {
                        $('<button type="button" class="wfeb-btn wfeb-btn--warning' + sm + ' wfeb-suspend-coach" data-tooltip="Temporarily disable this coach\'s access. Data is preserved">Suspend Coach</button>')
                            .attr('data-coach-id', coachId)
                            .attr('data-coach-name', coachName)
                            .insertBefore($removeBtn);
                    } else if (newStatus === 'rejected' || newStatus === 'suspended') {
                        $('<button type="button" class="wfeb-btn wfeb-btn--primary' + sm + ' wfeb-approve-coach" data-tooltip="Restore this coach\'s access to submit exams">Re-approve Coach</button>')
                            .attr('data-coach-id', coachId)
                            .attr('data-coach-name', coachName)
                            .insertBefore($removeBtn);
                    } else if (newStatus === 'pending') {
                        $('<button type="button" class="wfeb-btn wfeb-btn--primary' + sm + ' wfeb-approve-coach" data-tooltip="Grant this coach access to submit player exams">Approve Coach</button>')
                            .attr('data-coach-id', coachId)
                            .attr('data-coach-name', coachName)
                            .insertBefore($removeBtn);
                        $('<button type="button" class="wfeb-btn wfeb-btn--ghost' + sm + ' wfeb-reject-coach" data-tooltip="Deny this coach\'s registration">Reject Coach</button>')
                            .attr('data-coach-id', coachId)
                            .attr('data-coach-name', coachName)
                            .insertBefore($removeBtn);
                    }
                }

                // Header buttons (no --sm)
                rebuildButtons($detailPage, false);
                // Sidebar buttons (--sm not used either, full width)
                $('.wfeb-action-buttons').each(function () {
                    rebuildButtons($(this), false);
                });
                return;
            }

            // --- Dashboard pending section ---
            var $row = $triggerBtn.closest('tr');
            var $pendingSection = $row.closest('#wfeb-pending-coaches-section');
            if ($pendingSection.length) {
                $row.fadeOut(300, function () {
                    $(this).remove();
                    if ($pendingSection.find('tbody tr').length === 0) {
                        $pendingSection.fadeOut(300);
                    }
                });
                return;
            }

            // --- Coaches list table ---
            if (!coachName) {
                coachName = $row.find('td').first().text().trim();
            }
            $row.find('.wfeb-badge').text(statusLabel).attr('class', 'wfeb-badge wfeb-badge--' + newStatus);

            var $actions   = $row.find('.wfeb-row-actions');
            var $removeBtn = $actions.find('.wfeb-remove-coach');

            $actions.find('.wfeb-approve-coach, .wfeb-reject-coach, .wfeb-suspend-coach').remove();

            if (newStatus === 'approved') {
                $('<button type="button" class="wfeb-btn wfeb-btn--warning wfeb-btn--sm wfeb-suspend-coach" data-tooltip="Temporarily disable this coach\'s access. Data is preserved">Suspend</button>')
                    .attr('data-coach-id', coachId)
                    .attr('data-coach-name', coachName)
                    .insertBefore($removeBtn);
            } else if (newStatus === 'rejected' || newStatus === 'suspended') {
                $('<button type="button" class="wfeb-btn wfeb-btn--primary wfeb-btn--sm wfeb-approve-coach" data-tooltip="Restore this coach\'s access to submit exams">Re-approve</button>')
                    .attr('data-coach-id', coachId)
                    .attr('data-coach-name', coachName)
                    .insertBefore($removeBtn);
            }
        }

        // Stores the reject button that opened the current reject modal so the
        // confirm handler can locate the correct table row.
        var $currentRejectBtn = null;

        // =====================================================================
        // MODAL SYSTEM
        // =====================================================================

        /**
         * Build and inject the persistent modal scaffold (once per page).
         */
        function ensureModalScaffold() {
            if ($('#wfeb-modal-scaffold').length) return;
            $('body').append(
                '<div id="wfeb-modal-scaffold" class="wfeb-modal-overlay">' +
                    '<div class="wfeb-modal" role="dialog" aria-modal="true">' +
                        '<div class="wfeb-modal-header">' +
                            '<h3 class="wfeb-modal-title" id="wfeb-modal-title-text"></h3>' +
                            '<button type="button" class="wfeb-modal-close" aria-label="Close">&times;</button>' +
                        '</div>' +
                        '<div class="wfeb-modal-body" id="wfeb-modal-body"></div>' +
                        '<div class="wfeb-modal-footer" id="wfeb-modal-footer"></div>' +
                    '</div>' +
                '</div>'
            );
        }

        /**
         * Open the modal with a title, body HTML, and footer HTML.
         */
        function openModal(title, bodyHtml, footerHtml) {
            ensureModalScaffold();
            $('#wfeb-modal-title-text').text(title);
            $('#wfeb-modal-body').html(bodyHtml);
            $('#wfeb-modal-footer').html(footerHtml || '');
            $('#wfeb-modal-scaffold').addClass('active');
            // Focus first focusable element
            setTimeout(function () {
                $('#wfeb-modal-scaffold').find('button, input, textarea, select').first().trigger('focus');
            }, 50);
        }

        /**
         * Close the modal and remove any pending confirm handlers.
         */
        function closeModal() {
            $(document).off('click.wfebConfirm');
            $('#wfeb-modal-scaffold').removeClass('active');
        }

        /**
         * Show a styled confirm dialog.
         *
         * The modal stays open while the action runs so the spinner is visible.
         * onConfirm receives a `done` callback — call it when the action
         * finishes (success or error) to close the modal.
         *
         * @param {string}   title      — Modal heading
         * @param {string}   message    — Body message (HTML allowed)
         * @param {Function} onConfirm  — function(done) { ... done(); }
         * @param {object}   opts       — { confirmText, cancelText, confirmClass }
         */
        function wfebConfirm(title, message, onConfirm, opts) {
            opts = $.extend({ confirmText: 'Confirm', cancelText: 'Cancel', confirmClass: 'wfeb-btn--primary' }, opts);

            var footer =
                '<button type="button" class="wfeb-btn wfeb-btn--ghost" id="wfeb-confirm-cancel">' + escapeHtml(opts.cancelText) + '</button>' +
                '<button type="button" class="wfeb-btn ' + opts.confirmClass + '" id="wfeb-confirm-ok">' + escapeHtml(opts.confirmText) + '</button>';

            openModal(title, '<p>' + message + '</p>', footer);

            // Use a namespace so both handlers can be removed together,
            // preventing stale accumulation across multiple modal opens.
            $(document).off('click.wfebConfirm');

            $(document).on('click.wfebConfirm', '#wfeb-confirm-ok', function () {
                $(document).off('click.wfebConfirm'); // remove both at once

                var $ok     = $(this);
                var $cancel = $('#wfeb-confirm-cancel');

                setLoading($ok, true);
                $cancel.prop('disabled', true).css('opacity', '0.4');

                if (typeof onConfirm === 'function') {
                    onConfirm(function () { closeModal(); });
                } else {
                    closeModal();
                }
            });

            $(document).on('click.wfebConfirm', '#wfeb-confirm-cancel', function () {
                $(document).off('click.wfebConfirm'); // remove both at once
                closeModal();
            });
        }

        // Close on overlay click
        $(document).on('click', '#wfeb-modal-scaffold', function (e) {
            if (e.target === this) closeModal();
        });

        // Close on X button
        $(document).on('click', '.wfeb-modal-close', function () {
            closeModal();
        });

        // Close on Escape
        $(document).on('keydown', function (e) {
            if ((e.key === 'Escape' || e.keyCode === 27) && $('#wfeb-modal-scaffold').hasClass('active')) {
                closeModal();
            }
        });

        // =====================================================================
        // 1. COACH APPROVE / REJECT
        // =====================================================================

        // Approve Coach
        $(document).on('click', '.wfeb-approve-coach', function (e) {
            e.preventDefault();

            var $btn     = $(this);
            var coachId  = $btn.data('coach-id');
            var coachName = escapeHtml($btn.data('coach-name') || 'this coach');

            wfebConfirm(
                'Approve Coach',
                'Are you sure you want to approve <strong>' + coachName + '</strong>?',
                function (done) {
                    console.log('[WFEB] Approving coach:', coachId);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wfeb_approve_coach',
                            coach_id: coachId,
                            security: $('#wfeb_admin_nonce').val() || wfeb_admin.nonce
                        },
                        success: function (response) {
                            done();
                            if (response.success) {
                                showAdminNotice('Coach approved successfully.', 'success');
                                updateCoachRowAfterAction($btn, 'approved');
                            } else {
                                showAdminNotice(response.data.message || 'Failed to approve coach.', 'error');
                            }
                        },
                        error: function () {
                            done();
                            showAdminNotice('An error occurred. Please try again.', 'error');
                        }
                    });
                },
                { confirmText: 'Approve', confirmClass: 'wfeb-btn--primary' }
            );
        });

        // Reject Coach — opens modal with textarea
        $(document).on('click', '.wfeb-reject-coach', function (e) {
            e.preventDefault();

            $currentRejectBtn = $(this);
            var $btn     = $currentRejectBtn;
            var coachId  = $btn.data('coach-id');
            var coachName = escapeHtml($btn.data('coach-name') || 'this coach');

            var body =
                '<p>Provide a reason for rejecting <strong>' + coachName + '</strong>:</p>' +
                '<textarea id="wfeb-reject-reason" rows="4" placeholder="Enter reason for rejection..."></textarea>';

            var footer =
                '<button type="button" class="wfeb-btn wfeb-btn--ghost" id="wfeb-modal-cancel-btn">Cancel</button>' +
                '<button type="button" class="wfeb-btn wfeb-btn--danger" id="wfeb-confirm-reject" data-coach-id="' + coachId + '">Reject Coach</button>';

            openModal('Reject Coach', body, footer);
        });

        $(document).on('click', '#wfeb-confirm-reject', function () {
            var coachId = $(this).data('coach-id');
            var reason  = $('#wfeb-reject-reason').val().trim();
            var $btn    = $(this);

            setLoading($btn, true);
            console.log('[WFEB] Rejecting coach:', coachId);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wfeb_reject_coach',
                    coach_id: coachId,
                    reason: reason,
                    security: $('#wfeb_admin_nonce').val() || wfeb_admin.nonce
                },
                success: function (response) {
                    closeModal();
                    if (response.success) {
                        showAdminNotice('Coach rejected.', 'success');
                        if ($currentRejectBtn) {
                            updateCoachRowAfterAction($currentRejectBtn, 'rejected');
                            $currentRejectBtn = null;
                        }
                    } else {
                        showAdminNotice(response.data.message || 'Failed to reject coach.', 'error');
                    }
                },
                error: function () {
                    closeModal();
                    showAdminNotice('An error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 2. SUSPEND COACH
        // =====================================================================

        $(document).on('click', '.wfeb-suspend-coach', function (e) {
            e.preventDefault();

            var $btn      = $(this);
            var coachId   = $btn.data('coach-id');
            var coachName = escapeHtml($btn.data('coach-name') || 'this coach');

            wfebConfirm(
                'Suspend Coach',
                'Are you sure you want to suspend <strong>' + coachName + '</strong>? They will not be able to conduct exams.',
                function (done) {
                    console.log('[WFEB] Suspending coach:', coachId);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wfeb_suspend_coach',
                            coach_id: coachId,
                            security: $('#wfeb_admin_nonce').val() || wfeb_admin.nonce
                        },
                        success: function (response) {
                            done();
                            if (response.success) {
                                showAdminNotice(response.data.message || 'Coach suspended.', 'success');
                                updateCoachRowAfterAction($btn, 'suspended');
                            } else {
                                showAdminNotice(response.data.message || 'Failed to suspend coach.', 'error');
                            }
                        },
                        error: function () {
                            done();
                            showAdminNotice('An error occurred. Please try again.', 'error');
                        }
                    });
                },
                { confirmText: 'Suspend', confirmClass: 'wfeb-btn--warning' }
            );
        });

        // =====================================================================
        // 2a. DELETE PLAYER
        // =====================================================================

        $(document).on('click', '.wfeb-delete-player', function (e) {
            e.preventDefault();

            var $btn       = $(this);
            var playerId   = $btn.data('player-id');
            var playerName = escapeHtml($btn.data('player-name') || 'this player');

            wfebConfirm(
                'Delete Player',
                'Permanently delete <strong>' + playerName + '</strong>? This will remove their record and cannot be undone.',
                function (done) {
                    console.log('[WFEB] Deleting player:', playerId);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wfeb_admin_delete_player',
                            player_id: playerId,
                            security: $('#wfeb_admin_nonce').val() || wfeb_admin.nonce
                        },
                        success: function (response) {
                            done();
                            if (response.success) {
                                showAdminNotice('Player deleted successfully.', 'success');
                                $btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                            } else {
                                showAdminNotice(response.data.message || 'Failed to delete player.', 'error');
                            }
                        },
                        error: function () {
                            done();
                            showAdminNotice('An error occurred. Please try again.', 'error');
                        }
                    });
                },
                { confirmText: 'Delete', confirmClass: 'wfeb-btn--danger' }
            );
        });

        // =====================================================================
        // 2b. REMOVE COACH
        // =====================================================================

        $(document).on('click', '.wfeb-remove-coach', function (e) {
            e.preventDefault();

            var $btn      = $(this);
            var coachId   = $btn.data('coach-id');
            var coachName = escapeHtml($btn.data('coach-name') || 'this coach');

            wfebConfirm(
                'Remove Coach',
                'Permanently remove <strong>' + coachName + '</strong>? This will delete their account and all associated data. This action cannot be undone.',
                function (done) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wfeb_remove_coach',
                            coach_id: coachId,
                            security: $('#wfeb_admin_nonce').val() || wfeb_admin.nonce
                        },
                        success: function (response) {
                            done();
                            if (response.success) {
                                showAdminNotice('Coach removed successfully.', 'success');
                                var $row = $btn.closest('tr');
                                if ($row.length) {
                                    // List page — remove the row
                                    $row.fadeOut(300, function () { $(this).remove(); });
                                } else {
                                    // Detail page — redirect to coaches list
                                    setTimeout(function () {
                                        window.location.href = ajaxurl.replace('/admin-ajax.php', '/admin.php?page=wfeb-coaches');
                                    }, 600);
                                }
                            } else {
                                showAdminNotice(response.data.message || 'Failed to remove coach.', 'error');
                            }
                        },
                        error: function () {
                            done();
                            showAdminNotice('An error occurred. Please try again.', 'error');
                        }
                    });
                },
                { confirmText: 'Remove', confirmClass: 'wfeb-btn--danger' }
            );
        });

        // =====================================================================
        // 3. REVOKE CERTIFICATE
        // =====================================================================

        $(document).on('click', '.wfeb-revoke-certificate', function (e) {
            e.preventDefault();

            var $btn       = $(this);
            var certId     = $btn.data('cert-id');
            var certNumber = escapeHtml($btn.data('cert-number') || '');

            var body =
                '<p>Provide a reason for revoking' + (certNumber ? ' certificate <strong>' + certNumber + '</strong>' : ' this certificate') + ':</p>' +
                '<textarea id="wfeb-revoke-reason" rows="4" placeholder="Enter reason for revocation..."></textarea>';

            var footer =
                '<button type="button" class="wfeb-btn wfeb-btn--ghost" id="wfeb-modal-cancel-btn">Cancel</button>' +
                '<button type="button" class="wfeb-btn wfeb-btn--danger" id="wfeb-confirm-revoke" data-cert-id="' + certId + '">Revoke Certificate</button>';

            openModal('Revoke Certificate', body, footer);
        });

        $(document).on('click', '#wfeb-confirm-revoke', function () {
            var certId = $(this).data('cert-id');
            var reason = $('#wfeb-revoke-reason').val().trim();
            var $btn   = $(this);

            setLoading($btn, true);
            console.log('[WFEB] Revoking certificate:', certId);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wfeb_revoke_certificate',
                    certificate_id: certId,
                    reason: reason,
                    security: $('#wfeb_admin_nonce').val() || wfeb_admin.nonce
                },
                success: function (response) {
                    closeModal();
                    if (response.success) {
                        showAdminNotice('Certificate revoked.', 'success');
                        var $row = $('[data-cert-row="' + certId + '"]');
                        $row.find('.wfeb-badge').text('Revoked').attr('class', 'wfeb-badge wfeb-badge--revoked');
                        $row.find('.wfeb-revoke-certificate').remove();
                    } else {
                        showAdminNotice(response.data.message || 'Failed to revoke certificate.', 'error');
                    }
                },
                error: function () {
                    closeModal();
                    showAdminNotice('An error occurred. Please try again.', 'error');
                }
            });
        });

        // Generic cancel button inside any modal footer
        $(document).on('click', '#wfeb-modal-cancel-btn', function () {
            closeModal();
        });

        // =====================================================================
        // 4. CREDIT ADJUSTMENT
        // =====================================================================

        $(document).on('submit', '.wfeb-admin-credit-form', function (e) {
            e.preventDefault();

            var $form  = $(this);
            var $btn   = $form.find('.wfeb-admin-adjust-credits-btn');
            var coachId = $btn.data('coach-id');
            var amount  = parseInt($form.find('#wfeb-credit-amount').val(), 10);
            var reason  = $form.find('#wfeb-credit-reason').val().trim();

            if (isNaN(amount) || amount === 0) {
                showAdminNotice('Please enter a non-zero adjustment amount.', 'error');
                return;
            }

            setLoading($btn, true);

            // Replace balance number with spinner while loading
            var $balanceNum = $form.closest('.wfeb-card').find('.wfeb-credits-bar .wfeb-stat-number');
            var originalBalance = $balanceNum.text().trim();
            $balanceNum.html('<span class="wfeb-spinner">' + createSpinner().prop('outerHTML') + '</span>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wfeb_admin_adjust_credits',
                    coach_id: coachId,
                    amount: amount,
                    reason: reason,
                    security: wfeb_admin.nonce
                },
                success: function (response) {
                    setLoading($btn, false);
                    if (response.success) {
                        var newBal = response.data.data ? response.data.data.new_balance : originalBalance;
                        $balanceNum.text(newBal);
                        showAdminNotice(response.data.message || 'Credits adjusted.', 'success');
                        $form.find('#wfeb-credit-amount').val(0);
                        $form.find('#wfeb-credit-reason').val('');
                    } else {
                        $balanceNum.text(originalBalance);
                        showAdminNotice(response.data.message || 'Failed to adjust credits.', 'error');
                    }
                },
                error: function () {
                    setLoading($btn, false);
                    $balanceNum.text(originalBalance);
                    showAdminNotice('An error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 5. SETTINGS TABS (dead code — tabs are server-side, kept for safety)
        // =====================================================================

        $(document).on('click', '.wfeb-settings-tab', function (e) {
            e.preventDefault();
            var target = $(this).data('tab');
            $('.wfeb-settings-tab').removeClass('active');
            $(this).addClass('active');
            $('.wfeb-settings-panel').removeClass('active').hide();
            $('#wfeb-panel-' + target).addClass('active').show();
        });

        // =====================================================================
        // 6. SETTINGS SAVE
        // =====================================================================

        $(document).on('submit', '.wfeb-settings-form', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $btn  = $form.find('button[type="submit"], input[type="submit"]');
            setLoading($btn, true);

            var formData = new FormData(this);
            formData.append('action', 'wfeb_save_settings');
            if (!formData.has('security')) {
                formData.append('security', $('#wfeb_admin_nonce').val() || wfeb_admin.nonce);
            }
            var tabValue = $form.find('[name="wfeb_tab"]').val();
            if (tabValue) {
                formData.append('tab', tabValue);
            }

            console.log('[WFEB] Saving admin settings');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    setLoading($btn, false);
                    if (response.success) {
                        showAdminNotice('Settings saved successfully.', 'success');
                    } else {
                        showAdminNotice(response.data.message || 'Failed to save settings.', 'error');
                    }
                },
                error: function () {
                    setLoading($btn, false);
                    showAdminNotice('An error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // 8. TEST EMAIL BUTTON
        // =====================================================================

        $(document).on('click', '#wfeb-test-email-btn', function (e) {
            e.preventDefault();

            var $btn      = $(this);
            var testEmail = $('#wfeb-test-email-address').val() || '';

            if (!testEmail.trim()) {
                showAdminNotice('Please enter an email address for the test.', 'error');
                return;
            }

            setLoading($btn, true);
            console.log('[WFEB] Sending test email to:', testEmail);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wfeb_send_test_email',
                    email: testEmail.trim(),
                    security: $('#wfeb_admin_nonce').val() || wfeb_admin.nonce
                },
                success: function (response) {
                    setLoading($btn, false);
                    if (response.success) {
                        showAdminNotice('Test email sent to ' + escapeHtml(testEmail) + '.', 'success');
                    } else {
                        showAdminNotice(response.data.message || 'Failed to send test email.', 'error');
                    }
                },
                error: function () {
                    setLoading($btn, false);
                    showAdminNotice('An error occurred. Please try again.', 'error');
                }
            });
        });

        // =====================================================================
        // REGENERATE CERTIFICATES
        // =====================================================================

        $('#wfeb-regenerate-certs-btn').on('click', function () {
            var $btn = $(this);
            var $status = $('#wfeb-regenerate-certs-status');

            if (!confirm('This will regenerate ALL certificate files with QR codes and create score reports. Continue?')) {
                return;
            }

            setLoading($btn, true);
            $status.text('Processing...').css('color', '#666');

            $.ajax({
                url: wfeb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wfeb_regenerate_certificates',
                    security: $('#wfeb_admin_nonce').val() || wfeb_admin.nonce
                },
                success: function (response) {
                    setLoading($btn, false);
                    if (response.success) {
                        $status.text(response.data.message).css('color', '#10b981');
                        showAdminNotice(response.data.message, 'success');
                    } else {
                        $status.text(response.data.message || 'Failed.').css('color', '#ef4444');
                        showAdminNotice(response.data.message || 'Failed to regenerate certificates.', 'error');
                    }
                },
                error: function () {
                    setLoading($btn, false);
                    $status.text('An error occurred.').css('color', '#ef4444');
                    showAdminNotice('An error occurred. Please try again.', 'error');
                },
                timeout: 300000 // 5 minutes - regeneration can take time.
            });
        });

        // =====================================================================
        // ADMIN NOTICE HELPER
        // =====================================================================

        /**
         * Show a toast notification (top-right, fixed).
         *
         * @param {string} message
         * @param {string} type — 'success' | 'error' | 'warning' | 'info'
         */
        function showAdminNotice(message, type) {
            type = type || 'info';

            // Ensure container exists
            if (!$('#wfeb-toast-container').length) {
                $('body').append('<div id="wfeb-toast-container" class="wfeb-toast-container"></div>');
            }

            var icons = { success: '✓', error: '✕', warning: '!', info: 'i' };
            var icon  = icons[type] || 'i';

            var $toast = $(
                '<div class="wfeb-toast wfeb-toast--' + type + '">' +
                    '<span class="wfeb-toast-icon">' + icon + '</span>' +
                    '<span class="wfeb-toast-message">' + escapeHtml(message) + '</span>' +
                    '<button type="button" class="wfeb-toast-close" aria-label="Dismiss">&times;</button>' +
                '</div>'
            );

            $('#wfeb-toast-container').append($toast);

            $toast.find('.wfeb-toast-close').on('click', function () {
                dismissToast($toast);
            });

            // Auto-dismiss
            var delay = type === 'error' ? 8000 : 5000;
            setTimeout(function () { dismissToast($toast); }, delay);
        }

        function dismissToast($toast) {
            $toast.css({ animation: 'wfebToastOut .22s ease forwards' });
            setTimeout(function () { $toast.remove(); }, 220);
        }

        // =====================================================================
        // REAL-TIME TABLE SEARCH
        // =====================================================================

        (function initLiveSearch() {
            var $input = $('.wfeb-search-input');
            if (!$input.length) return;

            var $table = $('.wfeb-table');
            if (!$table.length) return;

            var $rows  = $table.find('tbody tr');

            // Prevent form submit on Enter when search input is focused
            $input.closest('form').on('submit', function (e) {
                if ($input.is(':focus')) {
                    e.preventDefault();
                }
            });

            $input.on('input', function () {
                var query = $.trim($(this).val()).toLowerCase();

                if (!query) {
                    $rows.show();
                    $table.find('.wfeb-no-results-row').remove();
                    return;
                }

                var visible = 0;
                $rows.each(function () {
                    var text = $(this).text().toLowerCase();
                    var match = text.indexOf(query) !== -1;
                    $(this).toggle(match);
                    if (match) visible++;
                });

                $table.find('.wfeb-no-results-row').remove();
                if (visible === 0) {
                    var cols = $table.find('thead th').length || 1;
                    $table.find('tbody').append(
                        '<tr class="wfeb-no-results-row"><td colspan="' + cols + '" style="text-align:center;padding:32px;color:#64748b;">No results matching "' + escapeHtml(query) + '"</td></tr>'
                    );
                }
            });
        })();

        // =====================================================================
        // MEDIA UPLOAD BUTTONS
        // =====================================================================

        $(document).on('click', '.wfeb-admin-upload-btn', function (e) {
            e.preventDefault();

            var $btn    = $(this);
            var target  = $btn.data('target');
            var $input  = $(target);

            if (!wp || !wp.media) {
                alert('WordPress media library not available.');
                return;
            }

            var frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use This Image' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.url).trigger('change');

                // Update preview if present.
                var $preview = $btn.closest('.wfeb-form-row').find('.wfeb-logo-preview, .wfeb-cert-bg-preview');
                if ($preview.length) {
                    $preview.find('img').attr('src', attachment.url);
                } else {
                    // Create preview if one doesn't exist.
                    var previewClass = target.indexOf('logo') !== -1 ? 'wfeb-logo-preview' : 'wfeb-cert-bg-preview';
                    $btn.after('<div class="' + previewClass + '" style="margin-top:10px;"><img src="' + escapeHtml(attachment.url) + '" style="max-height:60px;border-radius:4px;border:1px solid #e2e8f0;padding:4px;" /></div>');
                }
            });

            frame.open();
        });

        // =====================================================================
        // CERTIFICATE PREVIEW
        // =====================================================================

        // Remove custom certificate background.
        $(document).on('click', '.wfeb-admin-remove-cert-bg', function (e) {
            e.preventDefault();
            var $btn = $(this);
            $('#wfeb_cert_background').val('');
            $btn.closest('.wfeb-form-row').find('.wfeb-cert-bg-preview').fadeOut(200, function () {
                $(this).remove();
            });
            $btn.fadeOut(200, function () {
                $(this).remove();
            });
        });

        $(document).on('click', '.wfeb-admin-preview-certificate', function (e) {
            e.preventDefault();

            var $btn    = $(this);
            var $status = $btn.siblings('.wfeb-admin-preview-status');

            setLoading($btn, true);
            $status.text('Generating...').css('color', '#64748b');

            // Collect current form values so the preview uses them.
            var $form = $btn.closest('form');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wfeb_preview_certificate',
                    security: wfeb_admin.nonce,
                    cert_background: $form.find('#wfeb_cert_background').val() || '',
                    cert_authoriser_name: $form.find('#wfeb_cert_authoriser_name').val() || ''
                },
                success: function (response) {
                    setLoading($btn, false);
                    if (response.success && response.data.url) {
                        $status.text('');
                        window.open(response.data.url, '_blank');
                    } else {
                        $status.text(response.data && response.data.message ? response.data.message : 'Preview failed.').css('color', '#ef4444');
                    }
                },
                error: function () {
                    setLoading($btn, false);
                    $status.text('Request failed.').css('color', '#ef4444');
                }
            });
        });

    }); // end document.ready
})(jQuery);
