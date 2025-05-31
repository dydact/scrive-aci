# Verizon FiOS Router Configuration for aci.dydact.io

## Port Forwarding Setup

### Access Your Router
1. Open web browser and go to: `http://192.168.1.1` (or your router's IP)
2. Login with admin credentials

### Configure Port Forwarding

#### HTTP Traffic (Port 80)
- **Service Name**: ACI-HTTP
- **Internal IP**: [Your server's local IP, e.g., 192.168.1.100]
- **External Port**: 80
- **Internal Port**: 80
- **Protocol**: TCP
- **Enable**: Yes

#### HTTPS Traffic (Port 443)
- **Service Name**: ACI-HTTPS  
- **Internal IP**: [Your server's local IP, e.g., 192.168.1.100]
- **External Port**: 443
- **Internal Port**: 443
- **Protocol**: TCP
- **Enable**: Yes

### DMZ Configuration (Alternative)
If port forwarding doesn't work, you can set up DMZ:
1. Go to Router Settings > DMZ
2. Enable DMZ
3. Set DMZ IP to your server's local IP
4. Save settings

⚠️ **Security Note**: DMZ exposes all ports. Only use if you have proper firewall configured.

## Find Your Server's Local IP

### Linux/macOS:
```bash
ip addr show
# or
ifconfig
```

### Windows:
```cmd
ipconfig
```

Look for your network interface (usually eth0 or wlan0) and note the IP address.

## Static Local IP (Recommended)

### Set Static IP on Server
Edit network configuration to use static IP instead of DHCP:

#### Ubuntu/Debian:
```bash
sudo nano /etc/netplan/01-netcfg.yaml
```

Example configuration:
```yaml
network:
  version: 2
  renderer: networkd
  ethernets:
    eth0:
      addresses:
        - 192.168.1.100/24
      gateway4: 192.168.1.1
      nameservers:
        addresses: [8.8.8.8, 8.8.4.4]
```

Apply changes:
```bash
sudo netplan apply
```

#### CentOS/RHEL:
```bash
sudo nano /etc/sysconfig/network-scripts/ifcfg-eth0
```

Example configuration:
```
BOOTPROTO=static
IPADDR=192.168.1.100
NETMASK=255.255.255.0
GATEWAY=192.168.1.1
DNS1=8.8.8.8
DNS2=8.8.4.4
```

Restart network:
```bash
sudo systemctl restart network
```

## Firewall Configuration

### Ubuntu/Debian (UFW):
```bash
# Enable firewall
sudo ufw enable

# Allow SSH (if using)
sudo ufw allow 22

# Allow HTTP and HTTPS
sudo ufw allow 80
sudo ufw allow 443

# Check status
sudo ufw status
```

### CentOS/RHEL (firewalld):
```bash
# Enable firewall
sudo systemctl enable firewalld
sudo systemctl start firewalld

# Allow HTTP and HTTPS
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload

# Check status
sudo firewall-cmd --list-all
```

## Testing Network Configuration

### Test Local Access:
```bash
# Test HTTP
curl -I http://192.168.1.100

# Test HTTPS (after SSL setup)
curl -I https://192.168.1.100
```

### Test External Access:
```bash
# From another network or use online tools
curl -I http://YOUR.STATIC.IP.ADDRESS
```

### Test Domain Resolution:
```bash
# Test DNS resolution
nslookup aci.dydact.io

# Test full connectivity
curl -I https://aci.dydact.io
```

## Troubleshooting

### Can't Access from Internet:
1. Verify port forwarding rules are correct
2. Check if ISP blocks ports 80/443
3. Confirm static IP is active
4. Test firewall rules

### DNS Not Resolving:
1. Wait for DNS propagation (up to 24 hours)
2. Check Route 53 configuration
3. Use DNS checker tools online

### SSL Certificate Issues:
1. Ensure domains point to correct IP
2. Check that port 80 is accessible (needed for Let's Encrypt)
3. Verify Apache is running

## Verizon FiOS Specific Notes

### Static IP Confirmation:
- Login to Verizon account online
- Go to "My Services" > "Internet"
- Verify static IP assignment

### Business Support:
- If issues persist, contact Verizon Business Support
- Mention you're hosting a website and need ports 80/443 open
- Reference your static IP assignment

### Common Issues:
- Some FiOS routers have "Advanced Security" that blocks hosting
- May need to disable "DoS Protection" for web hosting
- Ensure "Remote Management" is disabled for security