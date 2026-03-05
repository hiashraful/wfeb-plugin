/**
 * WFEB Media Upload Handler
 *
 * Reusable image upload with optional crop modal (Cropper.js).
 * Works with .wfeb-upload-zone components rendered by WFEB_Media::upload_zone().
 *
 * @package WFEB
 * @since 1.0.0
 */
'use strict';

var WFEB_Upload = {

    currentCropper: null,
    currentCropZone: null,
    currentCropFile: null,
    currentCropRatio: 1,

    init: function () {
        this.bindUploadZone();
    },

    bindUploadZone: function () {
        var self = this;

        // Click upload button
        jQuery(document).on('click', '.wfeb-upload-zone .wfeb-upload-btn', function (e) {
            e.preventDefault();
            jQuery(this).closest('.wfeb-upload-zone').find('input[type="file"]').click();
        });

        // File selected
        jQuery(document).on('change', '.wfeb-upload-zone > input[type="file"]', function () {
            var file = this.files[0];
            if (file) {
                self.uploadFile(file, jQuery(this).closest('.wfeb-upload-zone'));
            }
            jQuery(this).val('');
        });

        // Drag and drop
        jQuery(document).on('dragover dragenter', '.wfeb-upload-zone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            jQuery(this).addClass('dragging');
        });

        jQuery(document).on('dragleave', '.wfeb-upload-zone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            jQuery(this).removeClass('dragging');
        });

        jQuery(document).on('drop', '.wfeb-upload-zone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            jQuery(this).removeClass('dragging');
            var file = e.originalEvent.dataTransfer.files[0];
            if (file) {
                self.uploadFile(file, jQuery(this));
            }
        });

        // Remove image
        jQuery(document).on('click', '.wfeb-upload-zone .wfeb-upload-remove-btn', function (e) {
            e.preventDefault();
            self.removeImage(jQuery(this).closest('.wfeb-upload-zone'));
        });
    },

    uploadFile: function (file, $zone) {
        if (!file.type.match('image.*')) {
            this.showError('Please select an image file (JPG, PNG, GIF, WebP)');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            this.showError('File must be less than 5MB');
            return;
        }

        var enableCrop = $zone.data('crop') === 1 || $zone.data('crop') === '1';

        if (enableCrop && typeof Cropper !== 'undefined') {
            this.showCropModal(file, $zone);
            return;
        }

        this.doUpload(file, $zone);
    },

    showCropModal: function (file, $zone) {
        var self = this;
        var ratio = parseFloat($zone.data('ratio')) || 1;

        this.currentCropZone = $zone;
        this.currentCropFile = file;
        this.currentCropRatio = ratio;

        // Create modal if needed
        if (!jQuery('#wfeb-crop-modal').length) {
            var modalHtml =
                '<div id="wfeb-crop-modal" class="wfeb-crop-modal">' +
                    '<div class="wfeb-crop-modal-overlay"></div>' +
                    '<div class="wfeb-crop-modal-content">' +
                        '<div class="wfeb-crop-modal-header">' +
                            '<div class="wfeb-crop-header-left">' +
                                '<h3>Crop Image</h3>' +
                                '<span class="wfeb-crop-subtitle">Adjust your image to fit perfectly</span>' +
                            '</div>' +
                            '<button type="button" class="wfeb-crop-modal-close" aria-label="Close">' +
                                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                            '</button>' +
                        '</div>' +
                        '<div class="wfeb-crop-modal-body">' +
                            '<div class="wfeb-crop-workspace">' +
                                '<div class="wfeb-crop-container">' +
                                    '<div class="wfeb-crop-loading">' +
                                        '<div class="wfeb-crop-spinner"></div>' +
                                        '<span>Loading image...</span>' +
                                    '</div>' +
                                    '<img id="wfeb-crop-image" src="" alt="Crop preview">' +
                                '</div>' +
                            '</div>' +
                            '<div class="wfeb-crop-controls">' +
                                '<div class="wfeb-crop-control-group">' +
                                    '<label class="wfeb-crop-control-label">' +
                                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>' +
                                        'Zoom' +
                                    '</label>' +
                                    '<div class="wfeb-crop-zoom-wrapper">' +
                                        '<button type="button" class="wfeb-crop-zoom-btn" data-action="zoom-out" title="Zoom out">' +
                                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
                                        '</button>' +
                                        '<input type="range" class="wfeb-crop-zoom-slider" id="wfeb-crop-zoom" min="0.1" max="3" step="0.01" value="1">' +
                                        '<button type="button" class="wfeb-crop-zoom-btn" data-action="zoom-in" title="Zoom in">' +
                                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
                                        '</button>' +
                                        '<span class="wfeb-crop-zoom-value">100%</span>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="wfeb-crop-control-group">' +
                                    '<label class="wfeb-crop-control-label">' +
                                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.3"/></svg>' +
                                        'Rotate' +
                                    '</label>' +
                                    '<div class="wfeb-crop-rotate-buttons">' +
                                        '<button type="button" class="wfeb-crop-action-btn" data-action="rotate-left" title="Rotate left">' +
                                            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>' +
                                        '</button>' +
                                        '<button type="button" class="wfeb-crop-action-btn" data-action="rotate-right" title="Rotate right">' +
                                            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>' +
                                        '</button>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="wfeb-crop-control-group">' +
                                    '<label class="wfeb-crop-control-label">' +
                                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5M8 3H3v5M3 16v5h5M21 16v5h-5"/></svg>' +
                                        'Flip' +
                                    '</label>' +
                                    '<div class="wfeb-crop-flip-buttons">' +
                                        '<button type="button" class="wfeb-crop-action-btn" data-action="flip-h" title="Flip horizontal">' +
                                            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M16 7l4 5-4 5M8 7l-4 5 4 5"/></svg>' +
                                        '</button>' +
                                        '<button type="button" class="wfeb-crop-action-btn" data-action="flip-v" title="Flip vertical">' +
                                            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M7 8l5-4 5 4M7 16l5 4 5-4"/></svg>' +
                                        '</button>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="wfeb-crop-control-group wfeb-crop-reset-group">' +
                                    '<button type="button" class="wfeb-crop-reset-btn" data-action="reset" title="Reset all changes">' +
                                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>' +
                                        'Reset' +
                                    '</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="wfeb-crop-modal-footer">' +
                            '<div class="wfeb-crop-footer-left">' +
                                '<span class="wfeb-crop-hint">Drag to reposition, use controls to adjust</span>' +
                            '</div>' +
                            '<div class="wfeb-crop-footer-right">' +
                                '<button type="button" class="wfeb-btn wfeb-btn--outline wfeb-crop-cancel">Cancel</button>' +
                                '<button type="button" class="wfeb-btn wfeb-btn--primary wfeb-crop-confirm">' +
                                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>' +
                                    '<span>Apply & Upload</span>' +
                                '</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            jQuery('body').append(modalHtml);

            // Bind modal events
            jQuery(document).on('click', '.wfeb-crop-modal-close, .wfeb-crop-cancel', function (e) {
                e.preventDefault();
                self.closeCropModal();
            });

            jQuery(document).on('click', '.wfeb-crop-modal-overlay', function (e) {
                if (e.target === this) {
                    self.closeCropModal();
                }
            });

            jQuery(document).on('click', '.wfeb-crop-confirm', function (e) {
                e.preventDefault();
                self.confirmCrop();
            });

            // Zoom slider
            jQuery(document).on('input', '#wfeb-crop-zoom', function () {
                var $img = jQuery('#wfeb-crop-image');
                if ($img.data('cropper')) {
                    var value = parseFloat(jQuery(this).val());
                    $img.cropper('zoomTo', value);
                    jQuery('.wfeb-crop-zoom-value').text(Math.round(value * 100) + '%');
                }
            });

            // Zoom buttons
            jQuery(document).on('click', '.wfeb-crop-zoom-btn', function (e) {
                e.preventDefault();
                var $img = jQuery('#wfeb-crop-image');
                if (!$img.data('cropper')) return;
                var action = jQuery(this).data('action');
                var $slider = jQuery('#wfeb-crop-zoom');
                var currentVal = parseFloat($slider.val());
                var newVal;
                if (action === 'zoom-in') {
                    newVal = Math.min(currentVal + 0.1, 3);
                } else {
                    newVal = Math.max(currentVal - 0.1, 0.1);
                }
                $slider.val(newVal).trigger('input');
            });

            // Action buttons (rotate, flip, reset)
            jQuery(document).on('click', '.wfeb-crop-action-btn, .wfeb-crop-reset-btn', function (e) {
                e.preventDefault();
                var $img = jQuery('#wfeb-crop-image');
                var cropper = $img.data('cropper');
                if (!cropper) return;
                var action = jQuery(this).data('action');
                switch (action) {
                    case 'rotate-left':
                        $img.cropper('rotate', -90);
                        break;
                    case 'rotate-right':
                        $img.cropper('rotate', 90);
                        break;
                    case 'flip-h':
                        var scaleX = cropper.getData().scaleX || 1;
                        $img.cropper('scaleX', -scaleX);
                        break;
                    case 'flip-v':
                        var scaleY = cropper.getData().scaleY || 1;
                        $img.cropper('scaleY', -scaleY);
                        break;
                    case 'reset':
                        $img.cropper('reset');
                        jQuery('#wfeb-crop-zoom').val(1);
                        jQuery('.wfeb-crop-zoom-value').text('100%');
                        break;
                }
            });

            // Keyboard shortcuts
            jQuery(document).on('keydown.wfebCrop', function (e) {
                if (!jQuery('#wfeb-crop-modal').is(':visible')) return;
                if (e.key === 'Escape') {
                    self.closeCropModal();
                } else if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.confirmCrop();
                }
            });
        }

        // Show loading
        var $modal = jQuery('#wfeb-crop-modal');
        var $loading = $modal.find('.wfeb-crop-loading');
        var $img = jQuery('#wfeb-crop-image');

        $loading.show();
        $img.hide();

        jQuery('#wfeb-crop-zoom').val(1);
        jQuery('.wfeb-crop-zoom-value').text('100%');

        // Read file
        var reader = new FileReader();
        reader.onload = function (e) {
            $img.attr('src', e.target.result);

            if (self.currentCropper) {
                self.currentCropper.destroy();
                self.currentCropper = null;
            }

            $modal.addClass('wfeb-crop-modal-visible');
            jQuery('body').addClass('wfeb-crop-modal-open');

            $img.off('load.wfebCrop').on('load.wfebCrop', function () {
                $loading.fadeOut(200, function () {
                    $img.fadeIn(200, function () {
                        $img.cropper({
                            aspectRatio: ratio,
                            viewMode: 2,
                            dragMode: 'move',
                            autoCropArea: 0.8,
                            responsive: true,
                            restore: false,
                            guides: true,
                            center: true,
                            highlight: true,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: false,
                            background: true,
                            modal: true,
                            zoomOnWheel: true,
                            wheelZoomRatio: 0.1,
                            minContainerWidth: 200,
                            minContainerHeight: 200,
                            ready: function () {
                                self.currentCropper = $img.data('cropper');
                                if (self.currentCropper) {
                                    var imageData = self.currentCropper.getImageData();
                                    var initialZoom = imageData.width / imageData.naturalWidth;
                                    jQuery('#wfeb-crop-zoom').val(initialZoom);
                                    jQuery('.wfeb-crop-zoom-value').text(Math.round(initialZoom * 100) + '%');
                                }
                            },
                            zoom: function (e) {
                                var zoomRatio = e.detail.ratio;
                                if (zoomRatio > 3) {
                                    e.preventDefault();
                                    $img.cropper('zoomTo', 3);
                                    zoomRatio = 3;
                                } else if (zoomRatio < 0.1) {
                                    e.preventDefault();
                                    $img.cropper('zoomTo', 0.1);
                                    zoomRatio = 0.1;
                                }
                                jQuery('#wfeb-crop-zoom').val(zoomRatio);
                                jQuery('.wfeb-crop-zoom-value').text(Math.round(zoomRatio * 100) + '%');
                            }
                        });

                        self.currentCropper = $img.data('cropper');
                    });
                });
            });

            if ($img[0].complete && $img[0].naturalWidth) {
                $img.trigger('load.wfebCrop');
            }
        };
        reader.readAsDataURL(file);
    },

    closeCropModal: function () {
        var self = this;
        var $modal = jQuery('#wfeb-crop-modal');
        var $img = jQuery('#wfeb-crop-image');

        $modal.removeClass('wfeb-crop-modal-visible');
        jQuery('body').removeClass('wfeb-crop-modal-open');

        setTimeout(function () {
            if ($img.data('cropper')) {
                $img.cropper('destroy');
            }
            self.currentCropper = null;
            $img.attr('src', '').hide();
            self.currentCropZone = null;
            self.currentCropFile = null;
        }, 300);
    },

    confirmCrop: function () {
        var self = this;
        var $img = jQuery('#wfeb-crop-image');
        var cropper = $img.data('cropper');

        if (!cropper || !this.currentCropZone || !this.currentCropFile) {
            this.closeCropModal();
            return;
        }

        var $zone = this.currentCropZone;
        var filename = this.currentCropFile.name;
        var ratio = this.currentCropRatio || 1;
        var $btn = jQuery('.wfeb-crop-confirm');
        var $btnText = $btn.find('span');
        var originalText = $btnText.text();

        $btn.prop('disabled', true).addClass('wfeb-crop-uploading');
        $btnText.text('Uploading...');

        var outputWidth = 800;
        var outputHeight = Math.round(outputWidth / ratio);

        var canvas = $img.cropper('getCroppedCanvas', {
            width: outputWidth,
            height: outputHeight,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });

        var imageData = canvas.toDataURL('image/jpeg', 0.92);

        jQuery.ajax({
            url: wfeb_coach.ajax_url,
            type: 'POST',
            data: {
                action: 'wfeb_upload_cropped_image',
                nonce: wfeb_coach.nonce,
                image_data: imageData,
                filename: filename
            },
            success: function (response) {
                $btn.prop('disabled', false).removeClass('wfeb-crop-uploading');
                $btnText.text(originalText);
                self.closeCropModal();

                if (response.success) {
                    var inputId = $zone.data('input');
                    var $input = inputId ? jQuery('#' + inputId) : $zone.find('input[type="hidden"]');
                    $input.val(response.data.id);

                    var imgUrl = response.data.thumb_url || response.data.url;
                    $zone.find('.wfeb-upload-preview').html(
                        '<img src="' + imgUrl + '" alt="">'
                    );
                    $zone.find('.wfeb-upload-remove-btn').show();
                } else {
                    self.showError(response.data || 'Upload failed');
                }
            },
            error: function () {
                $btn.prop('disabled', false).removeClass('wfeb-crop-uploading');
                $btnText.text(originalText);
                self.closeCropModal();
                self.showError('Upload failed. Please try again.');
            }
        });
    },

    doUpload: function (file, $zone) {
        var self = this;
        var $progress = $zone.find('.wfeb-upload-progress');
        var $bar = $zone.find('.wfeb-progress-bar');
        var $btn = $zone.find('.wfeb-upload-btn');
        var originalBtnText = $btn.text();

        $progress.show();
        $btn.prop('disabled', true).text('Uploading...');

        var formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'wfeb_upload_media');
        formData.append('nonce', wfeb_coach.nonce);

        jQuery.ajax({
            url: wfeb_coach.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function () {
                var xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var percent = Math.round(e.loaded / e.total * 100);
                        $bar.css('width', percent + '%');
                    }
                });
                return xhr;
            },
            success: function (response) {
                $progress.hide();
                $bar.css('width', '0');
                $btn.prop('disabled', false).text(originalBtnText);

                if (response.success) {
                    var inputId = $zone.data('input');
                    var $input = inputId ? jQuery('#' + inputId) : $zone.find('input[type="hidden"]');
                    $input.val(response.data.id);

                    var imgUrl = response.data.thumb_url || response.data.url;
                    $zone.find('.wfeb-upload-preview').html(
                        '<img src="' + imgUrl + '" alt="">'
                    );
                    $zone.find('.wfeb-upload-remove-btn').show();
                } else {
                    self.showError(response.data || 'Upload failed');
                }
            },
            error: function () {
                $progress.hide();
                $bar.css('width', '0');
                $btn.prop('disabled', false).text(originalBtnText);
                self.showError('Upload failed. Please try again.');
            }
        });
    },

    removeImage: function ($zone) {
        var type = $zone.data('type') || 'image';
        var inputId = $zone.data('input');
        var $input = inputId ? jQuery('#' + inputId) : $zone.find('input[type="hidden"]');

        $input.val('');
        $zone.find('.wfeb-upload-remove-btn').hide();

        // Restore placeholder
        var placeholders = {
            avatar: '<div class="wfeb-placeholder-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>',
            image: '<div class="wfeb-placeholder-svg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>'
        };

        $zone.find('.wfeb-upload-preview').html(placeholders[type] || placeholders.image);
    },

    showError: function (message) {
        // Use WFEB toast if available, otherwise alert
        if (typeof window.WFEB_showToast === 'function') {
            window.WFEB_showToast(message, 'error');
        } else {
            alert(message);
        }
    }
};

// Initialize when DOM is ready
jQuery(document).ready(function () {
    WFEB_Upload.init();
});
