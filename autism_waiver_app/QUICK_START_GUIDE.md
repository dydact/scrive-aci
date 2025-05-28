# 🚀 Quick Start Guide - Role-Based Portal System

## **5-Minute Setup & Demo**

### **Step 1: Access the Portal Router**
```
http://localhost:8081/autism_waiver_app/portal_router.php
```

### **Step 2: Setup Demo Data**
1. Click **"🎯 Setup Demo Data"** button
2. Confirm to create treatment plans for Emma & Michael
3. Wait for success confirmation

### **Step 3: Test Employee Portal**
1. Select **"🤝 Direct Care Staff (Level 2)"** from dropdown
2. Click **"Employee Portal"** card
3. Experience the auto-population workflow:
   - Select "Emma Rodriguez" from client list
   - Watch treatment goals auto-populate
   - Fill out quick session note
   - See time tracking in action

### **Step 4: Test Security System**
```
http://localhost:8081/autism_waiver_app/secure_clients.php
```
- See role-based MA number separation
- Organizational numbers hidden from non-admin staff
- Individual client MA numbers properly displayed

## **🎭 Role Testing**

### **Available Roles:**
- **👑 Administrator (Level 5)** - Access to all portals
- **👥 Supervisor (Level 4)** - Supervisor + Case Manager + Employee
- **📋 Case Manager (Level 3)** - Case Manager + Employee  
- **🤝 Direct Care Staff (Level 2)** - Employee Portal ONLY ✅
- **🔧 Technician (Level 1)** - Employee Portal ONLY ✅

### **Key Files to Test:**
1. `portal_router.php` - Role-based portal selection
2. `employee_portal.php` - Full-featured employee interface
3. `treatment_plan_api.php?endpoint=client_goals&client_id=1` - API test
4. `secure_clients.php` - Security demonstration

## **🎯 Core Features Demonstrated**

### **✅ Employee Portal:**
- Auto-populated treatment goals
- Time tracking with payroll summary
- Session note templates
- Progress rating system

### **✅ Security System:**
- Role-based access control
- MA number separation (org vs individual)
- Permission validation
- Audit logging

### **✅ Treatment Plan Integration:**
- Goal auto-population from database
- Progress tracking over time
- Client-specific objectives
- Visual progress indicators

## **💡 What to Focus On**

1. **Efficiency Gain:** Notice how selecting a client immediately loads their specific treatment goals
2. **Role Security:** Test different roles to see portal access changes
3. **Time Tracking:** Real-time clock with payroll calculations
4. **Mobile Ready:** Test on tablet/phone for field use

## **🔗 Next Steps**

1. **QuickBooks Integration Planning** - API research
2. **Case Manager Portal Development** - Treatment plan builder
3. **Mobile App Optimization** - Native iOS/Android
4. **Advanced Reporting** - Progress analytics

**The Employee Portal is production-ready for immediate deployment!** 