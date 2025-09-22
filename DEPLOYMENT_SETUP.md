# WPMU DEV Studio21 Deployment Setup

## GitHub Secrets Configuration

Add these secrets to your GitHub repository (Settings → Secrets and variables → Actions):

### Required Secrets:

1. **SSH_HOST**: `studio21.tempurl.host`
2. **SSH_USERNAME**: `git-derin`
3. **SSH_PRIVATE_KEY**:
   - This should be the private key that corresponds to your public key
   - The public key you've added to WPMU DEV: `ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIJz0Xx6KPvhlSUJOh1n0LWNIaDYQkXFZwX5wL/udoRiO`
   - Copy your entire private key including:
     ```
     -----BEGIN OPENSSH PRIVATE KEY-----
     [your key content here]
     -----END OPENSSH PRIVATE KEY-----
     ```

### Optional Secrets:

4. **WORDPRESS_PATH**: `site` (or leave empty, it will default to 'site')
5. **SSH_KNOWN_HOSTS**:
   - Get this by running: `ssh-keyscan -H studio21.tempurl.host`
   - This adds extra security by verifying the host fingerprint

## How to Add Secrets to GitHub:

1. Go to your repository: https://github.com/derintolu/realsatisfied-blocks
2. Click on "Settings" tab
3. In the left sidebar, click "Secrets and variables" → "Actions"
4. Click "New repository secret"
5. Add each secret with the name and value above

## Verify Your Setup:

After adding all secrets, the deployment will automatically trigger when you push to the `main` branch.

## WPMU DEV Hub Configuration:

Make sure in WPMU DEV Hub you have:
1. Created SSH user: `git-derin`
2. Added the public key: `ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIJz0Xx6KPvhlSUJOh1n0LWNIaDYQkXFZwX5wL/udoRiO`
3. Set appropriate path restrictions if needed

## Testing the Deployment:

You can manually trigger a deployment by:
1. Going to Actions tab in GitHub
2. Selecting "Deploy to WPMU DEV Studio21 Hosting"
3. Clicking "Run workflow"

## Deployment Path:

The plugin will be deployed to:
`site/wp-content/plugins/realsatisfied-blocks/`

## Support:

If deployment fails, check:
1. SSH credentials are correct
2. Public key is added to WPMU DEV Hub
3. SSH user has write permissions to the plugins directory