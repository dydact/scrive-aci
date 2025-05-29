# Autism Waiver Management System - Architecture Overview

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        User Interface Layer                       │
├─────────────────────────┬───────────────────┬──────────────────┤
│   Clinical Module       │ Scheduling Module │ Billing Module   │
│  • IISS Notes          │  • Calendar View  │ • Claims Mgmt    │
│  • Treatment Plans     │  • Appointments   │ • Prior Auth     │
│  • Goal Tracking       │  • Resources      │ • EDI Interface  │
├─────────────────────────┴───────────────────┴──────────────────┤
│                     Application Logic Layer                       │
│  • Session Management  • Role-Based Access  • Data Validation   │
├─────────────────────────────────────────────────────────────────┤
│                      Database Abstraction                         │
│              PDO MySQL  •  Prepared Statements                   │
├─────────────────────────────────────────────────────────────────┤
│                     MariaDB Database Layer                        │
│  • Clinical Tables  • Scheduling Tables  • Financial Tables      │
└─────────────────────────────────────────────────────────────────┘
```

## Module Overview

### 1. Clinical Documentation Module

**Purpose**: Manage autism-specific clinical documentation without full medical complexity

**Key Components**:
- `iiss_session_note.php` - Document IISS service sessions
- `treatment_plan_manager.php` - Create and manage treatment plans
- Goal progress tracking with 1-5 scale ratings
- Behavior incident reporting

**Database Tables**:
- `autism_iiss_notes` - Session documentation
- `autism_treatment_plans` - Client treatment plans
- `autism_treatment_goals` - Specific goals and objectives
- `autism_goal_progress` - Progress tracking

### 2. Scheduling & Resource Module

**Purpose**: Advanced scheduling with resource management

**Key Components**:
- `schedule_manager.php` - Visual weekly calendar interface
- Recurring appointment templates
- Staff availability management
- Resource/room booking

**Database Tables**:
- `autism_appointments` - Individual appointments
- `autism_schedule_templates` - Recurring patterns
- `autism_staff_shifts` - Staff scheduling
- `autism_resources` - Rooms and equipment

### 3. Financial & Billing Module

**Purpose**: Comprehensive billing with EDI integration structure

**Key Components**:
- Insurance management
- Prior authorization tracking
- Claims processing
- EDI 837/835 transaction framework

**Database Tables**:
- `autism_claims` - Claim management
- `autism_insurance_info` - Client insurance
- `autism_prior_authorizations` - Auth tracking
- `autism_edi_transactions` - EDI processing

## Security Architecture

### Authentication & Authorization
- 5-tier role-based access control
- Session-based authentication
- Prepared statements for SQL injection prevention
- XSS protection through output escaping

### Data Protection
- Environment-based configuration
- No hardcoded credentials
- Sensitive data encryption ready
- HIPAA-compliant audit logging

## Integration Points

### Current Integrations
1. **Time Clock System**
   - Links to `autism_billing_entries`
   - Automatic billing entry creation from sessions

2. **Session to Billing**
   - IISS notes create billing entries
   - Treatment goals link to progress tracking

### Future Integration Points
1. **Maryland Medicaid EVS**
   - Real-time eligibility verification
   - Prior authorization checking

2. **EDI Gateway**
   - 837 Professional claim submission
   - 835 Remittance processing

3. **Communication Features**
   - Secure messaging API
   - SMS/Email notifications
   - Telehealth platform

## Performance Considerations

### Database Optimization
- Indexed foreign keys for fast lookups
- Generated columns for calculated values
- Views for complex reporting queries
- Stored procedures for recurring operations

### Application Performance
- Lazy loading of related data
- Pagination for large datasets
- Client-side caching for static data
- Minimal external dependencies

## Scalability Plan

### Phase 1 (Current)
- Single server deployment
- Suitable for 50-100 concurrent users
- Basic caching through PHP sessions

### Phase 2 (Future)
- Load balanced web servers
- Read replica database
- Redis session storage
- CDN for static assets

### Phase 3 (Long-term)
- Microservices architecture
- API-first design
- Container orchestration
- Multi-tenant support

## Development Standards

### Code Organization
```
/autism_waiver_app/
├── auth_helper.php        # Authentication functions
├── config.php            # Configuration loader
├── [module]_[action].php # Module-specific files
└── api/                  # API endpoints (future)
```

### Naming Conventions
- Tables: `autism_[entity_name]`
- Foreign keys: `fk_[table]_[reference]`
- Indexes: `idx_[columns]`
- Views: `v_[purpose]`

### Coding Standards
- PSR-12 PHP coding standard
- Consistent error handling
- Comprehensive input validation
- Clear variable naming

## Deployment Architecture

### Production Environment
```
┌─────────────────┐     ┌──────────────────┐
│   CloudFlare    │────▶│  Apache Server   │
│      (CDN)      │     │   aci.dydact.io  │
└─────────────────┘     └────────┬─────────┘
                                 │
                        ┌────────▼─────────┐
                        │   PHP 7.4+       │
                        │  Application     │
                        └────────┬─────────┘
                                 │
                        ┌────────▼─────────┐
                        │  MariaDB 10.5+   │
                        │   Database       │
                        └──────────────────┘
```

## Monitoring & Maintenance

### Application Monitoring
- Error logging to system logs
- Performance metrics collection
- User activity tracking
- Resource utilization monitoring

### Database Maintenance
- Daily automated backups
- Weekly optimization runs
- Monthly audit log archival
- Quarterly performance review

## Compliance & Regulations

### HIPAA Compliance
- Encrypted data transmission (HTTPS)
- Access controls and audit logs
- Data retention policies
- Business Associate Agreements

### Maryland Medicaid Requirements
- Service documentation standards
- Billing code compliance
- Prior authorization workflows
- Timely filing requirements

---

Version: 1.0
Last Updated: December 2024