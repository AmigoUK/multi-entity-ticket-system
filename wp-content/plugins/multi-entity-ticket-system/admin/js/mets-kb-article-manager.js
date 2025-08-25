/**
 * Enhanced KB Article Manager JavaScript
 *
 * Handles auto-save, real-time validation, and AJAX operations
 * for the KB article editing interface.
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/js
 * @since      2.0.0
 */

(function($) {
    'use strict';

    /**
     * KB Article Manager
     */
    var KBArticleManager = {
        // Configuration
        config: {
            autosaveInterval: 30000, // 30 seconds
            debounceDelay: 1000,     // 1 second
            maxTitleLength: 255,
            maxExcerptLength: 500
        },

        // State
        state: {
            articleId: 0,
            isDirty: false,
            isAutoSaving: false,
            lastSaved: null,
            formData: {},
            validationErrors: {}
        },

        // DOM elements
        elements: {},

        // Timers
        timers: {
            autosave: null,
            debounce: null,
            validation: null
        },

        /**
         * Initialize the manager
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.initAutosave();
            this.initValidation();
            this.initFileUpload();
            this.initEntityCategories();
            this.initTags();
            this.setupBeforeUnload();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.elements = {
                form: $('#mets-kb-article-form'),
                status: $('#mets-kb-status'),
                title: $('#title'),
                content: $('#article_content'),
                entity: $('#article_entity'),
                categories: $('#categories-list'),
                categoriesLoading: $('#categories-loading'),
                tags: $('#article_tags'),
                addTagButtons: $('.add-tag-button'),
                attachments: $('#article_attachments'),
                currentAttachments: $('#current-attachments'),
                uploadProgress: $('#upload-progress'),
                saveButton: $('#publish')
            };

            // Get article ID from form
            this.state.articleId = parseInt(this.elements.form.data('article-id') || 0);
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Form field changes
            this.elements.form.on('input change', '[data-autosave="true"]', function() {
                self.markDirty();
                self.scheduleAutosave();
            });

            // TinyMCE content changes
            if (typeof tinymce !== 'undefined') {
                $(document).on('tinymce-editor-init', function(event, editor) {
                    if (editor.id === 'article_content') {
                        editor.on('input change keyup', function() {
                            self.markDirty();
                            self.scheduleAutosave();
                        });
                    }
                });
            }

            // Entity change - reload categories
            this.elements.entity.on('change', function() {
                self.loadCategories();
            });

            // Tag management
            this.elements.addTagButtons.on('click', function(e) {
                e.preventDefault();
                self.addTag($(this).data('tag'));
            });

            // File upload
            this.elements.attachments.on('change', function() {
                self.handleFileUpload();
            });

            // Delete attachment buttons
            $(document).on('click', '.delete-attachment', function(e) {
                e.preventDefault();
                self.deleteAttachment($(this).data('attachment-id'));
            });

            // Form submission
            this.elements.form.on('submit', function(e) {
                if (!self.validateForm()) {
                    e.preventDefault();
                    return false;
                }
                self.state.isDirty = false; // Prevent beforeunload warning
            });

            // Title validation
            this.elements.title.on('blur', function() {
                self.validateTitle();
            });

            // Real-time character counters
            this.elements.title.on('input', function() {
                self.updateCharacterCount('title', self.config.maxTitleLength);
            });

            $('#meta_description').on('input', function() {
                self.updateCharacterCount('meta_description', 160);
            });
        },

        /**
         * Initialize autosave functionality
         */
        initAutosave: function() {
            var self = this;

            // Set up autosave interval
            this.timers.autosave = setInterval(function() {
                if (self.state.isDirty && !self.state.isAutoSaving) {
                    self.performAutosave();
                }
            }, this.config.autosaveInterval);

            // Use WordPress Heartbeat for better autosave coordination
            if (typeof wp !== 'undefined' && wp.heartbeat) {
                $(document).on('heartbeat-send', function(e, data) {
                    if (self.state.isDirty && !self.state.isAutoSaving) {
                        var formData = self.getFormData();
                        if (formData.title && formData.title.trim()) {
                            data.mets_kb_autosave = formData;
                        }
                    }
                });

                $(document).on('heartbeat-tick', function(e, data) {
                    if (data.mets_kb_autosave_response) {
                        self.handleAutosaveResponse(data.mets_kb_autosave_response);
                    }
                });
            }
        },

        /**
         * Schedule autosave with debouncing
         */
        scheduleAutosave: function() {
            var self = this;

            clearTimeout(this.timers.debounce);
            this.timers.debounce = setTimeout(function() {
                if (self.state.isDirty && !self.state.isAutoSaving) {
                    self.performAutosave();
                }
            }, this.config.debounceDelay);
        },

        /**
         * Perform autosave operation
         */
        performAutosave: function() {
            var self = this;
            var formData = this.getFormData();

            // Don't autosave if required fields are empty
            if (!formData.title || !formData.title.trim()) {
                return;
            }

            this.state.isAutoSaving = true;
            this.updateStatus('saving');

            formData.action = 'mets_kb_auto_save';
            formData.nonce = metsKbArticle.nonce;
            formData.article_id = this.state.articleId;

            $.ajax({
                url: metsKbArticle.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    self.handleAutosaveResponse(response);
                },
                error: function(xhr, status, error) {
                    self.handleAutosaveError(error);
                },
                complete: function() {
                    self.state.isAutoSaving = false;
                }
            });
        },

        /**
         * Handle autosave response
         */
        handleAutosaveResponse: function(response) {
            if (response.success) {
                this.state.isDirty = false;
                this.state.lastSaved = new Date();
                
                // Update article ID if this was a new article
                if (response.data.article_id && !this.state.articleId) {
                    this.state.articleId = response.data.article_id;
                    this.elements.form.data('article-id', this.state.articleId);
                    $('input[name="article_id"]').val(this.state.articleId);
                }

                this.updateStatus('saved');
                this.showNotification(response.data.message, 'success');
            } else {
                this.handleAutosaveError(response.data.message);
            }
        },

        /**
         * Handle autosave error
         */
        handleAutosaveError: function(message) {
            this.state.isAutoSaving = false;
            this.updateStatus('error');
            this.showNotification(message || metsKbArticle.i18n.saveError, 'error');
        },

        /**
         * Get current form data
         */
        getFormData: function() {
            var data = {};

            // Get regular form fields
            this.elements.form.find('[data-autosave="true"]').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value;

                if ($field.is(':checkbox')) {
                    value = $field.is(':checked') ? $field.val() : '';
                } else if ($field.is(':radio')) {
                    if ($field.is(':checked')) {
                        value = $field.val();
                    }
                } else if (name === 'categories[]') {
                    if (!data.categories) data.categories = [];
                    if ($field.is(':checked')) {
                        data.categories.push($field.val());
                    }
                } else {
                    value = $field.val();
                }

                if (value !== undefined && name !== 'categories[]') {
                    data[name] = value;
                }
            });

            // Get TinyMCE content
            if (typeof tinymce !== 'undefined') {
                var editor = tinymce.get('article_content');
                if (editor && !editor.isHidden()) {
                    data.content = editor.getContent();
                } else {
                    data.content = $('#article_content').val();
                }
            } else {
                data.content = $('#article_content').val();
            }

            return data;
        },

        /**
         * Update status indicator
         */
        updateStatus: function(status) {
            var $status = this.elements.status;
            var $text = $status.find('.status-text');
            var $spinner = $status.find('.spinner');

            $status.removeClass('saving saved error');
            $spinner.removeClass('is-active');

            switch (status) {
                case 'saving':
                    $status.addClass('saving');
                    $spinner.addClass('is-active');
                    $text.text(metsKbArticle.i18n.saving);
                    break;
                case 'saved':
                    $status.addClass('saved');
                    $text.text(metsKbArticle.i18n.saved);
                    setTimeout(function() {
                        $status.removeClass('saved');
                        $text.text('Ready');
                    }, 3000);
                    break;
                case 'error':
                    $status.addClass('error');
                    $text.text(metsKbArticle.i18n.saveError);
                    break;
            }
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            // Use WordPress admin notices if available
            if (typeof wp !== 'undefined' && wp.ajax) {
                // Show as WordPress admin notice
                var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
                var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
                $('.wrap h1').after($notice);
                
                setTimeout(function() {
                    $notice.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            } else {
                // Fallback notification
                console.log(type + ': ' + message);
            }
        },

        /**
         * Mark form as dirty
         */
        markDirty: function() {
            this.state.isDirty = true;
        },

        /**
         * Initialize form validation
         */
        initValidation: function() {
            var self = this;

            // Real-time validation
            this.elements.title.on('input', function() {
                clearTimeout(self.timers.validation);
                self.timers.validation = setTimeout(function() {
                    self.validateField('title');
                }, 500);
            });

            // Content validation
            if (typeof tinymce !== 'undefined') {
                $(document).on('tinymce-editor-init', function(event, editor) {
                    if (editor.id === 'article_content') {
                        editor.on('input change', function() {
                            clearTimeout(self.timers.validation);
                            self.timers.validation = setTimeout(function() {
                                self.validateField('content');
                            }, 500);
                        });
                    }
                });
            }
        },

        /**
         * Validate individual field
         */
        validateField: function(fieldName) {
            var isValid = true;
            var message = '';

            switch (fieldName) {
                case 'title':
                    var title = this.elements.title.val().trim();
                    if (!title) {
                        isValid = false;
                        message = metsKbArticle.i18n.titleRequired;
                    } else if (title.length > this.config.maxTitleLength) {
                        isValid = false;
                        message = 'Title too long (' + title.length + '/' + this.config.maxTitleLength + ')';
                    }
                    this.showFieldValidation('title', isValid, message);
                    break;

                case 'content':
                    var content = '';
                    if (typeof tinymce !== 'undefined') {
                        var editor = tinymce.get('article_content');
                        if (editor && !editor.isHidden()) {
                            content = editor.getContent({format: 'text'});
                        }
                    }
                    if (!content) {
                        content = $('#article_content').val();
                    }
                    
                    if (!content || !content.trim()) {
                        isValid = false;
                        message = metsKbArticle.i18n.contentRequired;
                    }
                    this.showFieldValidation('content', isValid, message);
                    break;
            }

            this.state.validationErrors[fieldName] = !isValid;
            return isValid;
        },

        /**
         * Show field validation message
         */
        showFieldValidation: function(fieldName, isValid, message) {
            var $validation = $('#' + fieldName + '-validation');
            
            $validation.removeClass('error success');
            
            if (!isValid && message) {
                $validation.addClass('error').text(message).show();
            } else {
                $validation.hide();
            }
        },

        /**
         * Validate entire form
         */
        validateForm: function() {
            var isValid = true;

            // Validate required fields
            isValid = this.validateField('title') && isValid;
            isValid = this.validateField('content') && isValid;

            return isValid;
        },

        /**
         * Validate title uniqueness
         */
        validateTitle: function() {
            var self = this;
            var title = this.elements.title.val().trim();

            if (!title) return;

            $.ajax({
                url: metsKbArticle.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mets_kb_validate_title',
                    nonce: metsKbArticle.nonce,
                    title: title,
                    entity_id: this.elements.entity.val(),
                    article_id: this.state.articleId
                },
                success: function(response) {
                    if (!response.success) {
                        self.showFieldValidation('title', false, response.data.message);
                    }
                }
            });
        },

        /**
         * Update character count display
         */
        updateCharacterCount: function(fieldName, maxLength) {
            var $field = $('#' + fieldName);
            var value = $field.val();
            var length = value.length;
            var $counter = $field.siblings('.character-count');

            if (!$counter.length) {
                $counter = $('<div class="character-count"></div>');
                $field.after($counter);
            }

            $counter.text(length + '/' + maxLength);
            $counter.toggleClass('over-limit', length > maxLength);
        },

        /**
         * Initialize entity categories functionality
         */
        initEntityCategories: function() {
            // Categories are loaded when entity changes (handled in bindEvents)
        },

        /**
         * Load categories for selected entity
         */
        loadCategories: function() {
            var self = this;
            var entityId = this.elements.entity.val();

            this.elements.categoriesLoading.show();
            this.elements.categories.hide();

            $.ajax({
                url: metsKbArticle.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mets_kb_get_categories',
                    nonce: metsKbArticle.nonce,
                    entity_id: entityId,
                    article_id: this.state.articleId
                },
                success: function(response) {
                    if (response.success) {
                        self.renderCategories(response.data.categories);
                    } else {
                        self.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.showNotification('Failed to load categories', 'error');
                },
                complete: function() {
                    self.elements.categoriesLoading.hide();
                    self.elements.categories.show();
                }
            });
        },

        /**
         * Render categories checkboxes
         */
        renderCategories: function(categories) {
            var $container = this.elements.categories;
            $container.empty();

            if (!categories || categories.length === 0) {
                $container.append('<p class="no-categories">No categories available for this entity.</p>');
                return;
            }

            var template = wp.template('category-checkbox');
            
            $.each(categories, function(index, category) {
                $container.append(template(category));
            });

            // Re-bind autosave events for new checkboxes
            $container.find('input[type="checkbox"]').on('change', function() {
                KBArticleManager.markDirty();
                KBArticleManager.scheduleAutosave();
            });
        },

        /**
         * Initialize tags functionality
         */
        initTags: function() {
            // Add tag button functionality is handled in bindEvents
        },

        /**
         * Add tag to the tags field
         */
        addTag: function(tagName) {
            var currentTags = this.elements.tags.val();
            var tagsArray = currentTags ? currentTags.split(',').map(function(tag) {
                return tag.trim();
            }) : [];

            // Don't add if tag already exists
            if (tagsArray.indexOf(tagName) === -1) {
                tagsArray.push(tagName);
                this.elements.tags.val(tagsArray.join(', '));
                this.markDirty();
                this.scheduleAutosave();
            }
        },

        /**
         * Initialize file upload functionality
         */
        initFileUpload: function() {
            // File upload handling is done via bindEvents
        },

        /**
         * Handle file upload
         */
        handleFileUpload: function() {
            var self = this;
            var files = this.elements.attachments[0].files;

            if (!files.length) return;

            // Check if article is saved
            if (!this.state.articleId) {
                this.showNotification('Please save the article first before uploading files.', 'error');
                return;
            }

            $.each(files, function(index, file) {
                self.uploadFile(file);
            });

            // Clear the input
            this.elements.attachments.val('');
        },

        /**
         * Upload individual file
         */
        uploadFile: function(file) {
            var self = this;
            var formData = new FormData();
            
            formData.append('action', 'mets_kb_upload_file');
            formData.append('nonce', metsKbArticle.nonce);
            formData.append('article_id', this.state.articleId);
            formData.append('file', file);

            this.showUploadProgress(true);

            $.ajax({
                url: metsKbArticle.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percent = Math.round((e.loaded / e.total) * 100);
                            self.updateUploadProgress(percent);
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        self.addAttachmentToList(response.data.attachment);
                        self.showNotification(response.data.message, 'success');
                    } else {
                        self.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.showNotification('File upload failed', 'error');
                },
                complete: function() {
                    self.showUploadProgress(false);
                }
            });
        },

        /**
         * Show/hide upload progress
         */
        showUploadProgress: function(show) {
            if (show) {
                this.elements.uploadProgress.show();
            } else {
                this.elements.uploadProgress.hide();
            }
        },

        /**
         * Update upload progress
         */
        updateUploadProgress: function(percent) {
            this.elements.uploadProgress.find('.progress-fill').css('width', percent + '%');
            this.elements.uploadProgress.find('.progress-text').text(percent + '%');
        },

        /**
         * Add attachment to the list
         */
        addAttachmentToList: function(attachment) {
            var template = wp.template('attachment-item');
            var $item = $(template(attachment));
            
            this.elements.currentAttachments.find('.no-attachments').remove();
            this.elements.currentAttachments.append($item);
        },

        /**
         * Delete attachment
         */
        deleteAttachment: function(attachmentId) {
            var self = this;

            if (!confirm('Are you sure you want to delete this attachment?')) {
                return;
            }

            $.ajax({
                url: metsKbArticle.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mets_kb_delete_attachment',
                    nonce: metsKbArticle.nonce,
                    attachment_id: attachmentId
                },
                success: function(response) {
                    if (response.success) {
                        $('[data-attachment-id="' + attachmentId + '"]').remove();
                        self.showNotification(response.data.message, 'success');
                        
                        // Show "no attachments" message if list is empty
                        if (self.elements.currentAttachments.children().length === 0) {
                            self.elements.currentAttachments.append('<p class="no-attachments">No attachments</p>');
                        }
                    } else {
                        self.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.showNotification('Failed to delete attachment', 'error');
                }
            });
        },

        /**
         * Setup beforeunload warning
         */
        setupBeforeUnload: function() {
            var self = this;

            $(window).on('beforeunload', function(e) {
                if (self.state.isDirty) {
                    var message = metsKbArticle.i18n.confirmLeave;
                    e.returnValue = message;
                    return message;
                }
            });
        },

        /**
         * Cleanup on page unload
         */
        destroy: function() {
            // Clear timers
            if (this.timers.autosave) {
                clearInterval(this.timers.autosave);
            }
            if (this.timers.debounce) {
                clearTimeout(this.timers.debounce);
            }
            if (this.timers.validation) {
                clearTimeout(this.timers.validation);
            }

            // Remove event handlers
            $(window).off('beforeunload');
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Only initialize on KB article pages
        if ($('#mets-kb-article-form').length) {
            KBArticleManager.init();
        }
    });

    // Expose to global scope for debugging
    window.METSKBArticleManager = KBArticleManager;

})(jQuery);