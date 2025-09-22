# GitHub Secrets Setup for SSH Deployment

Since you have your SSH public key, you now need to configure GitHub with the corresponding private key and server details.

## Required GitHub Secrets

You need to add these secrets to your GitHub repository:

### 1. Navigate to GitHub Secrets
1. Go to your GitHub repository
2. Click **Settings** tab
3. Click **Secrets and variables** → **Actions**
4. Click **New repository secret**

### 2. Add These Secrets

#### SSH_PRIVATE_KEY (Required)
- **Name**: `SSH_PRIVATE_KEY`
- **Value**: Your SSH private key content (the one that corresponds to your public key)

#### SSH_HOST (Required)
- **Name**: `SSH_HOST`
- **Value**: `studio21.tempurl.host`

#### SSH_USERNAME (Required)
- **Name**: `SSH_USERNAME`
- **Value**: `c21mas-sftp` (or your SSH username)

#### WORDPRESS_PATH (Required)
- **Name**: `WORDPRESS_PATH`
- **Value**: `/home/c21mas-sftp/public_html` (or the full path to your WordPress installation)

#### SSH_PORT (Optional)
- **Name**: `SSH_PORT`
- **Value**: `22` (default SSH port, only add if different)

## How to Get Your Private Key

Since you provided the public key, you should have the private key file. Here's how to find it:

### If you generated the key pair recently:
```bash
# Look for your private key file (common locations):
ls ~/.ssh/id_rsa
ls ~/.ssh/id_ed25519
ls ~/.ssh/studio21_deploy

# Display the private key content:
cat ~/.ssh/id_rsa
# OR
cat ~/.ssh/your_private_key_file
```

### If you need to generate a new key pair:
```bash
# Generate new SSH key pair
ssh-keygen -t rsa -b 4096 -C "github-deploy-studio21"

# This creates:
# ~/.ssh/id_rsa (private key) - ADD THIS TO GITHUB SECRETS
# ~/.ssh/id_rsa.pub (public key) - ADD THIS TO YOUR SERVER
```

## What Your Private Key Looks Like

Your private key should look like this:
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAlwAAAAdzc2gtcn
NhAAAAAwEAAQAAAIEA1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOP
[many more lines of encoded data]
QRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
-----END OPENSSH PRIVATE KEY-----
```

**Copy the ENTIRE content** including the `-----BEGIN` and `-----END` lines.

## Server Setup

You also need to ensure your public key is added to your server:

### Option 1: Add via SSH (if you have access)
```bash
# Connect to your server
ssh c21mas-sftp@studio21.tempurl.host

# Create .ssh directory if it doesn't exist
mkdir -p ~/.ssh

# Add your public key to authorized_keys
echo "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQDdgDOsdBPdeDJiYQzfikX1Ydu5waJ1V4Gv4F6dtWhS2pL/7EsoP1RkDqPhMbZHFA/PuxpPNmSA5CbA+DNfN0KopSBmrufhD513XBBFTQ/eV4TOP4+nu4QP37uLyhL48trKf/1hAYvdHt1AvE28sUVSAYrR7MZfrIZKU0IWk474Hhe3hLekR2w8dr8rM0Cj1wUCqhLTFafkyCdI1MqIkFIlBDkaEF7Oz1Gdyi9eOWHKK9QaMJtKMYy+SQ5stS+YQY8T3sRygMMQfyfG25zjcn+yMU+LkAPykCxm38m9D5T/6j96EseOP6fUV44uHejv0//brqGSUqhgJ5HWmIi2R2JCg4Xdupy2JWr+aejcOBXdpNdAFRqWaEIMhbY30YP/p2xMj4sjVES42ECzvuiBqhmeHI52oOIK+rwUW81f3kT253YZiVO5F0bHf2y4zoS1aXAdx676Zh4Tz5tIZsWJMx79/1MjDIIIZ9+X+wmsZQZnn2SaMPGmdV3ru2sraGxeMr0=" >> ~/.ssh/authorized_keys

# Set proper permissions
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

### Option 2: Contact Hosting Provider
Send your hosting provider:
- Your public key (the one you already provided)
- Request they add it to the `c21mas-sftp` user's authorized_keys

## Testing Your Setup

After adding the secrets, test the connection:

1. **Manual workflow test**:
   - Go to GitHub Actions
   - Run "Deploy to Production (SSH)" workflow manually
   - Watch the logs for connection success

2. **Local SSH test** (if you have the private key):
   ```bash
   ssh -i ~/.ssh/your_private_key c21mas-sftp@studio21.tempurl.host
   ```

## WordPress Path Discovery

To find your WordPress path, try:
```bash
# Common WordPress paths:
/home/c21mas-sftp/public_html
/var/www/html
/home/c21mas-sftp/www
/home/c21mas-sftp/htdocs

# Or find it via SSH:
find /home -name "wp-config.php" 2>/dev/null
pwd  # when in WordPress directory
```

## Summary Checklist

- [ ] Add SSH_PRIVATE_KEY to GitHub secrets
- [ ] Add SSH_HOST (studio21.tempurl.host)
- [ ] Add SSH_USERNAME (c21mas-sftp)
- [ ] Add WORDPRESS_PATH (your WP installation path)
- [ ] Ensure public key is on server
- [ ] Test SSH connection
- [ ] Run test deployment

Do you have access to your private key file, or do you need to generate a new key pair?