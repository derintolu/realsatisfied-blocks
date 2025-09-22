# SSH Key Setup for Studio21 Deployment

Setting up SSH key authentication is more secure than using passwords and enables additional deployment features.

## Step 1: Generate SSH Key Pair

On your local machine, generate a new SSH key:

```bash
# Generate SSH key pair
ssh-keygen -t rsa -b 4096 -C "github-deploy-c21masters" -f ~/.ssh/studio21_deploy

# This creates two files:
# ~/.ssh/studio21_deploy (private key)
# ~/.ssh/studio21_deploy.pub (public key)
```

## Step 2: Add Public Key to Studio21 Server

You'll need to add the public key to your server. Here are the options:

### Option A: Via Hosting Control Panel
1. Log into your Studio21 hosting control panel
2. Look for "SSH Keys" or "Public Keys" section
3. Add the contents of `~/.ssh/studio21_deploy.pub`

### Option B: Via SFTP (if you have shell access)
1. Connect via SFTP: `sftp c21mas-sftp@studio21.tempurl.host`
2. Navigate to home directory
3. Create `.ssh` directory if it doesn't exist
4. Upload your public key to `.ssh/authorized_keys`

### Option C: Contact Studio21 Support
Send them your public key content and ask them to add it to the `c21mas-sftp` user account.

## Step 3: Get Your Public Key Content

```bash
# Display your public key
cat ~/.ssh/studio21_deploy.pub

# Copy this entire output - it should look like:
# ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQ... github-deploy-c21masters
```

## Step 4: Add Private Key to GitHub Secrets

1. Get your private key content:
   ```bash
   cat ~/.ssh/studio21_deploy
   ```

2. Copy the entire private key (including `-----BEGIN` and `-----END` lines)

3. In GitHub repository:
   - Go to **Settings** → **Secrets and variables** → **Actions**
   - Click **New repository secret**
   - Name: `SSH_PRIVATE_KEY`
   - Value: Paste the private key content

## Step 5: Test SSH Connection

```bash
# Test SSH connection
ssh -i ~/.ssh/studio21_deploy c21mas-sftp@studio21.tempurl.host

# If successful, you should get a shell prompt or see a welcome message
```

## Benefits of SSH Key Authentication

With SSH keys configured, the deployment will:

✅ **Enhanced Security**
- No password in GitHub secrets
- Key-based authentication

✅ **Additional Features**
- Automatic backups before deployment
- File permission management
- WordPress cache clearing
- Deployment verification

✅ **Better Error Handling**
- More detailed deployment logs
- Rollback capabilities
- Health checks

## Updated GitHub Secrets

With SSH keys, you'll have:

### Required:
- `SSH_PRIVATE_KEY` - Your private key content

### Optional (fallback):
- `SFTP_PASSWORD` - Keep as backup method

## Troubleshooting SSH Setup

### "Permission denied (publickey)"
- Verify public key is added to server
- Check private key format in GitHub secret
- Ensure SSH service is enabled on server

### "Host key verification failed"
- Add Studio21's host key to known_hosts
- Or use `StrictHostKeyChecking=no` in deployment

### "Connection refused"
- Verify SSH port (usually 22)
- Check if SSH is enabled on hosting
- Confirm hostname is correct

## Security Best Practices

1. **Use unique keys** for each deployment target
2. **Rotate keys regularly** (every 6-12 months)
3. **Restrict key permissions** on server if possible
4. **Monitor deployment logs** for suspicious activity
5. **Keep private keys secure** - never share or commit them

## Testing Your Setup

Once configured, test with a small change:

1. Make a minor edit to a PHP comment
2. Commit and push to main branch
3. Watch GitHub Actions deployment
4. Verify changes appear on production site

Would you like me to help you with any specific step of this process?