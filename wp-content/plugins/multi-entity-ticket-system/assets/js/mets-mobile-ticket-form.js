/**
 * METS Mobile Ticket Form JavaScript
 *
 * @package MultiEntityTicketSystem
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * Mobile Ticket Form Controller
     */
    var METSMobileTicketForm = {
        
        currentStep: 1,
        totalSteps: 5,
        formData: {},
        isSubmitting: false,

        /**
         * Initialize mobile ticket form
         */
        init: function() {
            if (!this.isMobileTicketForm()) {
                return;
            }

            this.createMobileFormInterface();
            this.bindEvents();
            this.initializeValidation();
            this.setupAutosave();
            this.loadDraftData();
            this.showStep(1);
        },

        /**
         * Check if we're on a ticket form page
         */
        isMobileTicketForm: function() {
            return window.location.href.indexOf('page=mets-all-tickets') > -1 && 
                   (window.location.href.indexOf('action=add') > -1 || 
                    $('#ticket-form').length > 0);
        },

        /**
         * Create mobile-optimized form interface
         */
        createMobileFormInterface: function() {
            // Only proceed on mobile/tablet
            if ($(window).width() > 1024) {
                return;
            }

            var $existingForm = $('#ticket-form');
            if (!$existingForm.length) {
                return;
            }

            // Hide original form
            $existingForm.hide();
            $('.wrap').addClass('mets-mobile-ticket-form');

            // Create mobile form structure
            this.createMobileForm();
        },

        /**
         * Create mobile form HTML structure
         */
        createMobileForm: function() {
            var mobileFormHTML = `
                <div class="mets-form-header">
                    <h1>Create New Ticket</h1>
                    <div class="form-progress">Step <span id="current-step">1</span> of ${this.totalSteps}</div>
                    <div class="mets-progress-bar">
                        <div class="mets-progress-fill" id="progress-fill"></div>
                    </div>
                </div>

                <div class="mets-form-wizard" id="mobile-ticket-wizard">
                    <!-- Step 1: Template Selection -->
                    <div class="mets-form-step" data-step="1">
                        <h2><span class="step-icon">1</span>Choose a Template</h2>
                        <div class="mets-template-selector">
                            <div class="mets-template-grid">
                                <button type="button" class="mets-template-btn" data-template="bug">
                                    <span class="mets-template-icon">🐛</span>
                                    Bug Report
                                </button>
                                <button type="button" class="mets-template-btn" data-template="feature">
                                    <span class="mets-template-icon">✨</span>
                                    Feature Request
                                </button>
                                <button type="button" class="mets-template-btn" data-template="support">
                                    <span class="mets-template-icon">🎧</span>
                                    Support Request
                                </button>
                                <button type="button" class="mets-template-btn" data-template="general">
                                    <span class="mets-template-icon">📝</span>
                                    General Inquiry
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Entity Selection -->
                    <div class="mets-form-step" data-step="2">
                        <h2><span class="step-icon">2</span>Select Entity</h2>
                        <div class="mets-entity-grid" id="entity-selection">
                            <!-- Dynamic entity options will be loaded here -->
                        </div>
                    </div>

                    <!-- Step 3: Basic Information -->
                    <div class="mets-form-step" data-step="3">
                        <h2><span class="step-icon">3</span>Basic Information</h2>
                        <div class="mets-field-group">
                            <label class="mets-field-label required" for="mobile-subject">Subject</label>
                            <input type="text" id="mobile-subject" class="mets-field-input" placeholder="Brief description of the issue">
                            <span class="mets-field-error" id="subject-error"></span>
                        </div>
                        <div class="mets-field-group">
                            <label class="mets-field-label required" for="mobile-description">Description</label>
                            <textarea id="mobile-description" class="mets-field-textarea" 
                                      placeholder="Please provide detailed information about your request..."></textarea>
                            <span class="mets-field-error" id="description-error"></span>
                        </div>
                    </div>

                    <!-- Step 4: Priority & Customer Info -->
                    <div class="mets-form-step" data-step="4">
                        <h2><span class="step-icon">4</span>Priority & Contact</h2>
                        <div class="mets-field-group">
                            <label class="mets-field-label">Priority Level</label>
                            <div class="mets-priority-grid">
                                <div class="mets-priority-option mets-priority-low" data-priority="low">
                                    <span class="mets-priority-icon">🟢</span>
                                    <span class="mets-priority-label">Low</span>
                                </div>
                                <div class="mets-priority-option mets-priority-normal" data-priority="normal">
                                    <span class="mets-priority-icon">🔵</span>
                                    <span class="mets-priority-label">Normal</span>
                                </div>
                                <div class="mets-priority-option mets-priority-high" data-priority="high">
                                    <span class="mets-priority-icon">🟡</span>
                                    <span class="mets-priority-label">High</span>
                                </div>
                                <div class="mets-priority-option mets-priority-critical" data-priority="critical">
                                    <span class="mets-priority-icon">🔴</span>
                                    <span class="mets-priority-label">Critical</span>
                                </div>
                            </div>
                        </div>
                        <div class="mets-customer-fields">
                            <div class="mets-field-group">
                                <label class="mets-field-label required" for="mobile-customer-name">Your Name</label>
                                <input type="text" id="mobile-customer-name" class="mets-field-input" placeholder="Full name">
                            </div>
                            <div class="mets-field-group">
                                <label class="mets-field-label required" for="mobile-customer-email">Email Address</label>
                                <input type="email" id="mobile-customer-email" class="mets-field-input" placeholder="your@email.com">
                            </div>
                            <div class="mets-field-group">
                                <label class="mets-field-label" for="mobile-customer-phone">Phone Number</label>
                                <input type="tel" id="mobile-customer-phone" class="mets-field-input" placeholder="Optional">
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Attachments & Review -->
                    <div class="mets-form-step" data-step="5">
                        <h2><span class="step-icon">5</span>Attachments & Review</h2>
                        <div class="mets-field-group">
                            <label class="mets-field-label">Attachments (Optional)</label>
                            <div class="mets-file-upload" id="file-upload-area">
                                <div class="mets-file-upload-icon">📎</div>
                                <div class="mets-file-upload-text">Tap to select files</div>
                                <div class="mets-file-upload-subtext">Or drag and drop files here</div>
                                <input type="file" id="mobile-attachments" class="mets-file-input" multiple 
                                       accept="image/*,.pdf,.doc,.docx,.txt">
                            </div>
                            <div id="selected-files"></div>
                        </div>
                        <div class="mets-field-group">
                            <h3>Review Your Ticket</h3>
                            <div id="ticket-review"></div>
                        </div>
                    </div>
                </div>

                <div class="mets-form-navigation">
                    <button type="button" class="mets-nav-btn mets-nav-btn-back" id="prev-step" style="display: none;">
                        ← Back
                    </button>
                    <button type="button" class="mets-nav-btn mets-nav-btn-next" id="next-step">
                        Next →
                    </button>
                </div>
            `;

            $('.wrap').append(mobileFormHTML);
            this.loadEntities();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Navigation buttons
            $(document).on('click', '#next-step', function() {
                self.nextStep();
            });

            $(document).on('click', '#prev-step', function() {
                self.prevStep();
            });

            // Template selection
            $(document).on('click', '.mets-template-btn', function() {
                $('.mets-template-btn').removeClass('selected');
                $(this).addClass('selected');
                self.formData.template = $(this).data('template');
                self.applyTemplate($(this).data('template'));
            });

            // Entity selection
            $(document).on('click', '.mets-entity-option', function() {
                $('.mets-entity-option').removeClass('selected');
                $(this).addClass('selected');
                self.formData.entity_id = $(this).data('entity-id');
            });

            // Priority selection
            $(document).on('click', '.mets-priority-option', function() {
                $('.mets-priority-option').removeClass('selected');
                $(this).addClass('selected');
                self.formData.priority = $(this).data('priority');
            });

            // Form inputs
            $(document).on('input', '.mets-field-input, .mets-field-textarea', function() {
                var field = $(this).attr('id').replace('mobile-', '');
                self.formData[field] = $(this).val();
                self.validateField($(this));
                self.saveDraft();
            });

            // File upload
            $(document).on('click', '#file-upload-area', function() {
                $('#mobile-attachments').click();
            });

            $(document).on('change', '#mobile-attachments', function() {
                self.handleFileSelection(this.files);
            });

            // Drag and drop
            $(document).on('dragover dragenter', '#file-upload-area', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            $(document).on('dragleave', '#file-upload-area', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            $(document).on('drop', '#file-upload-area', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                self.handleFileSelection(e.originalEvent.dataTransfer.files);
            });

            // Auto-resize textarea
            $(document).on('input', '.mets-field-textarea', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.keyCode) {
                        case 39: // Right arrow - next step
                            e.preventDefault();
                            self.nextStep();
                            break;
                        case 37: // Left arrow - previous step
                            e.preventDefault();
                            self.prevStep();
                            break;
                        case 83: // S - save draft
                            e.preventDefault();
                            self.saveDraft();
                            break;
                    }
                }
            });
        },

        /**
         * Show specific step
         */
        showStep: function(step) {
            $('.mets-form-step').removeClass('active slide-back');
            
            var $targetStep = $('.mets-form-step[data-step="' + step + '"]');
            
            if (step < this.currentStep) {
                $targetStep.addClass('slide-back');
            }
            
            $targetStep.addClass('active');
            
            this.currentStep = step;
            this.updateProgress();
            this.updateNavigation();
            
            // Focus on first input in step
            setTimeout(function() {
                $targetStep.find('.mets-field-input, .mets-field-textarea').first().focus();
            }, 300);
        },

        /**
         * Update progress bar and indicator
         */
        updateProgress: function() {
            var progress = (this.currentStep / this.totalSteps) * 100;
            $('#progress-fill').css('width', progress + '%');
            $('#current-step').text(this.currentStep);
        },

        /**
         * Update navigation buttons
         */
        updateNavigation: function() {
            var $backBtn = $('#prev-step');
            var $nextBtn = $('#next-step');

            // Show/hide back button
            if (this.currentStep === 1) {
                $backBtn.hide();
            } else {
                $backBtn.show();
            }

            // Update next button
            if (this.currentStep === this.totalSteps) {
                $nextBtn.text('Submit Ticket').removeClass('mets-nav-btn-next').addClass('mets-nav-btn-submit');
            } else {
                $nextBtn.text('Next →').removeClass('mets-nav-btn-submit').addClass('mets-nav-btn-next');
            }
        },

        /**
         * Move to next step
         */
        nextStep: function() {
            if (this.isSubmitting) {
                return;
            }

            if (!this.validateCurrentStep()) {
                return;
            }

            if (this.currentStep === this.totalSteps) {
                this.submitTicket();
            } else {
                this.showStep(this.currentStep + 1);
            }
        },

        /**
         * Move to previous step
         */
        prevStep: function() {
            if (this.currentStep > 1) {
                this.showStep(this.currentStep - 1);
            }
        },

        /**
         * Validate current step
         */
        validateCurrentStep: function() {
            var isValid = true;

            switch(this.currentStep) {
                case 1: // Template selection
                    if (!this.formData.template) {
                        this.showMessage('Please select a template to continue.', 'error');
                        isValid = false;
                    }
                    break;

                case 2: // Entity selection
                    if (!this.formData.entity_id) {
                        this.showMessage('Please select an entity to continue.', 'error');
                        isValid = false;
                    }
                    break;

                case 3: // Basic information
                    if (!$('#mobile-subject').val().trim()) {
                        this.showFieldError('#mobile-subject', 'Subject is required');
                        isValid = false;
                    }
                    if (!$('#mobile-description').val().trim()) {
                        this.showFieldError('#mobile-description', 'Description is required');
                        isValid = false;
                    }
                    break;

                case 4: // Priority and customer info
                    if (!this.formData.priority) {
                        this.showMessage('Please select a priority level.', 'error');
                        isValid = false;
                    }
                    if (!$('#mobile-customer-name').val().trim()) {
                        this.showFieldError('#mobile-customer-name', 'Name is required');
                        isValid = false;
                    }
                    if (!$('#mobile-customer-email').val().trim()) {
                        this.showFieldError('#mobile-customer-email', 'Email is required');
                        isValid = false;
                    }
                    break;

                case 5: // Review
                    this.updateReview();
                    break;
            }

            return isValid;
        },

        /**
         * Validate individual field
         */
        validateField: function($field) {
            var fieldId = $field.attr('id');
            var value = $field.val().trim();
            var isValid = true;

            // Clear previous error
            this.clearFieldError($field);

            // Field-specific validation
            switch(fieldId) {
                case 'mobile-customer-email':
                    if (value && !this.isValidEmail(value)) {
                        this.showFieldError($field, 'Please enter a valid email address');
                        isValid = false;
                    }
                    break;
                
                case 'mobile-subject':
                    if (value.length > 0 && value.length < 3) {
                        this.showFieldError($field, 'Subject must be at least 3 characters');
                        isValid = false;
                    }
                    break;
            }

            if (isValid) {
                $field.removeClass('error');
            } else {
                $field.addClass('error');
            }

            return isValid;
        },

        /**
         * Show field error
         */
        showFieldError: function(field, message) {
            var $field = $(field);
            var errorId = $field.attr('id').replace('mobile-', '') + '-error';
            $('#' + errorId).text(message);
            $field.addClass('error');
        },

        /**
         * Clear field error
         */
        clearFieldError: function($field) {
            var errorId = $field.attr('id').replace('mobile-', '') + '-error';
            $('#' + errorId).text('');
            $field.removeClass('error');
        },

        /**
         * Load entities for selection
         */
        loadEntities: function() {
            var self = this;
            
            $.ajax({
                url: mets_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mets_get_entities',
                    nonce: mets_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.renderEntities(response.data);
                    }
                },
                error: function() {
                    $('#entity-selection').html('<p>Unable to load entities. Please refresh the page.</p>');
                }
            });
        },

        /**
         * Render entities in grid
         */
        renderEntities: function(entities) {
            var html = '';
            
            entities.forEach(function(entity) {
                var icon = entity.parent_id ? '📂' : '🏢';
                var prefix = entity.parent_id ? '— ' : '';
                
                html += `
                    <div class="mets-entity-option" data-entity-id="${entity.id}">
                        <div class="mets-entity-icon">${icon}</div>
                        <div class="mets-entity-info">
                            <h3>${prefix}${entity.name}</h3>
                            <p>${entity.description || 'No description available'}</p>
                        </div>
                    </div>
                `;
            });
            
            $('#entity-selection').html(html);
        },

        /**
         * Apply template data
         */
        applyTemplate: function(template) {
            var templates = {
                bug: {
                    subject: 'Bug Report: ',
                    description: 'What happened?\n\nWhat did you expect to happen?\n\nSteps to reproduce:\n1. \n2. \n3. \n\nAdditional information:'
                },
                feature: {
                    subject: 'Feature Request: ',
                    description: 'Please describe the feature you would like to see:\n\nWhy would this be useful?\n\nAny specific requirements?'
                },
                support: {
                    subject: 'Support Request: ',
                    description: 'Please describe the issue you are experiencing:\n\nWhat have you tried so far?\n\nWhen did this issue start?'
                },
                general: {
                    subject: '',
                    description: 'Please provide details about your inquiry:'
                }
            };

            if (templates[template]) {
                // Don't overwrite if user has already entered data
                if (!$('#mobile-subject').val()) {
                    $('#mobile-subject').val(templates[template].subject);
                }
                if (!$('#mobile-description').val()) {
                    $('#mobile-description').val(templates[template].description);
                }
            }
        },

        /**
         * Handle file selection
         */
        handleFileSelection: function(files) {
            var self = this;
            var $selectedFiles = $('#selected-files');
            var html = '';

            if (files.length === 0) {
                return;
            }

            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                var fileSize = this.formatFileSize(file.size);
                var fileIcon = this.getFileIcon(file.type);

                html += `
                    <div class="selected-file" data-index="${i}">
                        <span class="file-icon">${fileIcon}</span>
                        <span class="file-name">${file.name}</span>
                        <span class="file-size">${fileSize}</span>
                        <button type="button" class="remove-file" data-index="${i}">×</button>
                    </div>
                `;
            }

            $selectedFiles.html(html);
            this.formData.files = files;

            // Add remove file functionality
            $(document).on('click', '.remove-file', function() {
                var index = $(this).data('index');
                $(this).closest('.selected-file').remove();
                // Note: In a real implementation, you'd update the FileList
            });
        },

        /**
         * Update review section
         */
        updateReview: function() {
            var reviewHTML = `
                <div class="ticket-review-item">
                    <strong>Entity:</strong> ${this.getSelectedEntityName()}
                </div>
                <div class="ticket-review-item">
                    <strong>Subject:</strong> ${$('#mobile-subject').val()}
                </div>
                <div class="ticket-review-item">
                    <strong>Priority:</strong> ${this.getSelectedPriorityName()}
                </div>
                <div class="ticket-review-item">
                    <strong>Customer:</strong> ${$('#mobile-customer-name').val()} (${$('#mobile-customer-email').val()})
                </div>
                <div class="ticket-review-item">
                    <strong>Description:</strong><br>
                    <div style="background: #f5f5f5; padding: 10px; border-radius: 4px; margin-top: 5px;">
                        ${$('#mobile-description').val().replace(/\n/g, '<br>')}
                    </div>
                </div>
            `;

            $('#ticket-review').html(reviewHTML);
        },

        /**
         * Submit ticket
         */
        submitTicket: function() {
            if (this.isSubmitting) {
                return;
            }

            this.isSubmitting = true;
            var $submitBtn = $('#next-step');
            var originalText = $submitBtn.html();
            
            $submitBtn.html('<span class="mets-loading-spinner"></span> Submitting...').prop('disabled', true);
            $('.mets-form-wizard').addClass('mets-form-loading');

            // Prepare form data
            var formData = new FormData();
            formData.append('action', 'create');
            formData.append('page', 'mets-add-ticket');
            formData.append('ticket_nonce', this.getNonce());
            formData.append('ticket_entity', this.formData.entity_id);
            formData.append('ticket_subject', $('#mobile-subject').val());
            formData.append('ticket_description', $('#mobile-description').val());
            formData.append('ticket_priority', this.formData.priority || 'normal');
            formData.append('customer_name', $('#mobile-customer-name').val());
            formData.append('customer_email', $('#mobile-customer-email').val());
            formData.append('customer_phone', $('#mobile-customer-phone').val());

            // Add files if any
            if (this.formData.files) {
                for (var i = 0; i < this.formData.files.length; i++) {
                    formData.append('attachments[]', this.formData.files[i]);
                }
            }

            var self = this;
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    self.handleSubmitSuccess(response);
                },
                error: function(xhr, status, error) {
                    self.handleSubmitError(error);
                },
                complete: function() {
                    self.isSubmitting = false;
                    $submitBtn.html(originalText).prop('disabled', false);
                    $('.mets-form-wizard').removeClass('mets-form-loading');
                }
            });
        },

        /**
         * Handle successful submission
         */
        handleSubmitSuccess: function(response) {
            this.clearDraft();
            this.showMessage('Ticket created successfully! Redirecting...', 'success');
            
            setTimeout(function() {
                // Check if we're on the all-tickets page and stay there if so
                var currentUrl = window.location.href;
                if (currentUrl.indexOf('page=mets-all-tickets') > -1) {
                    window.location.href = 'admin.php?page=mets-all-tickets';
                } else {
                    window.location.href = 'admin.php?page=mets-all-tickets';
                }
            }, 2000);
        },

        /**
         * Handle submission error
         */
        handleSubmitError: function(error) {
            this.showMessage('Failed to create ticket. Please try again.', 'error');
            console.error('Ticket submission error:', error);
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            var $message = $(`<div class="mets-form-message ${type}">${message}</div>`);
            $('.mets-form-wizard').prepend($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Auto-save functionality
         */
        setupAutosave: function() {
            var self = this;
            setInterval(function() {
                self.saveDraft();
            }, 30000); // Save every 30 seconds
        },

        /**
         * Save draft to localStorage
         */
        saveDraft: function() {
            var draftData = {
                step: this.currentStep,
                formData: this.formData,
                fields: {
                    subject: $('#mobile-subject').val(),
                    description: $('#mobile-description').val(),
                    customer_name: $('#mobile-customer-name').val(),
                    customer_email: $('#mobile-customer-email').val(),
                    customer_phone: $('#mobile-customer-phone').val()
                },
                timestamp: Date.now()
            };

            localStorage.setItem('mets_ticket_draft', JSON.stringify(draftData));
        },

        /**
         * Load draft from localStorage
         */
        loadDraftData: function() {
            var draft = localStorage.getItem('mets_ticket_draft');
            if (!draft) {
                return;
            }

            try {
                var draftData = JSON.parse(draft);
                
                // Check if draft is less than 24 hours old
                if (Date.now() - draftData.timestamp > 24 * 60 * 60 * 1000) {
                    this.clearDraft();
                    return;
                }

                // Restore form data
                this.formData = draftData.formData || {};
                
                // Restore field values
                if (draftData.fields) {
                    $('#mobile-subject').val(draftData.fields.subject || '');
                    $('#mobile-description').val(draftData.fields.description || '');
                    $('#mobile-customer-name').val(draftData.fields.customer_name || '');
                    $('#mobile-customer-email').val(draftData.fields.customer_email || '');
                    $('#mobile-customer-phone').val(draftData.fields.customer_phone || '');
                }

                // Show draft restored message
                this.showMessage('Draft restored from previous session', 'success');
                
            } catch (e) {
                console.error('Failed to load draft:', e);
                this.clearDraft();
            }
        },

        /**
         * Clear draft from localStorage
         */
        clearDraft: function() {
            localStorage.removeItem('mets_ticket_draft');
        },

        /**
         * Initialize form validation
         */
        initializeValidation: function() {
            // Real-time validation as user types
            $(document).on('blur', '.mets-field-input, .mets-field-textarea', function() {
                METSMobileTicketForm.validateField($(this));
            });
        },

        /**
         * Utility functions
         */
        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        getFileIcon: function(mimeType) {
            if (mimeType.startsWith('image/')) return '🖼️';
            if (mimeType.includes('pdf')) return '📄';
            if (mimeType.includes('word')) return '📝';
            if (mimeType.includes('text')) return '📄';
            return '📎';
        },

        getSelectedEntityName: function() {
            var $selected = $('.mets-entity-option.selected');
            return $selected.length ? $selected.find('h3').text() : 'Not selected';
        },

        getSelectedPriorityName: function() {
            var $selected = $('.mets-priority-option.selected');
            return $selected.length ? $selected.find('.mets-priority-label').text() : 'Not selected';
        },

        getNonce: function() {
            // Extract nonce from original form or create one
            var nonce = $('input[name="ticket_nonce"]').val();
            return nonce || mets_admin_ajax.nonce;
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        METSMobileTicketForm.init();
    });

    // Export for global access
    window.METSMobileTicketForm = METSMobileTicketForm;

})(jQuery);