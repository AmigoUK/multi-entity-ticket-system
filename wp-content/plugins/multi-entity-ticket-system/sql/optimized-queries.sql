-- METS Optimized Database Queries
-- This file contains optimized versions of common METS queries to prevent N+1 problems
-- and improve performance through proper indexing and query structure.

-- =======================================================================================
-- TICKET QUERIES - Optimized for performance
-- =======================================================================================

-- Get tickets with related data in a single query (prevents N+1)
-- Usage: Replace individual queries for tickets, entities, and users with this single query
-- Performance: Reduces 3+ queries to 1, uses composite indexes
SELECT 
    t.id,
    t.subject,
    t.status,
    t.priority,
    t.created_at,
    t.updated_at,
    t.entity_id,
    t.assigned_to,
    t.customer_id,
    t.sla_status,
    t.due_date,
    e.name as entity_name,
    e.entity_type,
    u.display_name as assigned_user_name,
    u.user_email as assigned_user_email,
    c.display_name as customer_name,
    c.user_email as customer_email,
    (SELECT COUNT(*) FROM wp_mets_ticket_replies r WHERE r.ticket_id = t.id) as reply_count,
    (SELECT MAX(r.created_at) FROM wp_mets_ticket_replies r WHERE r.ticket_id = t.id) as last_reply_date
FROM wp_mets_tickets t
LEFT JOIN wp_mets_entities e ON t.entity_id = e.id
LEFT JOIN wp_users u ON t.assigned_to = u.ID
LEFT JOIN wp_users c ON t.customer_id = c.ID
WHERE t.status IN ('open', 'pending', 'in_progress')
ORDER BY t.priority DESC, t.created_at DESC
LIMIT 50;

-- Get ticket dashboard data with aggregate counts (single query instead of multiple)
-- Performance: Uses single query with conditional aggregation
SELECT 
    COUNT(*) as total_tickets,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tickets,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
    SUM(CASE WHEN priority = 'critical' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as critical_open,
    SUM(CASE WHEN sla_status = 'breach' THEN 1 ELSE 0 END) as sla_breaches,
    AVG(CASE WHEN status = 'resolved' THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_resolution_hours
FROM wp_mets_tickets
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- =======================================================================================
-- ENTITY QUERIES - Hierarchical data optimized
-- =======================================================================================

-- Get entities with parent-child relationships and ticket counts
-- Performance: Uses single query with self-join and subquery for counts
SELECT 
    e.id,
    e.name,
    e.entity_type,
    e.status,
    e.parent_id,
    p.name as parent_name,
    e.created_at,
    (SELECT COUNT(*) FROM wp_mets_tickets t WHERE t.entity_id = e.id AND t.status NOT IN ('closed', 'resolved')) as open_tickets,
    (SELECT COUNT(*) FROM wp_mets_entities child WHERE child.parent_id = e.id) as child_count
FROM wp_mets_entities e
LEFT JOIN wp_mets_entities p ON e.parent_id = p.id
WHERE e.status = 'active'
ORDER BY e.entity_type, e.name;

-- =======================================================================================
-- KNOWLEDGE BASE QUERIES - Optimized for search and browsing
-- =======================================================================================

-- Get KB articles with category info and analytics in single query
-- Performance: Prevents N+1 queries for category and view count data
SELECT 
    a.id,
    a.title,
    a.slug,
    a.content,
    a.status,
    a.is_featured,
    a.created_at,
    a.updated_at,
    a.author_id,
    c.name as category_name,
    c.slug as category_slug,
    u.display_name as author_name,
    (SELECT COUNT(*) FROM wp_mets_kb_analytics an WHERE an.article_id = a.id AND an.event_type = 'view') as view_count,
    (SELECT COUNT(*) FROM wp_mets_kb_analytics an WHERE an.article_id = a.id AND an.event_type = 'helpful') as helpful_count
FROM wp_mets_kb_articles a
LEFT JOIN wp_mets_kb_categories c ON a.category_id = c.id
LEFT JOIN wp_users u ON a.author_id = u.ID
WHERE a.status = 'published'
ORDER BY a.is_featured DESC, a.updated_at DESC;

-- Full-text search for KB articles (uses FULLTEXT index)
-- Performance: Uses FULLTEXT index instead of LIKE queries
SELECT 
    a.id,
    a.title,
    a.slug,
    c.name as category_name,
    MATCH(a.title, a.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
FROM wp_mets_kb_articles a
LEFT JOIN wp_mets_kb_categories c ON a.category_id = c.id
WHERE a.status = 'published'
AND MATCH(a.title, a.content) AGAINST(? IN NATURAL LANGUAGE MODE)
ORDER BY relevance_score DESC, a.is_featured DESC
LIMIT 20;

-- =======================================================================================
-- REPORTING QUERIES - Optimized for analytics
-- =======================================================================================

-- Ticket volume and resolution time report (single query)
-- Performance: Uses date functions and conditional aggregation
SELECT 
    DATE(created_at) as date,
    COUNT(*) as tickets_created,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as tickets_resolved,
    AVG(CASE WHEN status = 'resolved' THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_resolution_hours,
    SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_tickets,
    SUM(CASE WHEN sla_status = 'breach' THEN 1 ELSE 0 END) as sla_breaches
FROM wp_mets_tickets
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Agent performance report (prevents N+1 for user data)
-- Performance: Single query with JOINs instead of multiple queries per agent
SELECT 
    u.ID as agent_id,
    u.display_name as agent_name,
    u.user_email as agent_email,
    COUNT(t.id) as total_assigned,
    SUM(CASE WHEN t.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
    SUM(CASE WHEN t.status IN ('open', 'pending', 'in_progress') THEN 1 ELSE 0 END) as active_count,
    AVG(CASE WHEN t.status = 'resolved' THEN TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at) END) as avg_resolution_hours,
    (SELECT COUNT(*) FROM wp_mets_ticket_replies r WHERE r.user_id = u.ID AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as replies_count
FROM wp_users u
INNER JOIN wp_mets_tickets t ON u.ID = t.assigned_to
WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY u.ID, u.display_name, u.user_email
ORDER BY resolved_count DESC, avg_resolution_hours ASC;

-- =======================================================================================
-- SLA MONITORING QUERIES - Optimized for real-time monitoring
-- =======================================================================================

-- Get tickets approaching SLA breach (optimized with composite index)
-- Performance: Uses composite index on (sla_status, due_date)
SELECT 
    t.id,
    t.subject,
    t.priority,
    t.status,
    t.due_date,
    t.sla_status,
    e.name as entity_name,
    u.display_name as assigned_user,
    TIMESTAMPDIFF(MINUTE, NOW(), t.due_date) as minutes_until_breach
FROM wp_mets_tickets t
LEFT JOIN wp_mets_entities e ON t.entity_id = e.id
LEFT JOIN wp_users u ON t.assigned_to = u.ID
WHERE t.sla_status IN ('warning', 'critical')
AND t.status NOT IN ('resolved', 'closed')
AND t.due_date > NOW()
ORDER BY t.due_date ASC;

-- =======================================================================================
-- BATCH UPDATE QUERIES - For bulk operations
-- =======================================================================================

-- Update SLA status for multiple tickets (batch operation)
-- Performance: Single UPDATE instead of multiple queries
UPDATE wp_mets_tickets 
SET sla_status = CASE
    WHEN due_date <= NOW() THEN 'breach'
    WHEN due_date <= DATE_ADD(NOW(), INTERVAL 2 HOUR) THEN 'critical'
    WHEN due_date <= DATE_ADD(NOW(), INTERVAL 6 HOUR) THEN 'warning'
    ELSE 'ok'
END
WHERE status NOT IN ('resolved', 'closed')
AND sla_status != 'breach';

-- =======================================================================================
-- CACHE WARMING QUERIES - Pre-populate frequently accessed data
-- =======================================================================================

-- Warm up ticket count cache per entity
-- Performance: Pre-calculate counts to avoid repeated aggregation
INSERT INTO wp_mets_cache (cache_key, cache_value, expires_at)
SELECT 
    CONCAT('entity_ticket_count_', e.id) as cache_key,
    COUNT(t.id) as cache_value,
    DATE_ADD(NOW(), INTERVAL 1 HOUR) as expires_at
FROM wp_mets_entities e
LEFT JOIN wp_mets_tickets t ON e.id = t.entity_id AND t.status NOT IN ('closed', 'resolved')
GROUP BY e.id
ON DUPLICATE KEY UPDATE 
    cache_value = VALUES(cache_value),
    expires_at = VALUES(expires_at);

-- =======================================================================================
-- MAINTENANCE QUERIES - For database optimization
-- =======================================================================================

-- Clean up old analytics data (keep only last 90 days)
DELETE FROM wp_mets_kb_analytics 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
LIMIT 1000;

-- Clean up old performance metrics (keep only last 30 days)
DELETE FROM wp_mets_performance_metrics 
WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)
LIMIT 1000;

-- =======================================================================================
-- INDEX CREATION STATEMENTS - Run these for optimal performance
-- =======================================================================================

-- Enhanced composite indexes for common query patterns
-- These indexes support the queries above and prevent full table scans

-- Tickets table indexes
CREATE INDEX idx_tickets_status_priority_created ON wp_mets_tickets (status, priority, created_at DESC);
CREATE INDEX idx_tickets_entity_status_priority ON wp_mets_tickets (entity_id, status, priority);
CREATE INDEX idx_tickets_assigned_status_updated ON wp_mets_tickets (assigned_to, status, updated_at DESC);
CREATE INDEX idx_tickets_customer_status_created ON wp_mets_tickets (customer_id, status, created_at DESC);
CREATE INDEX idx_tickets_sla_due_date ON wp_mets_tickets (sla_status, due_date);
CREATE INDEX idx_tickets_created_status ON wp_mets_tickets (created_at DESC, status);

-- Ticket replies indexes
CREATE INDEX idx_replies_ticket_user_created ON wp_mets_ticket_replies (ticket_id, user_id, created_at DESC);
CREATE INDEX idx_replies_user_created ON wp_mets_ticket_replies (user_id, created_at DESC);

-- Entities indexes
CREATE INDEX idx_entities_type_status_parent ON wp_mets_entities (entity_type, status, parent_id);
CREATE INDEX idx_entities_parent_status_created ON wp_mets_entities (parent_id, status, created_at DESC);

-- KB articles indexes
CREATE INDEX idx_kb_category_status_featured_updated ON wp_mets_kb_articles (category_id, status, is_featured, updated_at DESC);
CREATE INDEX idx_kb_author_status_created ON wp_mets_kb_articles (author_id, status, created_at DESC);
CREATE FULLTEXT INDEX idx_kb_fulltext_search ON wp_mets_kb_articles (title, content);

-- KB analytics indexes
CREATE INDEX idx_kb_analytics_article_event_date ON wp_mets_kb_analytics (article_id, event_type, created_at DESC);

-- Performance monitoring indexes
CREATE INDEX idx_performance_timestamp_duration ON wp_mets_performance_metrics (timestamp DESC, request_duration);
CREATE INDEX idx_performance_uri_timestamp ON wp_mets_performance_metrics (request_uri(100), timestamp DESC);

-- =======================================================================================
-- QUERY OPTIMIZATION TIPS
-- =======================================================================================

/*
1. Always use LIMIT for queries that might return many results
2. Use composite indexes that match your WHERE and ORDER BY clauses
3. Avoid SELECT * - only select needed columns
4. Use EXPLAIN to analyze query execution plans
5. Prefer JOINs over subqueries when possible
6. Use conditional aggregation instead of multiple queries
7. Implement proper caching for frequently accessed data
8. Use batch operations for bulk updates
9. Regular ANALYZE TABLE to update index statistics
10. Monitor slow query log for optimization opportunities
*/