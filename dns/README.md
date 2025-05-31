# AWS Route 53 Configuration for ACI.DYDACT.IO

This directory contains the DNS configuration files for setting up American Caregivers Inc's domain on AWS Route 53.

## Prerequisites

1. **AWS Account**: You need an AWS account with Route 53 access
2. **Domain**: The domain `dydact.io` should be registered and hosted on Route 53
3. **AWS CLI**: Install and configure the AWS CLI with appropriate credentials
4. **Static IP**: Your Verizon FiOS business static IP address

## Setup Instructions

### 1. Configure Your Variables

Edit the `setup-route53.sh` script and update these variables:

```bash
HOSTED_ZONE_ID="Z1234567890ABC"  # Your dydact.io hosted zone ID
STATIC_IP="123.456.789.101"     # Your Verizon FiOS static IP
```

To find your hosted zone ID:
```bash
aws route53 list-hosted-zones --query 'HostedZones[?Name==`dydact.io.`]'
```

### 2. Run the Setup Script

```bash
chmod +x setup-route53.sh
./setup-route53.sh
```

### 3. Verify DNS Records

After running the script, verify the records were created:

```bash
# Check main domain
dig aci.dydact.io

# Check subdomains
dig www.aci.dydact.io
dig staff.aci.dydact.io
dig admin.aci.dydact.io
dig api.aci.dydact.io
```

## DNS Records Created

The script creates the following DNS records:

| Record | Type | Value | Purpose |
|--------|------|-------|---------|
| aci.dydact.io | A | Your Static IP | Main website |
| www.aci.dydact.io | CNAME | aci.dydact.io | WWW redirect |
| staff.aci.dydact.io | CNAME | aci.dydact.io | Staff portal |
| admin.aci.dydact.io | CNAME | aci.dydact.io | Admin dashboard |
| api.aci.dydact.io | CNAME | aci.dydact.io | API endpoints |

## Network Configuration

### Verizon FiOS Business Setup

1. **Port Forwarding**: Configure your router to forward ports 80 and 443 to your server
2. **Firewall**: Ensure your server firewall allows incoming connections on ports 80 and 443
3. **Static IP**: Confirm your static IP assignment with Verizon

### Server Configuration

1. **Apache Virtual Hosts**: Configure virtual hosts for each subdomain
2. **SSL Certificates**: Set up SSL certificates (recommended: Let's Encrypt)
3. **Security**: Implement proper security headers and access controls

## Troubleshooting

### DNS Not Resolving
- Check TTL settings (300 seconds = 5 minutes)
- Verify hosted zone ID is correct
- Ensure domain is properly delegated to Route 53

### Can't Access Website
- Verify port forwarding on router
- Check server firewall settings
- Confirm Apache is running and configured correctly

### SSL Issues
- Ensure certificates are valid and not expired
- Check certificate chain is complete
- Verify SSL configuration in Apache

## Monitoring

Set up CloudWatch monitoring for:
- DNS query counts
- Health checks for your domain
- SSL certificate expiration alerts

## Costs

Route 53 pricing (as of 2025):
- Hosted zone: $0.50/month
- DNS queries: $0.40 per million queries
- Health checks: $0.50/month per check

## Support

For issues with:
- AWS Route 53: Contact AWS Support
- Domain registration: Contact your domain registrar
- Verizon FiOS: Contact Verizon Business Support
- Application issues: Check application logs and documentation