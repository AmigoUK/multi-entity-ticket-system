# Phase 4: SLA, Notifications, and Automation - Implementation Plan

## Overview
This phase focuses on implementing Service Level Agreements (SLA), comprehensive email notification system, and workflow automation to transform the Multi-Entity Ticket System into a professional-grade support platform.

## Goals
- Implement SLA tracking and escalation rules
- Build comprehensive email notification system
- Create automated workflow triggers
- Add response time tracking and metrics
- Establish escalation procedures
- Enable automated assignments and status changes

---

## 1. SLA System Implementation

### 1.1 Database Schema Updates

#### SLA Rules Table
```sql
CREATE TABLE wp_mets_sla_rules (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    entity_id bigint(20) unsigned NOT NULL,
    name varchar(255) NOT NULL,
    priority varchar(50) NOT NULL,
    response_time_hours int(11) DEFAULT NULL,
    resolution_time_hours int(11) DEFAULT NULL,
    escalation_time_hours int(11) DEFAULT NULL,
    business_hours_only tinyint(1) DEFAULT 0,
    conditions longtext,
    is_active tinyint(1) DEFAULT 1,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY entity_priority (entity_id, priority),
    FOREIGN KEY (entity_id) REFERENCES wp_mets_entities(id) ON DELETE CASCADE
);
```

#### Business Hours Table
```sql
CREATE TABLE wp_mets_business_hours (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    entity_id bigint(20) unsigned,
    day_of_week tinyint(1) NOT NULL,
    start_time time NOT NULL,
    end_time time NOT NULL,
    is_active tinyint(1) DEFAULT 1,
    PRIMARY KEY (id),
    KEY entity_day (entity_id, day_of_week),
    FOREIGN KEY (entity_id) REFERENCES wp_mets_entities(id) ON DELETE CASCADE
);
```

#### SLA Tracking Updates to Tickets Table
- Add columns to existing tickets table:
  - `sla_rule_id` - Link to applicable SLA rule
  - `sla_response_due` - When first response is due
  - `sla_resolution_due` - When resolution is due
  - `sla_escalation_due` - When escalation should occur
  - `sla_response_breached` - Boolean flag
  - `sla_resolution_breached` - Boolean flag
  - `first_response_at` - Timestamp of first agent response
  - `resolved_at` - Timestamp when ticket was resolved

### 1.2 SLA Models and Logic

#### Files to Create:
- `includes/models/class-mets-sla-rule-model.php`
- `includes/models/class-mets-business-hours-model.php`
- `includes/class-mets-sla-calculator.php`

#### Core SLA Functions:
1. **SLA Rule Assignment** - Automatically assign SLA rules based on entity, priority, and conditions
2. **Due Date Calculation** - Calculate response and resolution due dates considering business hours
3. **Breach Detection** - Monitor and flag SLA breaches
4. **Escalation Triggers** - Automatically escalate tickets approaching SLA breach

### 1.3 Admin Interface for SLA Management

#### New Admin Pages:
- **SLA Rules Management** (`admin.php?page=mets-sla-rules`)
  - Create/edit SLA rules
  - Define response/resolution times
  - Set escalation procedures
  - Configure conditions and triggers

- **Business Hours Setup** (`admin.php?page=mets-business-hours`)
  - Configure business hours per entity
  - Set holidays and exceptions
  - Time zone management

---

## 2. Email Notification System

### 2.1 Email Templates System

#### Template Structure:
```
includes/email-templates/
├── ticket-created-customer.php
├── ticket-created-agent.php
├── ticket-reply-customer.php
├── ticket-reply-agent.php
├── ticket-assigned.php
├── ticket-status-changed.php
├── sla-breach-warning.php
├── sla-breach-notification.php
└── escalation-notification.php
```

#### Template Variables:
- `{{ticket_number}}`
- `{{customer_name}}`
- `{{agent_name}}`
- `{{entity_name}}`
- `{{ticket_subject}}`
- `{{ticket_content}}`
- `{{reply_content}}`
- `{{ticket_url}}`
- `{{portal_url}}`
- `{{due_date}}`
- `{{priority}}`
- `{{status}}`

### 2.2 Email Queue System

#### Queue Table:
```sql
CREATE TABLE wp_mets_email_queue (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    recipient_email varchar(255) NOT NULL,
    recipient_name varchar(255),
    subject varchar(255) NOT NULL,
    body longtext NOT NULL,
    template_name varchar(100),
    template_data longtext,
    priority tinyint(1) DEFAULT 5,
    attempts tinyint(1) DEFAULT 0,
    max_attempts tinyint(1) DEFAULT 3,
    status enum('pending','sent','failed','cancelled') DEFAULT 'pending',
    scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
    sent_at datetime,
    error_message text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY status_priority (status, priority),
    KEY scheduled_at (scheduled_at)
);
```

#### Email Processing:
- **Immediate sending** for critical notifications
- **Queue processing** via WordPress cron for bulk emails
- **Retry mechanism** for failed emails
- **Rate limiting** to prevent spam issues

### 2.3 Notification Settings

#### Admin Configuration:
- Enable/disable specific notification types
- Configure email templates per entity
- Set notification recipients (customer, agent, admin)
- SMTP configuration options
- Email frequency limits

---

## 3. Workflow Automation

### 3.1 Automation Rules Engine

#### Automation Rules Table:
```sql
CREATE TABLE wp_mets_automation_rules (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    entity_id bigint(20) unsigned,
    trigger_event varchar(100) NOT NULL,
    conditions longtext,
    actions longtext,
    is_active tinyint(1) DEFAULT 1,
    execution_order int(11) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY entity_trigger (entity_id, trigger_event),
    KEY active_order (is_active, execution_order)
);
```

#### Trigger Events:
- `ticket_created`
- `ticket_updated`
- `reply_added`
- `status_changed`
- `priority_changed`
- `assigned_to_changed`
- `sla_breach_warning`
- `sla_breach_occurred`
- `escalation_due`

#### Available Actions:
- **Assignment Actions**
  - Auto-assign to specific agent
  - Auto-assign based on workload
  - Auto-assign based on expertise/entity
  
- **Status Actions**
  - Change ticket status
  - Close ticket automatically
  - Escalate ticket
  
- **Communication Actions**
  - Send email notification
  - Add internal note
  - Send SMS (if integrated)
  
- **SLA Actions**
  - Extend SLA deadlines
  - Change SLA rule
  - Trigger escalation

### 3.2 Automation Rules Builder

#### Admin Interface Features:
- **Visual Rule Builder** - Drag-and-drop interface for creating rules
- **Condition Builder** - Complex condition logic (AND/OR operators)
- **Action Sequencer** - Define multiple actions in order
- **Testing Mode** - Test rules without executing actions
- **Rule Analytics** - Track rule execution and effectiveness

---

## 4. Response Time Tracking

### 4.1 Metrics Collection

#### Response Time Metrics:
- **First Response Time** - Time from ticket creation to first agent reply
- **Average Response Time** - Average time between customer replies and agent responses
- **Resolution Time** - Time from creation to ticket closure
- **Agent Response Time** - Individual agent response performance

#### Metrics Storage:
```sql
CREATE TABLE wp_mets_response_metrics (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    ticket_id bigint(20) unsigned NOT NULL,
    metric_type varchar(50) NOT NULL,
    start_time datetime NOT NULL,
    end_time datetime,
    duration_minutes int(11),
    business_duration_minutes int(11),
    sla_target_minutes int(11),
    within_sla tinyint(1) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ticket_metric (ticket_id, metric_type),
    FOREIGN KEY (ticket_id) REFERENCES wp_mets_tickets(id) ON DELETE CASCADE
);
```

### 4.2 Performance Dashboard

#### Key Performance Indicators (KPIs):
- **SLA Compliance Rate** - Percentage of tickets meeting SLA
- **Average Response Time** - By agent, entity, priority
- **Customer Satisfaction** - If feedback system implemented
- **Ticket Volume Trends** - Daily/weekly/monthly patterns
- **Agent Workload** - Active tickets per agent

---

## 5. Escalation System

### 5.1 Escalation Rules

#### Escalation Triggers:
- **Time-based** - Automatic escalation after X hours
- **SLA-based** - Escalate when SLA breach imminent
- **Priority-based** - High priority tickets escalate faster
- **Manual** - Agent-initiated escalation

#### Escalation Actions:
- **Notification Escalation** - Notify supervisors/managers
- **Assignment Escalation** - Reassign to senior agent
- **Priority Escalation** - Increase ticket priority
- **External Escalation** - Notify external stakeholders

### 5.2 Escalation Hierarchy

#### Configuration Options:
- **Entity-based hierarchy** - Different escalation paths per entity
- **Time-based escalation** - Multiple escalation levels over time
- **Conditional escalation** - Based on ticket properties
- **Business hours awareness** - Escalate only during business hours

---

## 6. Implementation Timeline

### Phase 4.1: Foundation (Week 1-2)
- **Database schema updates**
- **Basic SLA rule model**
- **Email template system structure**
- **Business hours management**

### Phase 4.2: SLA System (Week 3-4)
- **SLA calculator implementation**
- **Due date calculation logic**
- **SLA admin interface**
- **Basic breach detection**

### Phase 4.3: Email Notifications (Week 5-6)
- **Email queue system**
- **Template engine implementation**
- **Basic notification triggers**
- **SMTP configuration**

### Phase 4.4: Automation Engine (Week 7-8)
- **Automation rules model**
- **Trigger system implementation**
- **Action execution engine**
- **Rules builder interface**

### Phase 4.5: Response Tracking (Week 9-10)
- **Metrics collection system**
- **Performance calculations**
- **Basic reporting dashboard**
- **Agent performance tracking**

### Phase 4.6: Escalation System (Week 11-12)
- **Escalation rules engine**
- **Hierarchy management**
- **Escalation triggers**
- **Integration with SLA system**

### Phase 4.7: Testing and Polish (Week 13-14)
- **Comprehensive testing**
- **Performance optimization**
- **UI/UX improvements**
- **Documentation completion**

---

## 7. Technical Considerations

### 7.1 Performance Optimization
- **Caching strategies** for SLA calculations
- **Database indexing** for time-based queries
- **Queue processing optimization** for email system
- **Batch processing** for metrics calculation

### 7.2 Security Considerations
- **Email content sanitization**
- **Template injection prevention**
- **Queue manipulation protection**
- **SLA rule access control**

### 7.3 Scalability Planning
- **Email queue partitioning** for high volume
- **Metric data archiving** strategies
- **Rule execution optimization**
- **Database growth management**

---

## 8. Integration Points

### 8.1 Existing System Integration
- **Ticket model updates** for SLA tracking
- **User permission integration** for escalation
- **Entity system integration** for SLA rules
- **Settings system extension** for configuration

### 8.2 External Integration Preparation
- **Webhook system** for external notifications
- **API endpoints** for SLA data access
- **Export functionality** for metrics
- **Third-party service hooks** (SMS, etc.)

---

## 9. Success Metrics

### 9.1 System Performance
- **SLA compliance rate** > 95%
- **Email delivery rate** > 99%
- **Automation rule execution** < 5 seconds
- **Response time calculation** < 1 second

### 9.2 User Experience
- **Reduced manual assignment** by 80%
- **Faster first response** by 50%
- **Improved agent efficiency** by 30%
- **Enhanced customer satisfaction**

---

## 10. Risk Assessment

### 10.1 Technical Risks
- **Email delivery issues** - Mitigation: Multiple delivery methods
- **SLA calculation complexity** - Mitigation: Thorough testing
- **Performance impact** - Mitigation: Caching and optimization
- **Data integrity** - Mitigation: Transaction handling

### 10.2 Business Risks
- **Over-automation** - Mitigation: Manual override options
- **SLA conflicts** - Mitigation: Clear rule precedence
- **Email spam issues** - Mitigation: Rate limiting
- **Agent resistance** - Mitigation: Training and gradual rollout

---

This comprehensive plan provides the foundation for implementing a professional-grade SLA, notification, and automation system that will significantly enhance the Multi-Entity Ticket System's capabilities and user experience.