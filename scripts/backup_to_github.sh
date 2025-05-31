#!/bin/bash

# Backup Autism Waiver Management System to GitHub
# Repository: http://github.com/scrive-aci.git

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting GitHub Backup for Autism Waiver Management System...${NC}"

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo -e "${RED}Error: Git is not installed. Please install git first.${NC}"
    exit 1
fi

# Create a clean backup directory
BACKUP_DIR="github_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo -e "${YELLOW}Step 1: Creating clean repository structure...${NC}"

# Copy essential files for deployment
cp -r autism_waiver_app "$BACKUP_DIR/"
cp -r sql "$BACKUP_DIR/"
cp -r src "$BACKUP_DIR/"
cp -r apache "$BACKUP_DIR/" 2>/dev/null || echo "Apache config not found, skipping"

# Copy configuration files
cp docker-compose.yml "$BACKUP_DIR/" 2>/dev/null || echo "docker-compose.yml not found, skipping"
cp Dockerfile "$BACKUP_DIR/" 2>/dev/null || echo "Dockerfile not found, skipping"
cp .env.example "$BACKUP_DIR/" 2>/dev/null || echo ".env.example not found, skipping"

# Copy documentation
cp *.md "$BACKUP_DIR/" 2>/dev/null || echo "Markdown files not found, skipping"

# Create .gitignore for the repository
cat > "$BACKUP_DIR/.gitignore" << 'EOF'
# Environment files
.env
.env.local
.env.production

# Sensitive data
*.csv
*.db
*.sql.backup

# Logs
*.log
error_log
access_log

# Temporary files
*.tmp
*.temp

# IDE files
.vscode/
.idea/
*.swp
*.swo

# OS files
.DS_Store
Thumbs.db

# Backup files
backups/
*.backup
*.bak

# Session files
/tmp/sess_*

# Cache
cache/
*.cache
EOF

# Create README for GitHub
cat > "$BACKUP_DIR/README.md" << 'EOF'
# Autism Waiver Management System

A comprehensive management system for autism waiver services, developed for American Caregivers Inc.

## Quick Start

### Immediate Testing (No Database Required)
1. Upload files to your web server
2. Navigate to `/autism_waiver_app/simple_login.php`
3. Login with: `admin` / `AdminPass123!`

### Full Production Setup
1. Set environment variables (see `.env.example`)
2. Import SQL schemas from `/sql/` directory
3. Use `/autism_waiver_app/login.php` for full features

## Features

- **Clinical Documentation**: IISS session notes and treatment plans
- **Scheduling Management**: Visual calendar with appointment tracking
- **Financial Integration**: Billing and claims management structure
- **Role-Based Access**: 5-tier permission system

## Login Credentials (Testing)

| Username | Password | Role |
|----------|----------|------|
| `admin` | `AdminPass123!` | Administrator |
| `dsp_test` | `TestPass123!` | DSP Staff |
| `cm_test` | `TestPass123!` | Case Manager |
| `supervisor_test` | `TestPass123!` | Supervisor |

## Documentation

- `QUICK_START.md` - Immediate setup guide
- `DEPLOYMENT_GUIDE.md` - Full deployment instructions
- `TESTING_SCENARIOS.md` - Testing procedures
- `SYSTEM_ARCHITECTURE.md` - Technical overview

## Support

For technical issues or questions, refer to the documentation files included in this repository.

---

**Note**: This system is designed for HIPAA-compliant autism waiver services in Maryland.
EOF

echo -e "${YELLOW}Step 2: Initializing git repository...${NC}"

cd "$BACKUP_DIR"

# Initialize git repository
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial commit: Autism Waiver Management System

- Complete clinical documentation system
- IISS session notes and treatment plans
- Scheduling and resource management
- Financial/billing integration structure
- Role-based access control (5 levels)
- Simple login system for immediate testing
- Full database integration support

Deployment ready for aci.dydact.io"

echo -e "${YELLOW}Step 3: Adding GitHub remote...${NC}"

# Add GitHub remote
git remote add origin https://github.com/scrive-aci.git

echo -e "${YELLOW}Step 4: Pushing to GitHub...${NC}"

# Push to GitHub
git branch -M main
git push -u origin main

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Successfully backed up to GitHub!${NC}"
    echo -e "${GREEN}Repository URL: https://github.com/scrive-aci${NC}"
else
    echo -e "${RED}✗ Error pushing to GitHub. You may need to:${NC}"
    echo -e "${YELLOW}1. Create the repository on GitHub first${NC}"
    echo -e "${YELLOW}2. Set up authentication (SSH key or token)${NC}"
    echo -e "${YELLOW}3. Run these commands manually:${NC}"
    echo ""
    echo "cd $BACKUP_DIR"
    echo "git remote set-url origin https://github.com/scrive-aci.git"
    echo "git push -u origin main"
fi

cd ..

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}Backup Process Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "\nLocal backup directory: ${YELLOW}$BACKUP_DIR${NC}"
echo -e "GitHub repository: ${YELLOW}https://github.com/scrive-aci${NC}"

echo -e "\n${YELLOW}Next Steps:${NC}"
echo -e "1. Clone the repository on your server:"
echo -e "   ${GREEN}git clone https://github.com/scrive-aci.git${NC}"
echo -e "2. Set up web server to point to the cloned directory"
echo -e "3. Test login at: ${GREEN}/autism_waiver_app/simple_login.php${NC}"
echo -e "4. Use credentials: ${GREEN}admin / AdminPass123!${NC}"

echo -e "\n${YELLOW}Login Troubleshooting:${NC}"
echo -e "If login doesn't work on the server:"
echo -e "1. Check PHP session configuration"
echo -e "2. Verify file permissions (755 for directories, 644 for files)"
echo -e "3. Check Apache/Nginx error logs"
echo -e "4. Try accessing simple_dashboard.php directly"
EOF