/**
 * Team Performance Dashboard JavaScript
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/assets/js
 * @since      1.0.0
 */

(function($) {
    'use strict';

    /**
     * Team Dashboard Object
     */
    const METSTeamDashboard = {
        
        /**
         * Cache for storing dashboard data
         */
        cache: {
            metrics: null,
            activities: null,
            agents: null,
            sla: null,
            lastUpdate: null
        },

        /**
         * Configuration
         */
        config: {
            refreshInterval: 30000, // 30 seconds
            ajaxTimeout: 10000,     // 10 seconds
            maxRetries: 3,
            debounceDelay: 300,
            cacheExpiry: 300000     // 5 minutes
        },

        /**
         * Current state
         */
        state: {
            currentPeriod: 'today',
            isLoading: false,
            retryCount: 0,
            customDateRange: {
                from: null,
                to: null
            },
            filters: {
                activity: 'all',
                search: ''
            }
        },

        /**
         * Timer reference for periodic refresh
         */
        refreshTimer: null,

        /**
         * Initialize dashboard
         */
        init: function() {
            this.bindEvents();
            this.setupPeriodicRefresh();
            this.initializeCustomDatePicker();
            
            // Initial load
            this.refreshData();
            
            console.log('Team Dashboard initialized');
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            const self = this;

            // Period selector change
            $(document).on('change', '#mets-period-selector', function() {
                const period = $(this).val();
                self.handlePeriodChange(period);
            });

            // Custom date range inputs
            $(document).on('change', '#mets-date-from, #mets-date-to', function() {
                self.handleCustomDateChange();
            });

            // Manual refresh button
            $(document).on('click', '.mets-refresh-data', function(e) {
                e.preventDefault();
                if (!self.state.isLoading) {
                    self.refreshData(true);
                }
            });

            // Activity search with debounce
            let searchTimeout;
            $(document).on('input', '.mets-activity-search', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val();
                
                searchTimeout = setTimeout(function() {
                    self.filterActivities(query, self.state.filters.activity);
                }, self.config.debounceDelay);
            });

            // Activity filter
            $(document).on('change', '.mets-activity-filter', function() {
                const filter = $(this).val();
                self.filterActivities(self.state.filters.search, filter);
            });

            // Metric card click for drill-down
            $(document).on('click', '.mets-metric-card', function() {
                const metric = $(this).data('metric');
                if (metric) {
                    self.drillDownMetric(metric);
                }
            });

            // Handle visibility change (pause/resume when tab not active)
            $(document).on('visibilitychange', function() {
                if (document.hidden) {
                    self.pauseRefresh();
                } else {
                    self.resumeRefresh();
                }
            });

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + Shift + R for dashboard refresh (to avoid conflict with page refresh)
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.keyCode === 82) {
                    e.preventDefault();
                    if (!self.state.isLoading) {
                        self.refreshData(true);
                    }
                }
            });
        },

        /**
         * Handle period change
         */
        handlePeriodChange: function(period) {
            this.state.currentPeriod = period;
            
            const customDiv = $('.mets-period-custom');
            
            if (period === 'custom') {
                customDiv.addClass('active');
                this.initializeCustomDatePicker();
            } else {
                customDiv.removeClass('active');
                this.refreshData();
            }
        },

        /**
         * Handle custom date range change
         */
        handleCustomDateChange: function() {
            const fromDate = $('#mets-date-from').val();
            const toDate = $('#mets-date-to').val();
            
            if (fromDate && toDate) {
                this.state.customDateRange.from = fromDate;
                this.state.customDateRange.to = toDate;
                this.refreshData();
            }
        },

        /**
         * Initialize custom date picker
         */
        initializeCustomDatePicker: function() {
            const today = new Date().toISOString().split('T')[0];
            const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            
            $('#mets-date-from').attr('max', today);
            $('#mets-date-to').attr('max', today);
            
            // Set default values if empty
            if (!$('#mets-date-from').val()) {
                $('#mets-date-from').val(weekAgo);
            }
            if (!$('#mets-date-to').val()) {
                $('#mets-date-to').val(today);
            }
        },

        /**
         * Refresh dashboard data
         */
        refreshData: function(forceRefresh = false) {
            if (this.state.isLoading && !forceRefresh) {
                return;
            }

            // Check cache first
            if (!forceRefresh && this.isCacheValid()) {
                this.updateUI(this.cache);
                return;
            }

            this.setLoadingState(true);
            // Don't reset retryCount here - only reset on successful response

            this.fetchData()
                .then(data => {
                    this.updateCache(data);
                    this.updateUI(data);
                    this.setLoadingState(false);
                    this.state.retryCount = 0; // Only reset on success
                })
                .catch(error => {
                    console.error('Dashboard refresh failed:', error);
                    this.handleError(error);
                });
        },

        /**
         * Fetch data via AJAX
         */
        fetchData: function() {
            // Validate required global variables
            if (typeof metsTeamDashboard === 'undefined') {
                return Promise.reject(new Error('Dashboard configuration not loaded'));
            }
            
            if (!metsTeamDashboard.ajaxUrl || !metsTeamDashboard.nonce) {
                return Promise.reject(new Error('Missing AJAX configuration'));
            }
            
            const self = this;
            
            const requestData = {
                action: 'mets_refresh_dashboard',
                nonce: metsTeamDashboard.nonce,
                period: this.state.currentPeriod,
                filters: this.state.filters
            };

            // Add custom date range if applicable
            if (this.state.currentPeriod === 'custom') {
                requestData.date_from = this.state.customDateRange.from;
                requestData.date_to = this.state.customDateRange.to;
            }

            return new Promise((resolve, reject) => {
                $.ajax({
                    url: metsTeamDashboard.ajaxUrl,
                    type: 'POST',
                    data: requestData,
                    timeout: self.config.ajaxTimeout,
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error(`AJAX Error: ${status} - ${error}`));
                    }
                });
            });
        },

        /**
         * Update cache
         */
        updateCache: function(data) {
            this.cache = {
                ...data,
                lastUpdate: Date.now()
            };
        },

        /**
         * Check if cache is valid
         */
        isCacheValid: function() {
            return this.cache.lastUpdate && 
                   (Date.now() - this.cache.lastUpdate) < this.config.cacheExpiry;
        },

        /**
         * Update UI with new data
         */
        updateUI: function(data) {
            if (data.metrics) {
                this.updateMetrics(data.metrics);
            }
            
            if (data.activities) {
                this.updateActivities(data.activities);
            }
            
            if (data.agents) {
                this.updateAgentPerformance(data.agents);
            }
            
            if (data.sla) {
                this.updateSLAMetrics(data.sla);
            }

            // Update last refresh time
            this.updateLastRefreshTime();
            
            // Add fade-in animation
            $('.mets-metric-card, .mets-sla-card').addClass('mets-fade-in');
        },

        /**
         * Update metrics cards
         */
        updateMetrics: function(metrics) {
            metrics.forEach(metric => {
                const card = $(`.mets-metric-card[data-metric="${metric.key}"]`);
                if (card.length) {
                    const numberEl = card.find('.mets-metric-number');
                    const changeEl = card.find('.mets-metric-change');
                    
                    // Update number with animation
                    this.animateNumber(numberEl, metric.number);
                    
                    // Update change percentage
                    changeEl.text(`${metric.change >= 0 ? '+' : ''}${metric.change}%`)
                           .removeClass('mets-metric-up mets-metric-down')
                           .addClass(metric.change >= 0 ? 'mets-metric-up' : 'mets-metric-down');
                    
                    // Handle negative values
                    numberEl.toggleClass('negative', metric.is_negative);
                }
            });
        },

        /**
         * Update activities feed
         */
        updateActivities: function(activities) {
            const container = $('.mets-activity-feed .mets-activity-list');
            
            if (!container.length) {
                // Create activity list container if it doesn't exist
                $('.mets-activity-feed').append('<div class="mets-activity-list"></div>');
            }
            
            let html = '';
            
            activities.forEach(activity => {
                html += this.buildActivityItem(activity);
            });
            
            container.html(html).addClass('mets-slide-in');
        },

        /**
         * Build activity item HTML
         */
        buildActivityItem: function(activity) {
            const statusClass = `mets-status-${activity.status.toLowerCase().replace(/[^a-z0-9]/g, '-')}`;
            
            return `
                <div class="mets-activity-item" data-ticket-id="${activity.ticket_id}">
                    <div>
                        <a href="${activity.ticket_url}" class="mets-activity-ticket">
                            ${activity.ticket_number}
                        </a>
                        - ${activity.subject}
                        <span class="mets-activity-status ${statusClass}">${activity.status}</span>
                    </div>
                    <div class="mets-activity-time">
                        ${activity.agent_name} • ${activity.time_ago} • ${activity.action}
                    </div>
                </div>
            `;
        },

        /**
         * Update agent performance table
         */
        updateAgentPerformance: function(agents) {
            const tbody = $('.mets-agent-performance tbody');
            
            let html = '';
            
            agents.forEach(agent => {
                const workloadClass = `mets-workload-${agent.workload}`;
                
                html += `
                    <tr data-agent-id="${agent.id}">
                        <td><strong>${agent.name}</strong></td>
                        <td>${agent.active_tickets}</td>
                        <td>${agent.resolved_today}</td>
                        <td>${agent.avg_response || 'N/A'}</td>
                        <td>${agent.rating || 'N/A'}</td>
                        <td><span class="${workloadClass}">${agent.workload_label}</span></td>
                    </tr>
                `;
            });
            
            tbody.html(html);
        },

        /**
         * Update SLA metrics
         */
        updateSLAMetrics: function(slaData) {
            slaData.forEach(sla => {
                const card = $(`.mets-sla-card[data-sla="${sla.key}"]`);
                if (card.length) {
                    const percentageEl = card.find('.mets-sla-percentage');
                    
                    // Update percentage with animation
                    this.animateNumber(percentageEl, sla.percentage, '%');
                    
                    // Update status class
                    percentageEl.removeClass('mets-sla-good mets-sla-warning mets-sla-critical negative-value')
                               .addClass(`mets-sla-${sla.status}`);
                    
                    if (sla.percentage < 0) {
                        percentageEl.addClass('negative-value');
                    }
                }
            });
        },

        /**
         * Animate number changes
         */
        animateNumber: function(element, newValue, suffix = '') {
            const current = parseFloat(element.text()) || 0;
            const target = parseFloat(newValue) || 0;
            const difference = target - current;
            const steps = 20;
            const stepValue = difference / steps;
            let currentStep = 0;
            
            const animation = setInterval(() => {
                currentStep++;
                const value = current + (stepValue * currentStep);
                
                if (currentStep >= steps) {
                    element.text(target + suffix);
                    clearInterval(animation);
                } else {
                    element.text(Math.round(value * 10) / 10 + suffix);
                }
            }, 50);
        },

        /**
         * Filter activities
         */
        filterActivities: function(searchQuery, statusFilter) {
            this.state.filters.search = searchQuery;
            this.state.filters.activity = statusFilter;
            
            const items = $('.mets-activity-item');
            
            items.each(function() {
                const item = $(this);
                const text = item.text().toLowerCase();
                const status = item.find('.mets-activity-status').text().toLowerCase();
                
                const matchesSearch = !searchQuery || text.includes(searchQuery.toLowerCase());
                const matchesFilter = statusFilter === 'all' || status.includes(statusFilter);
                
                item.toggle(matchesSearch && matchesFilter);
            });
        },

        /**
         * Handle drill-down metric click
         */
        drillDownMetric: function(metric) {
            // This could open a modal or navigate to detailed view
            console.log('Drill down for metric:', metric);
            
            // For now, just show an alert - this can be enhanced later
            // alert(`Detailed view for ${metric} - Feature coming soon!`);
        },

        /**
         * Set loading state
         */
        setLoadingState: function(isLoading) {
            this.state.isLoading = isLoading;
            
            const containers = $('.mets-metrics-grid, .mets-activity-feed, .mets-agent-performance, .mets-sla-grid');
            
            if (isLoading) {
                containers.addClass('mets-loading');
                $('.mets-refresh-data').prop('disabled', true).text('Refreshing...');
            } else {
                containers.removeClass('mets-loading');
                $('.mets-refresh-data').prop('disabled', false).text('Refresh Data');
            }
        },

        /**
         * Handle errors
         */
        handleError: function(error) {
            this.setLoadingState(false);
            
            if (this.state.retryCount < this.config.maxRetries) {
                this.state.retryCount++;
                console.log(`Retrying request (${this.state.retryCount}/${this.config.maxRetries})`);
                
                setTimeout(() => {
                    // Retry with fetchData directly to avoid retry counter reset
                    this.fetchData()
                        .then(data => {
                            this.updateCache(data);
                            this.updateUI(data);
                            this.setLoadingState(false);
                            this.state.retryCount = 0; // Reset only on success
                        })
                        .catch(retryError => {
                            console.error('Retry failed:', retryError);
                            this.handleError(retryError); // Recursive retry
                        });
                }, 2000 * this.state.retryCount); // Exponential backoff
                
            } else {
                this.showError('Failed to refresh dashboard data after multiple attempts. Please refresh the page.');
                // Stop periodic refresh on persistent failures
                if (this.refreshTimer) {
                    clearInterval(this.refreshTimer);
                    console.log('Periodic refresh stopped after persistent failures');
                }
            }
        },

        /**
         * Show error message
         */
        showError: function(message) {
            // Create or update error notification
            let errorDiv = $('.mets-dashboard-error');
            
            if (!errorDiv.length) {
                errorDiv = $('<div class="notice notice-error mets-dashboard-error"><p></p></div>');
                $('.mets-manager-dashboard').prepend(errorDiv);
            }
            
            errorDiv.find('p').text(message);
            errorDiv.show();
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                errorDiv.fadeOut();
            }, 5000);
        },

        /**
         * Setup periodic refresh
         */
        setupPeriodicRefresh: function() {
            // Clear any existing timer
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
            }
            
            this.refreshTimer = setInterval(() => {
                if (!document.hidden && !this.state.isLoading && this.state.retryCount === 0) {
                    this.refreshData();
                }
            }, this.config.refreshInterval);
        },

        /**
         * Pause refresh (when tab not active)
         */
        pauseRefresh: function() {
            // Refresh is already paused by the visibility check in setupPeriodicRefresh
            console.log('Dashboard refresh paused');
        },

        /**
         * Resume refresh (when tab becomes active)
         */
        resumeRefresh: function() {
            console.log('Dashboard refresh resumed');
            // Refresh immediately when tab becomes active
            this.refreshData();
        },

        /**
         * Update last refresh time display
         */
        updateLastRefreshTime: function() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            
            let refreshTime = $('.mets-last-refresh');
            if (!refreshTime.length) {
                refreshTime = $('<div class="mets-last-refresh"></div>');
                $('.mets-dashboard-header').append(refreshTime);
            }
            
            refreshTime.text(`Last updated: ${timeString}`);
        },

        /**
         * Cleanup method
         */
        cleanup: function() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }
            console.log('Dashboard cleanup completed');
        },

        /**
         * Utility: Debounce function
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
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
        // Only initialize if we're on the team dashboard page
        if ($('.mets-manager-dashboard').length) {
            METSTeamDashboard.init();
            
            // Cleanup on page unload
            $(window).on('beforeunload', function() {
                METSTeamDashboard.cleanup();
            });
        }
    });

    /**
     * Export to global scope for external access
     */
    window.METSTeamDashboard = METSTeamDashboard;

})(jQuery);