# Create New SSH Keys for Production Deployment

Let's generate a fresh SSH key pair for your production deployment.

## Step 1: Generate New SSH Key Pair

Open your terminal and run:

```bash
# Generate a new SSH key pair specifically for this deployment
ssh-keygen -t rsa -b 4096 -C "github-deploy-c21masters-realsatisfied" -f ~/.ssh/c21masters_deploy

# When prompted:
# - "Enter passphrase": Just press ENTER (no passphrase for automated deployment)
# - "Enter same passphrase again": Press ENTER again
```

This creates two files:
- `~/.ssh/c21masters_deploy` (private key) - for GitHub secrets
- `~/.ssh/c21masters_deploy.pub` (public key) - for your server

## Step 2: Get Your Public Key

```bash
# Display your new public key
cat ~/.ssh/c21masters_deploy.pub

# Copy this entire output - it should look like:
# ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQ... github-deploy-c21masters-realsatisfied
```

## Step 3: Get Your Private Key

```bash
# Display your private key
cat ~/.ssh/c21masters_deploy

# Copy this ENTIRE output including the BEGIN/END lines:
# -----BEGIN OPENSSH PRIVATE KEY-----
# [lots of encoded text]
# -----END OPENSSH PRIVATE KEY-----
```

## Step 4: Add Public Key to Your Server

You need to add the public key to your Studio21 server. Here are your options:

### Option A: Via SSH (if you have current access)
```bash
# Connect to your server
ssh c21mas-sftp@studio21.tempurl.host

# Create .ssh directory if needed
mkdir -p ~/.ssh

# Add your NEW public key (replace with your actual key)
echo "YOUR_NEW_PUBLIC_KEY_HERE" >> ~/.ssh/authorized_keys

# Set permissions
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

### Option B: Via Hosting Control Panel
1. Log into Studio21 hosting control panel
2. Look for "SSH Keys" or "Security" section
3. Add your new public key

### Option C: Contact Studio21 Support
Send them your new public key and ask them to add it to the `c21mas-sftp` account.

## Step 5: Add Private Key to GitHub Secrets

1. Go to your GitHub repository
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**

Add these secrets:

### SSH_PRIVATE_KEY
- **Name**: `SSH_PRIVATE_KEY`
- **Value**: Paste your entire private key (from Step 3)

### SSH_HOST
- **Name**: `SSH_HOST`
- **Value**: `studio21.tempurl.host`

### SSH_USERNAME
- **Name**: `SSH_USERNAME`
- **Value**: `c21mas-sftp`

### WORDPRESS_PATH
- **Name**: `WORDPRESS_PATH`
- **Value**: `/home/c21mas-sftp/public_html` (adjust if different)

## Step 6: Test SSH Connection

```bash
# Test your new SSH connection
ssh -i ~/.ssh/c21masters_deploy c21mas-sftp@studio21.tempurl.host

# If successful, you should get a shell prompt or welcome message
```

## Step 7: Find Your WordPress Path

Once connected via SSH, find your WordPress installation:

```bash
# Check current directory
pwd

# Look for WordPress files
ls -la

# Common WordPress indicators
ls wp-config.php wp-content wp-admin

# If not in WordPress directory, try:
find /home -name "wp-config.php" 2>/dev/null
```

## Quick Copy-Paste Commands

Run these in sequence:

```bash
# 1. Generate key pair
ssh-keygen -t rsa -b 4096 -C "github-deploy-c21masters" -f ~/.ssh/c21masters_deploy

# 2. Show public key (copy this for your server)
echo "=== PUBLIC KEY (add to server) ==="
cat ~/.ssh/c21masters_deploy.pub
echo ""

# 3. Show private key (copy this for GitHub secrets)
echo "=== PRIVATE KEY (add to GitHub SSH_PRIVATE_KEY secret) ==="
cat ~/.ssh/c21masters_deploy
echo ""

# 4. Test connection (after adding public key to server)
echo "=== TEST CONNECTION ==="
echo "Run this after adding public key to server:"
echo "ssh -i ~/.ssh/c21masters_deploy c21mas-sftp@studio21.tempurl.host"
```

## Troubleshooting

### "Permission denied (publickey)"
- Make sure you added the public key to the server correctly
- Check the private key is correct in GitHub secrets
- Verify the username is correct

### "Host key verification failed"
- Add Studio21's host key to known_hosts:
```bash
ssh-keyscan studio21.tempurl.host >> ~/.ssh/known_hosts
```

### "Connection refused"
- Verify SSH is enabled on your hosting
- Check if the hostname is correct
- Try different SSH port if needed

Ready to generate the keys? Run the commands above and let me know what output you get!