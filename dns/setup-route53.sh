#!/bin/bash

# AWS Route 53 Setup Script for ACI.DYDACT.IO
# This script configures DNS records for American Caregivers Inc

echo "Setting up AWS Route 53 for aci.dydact.io"

# Configuration variables
DOMAIN="aci.dydact.io"
HOSTED_ZONE_ID="Z004717721ORURY4RNWKT"  # Your dydact.io hosted zone ID
STATIC_IP="71.244.253.183"              # Your Verizon FiOS static IP

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    echo "AWS CLI is not installed. Please install it first:"
    echo "https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html"
    exit 1
fi

# Check if required variables are set
if [ -z "$HOSTED_ZONE_ID" ] || [ -z "$STATIC_IP" ]; then
    echo "Please edit this script and set HOSTED_ZONE_ID and STATIC_IP variables"
    echo "HOSTED_ZONE_ID: Your Route 53 hosted zone ID for dydact.io"
    echo "STATIC_IP: Your Verizon FiOS business static IP address"
    exit 1
fi

# Function to create/update DNS record
update_dns_record() {
    local name=$1
    local type=$2
    local value=$3
    local ttl=${4:-300}
    
    echo "Updating $type record for $name..."
    
    cat > /tmp/dns-record.json <<EOF
{
    "Comment": "Update $name record",
    "Changes": [
        {
            "Action": "UPSERT",
            "ResourceRecordSet": {
                "Name": "$name",
                "Type": "$type",
                "TTL": $ttl,
                "ResourceRecords": [
                    {
                        "Value": "$value"
                    }
                ]
            }
        }
    ]
}
EOF

    aws route53 change-resource-record-sets \
        --hosted-zone-id "$HOSTED_ZONE_ID" \
        --change-batch file:///tmp/dns-record.json
    
    if [ $? -eq 0 ]; then
        echo "✓ Successfully updated $name"
    else
        echo "✗ Failed to update $name"
    fi
}

# Create main A record
update_dns_record "$DOMAIN" "A" "$STATIC_IP"

# Create CNAME records for subdomains
update_dns_record "www.$DOMAIN" "CNAME" "$DOMAIN"
update_dns_record "staff.$DOMAIN" "CNAME" "$DOMAIN"
update_dns_record "admin.$DOMAIN" "CNAME" "$DOMAIN"
update_dns_record "api.$DOMAIN" "CNAME" "$DOMAIN"

# Clean up temporary file
rm -f /tmp/dns-record.json

echo ""
echo "DNS setup complete! Records created:"
echo "  aci.dydact.io → $STATIC_IP"
echo "  www.aci.dydact.io → aci.dydact.io"
echo "  staff.aci.dydact.io → aci.dydact.io"
echo "  admin.aci.dydact.io → aci.dydact.io"
echo "  api.aci.dydact.io → aci.dydact.io"
echo ""
echo "Please allow 5-10 minutes for DNS propagation."
echo "You can test with: dig aci.dydact.io"