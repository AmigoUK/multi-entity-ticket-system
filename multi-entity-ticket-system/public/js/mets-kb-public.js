/**
 * Multi-Entity Ticket System - Knowledgebase Public JavaScript
 *
 * @package MultiEntityTicketSystem
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * Knowledgebase functionality
     */
    var METS_KB = {
        searchTimeout: null,
        searchCache: {},

        /**
         * Initialize the knowledgebase
         */
        init: function() {
            this.bindEvents();
            this.initSearch();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Search functionality
            $(document).on('input', '#mets-kb-search-input', this.handleSearchInput.bind(this));
            $(document).on('submit', '#mets-kb-search', this.handleSearchSubmit.bind(this));
            $(document).on('click', '.mets-kb-suggestion-item', this.handleSuggestionClick.bind(this));
            $(document).on('focus', '#mets-kb-search-input', this.showSuggestions.bind(this));
            $(document).on('blur', '#mets-kb-search-input', this.hideSuggestions.bind(this));

            // Article feedback
            $(document).on('click', '.mets-kb-feedback-btn', this.handleFeedback.bind(this));

            // Close suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.mets-kb-search-form').length) {
                    $('#mets-kb-search-suggestions').hide();
                }
            });

            // Keyboard navigation for suggestions
            $(document).on('keydown', '#mets-kb-search-input', this.handleKeyboardNav.bind(this));
        },

        /**
         * Initialize search functionality
         */
        initSearch: function() {
            // Auto-focus search input if present
            var $searchInput = $('#mets-kb-search-input');
            if ($searchInput.length && !this.isMobile()) {
                $searchInput.focus();
            }

            // Restore search query from URL
            var urlParams = new URLSearchParams(window.location.search);
            var query = urlParams.get('q');
            if (query && $searchInput.length) {
                $searchInput.val(query);
            }
        },

        /**
         * Handle search input with debouncing
         */
        handleSearchInput: function(e) {
            var query = $(e.target).val().trim();
            var self = this;

            // Clear previous timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            // Hide suggestions if query is too short
            if (query.length < 2) {
                this.hideSuggestions();
                return;
            }

            // Debounce search requests
            this.searchTimeout = setTimeout(function() {
                self.performSearch(query, true);
            }, 300);
        },

        /**
         * Handle search form submission
         */
        handleSearchSubmit: function(e) {
            var query = $('#mets-kb-search-input').val().trim();
            
            if (query.length < 2) {
                e.preventDefault();
                this.showMessage(mets_kb_ajax.strings.error, 'error');
                return false;
            }

            // Let the form submit naturally for full search results
            return true;
        },

        /**
         * Handle suggestion click
         */
        handleSuggestionClick: function(e) {
            e.preventDefault();
            var $suggestion = $(e.currentTarget);
            var url = $suggestion.data('url');
            
            if (url) {
                window.location.href = url;
            }
        },

        /**
         * Handle keyboard navigation in suggestions
         */
        handleKeyboardNav: function(e) {
            var $suggestions = $('#mets-kb-search-suggestions');
            var $items = $suggestions.find('.mets-kb-suggestion-item');
            var $active = $items.filter('.active');

            if (!$suggestions.is(':visible') || $items.length === 0) {
                return;
            }

            switch (e.keyCode) {
                case 38: // Up arrow
                    e.preventDefault();
                    if ($active.length === 0) {
                        $items.last().addClass('active');
                    } else {
                        $active.removeClass('active').prev().addClass('active');
                        if ($items.filter('.active').length === 0) {
                            $items.last().addClass('active');
                        }
                    }
                    break;

                case 40: // Down arrow
                    e.preventDefault();
                    if ($active.length === 0) {
                        $items.first().addClass('active');
                    } else {
                        $active.removeClass('active').next().addClass('active');
                        if ($items.filter('.active').length === 0) {
                            $items.first().addClass('active');
                        }
                    }
                    break;

                case 13: // Enter
                    if ($active.length > 0) {
                        e.preventDefault();
                        $active.click();
                    }
                    break;

                case 27: // Escape
                    this.hideSuggestions();
                    break;
            }
        },

        /**
         * Perform search request
         */
        performSearch: function(query, showSuggestions) {
            var self = this;
            showSuggestions = showSuggestions !== false;

            // Check cache first
            if (this.searchCache[query]) {
                if (showSuggestions) {
                    this.displaySuggestions(this.searchCache[query], query);
                }
                return;
            }

            // Get entity filter if available
            var entityId = $('input[name="entity"]').val() || null;

            $.ajax({
                url: mets_kb_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mets_kb_search',
                    nonce: mets_kb_ajax.nonce,
                    query: query,
                    entity_id: entityId,
                    limit: 5
                },
                beforeSend: function() {
                    if (showSuggestions) {
                        self.showLoadingSuggestions();
                    }
                },
                success: function(response) {
                    if (response.success) {
                        // Cache the results
                        self.searchCache[query] = response.data;
                        
                        if (showSuggestions) {
                            self.displaySuggestions(response.data, query);
                        }
                    } else {
                        if (showSuggestions) {
                            self.hideSuggestions();
                        }
                    }
                },
                error: function() {
                    if (showSuggestions) {
                        self.hideSuggestions();
                    }
                }
            });
        },

        /**
         * Display search suggestions
         */
        displaySuggestions: function(data, query) {
            var $suggestions = $('#mets-kb-search-suggestions');
            var html = '';

            if (data.articles && data.articles.length > 0) {
                $.each(data.articles, function(index, article) {
                    var excerpt = article.excerpt || '';
                    if (excerpt.length > 100) {
                        excerpt = excerpt.substring(0, 100) + '...';
                    }

                    html += '<div class="mets-kb-suggestion-item" data-url="' + 
                           this.getArticleUrl(article.slug) + '">';
                    html += '<div class="mets-kb-suggestion-title">' + 
                           this.escapeHtml(article.title) + '</div>';
                    if (excerpt) {
                        html += '<div class="mets-kb-suggestion-excerpt">' + 
                               this.escapeHtml(excerpt) + '</div>';
                    }
                    html += '</div>';
                }.bind(this));

                if (data.total > data.articles.length) {
                    html += '<div class="mets-kb-suggestion-more">';
                    html += '<a href="' + this.getSearchUrl(query) + '">';
                    html += mets_kb_ajax.strings.loading.replace('%d', data.total);
                    html += '</a>';
                    html += '</div>';
                }
            } else {
                html = '<div class="mets-kb-suggestion-item mets-kb-no-suggestions">';
                html += mets_kb_ajax.strings.no_results;
                html += '</div>';
            }

            $suggestions.html(html).show();
        },

        /**
         * Show loading suggestions
         */
        showLoadingSuggestions: function() {
            var $suggestions = $('#mets-kb-search-suggestions');
            var html = '<div class="mets-kb-suggestion-item mets-kb-loading">';
            html += mets_kb_ajax.strings.loading;
            html += '</div>';
            $suggestions.html(html).show();
        },

        /**
         * Show suggestions
         */
        showSuggestions: function() {
            var query = $('#mets-kb-search-input').val().trim();
            if (query.length >= 2 && this.searchCache[query]) {
                this.displaySuggestions(this.searchCache[query], query);
            }
        },

        /**
         * Hide suggestions
         */
        hideSuggestions: function() {
            setTimeout(function() {
                $('#mets-kb-search-suggestions').hide();
            }, 200);
        },

        /**
         * Handle article feedback
         */
        handleFeedback: function(e) {
            e.preventDefault();
            
            var $btn = $(e.currentTarget);
            var $container = $btn.closest('.mets-kb-feedback-buttons');
            var articleId = $container.data('article-id');
            var vote = $btn.data('vote');
            var $message = $container.siblings('.mets-kb-feedback-message');

            // Prevent double-clicking
            if ($btn.hasClass('voted')) {
                return;
            }

            $.ajax({
                url: mets_kb_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mets_kb_rate_article',
                    nonce: mets_kb_ajax.nonce,
                    article_id: articleId,
                    vote: vote
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        // Mark all buttons as voted
                        $container.find('.mets-kb-feedback-btn').addClass('voted');
                        
                        // Show thank you message
                        $message.html(response.data.message).show();
                        
                        // Highlight the clicked button
                        $btn.addClass('selected');
                    } else {
                        METS_KB.showMessage(response.data || mets_kb_ajax.strings.error, 'error');
                    }
                },
                error: function() {
                    METS_KB.showMessage(mets_kb_ajax.strings.error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Show message to user
         */
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Create message element if it doesn't exist
            var $message = $('#mets-kb-message');
            if ($message.length === 0) {
                $message = $('<div id="mets-kb-message" class="mets-kb-message"></div>');
                $('body').append($message);
            }

            // Show message
            $message.removeClass('info success warning error')
                   .addClass(type)
                   .html(message)
                   .fadeIn();

            // Auto-hide after 5 seconds
            setTimeout(function() {
                $message.fadeOut();
            }, 5000);
        },

        /**
         * Get article URL
         */
        getArticleUrl: function(slug) {
            return mets_kb_ajax.home_url + '/knowledgebase/article/' + slug + '/';
        },

        /**
         * Get search URL
         */
        getSearchUrl: function(query) {
            return mets_kb_ajax.home_url + '/knowledgebase/search/?q=' + encodeURIComponent(query);
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Check if device is mobile
         */
        isMobile: function() {
            return window.innerWidth <= 768;
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        METS_KB.init();
    });

    /**
     * Additional CSS for suggestions
     */
    var additionalCSS = `
        .mets-kb-suggestion-item {
            border-bottom: 1px solid #f1f1f1;
        }
        .mets-kb-suggestion-item:hover,
        .mets-kb-suggestion-item.active {
            background: #f8f9fa;
        }
        .mets-kb-suggestion-title {
            font-weight: 600;
            color: #0073aa;
            margin-bottom: 4px;
        }
        .mets-kb-suggestion-excerpt {
            font-size: 0.9em;
            color: #666;
            line-height: 1.4;
        }
        .mets-kb-suggestion-more {
            padding: 12px 20px;
            text-align: center;
            border-top: 1px solid #e1e5e9;
            background: #f8f9fa;
        }
        .mets-kb-suggestion-more a {
            color: #0073aa;
            text-decoration: none;
            font-weight: 500;
        }
        .mets-kb-suggestion-more a:hover {
            text-decoration: underline;
        }
        .mets-kb-no-suggestions {
            text-align: center;
            color: #888;
            font-style: italic;
        }
        .mets-kb-loading {
            text-align: center;
            color: #666;
        }
        .mets-kb-feedback-btn.selected.mets-kb-helpful-yes {
            background: #00a32a;
            color: white;
            border-color: #00a32a;
        }
        .mets-kb-feedback-btn.selected.mets-kb-helpful-no {
            background: #d63638;
            color: white;
            border-color: #d63638;
        }
        #mets-kb-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            font-weight: 500;
            z-index: 10000;
            display: none;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        #mets-kb-message.success {
            background: #d1eddb;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        #mets-kb-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        #mets-kb-message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        #mets-kb-message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    `;

    // Inject additional CSS
    $('<style>').text(additionalCSS).appendTo('head');

})(jQuery);