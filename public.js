// public/assets/public.js

jQuery(document).ready(function($) {
    'use strict';
    
    // Registration Process
    URA_Registration.init();
    
    // File Upload
    URA_FileUpload.init();
    
    // User Dashboard
    URA_UserDashboard.init();
    
    // Services Selection
    URA_Services.init();
});

// Registration Module
var URA_Registration = {
    init: function() {
        this.bindEvents();
    },
    
    bindEvents: function() {
        // Phone verification
        $('#ura-phone-form').on('submit', this.handlePhoneSubmit.bind(this));
        
        // SMS verification
        $('#ura-verify-form').on('submit', this.handleSMSVerify.bind(this));
        
        // Personal details
        $('#ura-details-form').on('submit', this.handleDetailsSubmit.bind(this));
        
        // Address form
        $('#ura-address-form').on('submit', this.handleAddressSubmit.bind(this));
        
        // SMS code auto-fill
        $('.ura-sms-digit').on('input', this.handleSMSDigitInput.bind(this));
        
        // Resend SMS
        $('#ura-resend-sms').on('click', this.handleResendSMS.bind(this));
        
        // Zipcode modal
        $('#ura-find-zipcode').on('click', this.openZipcodeModal.bind(this));
        $('#ura-zipcode-form').on('submit', this.handleZipcodeSearch.bind(this));
    },
    
    handlePhoneSubmit: function(e) {
        e.preventDefault();
        
        var phone = $('#ura-phone').val().trim();
        
        if (!this.validatePhone(phone)) {
            this.showError('מספר הטלפון אינו תקין');
            return;
        }
        
        this.showLoading('שולח קוד אימות...');
        
        $.ajax({
            url: ura_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ura_send_sms_code',
                phone: phone,
                nonce: ura_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    URA_Registration.handlePhoneSuccess(response.data);
                } else {
                    URA_Registration.showError(response.data);
                }
            },
            error: function() {
                URA_Registration.showError('שגיאה בשליחת ה-SMS. נסה שוב.');
            },
            complete: function() {
                URA_Registration.hideLoading();
            }
        });
    },
    
    handlePhoneSuccess: function(data) {
        $('#ura-phone-display').text(data.phone_display);
        $('#ura-step-phone').removeClass('active');
        $('#ura-step-verify').addClass('active');
        
        // Start countdown for resend
        this.startResendCountdown();
        
        // Focus first digit
        $('.ura-sms-digit').first().focus();
    },
    
    handleSMSVerify: function(e) {
        e.preventDefault();
        
        var smsCode = $('#ura-sms-code').val();
        
        if (smsCode.length !== 6) {
            this.showError('אנא הזן קוד בן 6 ספרות');
            return;
        }
        
        this.showLoading('מאמת קוד...');
        
        $.ajax({
            url: ura_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ura_verify_sms_code',
                sms_code: smsCode,
                nonce: ura_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    URA_Registration.handleSMSVerifySuccess();
                } else {
                    URA_Registration.showError(response.data);
                }
            },
            error: function() {
                URA_Registration.showError('שגיאה באימות הקוד');
            },
            complete: function() {
                URA_Registration.hideLoading();
            }
        });
    },
    
    handleSMSVerifySuccess: function() {
        $('#ura-step-verify').removeClass('active');
        $('#ura-step-details').addClass('active');
    },
    
    handleSMSDigitInput: function(e) {
        var $this = $(this);
        var value = $this.val();
        var index = parseInt($this.data('index'));
        
        if (value.length === 1 && index < 6) {
            $('.ura-sms-digit[data-index="' + (index + 1) + '"]').focus();
        }
        
        // Update hidden field
        this.updateSMSCodeField();
    },
    
    updateSMSCodeField: function() {
        var code = '';
        $('.ura-sms-digit').each(function() {
            code += $(this).val();
        });
        $('#ura-sms-code').val(code);
    },
    
    startResendCountdown: function() {
        var countdown = 60;
        var $timer = $('#ura-countdown');
        var $resendBtn = $('#ura-resend-sms');
        
        $resendBtn.prop('disabled', true);
        
        var timer = setInterval(function() {
            countdown--;
            $timer.text(countdown);
            
            if (countdown <= 0) {
                clearInterval(timer);
                $resendBtn.prop('disabled', false);
                $('#ura-timer-text').hide();
            }
        }, 1000);
    },
    
    validatePhone: function(phone) {
        var cleanPhone = phone.replace(/[^0-9]/g, '');
        return cleanPhone.length === 10 && cleanPhone.startsWith('05');
    },
    
    showError: function(message) {
        $('.ura-error-message').remove();
        $('<div class="ura-error-message">' + message + '</div>')
            .hide()
            .insertBefore($('.ura-form-group').first())
            .slideDown();
    },
    
    showLoading: function(message) {
        $('body').append(
            '<div class="ura-loading-overlay">' +
            '<div class="ura-loading-content">' +
            '<div class="ura-spinner"></div>' +
            '<p>' + (message || 'טוען...') + '</p>' +
            '</div>' +
            '</div>'
        );
    },
    
    hideLoading: function() {
        $('.ura-loading-overlay').remove();
    }
};

// File Upload Module
var URA_FileUpload = {
    init: function() {
        this.bindEvents();
    },
    
    bindEvents: function() {
        // File selection
        $('#ura-select-files').on('click', this.openFileDialog.bind(this));
        $('#ura-file-input').on('change', this.handleFileSelection.bind(this));
        
        // Drag and drop
        this.initDragAndDrop();
        
        // Upload start
        $('#ura-start-upload').on('click', this.startUpload.bind(this));
        
        // Terms acceptance
        $('#ura-accept-terms').on('change', this.handleTermsAcceptance.bind(this));
    },
    
    openFileDialog: function() {
        $('#ura-file-input').click();
    },
    
    handleFileSelection: function(e) {
        var files = e.target.files;
        this.displaySelectedFiles(files);
    },
    
    initDragAndDrop: function() {
        var $uploadArea = $('#ura-upload-area');
        
        $uploadArea.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });
        
        $uploadArea.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });
        
        $uploadArea.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
            
            var files = e.originalEvent.dataTransfer.files;
            URA_FileUpload.displaySelectedFiles(files);
        });
    },
    
    displaySelectedFiles: function(files) {
        if (files.length === 0) return;
        
        var $fileList = $('#ura-selected-files-list');
        $fileList.empty();
        
        var totalSize = 0;
        var hasErrors = false;
        
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            totalSize += file.size;
            
            var validation = this.validateFile(file);
            var fileHtml = this.createFileHTML(file, validation);
            
            $fileList.append(fileHtml);
            
            if (!validation.valid) {
                hasErrors = true;
            }
        }
        
        // Show upload section
        $('#ura-upload-placeholder').hide();
        $('#ura-upload-progress').show();
        
        // Enable/disable upload button
        $('#ura-start-upload').prop('disabled', hasErrors || !$('#ura-accept-terms').is(':checked'));
    },
    
    validateFile: function(file) {
        var errors = [];
        var maxSize = 50 * 1024 * 1024; // 50MB
        var allowedTypes = ['pdf', 'doc', 'docx'];
        
        var extension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(extension)) {
            errors.push('סוג קובץ לא נתמך');
        }
        
        if (file.size > maxSize) {
            errors.push('גודל קובץ חורג מהמותר');
        }
        
        if (file.size === 0) {
            errors.push('הקובץ ריק');
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    },
    
    createFileHTML: function(file, validation) {
        var size = this.formatFileSize(file.size);
        var statusIcon = validation.valid ? '✅' : '❌';
        var statusClass = validation.valid ? 'ura-file-valid' : 'ura-file-invalid';
        
        var errorsHtml = '';
        if (!validation.valid) {
            errorsHtml = '<div class="ura-file-errors">' + 
                        validation.errors.join(', ') + 
                        '</div>';
        }
        
        return `
            <div class="ura-selected-file ${statusClass}">
                <div class="ura-file-info">
                    <span class="ura-file-status">${statusIcon}</span>
                    <span class="ura-file-name">${file.name}</span>
                    <span class="ura-file-size">(${size})</span>
                </div>
                ${errorsHtml}
            </div>
        `;
    },
    
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    handleTermsAcceptance: function() {
        var accepted = $('#ura-accept-terms').is(':checked');
        var hasFiles = $('#ura-selected-files-list').children().length > 0;
        var hasErrors = $('.ura-file-invalid').length > 0;
        
        $('#ura-start-upload').prop('disabled', !accepted || !hasFiles || hasErrors);
    },
    
    startUpload: function() {
        var files = $('#ura-file-input')[0].files;
        var orderId = this.getOrderIdFromURL();
        
        if (!orderId) {
            this.showError('מספר הזמנה לא תקין');
            return;
        }
        
        this.showUploadProgressModal();
        this.uploadFiles(files, orderId);
    },
    
    uploadFiles: function(files, orderId) {
        var totalFiles = files.length;
        var uploadedCount = 0;
        var totalSize = 0;
        
        // Calculate total size
        for (var i = 0; i < files.length; i++) {
            totalSize += files[i].size;
        }
        
        var startTime = Date.now();
        
        var uploadNextFile = function(index) {
            if (index >= files.length) {
                // All files uploaded
                URA_FileUpload.showUploadSuccess();
                return;
            }
            
            var file = files[index];
            URA_FileUpload.uploadSingleFile(file, orderId, index, totalFiles, totalSize, startTime)
                .then(function() {
                    uploadedCount++;
                    var progress = (uploadedCount / totalFiles) * 100;
                    URA_FileUpload.updateOverallProgress(progress);
                    uploadNextFile(index + 1);
                })
                .catch(function(error) {
                    URA_FileUpload.showUploadError(error);
                });
        };
        
        uploadNextFile(0);
    },
    
    uploadSingleFile: function(file, orderId, fileIndex, totalFiles, totalSize, startTime) {
        return new Promise(function(resolve, reject) {
            // First, get upload URL
            $.ajax({
                url: ura_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ura_generate_upload_url',
                    order_id: orderId,
                    file_name: file.name,
                    file_size: file.size,
                    file_type: file.type,
                    nonce: ura_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        URA_FileUpload.uploadToS3(file, response.data.upload_url)
                            .then(function() {
                                // Confirm upload
                                return URA_FileUpload.confirmUpload(orderId, response.data.file_key, file);
                            })
                            .then(resolve)
                            .catch(reject);
                    } else {
                        reject(response.data);
                    }
                },
                error: function() {
                    reject('שגיאה ביצירת קישור העלאה');
                }
            });
        });
    },
    
    uploadToS3: function(file, uploadUrl) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var percentComplete = (e.loaded / e.total) * 100;
                    URA_FileUpload.updateFileProgress(file.name, percentComplete);
                }
            });
            
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    resolve();
                } else {
                    reject('שגיאה בהעלאה ל-S3');
                }
            });
            
            xhr.addEventListener('error', function() {
                reject('שגיאת רשת בהעלאה');
            });
            
            xhr.open('PUT', uploadUrl);
            xhr.setRequestHeader('Content-Type', file.type);
            xhr.send(file);
        });
    },
    
    updateFileProgress: function(fileName, progress) {
        var $fileProgress = $('#ura-file-progress-' + this.sanitizeFileName(fileName));
        if ($fileProgress.length) {
            $fileProgress.find('.ura-progress-fill').css('width', progress + '%');
            $fileProgress.find('.ura-progress-text').text(Math.round(progress) + '%');
        }
    },
    
    updateOverallProgress: function(progress) {
        $('#ura-overall-progress-bar').css('width', progress + '%');
        $('#ura-overall-percentage').text(Math.round(progress) + '%');
    },
    
    sanitizeFileName: function(fileName) {
        return fileName.replace(/[^a-zA-Z0-9]/g, '_');
    },
    
    getOrderIdFromURL: function() {
        var urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('order_id');
    },
    
    showUploadProgressModal: function() {
        $('#ura-upload-progress-modal').show();
    },
    
    showUploadSuccess: function() {
        $('#ura-upload-progress-modal').hide();
        $('#ura-upload-success-modal').show();
    },
    
    showUploadError: function(error) {
        this.showError('שגיאה בהעלאת קובץ: ' + error);
        $('#ura-upload-progress-modal').hide();
    }
};

// User Dashboard Module
var URA_UserDashboard = {
    init: function() {
        this.bindEvents();
    },
    
    bindEvents: function() {
        // Address editing
        $('#ura-edit-address').on('click', this.editAddress.bind(this));
        $('#ura-cancel-edit').on('click', this.cancelEditAddress.bind(this));
        $('#ura-address-update-form').on('submit', this.updateAddress.bind(this));
        
        // Order actions
        $('.ura-view-order').on('click', this.viewOrderDetails.bind(this));
        $('.ura-download-report').on('click', this.downloadReport.bind(this));
        $('.ura-upload-files').on('click', this.uploadFiles.bind(this));
    },
    
    editAddress: function() {
        $('#ura-edit-address-form').slideDown();
        $('#ura-edit-address').hide();
    },
    
    cancelEditAddress: function() {
        $('#ura-edit-address-form').slideUp();
        $('#ura-edit-address').show();
    },
    
    updateAddress: function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: ura_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=ura_update_user_address&nonce=' + ura_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    URA_UserDashboard.handleAddressUpdateSuccess(response.data);
                } else {
                    URA_Registration.showError(response.data);
                }
            },
            error: function() {
                URA_Registration.showError('שגיאה בעדכון הכתובת');
            }
        });
    },
    
    handleAddressUpdateSuccess: function(newAddress) {
        $('#ura-current-address').text(newAddress);
        this.cancelEditAddress();
        URA_Registration.showError('הכתובת עודכנה בהצלחה', 'success');
    },
    
    viewOrderDetails: function() {
        var orderId = $(this).data('order-id');
        
        $.ajax({
            url: ura_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ura_get_order_details',
                order_id: orderId,
                nonce: ura_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#ura-user-order-content').html(response.data);
                    $('#ura-user-order-modal').show();
                } else {
                    URA_Registration.showError(response.data);
                }
            }
        });
    },
    
    downloadReport: function() {
        var orderId = $(this).data('order-id');
        
        window.open(ura_ajax.ajax_url + '?action=ura_download_report&order_id=' + orderId + '&nonce=' + ura_ajax.nonce);
    },
    
    uploadFiles: function() {
        var orderId = $(this).data('order-id');
        window.location.href = '/file-upload?order_id=' + orderId;
    }
};

// Services Selection Module
var URA_Services = {
    init: function() {
        this.bindEvents();
        this.updateTotalPrice();
    },
    
    bindEvents: function() {
        $('input[name="ura_services[]"]').on('change', this.handleServiceSelection.bind(this));
        $('#ura-continue-to-address').on('click', this.proceedToAddress.bind(this));
    },
    
    handleServiceSelection: function() {
        this.updateTotalPrice();
    },
    
    updateTotalPrice: function() {
        var total = 0;
        
        $('input[name="ura_services[]"]:checked').each(function() {
            var price = parseFloat($(this).data('price')) || 0;
            total += price;
        });
        
        $('#ura-total-price').text(total.toFixed(2));
        
        // Show/hide total section
        if (total > 0) {
            $('.ura-services-total').show();
        } else {
            $('.ura-services-total').hide();
        }
    },
    
    proceedToAddress: function() {
        var selectedServices = [];
        
        $('input[name="ura_services[]"]:checked').each(function() {
            selectedServices.push($(this).val());
        });
        
        if (selectedServices.length === 0) {
            URA_Registration.showError('אנא בחר לפחות שירות אחד');
            return;
        }
        
        // Store selected services in session
        sessionStorage.setItem('ura_selected_services', JSON.stringify(selectedServices));
        
        // Proceed to next step
        $('#ura-step-services').removeClass('active');
        $('#ura-step-address').addClass('active');
    }
};

// Global utility functions
function ura_format_phone(phone) {
    if (!phone) return '';
    
    var cleaned = phone.replace(/\D/g, '');
    
    if (cleaned.length === 10) {
        return cleaned.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
    }
    
    return phone;
}

function ura_validate_email(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function ura_show_notification(message, type) {
    var cssClass = type === 'success' ? 'ura-success-message' : 
                   type === 'error' ? 'ura-error-message' : 'ura-warning-message';
    
    $('<div class="' + cssClass + '">' + message + '</div>')
        .hide()
        .prependTo('body')
        .slideDown()
        .delay(5000)
        .slideUp(function() {
            $(this).remove();
        });
}