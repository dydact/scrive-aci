# Quick Start Guide - Immediate Access

## 🚀 Login Right Now (No Database Setup Required)

I've created a simple login system that works immediately without any database setup.

### 📱 Access URLs

1. **Simple Login**: `https://aci.dydact.io/autism_waiver_app/simple_login.php`
2. **Simple Dashboard**: `https://aci.dydact.io/autism_waiver_app/simple_dashboard.php`

### 🔐 Login Credentials (Ready to Use)

| Username | Password | Role | What You Can Do |
|----------|----------|------|-----------------|
| `admin` | `AdminPass123!` | **Administrator** | Full system access |
| `dsp_test` | `TestPass123!` | **DSP Staff** | Create session notes |
| `cm_test` | `TestPass123!` | **Case Manager** | Treatment plans, all clients |
| `supervisor_test` | `TestPass123!` | **Supervisor** | Staff management, reports |

## 🎯 How to Test Right Now

### Step 1: Login
1. Go to: `https://aci.dydact.io/autism_waiver_app/simple_login.php`
2. Use `admin` / `AdminPass123!`
3. Click "Sign In"

### Step 2: Explore Dashboard
- You'll see the main dashboard with module cards
- Each card shows what you can access based on your role
- Statistics show current system status

### Step 3: Test Core Features

#### 📝 IISS Session Notes
- Click "Create Session Note" from dashboard
- Or go directly to: `/autism_waiver_app/iiss_session_note.php`
- **Note**: This will work with test data if database isn't set up

#### 📋 Treatment Plans
- Click "Treatment Plans" from dashboard  
- Or go to: `/autism_waiver_app/treatment_plan_manager.php`
- Create and manage treatment goals

#### 📅 Schedule Manager
- Click "View Schedule" from dashboard
- Or go to: `/autism_waiver_app/schedule_manager.php`
- Visual weekly calendar interface

## 🔄 Two Login Systems Available

### Option 1: Simple Login (Works Immediately)
- **File**: `simple_login.php`
- **No database required**
- **Hardcoded test accounts**
- **Perfect for immediate testing**

### Option 2: Full Database Login (Production Ready)
- **File**: `login.php` 
- **Requires database setup**
- **User management system**
- **For production deployment**

## 🛠 If You Want Full Database Features

1. **Set up environment variables**:
   ```bash
   export MARIADB_DATABASE=iris
   export MARIADB_USER=iris_user
   export MARIADB_PASSWORD=your_password
   export MARIADB_HOST=localhost
   ```

2. **Import database schemas**:
   ```bash
   mysql -u root -p iris < sql/clinical_documentation_system.sql
   mysql -u root -p iris < sql/scheduling_resource_management.sql
   mysql -u root -p iris < sql/financial_billing_enhancement.sql
   mysql -u root -p iris < sql/create_admin_user.sql
   ```

3. **Use the full login system**: `/autism_waiver_app/login.php`

## 🎮 Test Scenarios

### As Administrator (`admin`)
- See all dashboard modules
- Access all features
- View system statistics

### As DSP Staff (`dsp_test`)
- Create IISS session notes
- View assigned clients
- Limited dashboard access

### As Case Manager (`cm_test`)
- Everything DSP can do
- Plus treatment plan management
- Client progress tracking

### As Supervisor (`supervisor_test`)
- Everything Case Manager can do
- Plus staff management
- Scheduling oversight

## 📋 Test Data Available

The simple system includes:
- **3 test clients** (John Doe, Emily Smith, Michael Johnson)
- **3 service types** (IISS, Therapeutic Integration, Respite)
- **4 user accounts** with different access levels

## 🔧 Files for Immediate Testing

| File | Purpose |
|------|---------|
| `simple_login.php` | Hardcoded login (works now) |
| `simple_dashboard.php` | Main dashboard interface |
| `simple_auth_helper.php` | Authentication functions |
| `iiss_session_note.php` | Session documentation |
| `treatment_plan_manager.php` | Treatment plans |
| `schedule_manager.php` | Calendar interface |

## 🚨 Important Notes

1. **The simple login is for testing only** - change passwords in production
2. **Database features will use test data** if database isn't connected
3. **All major features are accessible** through the simple system
4. **Session timeout is 24+ hours** for extended testing

## 📞 Quick Support

If you have issues:
1. Try the simple login first: `simple_login.php`
2. Check that PHP sessions are working
3. Verify file permissions on the server
4. Check Apache error logs

---

**Bottom Line**: You can start testing immediately with `simple_login.php` using `admin` / `AdminPass123!` 🎉