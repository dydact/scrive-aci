# Fixing Full Disk Access for SSH Setup

## The Issue
When you run `sudo systemsetup -setremotelogin on`, you get:
```
setremotelogin: Turning Remote Login on or off requires Full Disk Access privileges.
```

This is because macOS requires Full Disk Access for Terminal to modify system settings.

## Solution: Grant Full Disk Access to Terminal

### Step 1: Open System Settings
1. Click the Apple menu (🍎) in the top-left corner
2. Select **System Settings** (or System Preferences on older macOS)

### Step 2: Navigate to Privacy & Security
1. In System Settings, click **Privacy & Security** in the sidebar
2. Click **Full Disk Access** on the right

### Step 3: Grant Access to Terminal
1. You'll see a list of apps with Full Disk Access permissions
2. Click the **+** button at the bottom of the list
3. Navigate to: `/System/Applications/Utilities/Terminal.app`
   - Or press Cmd+Shift+G and paste: `/System/Applications/Utilities/`
   - Select Terminal.app
4. Click **Open**
5. Terminal will be added to the list - make sure its checkbox is **checked**

### Step 4: Restart Terminal
1. **Quit Terminal completely** (Cmd+Q)
2. Open Terminal again

### Step 5: Enable SSH
Now run the command again:
```bash
sudo systemsetup -setremotelogin on
```

It should work without the Full Disk Access error.

## Alternative Method: Using System Settings GUI

If the terminal method still doesn't work, use the GUI:

1. Open **System Settings**
2. Click **General** in the sidebar
3. Click **Sharing**
4. Turn on **Remote Login**
5. Note the SSH command shown (e.g., `ssh dydact@192.168.1.100`)

## Quick Verification

After enabling SSH, verify it's working:
```bash
# Check if SSH is enabled
sudo systemsetup -getremotelogin

# Should output: Remote Login: On
```

## Security Note

Once SSH is enabled, make sure to:
1. Use strong passwords
2. Consider setting up SSH key authentication
3. Optionally change the default SSH port
4. Enable firewall with SSH exception

## Next Steps

Once SSH is enabled on your Mac mini:

1. **From your MacBook Pro**, get your Mac mini's IP:
   ```bash
   # On Mac mini
   ipconfig getifaddr en0
   ```

2. **Test SSH connection** from MacBook Pro:
   ```bash
   ssh dydact@[mac-mini-ip]
   ```

3. **Set up SSH keys** for passwordless login:
   ```bash
   # On MacBook Pro
   ssh-keygen -t ed25519
   ssh-copy-id dydact@[mac-mini-ip]
   ```

4. **Configure SSH** for easy access:
   ```bash
   # On MacBook Pro
   nano ~/.ssh/config
   ```
   
   Add:
   ```
   Host aci-dev
       HostName [mac-mini-ip]
       User dydact
       LocalForward 8080 localhost:8080
       LocalForward 8443 localhost:8443
   ```

5. **Connect with port forwarding**:
   ```bash
   ssh aci-dev
   ```
   
   Now you can access the app at http://localhost:8080 on your MacBook Pro!

## Troubleshooting

If you still have issues:

1. **Check if Terminal has Full Disk Access**:
   - System Settings → Privacy & Security → Full Disk Access
   - Terminal should be in the list and checked

2. **Try iTerm2 or another terminal**:
   - Download iTerm2
   - Grant it Full Disk Access
   - Use iTerm2 to run the command

3. **Check current SSH status**:
   ```bash
   sudo launchctl list | grep ssh
   ```

4. **Manually start SSH daemon**:
   ```bash
   sudo launchctl load -w /System/Library/LaunchDaemons/ssh.plist
   ```

## Summary

The key is granting Terminal (or your terminal app) Full Disk Access in System Settings. Once that's done, you can enable SSH and connect remotely to continue development on your Scrive ACI system.