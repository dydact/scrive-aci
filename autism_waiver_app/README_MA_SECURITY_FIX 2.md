# 🔐 CRITICAL SECURITY FIX: MA Number Access Control

## American Caregivers Incorporated - Autism Waiver System

**Date:** January 2025  
**Priority:** CRITICAL  
**Status:** IMPLEMENTED  

---

## 🚨 SECURITY ISSUE IDENTIFIED

### **Problem Description**
The autism waiver system was incorrectly displaying **organizational billing MA numbers** to all staff members. These numbers are American Caregivers' confidential billing identification numbers and should only be visible to administrators.

### **Impact**
- **HIGH RISK:** Internal billing information exposed to unauthorized staff
- **Compliance Issue:** Potential violation of internal security policies
- **Privacy Concern:** Confusion between organizational and individual client data

---

## 🔍 MA NUMBER TYPES - EXPLAINED

### **🏢 Organizational MA Numbers (ADMIN ONLY)**
These are **American Caregivers' billing identification numbers** for each program:
- **AW Program:** `410608300`
- **DDA Program:** `410608301` 
- **CFC Program:** `522902200`
- **CS Program:** `433226100`

**Purpose:** Used by American Caregivers to bill insurance/state for services  
**Access:** Administrators ONLY  
**Risk Level:** HIGH - Internal billing information  

### **👤 Individual Client MA Numbers**
These are **each client's personal Medical Assistance numbers** (like SSN):
- **Emma Rodriguez:** `MA123456789`
- **Michael Johnson:** `MA987654321`
- **Aiden Chen:** `MA555888999`

**Purpose:** Client's personal medical assistance identification  
**Access:** Staff with appropriate permissions  
**Risk Level:** MEDIUM - Personal client information  

---

## 🛡️ IMPLEMENTED SOLUTION

### **1. Role-Based Access Control**
Created 5-tier staff role system:

| Role | Level | Org MA Access | Client MA Access | Other Permissions |
|------|-------|---------------|------------------|-------------------|
| **Administrator** | 5 | ✅ YES | ✅ YES | Full system access |
| **Supervisor** | 4 | ❌ NO | ✅ YES | Billing reports, authorizations |
| **Case Manager** | 3 | ❌ NO | ✅ YES | Client data, scheduling |
| **Direct Care Staff** | 2 | ❌ NO | ✅ YES | View-only client data |
| **Technician** | 1 | ❌ NO | ❌ NO | Session documentation only |

### **2. Database Separation**
- **`autism_org_ma_numbers`** - Organizational billing numbers (secured)
- **`autism_client_enrollments.ma_number`** - Individual client MA numbers
- **`autism_staff_roles`** - Role definitions with permissions
- **`autism_user_roles`** - User-role assignments
- **`autism_security_log`** - Audit trail of access attempts

### **3. API Security**
- **Role validation** before data access
- **Separate endpoints** for organizational vs client MA numbers
- **Security logging** of all access attempts
- **Error responses** that don't leak sensitive data

---

## 📁 FILES UPDATED

### **Security Implementation**
- `auth_helper.php` - Role-based authentication system
- `secure_api.php` - API with proper access controls
- `secure_clients.php` - Client interface with role-based visibility

### **Database Schema**
```sql
-- Role-based permissions
CREATE TABLE autism_staff_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_level INT NOT NULL DEFAULT 1,
    can_view_org_ma_numbers BOOLEAN DEFAULT FALSE,
    can_view_client_ma_numbers BOOLEAN DEFAULT TRUE,
    -- ... other permissions
);

-- Organizational MA numbers (ADMIN ONLY)
CREATE TABLE autism_org_ma_numbers (
    org_ma_id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    ma_number VARCHAR(20) NOT NULL, -- American Caregivers billing numbers
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Individual client MA numbers
ALTER TABLE autism_client_enrollments 
MODIFY COLUMN ma_number VARCHAR(20) NULL 
COMMENT 'Individual client MA number (like SSN)';
```

---

## 🧪 TESTING & VERIFICATION

### **Test Cases**
1. **Administrator Access**
   - ✅ Can view organizational MA numbers
   - ✅ Can view client MA numbers
   - ✅ Can manage all system functions

2. **Case Manager Access**
   - ❌ Cannot view organizational MA numbers
   - ✅ Can view/edit client MA numbers
   - ✅ Can schedule sessions

3. **Direct Care Staff Access**
   - ❌ Cannot view organizational MA numbers
   - ✅ Can view client MA numbers (read-only)
   - ❌ Cannot edit client data

4. **Technician Access**
   - ❌ Cannot view organizational MA numbers
   - ❌ Cannot view client MA numbers
   - ✅ Can document sessions only

### **Security Audit**
All access attempts are logged in `autism_security_log`:
```sql
SELECT 
    u.username,
    sl.action,
    sl.resource,
    sl.result,
    sl.timestamp
FROM autism_security_log sl
JOIN users u ON sl.user_id = u.id
WHERE sl.resource LIKE '%ma%'
ORDER BY sl.timestamp DESC;
```

---

## 🎯 COMPLIANCE & NEXT STEPS

### **Immediate Actions Completed**
- ✅ Organizational MA numbers secured from non-admin staff
- ✅ Role-based access control implemented
- ✅ Security audit logging enabled
- ✅ Staff interfaces updated with proper permissions

### **Ongoing Monitoring**
- 📊 Regular review of security logs
- 🔍 Quarterly access permission audits
- 📋 Staff training on data access levels
- 🛡️ Continuous security assessment

### **Future Enhancements**
- Two-factor authentication for administrators
- Data encryption for sensitive fields
- Advanced audit reporting dashboard
- Integration with OpenEMR's existing security framework

---

## 📞 SUPPORT & CONTACTS

### **For Technical Issues**
- **System Administrator:** Contact OpenEMR admin
- **Database Issues:** Check error logs in `autism_security_log`
- **Permission Problems:** Review user role assignments

### **For Role Changes**
- **Administrator Access Required:** Contact your system administrator
- **Role Assignments:** Only administrators can modify user roles
- **Permission Requests:** Submit formal request with justification

---

## 📋 QUICK REFERENCE

### **Check User Permissions**
```php
// Get current user permissions
$permissions = getUserPermissions($userId);

// Check specific permission
$canViewOrgMA = checkPermission($userId, 'can_view_org_ma_numbers');
```

### **Access Organizational MA Numbers**
```bash
# Only for administrators
GET /secure_api.php?endpoint=org_ma_numbers
```

### **Access Client MA Numbers**
```bash
# For authorized staff
GET /secure_api.php?endpoint=clients
```

### **View Security Log**
```bash
# For administrators
GET /secure_api.php?endpoint=security_log
```

---

**⚠️ IMPORTANT:** This security fix addresses a critical data exposure issue. All staff should be aware of the distinction between organizational billing numbers and individual client MA numbers. Access is now properly controlled based on job role and security clearance level. 