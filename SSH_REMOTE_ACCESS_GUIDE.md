# SSH Remote Access Setup for Mac Mini Server

## Overview
This guide will help you set up SSH access from your MacBook Pro to your Mac mini server so you can continue development remotely using Claude.

## Step 1: Enable SSH on Mac Mini Server

### Option A: Using System Settings (macOS Ventura or later)
1. Open **System Settings** on the Mac mini
2. Click **General** in the sidebar
3. Click **Sharing**
4. Turn on **Remote Login**
5. Click the info button (i) next to Remote Login
6. Note the SSH command shown (e.g., `ssh username@192.168.1.100`)

### Option B: Using Terminal
```bash
# Enable SSH
sudo systemsetup -setremotelogin on

# Check if SSH is enabled
sudo systemsetup -getremotelogin
```

## Step 2: Find Your Mac Mini's IP Address

On the Mac mini, run:
```bash
# Get local IP address
ifconfig | grep "inet " | grep -v 127.0.0.1

# Or use this simpler command
ipconfig getifaddr en0
```

You'll see something like: `192.168.1.100`

## Step 3: Set Up SSH Keys (Recommended)

On your MacBook Pro:

### Generate SSH Key (if you don't have one)
```bash
# Generate a new SSH key
ssh-keygen -t ed25519 -C "your_email@example.com"

# Or use RSA if preferred
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"
```

### Copy SSH Key to Mac Mini
```bash
# Replace 'username' with your Mac mini username and 'ip_address' with the IP
ssh-copy-id username@ip_address

# Example
ssh-copy-id dydact@192.168.1.100
```

## Step 4: Test SSH Connection

From your MacBook Pro:
```bash
# Basic SSH connection
ssh username@ip_address

# Example
ssh dydact@192.168.1.100

# With specific port (if changed from default 22)
ssh -p 22 dydact@192.168.1.100
```

## Step 5: Configure SSH for Easier Access

Create/edit `~/.ssh/config` on your MacBook Pro:
```bash
nano ~/.ssh/config
```

Add:
```
Host mac-mini
    HostName 192.168.1.100
    User dydact
    Port 22
    ForwardAgent yes
```

Now you can simply connect with:
```bash
ssh mac-mini
```

## Step 6: Keep SSH Session Alive

Add these settings to prevent disconnection:

### On MacBook Pro (client)
Edit `~/.ssh/config` and add:
```
Host *
    ServerAliveInterval 60
    ServerAliveCountMax 3
```

### On Mac Mini (server)
Edit `/etc/ssh/sshd_config`:
```bash
sudo nano /etc/ssh/sshd_config
```

Add or modify:
```
ClientAliveInterval 60
ClientAliveCountMax 3
```

## Step 7: Using Claude via SSH

Once connected via SSH:

### Option 1: Use Claude in Terminal
```bash
# Navigate to your project
cd /Users/dydact/Desktop/dydact/labs/scrive-aci

# Use your preferred text editor
nano file.php
vim file.php
code file.php  # If VS Code CLI is installed
```

### Option 2: Use VS Code Remote SSH
1. Install VS Code on MacBook Pro
2. Install "Remote - SSH" extension
3. Connect to Mac mini through VS Code
4. Use Claude while editing files remotely

### Option 3: Use tmux/screen for Persistent Sessions
```bash
# Install tmux
brew install tmux

# Start new session
tmux new -s development

# Detach from session
Ctrl+b, then d

# Reattach to session
tmux attach -t development
```

## Step 8: Port Forwarding for Web Development

Forward the Docker ports through SSH:
```bash
# Forward web ports
ssh -L 8080:localhost:8080 -L 8443:localhost:8443 -L 3306:localhost:3306 mac-mini

# Or add to SSH config
Host mac-mini
    HostName 192.168.1.100
    User dydact
    LocalForward 8080 localhost:8080
    LocalForward 8443 localhost:8443
    LocalForward 3306 localhost:3306
```

Now access the application on your MacBook Pro at:
- http://localhost:8080
- https://localhost:8443

## Step 9: Security Best Practices

### Change Default SSH Port (Optional)
```bash
# Edit SSH config on Mac mini
sudo nano /etc/ssh/sshd_config

# Change Port 22 to something else
Port 2222

# Restart SSH
sudo launchctl stop com.openssh.sshd
sudo launchctl start com.openssh.sshd
```

### Disable Password Authentication (After setting up keys)
```bash
# Edit SSH config
sudo nano /etc/ssh/sshd_config

# Set these values
PasswordAuthentication no
PubkeyAuthentication yes
```

### Set Up Firewall
```bash
# Enable firewall
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --setglobalstate on

# Allow SSH
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --add /usr/sbin/sshd
```

## Step 10: Remote Access Over Internet

### Option A: Port Forwarding on Router
1. Access router admin (usually http://192.168.1.1)
2. Set up port forwarding:
   - External Port: 22 (or custom)
   - Internal IP: Mac mini's IP
   - Internal Port: 22

### Option B: Use Tailscale (Recommended)
```bash
# Install on both Macs
brew install tailscale

# Start Tailscale
tailscale up

# Get Tailscale IP
tailscale ip -4
```

### Option C: Use ngrok
```bash
# Install ngrok
brew install ngrok

# Expose SSH
ngrok tcp 22
```

## Troubleshooting

### Permission Denied
```bash
# Check SSH service
sudo systemsetup -getremotelogin

# Check firewall
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --getglobalstate

# Check SSH logs
log show --predicate 'process == "sshd"' --last 1h
```

### Connection Refused
```bash
# Check if SSH is listening
sudo lsof -i :22

# Restart SSH service
sudo launchctl stop com.openssh.sshd
sudo launchctl start com.openssh.sshd
```

### Slow Connection
```bash
# Add to /etc/ssh/sshd_config on Mac mini
UseDNS no
```

## Quick Start Commands

From your MacBook Pro:
```bash
# 1. First time setup
ssh-keygen -t ed25519
ssh-copy-id dydact@[mac-mini-ip]

# 2. Connect with port forwarding
ssh -L 8080:localhost:8080 dydact@[mac-mini-ip]

# 3. Navigate to project
cd /Users/dydact/Desktop/dydact/labs/scrive-aci

# 4. Start tmux session
tmux new -s aci-dev

# 5. Work with files
ls -la
nano file.php
docker-compose ps
```

## Summary

Once set up, you can:
1. SSH from MacBook Pro to Mac mini
2. Access the Scrive ACI project files
3. Use Claude to edit files remotely
4. Access the web application through port forwarding
5. Maintain persistent development sessions with tmux

The Docker containers will continue running on the Mac mini, and you can develop from anywhere!