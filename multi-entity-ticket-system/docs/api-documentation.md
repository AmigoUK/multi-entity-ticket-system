# REST API Documentation

The Multi-Entity Ticket System provides a comprehensive REST API for managing tickets, entities, customers, agents, SLA metrics, knowledge base articles, and system reporting.

## Base URL

All API endpoints are prefixed with: `/wp-json/mets/v1/`

## Authentication

The API uses WordPress's built-in authentication system:
- **Session Authentication**: For logged-in users via browser
- **Application Passwords**: For external applications (WordPress 5.6+)
- **Basic Authentication**: When using authentication plugins

### Authentication Examples

```bash
# Using session authentication (logged-in browser)
curl -X GET "https://yoursite.com/wp-json/mets/v1/tickets" \
  --cookie "wordpress_logged_in_cookie_value"

# Using application passwords
curl -X GET "https://yoursite.com/wp-json/mets/v1/tickets" \
  --user "username:application_password"
```

## Response Format

All API responses follow a consistent JSON format:

```json
{
  "data": {},
  "status": 200,
  "headers": {}
}
```

### Pagination

Paginated responses include additional headers:
- `X-WP-Total`: Total number of items
- `X-WP-TotalPages`: Total number of pages
- `X-WP-Page`: Current page number
- `X-WP-PerPage`: Items per page

## Entities

### List Entities

```http
GET /wp-json/mets/v1/entities
```

**Parameters:**
- `status` (string): Filter by status (`active`, `inactive`, `all`)
- `parent_id` (integer): Filter by parent entity ID
- `per_page` (integer): Items per page (1-100, default: 10)
- `page` (integer): Page number (default: 1)

**Response:**
```json
[
  {
    "id": 1,
    "name": "Company A",
    "type": "company",
    "contact_email": "contact@company-a.com",
    "description": "Primary company entity",
    "parent_id": null,
    "metadata": {},
    "status": "active",
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
]
```

### Get Single Entity

```http
GET /wp-json/mets/v1/entities/{id}
```

### Create Entity

```http
POST /wp-json/mets/v1/entities
```

**Body:**
```json
{
  "name": "New Company",
  "type": "company",
  "contact_email": "contact@newcompany.com",
  "description": "Company description",
  "parent_id": null,
  "metadata": {}
}
```

### Update Entity

```http
PUT /wp-json/mets/v1/entities/{id}
```

### Delete Entity

```http
DELETE /wp-json/mets/v1/entities/{id}
```

## Tickets

### List Tickets

```http
GET /wp-json/mets/v1/tickets
```

**Parameters:**
- `status` (array): Filter by status (`open`, `in_progress`, `resolved`, `closed`, `on_hold`)
- `priority` (array): Filter by priority (`low`, `medium`, `high`, `critical`)
- `entity_id` (integer): Filter by entity ID
- `customer_id` (integer): Filter by customer ID
- `assigned_to` (integer): Filter by assigned agent ID
- `search` (string): Search in subject and description
- `orderby` (string): Sort field (`created_at`, `updated_at`, `priority`, `status`)
- `order` (string): Sort direction (`ASC`, `DESC`)
- `per_page` (integer): Items per page (1-100, default: 10)
- `page` (integer): Page number (default: 1)

**Response:**
```json
[
  {
    "id": 123,
    "subject": "Login issue",
    "status": "open",
    "priority": "medium",
    "entity_id": 1,
    "entity_name": "Company A",
    "customer_id": 456,
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "assigned_to": 789,
    "assigned_agent_name": "Agent Smith",
    "created_at": "2024-01-01T10:00:00Z",
    "updated_at": "2024-01-01T10:30:00Z",
    "resolved_at": null
  }
]
```

### Get Single Ticket

```http
GET /wp-json/mets/v1/tickets/{id}
```

**Response includes additional fields:**
```json
{
  "id": 123,
  "subject": "Login issue",
  "description": "Full ticket description...",
  "status": "open",
  "priority": "medium",
  "entity_id": 1,
  "entity_name": "Company A",
  "sla_due_date": "2024-01-02T10:00:00Z",
  "sla_status": "met",
  "first_response_at": "2024-01-01T10:15:00Z",
  "reply_count": 3,
  "source": "web"
}
```

### Create Ticket

```http
POST /wp-json/mets/v1/tickets
```

**Body:**
```json
{
  "subject": "Cannot login to system",
  "description": "Detailed description of the issue...",
  "entity_id": 1,
  "priority": "medium",
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "attachments": [123, 124]
}
```

### Update Ticket

```http
PUT /wp-json/mets/v1/tickets/{id}
```

**Body:**
```json
{
  "subject": "Updated subject",
  "status": "in_progress",
  "priority": "high",
  "assigned_to": 789
}
```

### Add Reply to Ticket

```http
POST /wp-json/mets/v1/tickets/{id}/replies
```

**Body:**
```json
{
  "content": "Reply content...",
  "attachments": [125]
}
```

### Get Ticket Replies

```http
GET /wp-json/mets/v1/tickets/{id}/replies
```

### Assign Ticket

```http
POST /wp-json/mets/v1/tickets/{id}/assign
```

**Body:**
```json
{
  "agent_id": 789
}
```

### Update Ticket Status

```http
POST /wp-json/mets/v1/tickets/{id}/status
```

**Body:**
```json
{
  "status": "resolved"
}
```

## Customers

### List Customers

```http
GET /wp-json/mets/v1/customers
```

**Parameters:**
- `search` (string): Search in name and email
- `entity_id` (integer): Filter by entity ID
- `per_page` (integer): Items per page (default: 10)
- `page` (integer): Page number (default: 1)

### Get Customer Details

```http
GET /wp-json/mets/v1/customers/{id}
```

**Response:**
```json
{
  "id": 456,
  "display_name": "John Doe",
  "email": "john@example.com",
  "registered": "2023-01-01T00:00:00Z",
  "statistics": {
    "total_tickets": 15,
    "open_tickets": 2,
    "resolved_tickets": 13
  }
}
```

### Get Customer Tickets

```http
GET /wp-json/mets/v1/customers/{id}/tickets
```

## Agents

### List Agents

```http
GET /wp-json/mets/v1/agents
```

**Parameters:**
- `entity_id` (integer): Filter by entity ID
- `available` (boolean): Include availability status

**Response:**
```json
[
  {
    "id": 789,
    "display_name": "Agent Smith",
    "email": "agent@company.com",
    "role": "mets_agent",
    "available": true
  }
]
```

### Get Agent Statistics

```http
GET /wp-json/mets/v1/agents/{id}/stats
```

**Parameters:**
- `period` (string): Time period (`24hours`, `7days`, `30days`, `90days`, `all`)

**Response:**
```json
{
  "agent_id": 789,
  "period": "30days",
  "stats": {
    "total_tickets": 45,
    "resolved_tickets": 38,
    "closed_tickets": 5,
    "avg_resolution_time": 24.5,
    "resolution_rate": 95.6,
    "sla_compliance": 91.1
  }
}
```

## SLA

### List SLA Rules

```http
GET /wp-json/mets/v1/sla/rules
```

### Get SLA Performance

```http
GET /wp-json/mets/v1/sla/performance
```

**Parameters:**
- `period` (string): Time period
- `entity_id` (integer): Filter by entity ID

**Response:**
```json
{
  "period": "30days",
  "entity_id": null,
  "metrics": {
    "total_tickets": 234,
    "tickets_with_sla": 198,
    "sla_compliance": 87.4,
    "breakdown": {
      "met": 173,
      "warning": 15,
      "breached": 10
    },
    "response_times": {
      "avg_first_response": 45.2,
      "avg_resolution": 720.5
    }
  }
}
```

## Knowledge Base

### List KB Articles

```http
GET /wp-json/mets/v1/kb/articles
```

**Parameters:**
- `search` (string): Search in title and content
- `category_id` (integer): Filter by category ID
- `entity_id` (integer): Filter by entity ID
- `featured` (boolean): Filter featured articles
- `per_page` (integer): Items per page (default: 10)
- `page` (integer): Page number (default: 1)

**Response:**
```json
[
  {
    "id": 101,
    "title": "How to reset your password",
    "slug": "how-to-reset-password",
    "excerpt": "Step-by-step guide...",
    "entity_id": 1,
    "entity_name": "Company A",
    "featured": true,
    "view_count": 245,
    "helpful_count": 23,
    "not_helpful_count": 2,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-15T00:00:00Z"
  }
]
```

### Get Single KB Article

```http
GET /wp-json/mets/v1/kb/articles/{id}
```

**Response includes full content and categories.**

### Create KB Article

```http
POST /wp-json/mets/v1/kb/articles
```

**Body:**
```json
{
  "title": "New Article Title",
  "content": "Full article content...",
  "entity_id": 1,
  "category_id": 5,
  "visibility": "customer",
  "featured": false,
  "status": "published"
}
```

### Update KB Article

```http
PUT /wp-json/mets/v1/kb/articles/{id}
```

### Submit Article Feedback

```http
POST /wp-json/mets/v1/kb/articles/{id}/feedback
```

**Body:**
```json
{
  "helpful": true
}
```

### List KB Categories

```http
GET /wp-json/mets/v1/kb/categories
```

**Parameters:**
- `entity_id` (integer): Filter by entity ID

### Search KB Articles

```http
GET /wp-json/mets/v1/kb/search
```

**Parameters:**
- `query` (string, required): Search query
- `entity_id` (integer): Filter by entity ID
- `limit` (integer): Maximum results (default: 10)

**Response:**
```json
{
  "query": "password reset",
  "results": [
    {
      "id": 101,
      "title": "How to reset your password",
      "excerpt": "Step-by-step guide...",
      "entity_name": "Company A"
    }
  ],
  "total": 1
}
```

## Reporting

### Get Dashboard Metrics

```http
GET /wp-json/mets/v1/reports/dashboard
```

**Parameters:**
- `period` (string): Time period (default: `30days`)
- `entity_id` (integer): Filter by entity ID

**Response:**
```json
{
  "period": "30days",
  "entity_id": null,
  "metrics": {
    "tickets": {
      "total": 234,
      "open": 45,
      "in_progress": 23,
      "resolved": 145,
      "closed": 21,
      "critical": 3,
      "high": 12
    },
    "sla": {
      "compliance": 87.4,
      "met": 173,
      "breached": 25
    },
    "knowledge_base": {
      "total_searches": 456,
      "search_ctr": 73.2,
      "top_articles": 15
    }
  }
}
```

### Generate Custom Report

```http
POST /wp-json/mets/v1/reports/custom
```

**Body:**
```json
{
  "report_type": "tickets",
  "filters": {
    "status": ["open", "in_progress"],
    "priority": ["high", "critical"]
  },
  "date_range": {
    "date_range": "last_30_days"
  },
  "format": "json"
}
```

**Response:**
Returns structured report data based on the specified type and filters.

### Export Report

```http
GET /wp-json/mets/v1/reports/export/{report_id}
```

**Parameters:**
- `format` (string): Export format (`csv`, `pdf`)

## System

### Get System Information

```http
GET /wp-json/mets/v1/system/info
```

**Response:**
```json
{
  "version": "1.0.0",
  "wordpress_version": "6.4.0",
  "php_version": "8.1.0",
  "mysql_version": "8.0.35",
  "statistics": {
    "total_tickets": 1234,
    "total_entities": 15,
    "total_kb_articles": 89,
    "total_agents": 12
  },
  "settings": {
    "smtp_configured": true,
    "sla_enabled": true,
    "kb_enabled": true
  }
}
```

### Get System Status

```http
GET /wp-json/mets/v1/system/status
```

**Response:**
```json
{
  "status": "ok",
  "timestamp": "2024-01-01T12:00:00Z",
  "checks": {
    "database": true,
    "filesystem": true,
    "api": true
  }
}
```

### Test Email Configuration

```http
POST /wp-json/mets/v1/system/test-email
```

**Body:**
```json
{
  "to": "test@example.com"
}
```

## Error Handling

The API returns standard HTTP status codes:

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Internal Server Error

Error responses include details:

```json
{
  "code": "ticket_not_found",
  "message": "Ticket not found.",
  "data": {
    "status": 404
  }
}
```

## Rate Limiting

The API inherits WordPress's built-in rate limiting. For high-volume applications, consider:

- Caching responses where appropriate
- Using batch operations when available
- Implementing client-side rate limiting

## Examples

### Create a Ticket and Add a Reply

```bash
# Create ticket
TICKET_ID=$(curl -X POST "https://yoursite.com/wp-json/mets/v1/tickets" \
  --user "username:password" \
  --header "Content-Type: application/json" \
  --data '{
    "subject": "API Test Ticket",
    "description": "Testing ticket creation via API",
    "entity_id": 1,
    "priority": "medium",
    "customer_email": "test@example.com"
  }' | jq -r '.id')

# Add reply
curl -X POST "https://yoursite.com/wp-json/mets/v1/tickets/$TICKET_ID/replies" \
  --user "username:password" \
  --header "Content-Type: application/json" \
  --data '{
    "content": "This is a reply added via API"
  }'
```

### Search Knowledge Base

```bash
curl -X GET "https://yoursite.com/wp-json/mets/v1/kb/search?query=password&limit=5" \
  | jq '.results[].title'
```

### Get Agent Performance

```bash
curl -X GET "https://yoursite.com/wp-json/mets/v1/agents/123/stats?period=7days" \
  --user "username:password" \
  | jq '.stats'
```

This API provides comprehensive access to all Multi-Entity Ticket System functionality, enabling integration with external applications, mobile apps, and automated workflows.