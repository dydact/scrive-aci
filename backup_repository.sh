#!/bin/bash

# Autism Waiver Management System - Backup Script
# This script creates a comprehensive backup of the repository before deployment

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="autism_waiver_backup_${TIMESTAMP}"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo -e "${GREEN}Starting Autism Waiver Management System Backup...${NC}"
echo "Backup name: $BACKUP_NAME"

# Step 1: Create SQL dump of database structure and data
echo -e "\n${YELLOW}Step 1: Backing up database...${NC}"
if command -v mysqldump &> /dev/null; then
    # Get database credentials from environment or use defaults
    DB_NAME=${MARIADB_DATABASE:-iris}
    DB_USER=${MARIADB_USER:-iris_user}
    DB_HOST=${MARIADB_HOST:-localhost}
    
    # Prompt for password if not in environment
    if [ -z "$MARIADB_PASSWORD" ]; then
        echo -n "Enter MySQL/MariaDB password for $DB_USER: "
        read -s DB_PASS
        echo
    else
        DB_PASS=$MARIADB_PASSWORD
    fi
    
    # Create database backup
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
        --databases "$DB_NAME" \
        --routines \
        --triggers \
        --single-transaction \
        --complete-insert \
        --extended-insert \
        --lock-tables=false \
        > "$BACKUP_DIR/${BACKUP_NAME}_database.sql" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Database backup completed${NC}"
    else
        echo -e "${RED}✗ Database backup failed (continuing with file backup)${NC}"
    fi
else
    echo -e "${YELLOW}⚠ mysqldump not found, skipping database backup${NC}"
fi

# Step 2: Create file inventory
echo -e "\n${YELLOW}Step 2: Creating file inventory...${NC}"
cat > "$BACKUP_DIR/${BACKUP_NAME}_inventory.txt" << EOF
Autism Waiver Management System Backup
Created: $(date)
System Version: 1.0

CONTENTS:
=========

1. Application Files:
   - autism_waiver_app/ (Complete application directory)
   - src/ (Dashboard and core files)
   - sql/ (Database schemas)

2. Configuration Files:
   - .env files (if present)
   - Apache configuration
   - Docker configuration

3. Documentation:
   - README files
   - Deployment guides
   - System documentation

4. Database:
   - Complete database dump (if available)
   - Table structures
   - Stored procedures
   - Views

FILE LIST:
==========
EOF

# Add file listing to inventory
find . -type f \( -name "*.php" -o -name "*.sql" -o -name "*.md" -o -name "*.conf" \) \
    -not -path "./backups/*" \
    -not -path "./.git/*" \
    | sort >> "$BACKUP_DIR/${BACKUP_NAME}_inventory.txt"

echo -e "${GREEN}✓ File inventory created${NC}"

# Step 3: Create application backup archive
echo -e "\n${YELLOW}Step 3: Creating application backup archive...${NC}"

# Files and directories to backup
BACKUP_ITEMS=(
    "autism_waiver_app"
    "src"
    "sql"
    "apache"
    "docker-compose.yml"
    "Dockerfile"
    ".env.example"
    "*.md"
)

# Create tar archive
tar -czf "$BACKUP_DIR/${BACKUP_NAME}.tar.gz" \
    --exclude="backups" \
    --exclude=".git" \
    --exclude="*.csv" \
    --exclude="*.log" \
    --exclude="node_modules" \
    --exclude="vendor" \
    ${BACKUP_ITEMS[@]} 2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Application backup archive created${NC}"
else
    echo -e "${RED}✗ Some files may not have been included in the archive${NC}"
fi

# Step 4: Create deployment checklist
echo -e "\n${YELLOW}Step 4: Creating deployment checklist...${NC}"
cat > "$BACKUP_DIR/${BACKUP_NAME}_checklist.md" << 'EOF'
# Pre-Deployment Checklist

## Before Deployment:
- [ ] Database backup completed
- [ ] Application files backed up
- [ ] Environment variables documented
- [ ] Current system state documented
- [ ] Rollback plan prepared

## During Deployment:
- [ ] Set environment variables
- [ ] Import database schemas
- [ ] Deploy application files
- [ ] Set file permissions
- [ ] Configure Apache/Nginx
- [ ] Remove sensitive files (CSV, etc.)

## After Deployment:
- [ ] Test login functionality
- [ ] Verify database connections
- [ ] Test IISS session notes
- [ ] Test treatment plan manager
- [ ] Test schedule manager
- [ ] Check error logs
- [ ] Verify billing integration

## Rollback Steps (if needed):
1. Restore database from backup
2. Restore application files
3. Clear cache/sessions
4. Restart web server
5. Verify system functionality

## Key URLs to Test:
- Login: /autism_waiver_app/login.php
- Dashboard: /src/dashboard.php
- IISS Notes: /autism_waiver_app/iiss_session_note.php
- Treatment Plans: /autism_waiver_app/treatment_plan_manager.php
- Schedule: /autism_waiver_app/schedule_manager.php
EOF

echo -e "${GREEN}✓ Deployment checklist created${NC}"

# Step 5: Generate backup summary
echo -e "\n${YELLOW}Step 5: Generating backup summary...${NC}"

# Calculate sizes
if [ -f "$BACKUP_DIR/${BACKUP_NAME}.tar.gz" ]; then
    APP_SIZE=$(ls -lh "$BACKUP_DIR/${BACKUP_NAME}.tar.gz" | awk '{print $5}')
else
    APP_SIZE="N/A"
fi

if [ -f "$BACKUP_DIR/${BACKUP_NAME}_database.sql" ]; then
    DB_SIZE=$(ls -lh "$BACKUP_DIR/${BACKUP_NAME}_database.sql" | awk '{print $5}')
else
    DB_SIZE="N/A"
fi

cat > "$BACKUP_DIR/${BACKUP_NAME}_summary.txt" << EOF
BACKUP SUMMARY
==============
Timestamp: $(date)
Backup Name: $BACKUP_NAME

Files Created:
- ${BACKUP_NAME}.tar.gz (Application: $APP_SIZE)
- ${BACKUP_NAME}_database.sql (Database: $DB_SIZE)
- ${BACKUP_NAME}_inventory.txt
- ${BACKUP_NAME}_checklist.md
- ${BACKUP_NAME}_summary.txt

Total Files Backed Up: $(find . -type f -not -path "./backups/*" -not -path "./.git/*" | wc -l)

Next Steps:
1. Verify backup integrity
2. Copy backups to secure location
3. Proceed with deployment using DEPLOYMENT_GUIDE.md
4. Use checklist for deployment verification

Restore Command Examples:
- Database: mysql -u root -p iris < ${BACKUP_NAME}_database.sql
- Files: tar -xzf ${BACKUP_NAME}.tar.gz -C /target/directory/
EOF

echo -e "${GREEN}✓ Backup summary created${NC}"

# Step 6: Create quick restore script
echo -e "\n${YELLOW}Step 6: Creating restore script...${NC}"
cat > "$BACKUP_DIR/restore_${BACKUP_NAME}.sh" << EOF
#!/bin/bash
# Quick restore script for backup: $BACKUP_NAME

echo "This script will restore the system from backup: $BACKUP_NAME"
echo "WARNING: This will overwrite current files and database!"
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "\$CONFIRM" != "yes" ]; then
    echo "Restore cancelled."
    exit 1
fi

# Restore database
if [ -f "${BACKUP_NAME}_database.sql" ]; then
    echo "Restoring database..."
    mysql -u root -p iris < ${BACKUP_NAME}_database.sql
    echo "Database restored."
else
    echo "Database backup not found, skipping database restore."
fi

# Restore files
if [ -f "${BACKUP_NAME}.tar.gz" ]; then
    echo "Restoring application files..."
    read -p "Enter target directory (e.g., /var/www/html/aci.dydact.io): " TARGET_DIR
    tar -xzf ${BACKUP_NAME}.tar.gz -C "\$TARGET_DIR"
    echo "Files restored to \$TARGET_DIR"
else
    echo "Application backup not found!"
    exit 1
fi

echo "Restore completed. Please restart your web server and test the application."
EOF

chmod +x "$BACKUP_DIR/restore_${BACKUP_NAME}.sh"
echo -e "${GREEN}✓ Restore script created${NC}"

# Final summary
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}Backup completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "\nBackup location: ${YELLOW}$BACKUP_DIR/$BACKUP_NAME${NC}"
echo -e "\nFiles created:"
ls -la "$BACKUP_DIR/${BACKUP_NAME}"* | grep -v total

echo -e "\n${YELLOW}IMPORTANT: Please copy these backup files to a secure location before proceeding with deployment.${NC}"
echo -e "\nTo proceed with deployment, follow the instructions in ${GREEN}DEPLOYMENT_GUIDE.md${NC}"