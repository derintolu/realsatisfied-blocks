# Production Deployment Setup for Studio21

This guide shows how to configure GitHub Actions for automated deployment to your Studio21 production server.

## Server Details
- **Host**: studio21.tempurl.host
- **Username**: c21mas-sftp
- **Protocol**: SFTP
- **WordPress Path**: /wp-content/plugins/realsatisfied-blocks/

## Required GitHub Secrets

You need to add these secrets to your GitHub repository:

### 1. Navigate to GitHub Secrets
1. Go to your GitHub repository
2. Click **Settings** tab
3. Click **Secrets and variables** → **Actions**
4. Click **New repository secret**

### 2. Add Required Secrets

#### SFTP_PASSWORD (Required)
- **Name**: `SFTP_PASSWORD`
- **Value**: Your SFTP password for c21mas-sftp user

#### SSH_PRIVATE_KEY (Optional - for enhanced features)
- **Name**: `SSH_PRIVATE_KEY`
- **Value**: Your SSH private key content (if SSH access is available)

## How to Get Your SFTP Password

Since you provided the SFTP URL, you'll need the password for the `c21mas-sftp` user. This should be:
- Provided by Studio21/your hosting provider
- Available in your hosting control panel
- Sent via email when the account was created

## Deployment Process

### Automatic Deployment
The plugin will automatically deploy when you:

1. **Push to main branch**:
   ```bash
   git checkout main
   git merge develop
   git push origin main
   ```

2. **Create a version tag**:
   ```bash
   git tag v1.4.1
   git push origin v1.4.1
   ```

### Manual Deployment
1. Go to your GitHub repository
2. Click **Actions** tab
3. Select "Deploy to Production (Studio21)"
4. Click **Run workflow**
5. Choose branch and click **Run workflow**

## What Happens During Deployment

1. **Code Quality Checks**
   - WordPress Coding Standards validation
   - Security scanning
   - PHP syntax validation

2. **Build Process**
   - Creates clean deployment package
   - Excludes development files
   - Generates backup archive

3. **SFTP Upload**
   - Connects to studio21.tempurl.host
   - Uploads files to /wp-content/plugins/realsatisfied-blocks/
   - Preserves file structure

4. **Backup & Cleanup** (if SSH available)
   - Creates timestamped backup
   - Removes old backups (keeps last 5)

## Files Excluded from Production

The following are automatically excluded:
- `.git*` - Git files
- `node_modules/` - Node dependencies
- `vendor/` - Composer dependencies
- `tests/` - Test files
- `.github/` - GitHub workflows
- Development config files
- Documentation files

## Troubleshooting

### Common Issues:

1. **"SFTP connection failed"**
   - Verify SFTP_PASSWORD secret is correct
   - Check if server allows SFTP connections
   - Confirm username is `c21mas-sftp`

2. **"Permission denied"**
   - Ensure SFTP user has write access to plugin directory
   - Check WordPress file permissions

3. **"Files not updating"**
   - WordPress caching may be active
   - Clear object cache if installed
   - Check if files actually uploaded

### Testing Connection

To test your SFTP connection locally:
```bash
sftp c21mas-sftp@studio21.tempurl.host
# Enter password when prompted
# Navigate to /wp-content/plugins/
# Try uploading a test file
```

## Security Notes

- SFTP password is stored securely as GitHub secret
- Only authorized GitHub users can trigger deployments
- All connections use encrypted SFTP protocol
- Deployment logs don't expose sensitive information

## Rollback Process

If deployment causes issues:

1. **Automatic Backup** (if SSH configured):
   - Previous version backed up as `realsatisfied-blocks-backup-YYYYMMDD-HHMMSS`
   - Rename backup folder to restore

2. **Manual Rollback**:
   - Use SFTP client to upload previous version
   - Or restore from your local backup

3. **Emergency Disable**:
   - Rename plugin folder to deactivate
   - Via WordPress admin or SFTP

## Next Steps

1. **Get SFTP password from Studio21/hosting provider**
2. **Add SFTP_PASSWORD to GitHub secrets**
3. **Test deployment with a small change**
4. **Monitor first deployment carefully**

## Monitoring

After deployment, check:
- ✅ Plugin still appears in WordPress admin
- ✅ No PHP errors in error logs
- ✅ Frontend blocks still working
- ✅ No broken functionality

## Support

If you encounter issues:
1. Check GitHub Actions logs
2. Verify SFTP credentials with hosting provider
3. Test SFTP connection manually
4. Check WordPress error logs on server