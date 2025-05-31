# Verizon FiOS Router Configuration for Your Setup

## Your Network Details
- **External IP**: 71.244.253.183 (Static)
- **Gateway**: 71.244.253.1
- **Subnet**: 255.255.255.0
- **DNS**: 71.242.0.12, 68.237.161.12

## Step 1: Access Your Router
1. Open browser and go to: `http://192.168.1.1`
2. Login with admin credentials (usually on router label)

## Step 2: Find Your Server's Local IP

First, find your server's IP address on the local network:

### On your server, run:
```bash
# Linux/Mac
ip addr show | grep "inet 192.168"
# or
ifconfig | grep "inet 192.168"

# Windows
ipconfig | findstr "192.168"
```

You should see something like: `192.168.1.XXX` - note this IP address.

## Step 3: Configure Port Forwarding

### In your Verizon router interface:

1. Navigate to: **Router Settings** → **Port Forwarding** or **Firewall** → **Port Forwarding**

2. **Add HTTP Rule:**
   - **Service Name**: ACI-HTTP
   - **External Host**: * (or leave blank)
   - **External Port Range**: 80 to 80
   - **Internal Host**: [Your server's 192.168.1.XXX IP]
   - **Internal Port Range**: 80 to 80
   - **Protocol**: TCP
   - **Enable**: ✓

3. **Add HTTPS Rule:**
   - **Service Name**: ACI-HTTPS
   - **External Host**: * (or leave blank)  
   - **External Port Range**: 443 to 443
   - **Internal Host**: [Your server's 192.168.1.XXX IP]
   - **Internal Port Range**: 443 to 443
   - **Protocol**: TCP
   - **Enable**: ✓

4. **Save/Apply** the settings

## Step 4: Configure Static IP for Server

### Option A: Set on Server (Recommended)
```bash
# Ubuntu/Debian
sudo nano /etc/netplan/01-netcfg.yaml
```

Add this configuration (adjust IP as needed):
```yaml
network:
  version: 2
  renderer: networkd
  ethernets:
    eth0:  # or your interface name
      addresses:
        - 192.168.1.100/24  # Choose unused IP
      gateway4: 192.168.1.1
      nameservers:
        addresses: [71.242.0.12, 68.237.161.12]
```

Apply changes:
```bash
sudo netplan apply
```

### Option B: Reserve IP in Router
1. Go to **DHCP Settings** or **LAN Settings**
2. Find **DHCP Reservations** or **Static IP Assignment**
3. Add your server's MAC address with desired IP (e.g., 192.168.1.100)

## Step 5: Test Configuration

### Test Port Forwarding:
```bash
# From your server
curl -I http://71.244.253.183

# From external network (use phone data)
curl -I http://71.244.253.183
```

### Test Internal Connectivity:
```bash
# Test Apache is running
sudo systemctl status apache2

# Test local access
curl -I http://192.168.1.100  # Your server's local IP
```

## Common Verizon Router Interface Locations

Depending on your router model, port forwarding might be under:
- **Advanced** → **Port Forwarding**
- **Firewall** → **Port Forwarding Rules**
- **Router Settings** → **Port Forwarding**
- **Security** → **Port Forwarding**

## Troubleshooting

### If Port Forwarding Doesn't Work:

1. **Check for UPnP conflicts:**
   - Disable UPnP in router settings
   - Reboot router

2. **Try DMZ (temporary test):**
   - Go to **DMZ Settings**
   - Enable DMZ for your server's IP
   - Test if website accessible
   - **Important**: Disable DMZ after test, use proper port forwarding

3. **Check Verizon Security Features:**
   - Some routers have "DoS Protection" - try disabling
   - Look for "Advanced Security" - may need to whitelist web hosting

### If Still Not Working:

1. **Contact Verizon Business Support:**
   - Tell them you're hosting a website
   - Ask if they block ports 80/443
   - Confirm your static IP is active

2. **Check ISP Restrictions:**
   - Some residential plans block server hosting
   - Business plans usually allow it

## Next Steps After Port Forwarding Works:

1. **Run DNS Setup:**
   ```bash
   cd dns/
   ./setup-route53.sh
   ```

2. **Wait for DNS Propagation** (5-10 minutes)

3. **Test Domain Resolution:**
   ```bash
   nslookup aci.dydact.io
   # Should return: 71.244.253.183
   ```

4. **Setup SSL Certificates:**
   ```bash
   sudo ./ssl/setup-ssl.sh
   ```

## Security Notes

- Never put your router in full DMZ mode permanently
- Use strong admin passwords for router
- Regularly update router firmware
- Consider setting up fail2ban on your server for additional security