# Deployment Guide

This guide explains how to set up automated deployment for the RealSatisfied Blocks plugin using GitHub Actions.

## Overview

The deployment system includes:
- **Automated testing** on pull requests and development pushes
- **Production deployment** on main branch pushes and tags
- **WordPress coding standards** validation
- **Security scanning**
- **PHP compatibility testing** (7.4 - 8.2)

## Setup Instructions

### 1. GitHub Repository Secrets

Configure the following secrets in your GitHub repository settings:

#### For FTP Deployment:
```
FTP_SERVER=your-ftp-server.com
FTP_USERNAME=your-ftp-username
FTP_PASSWORD=your-ftp-password
FTP_SERVER_DIR=/public_html
```

#### For SSH Deployment (Recommended):
```
SSH_HOST=your-server-ip-or-domain.com
SSH_USERNAME=your-ssh-username
SSH_KEY=your-private-ssh-key-content
SSH_PORT=22
DEPLOY_PATH=/var/www/html/your-site
```

### 2. SSH Key Setup

To use SSH deployment:

1. Generate an SSH key pair on your local machine:
   ```bash
   ssh-keygen -t rsa -b 4096 -C "github-deploy"
   ```

2. Add the public key to your server's `~/.ssh/authorized_keys`

3. Add the private key content to GitHub secret `SSH_KEY`

### 3. Workflow Triggers

#### Testing Workflow (`test.yml`)
- Runs on pull requests to `main` or `develop`
- Runs on pushes to `develop` branch
- Includes PHPCS, security scans, and compatibility tests

#### Deployment Workflow (`deploy.yml`)
- Runs on pushes to `main` branch
- Runs on version tags (v1.0.0, v2.1.3, etc.)
- Can be manually triggered via GitHub Actions UI

## Deployment Process

### Automatic Deployment

1. **Push to main branch**:
   ```bash
   git checkout main
   git merge develop
   git push origin main
   ```

2. **Create version tag**:
   ```bash
   git tag v1.4.1
   git push origin v1.4.1
   ```

### Manual Deployment

1. Go to GitHub Actions tab in your repository
2. Select "Deploy to Production" workflow
3. Click "Run workflow" button
4. Choose branch and run

## Deployment Steps

The automated deployment process:

1. **Code Quality Checks**
   - WordPress Coding Standards validation
   - Security vulnerability scanning
   - PHP syntax validation

2. **Build Process**
   - Install dependencies
   - Build assets (if applicable)
   - Create clean deployment package

3. **Deploy to Production**
   - Backup current plugin version
   - Upload new version via FTP/SSH
   - Set proper file permissions
   - Clear WordPress caches

4. **Post-Deployment**
   - Create GitHub release (for tags)
   - Send deployment notifications

## Deployment Methods

### FTP Deployment
- Uses `SamKirkland/FTP-Deploy-Action`
- Suitable for shared hosting
- Uploads files directly via FTP

### SSH Deployment
- Uses `appleboy/ssh-action`
- Recommended for VPS/dedicated servers
- More secure and reliable
- Includes backup and rollback capabilities

## File Exclusions

The following files/directories are excluded from production:
- `.git*` - Git files
- `node_modules/` - Node.js dependencies
- `vendor/` - Composer dependencies
- `tests/` - Test files
- `src/` - Source files (if using build process)
- Development configuration files

## Rollback Process

If deployment fails or issues arise:

### Automatic Rollback
- Previous version is automatically backed up
- Located at `realsatisfied-blocks-backup-YYYYMMDD-HHMMSS/`

### Manual Rollback
```bash
# SSH into your server
cd /path/to/wp-content/plugins/

# Remove current version
rm -rf realsatisfied-blocks

# Restore from backup
mv realsatisfied-blocks-backup-YYYYMMDD-HHMMSS realsatisfied-blocks

# Clear caches
wp cache flush --path=/path/to/wordpress
```

## Monitoring

### Success Indicators
- ✅ All tests pass
- ✅ Deployment completes without errors
- ✅ Plugin remains active after deployment
- ✅ No PHP errors in logs

### Troubleshooting

#### Common Issues:

1. **PHPCS Failures**
   - Run `composer run-script lint-fix` locally
   - Commit formatting fixes

2. **SSH Connection Failed**
   - Verify SSH key format in GitHub secrets
   - Check server SSH configuration
   - Ensure correct hostname/IP

3. **Permission Errors**
   - Verify SSH user has write permissions
   - Check WordPress file ownership

4. **Plugin Deactivation**
   - Check PHP error logs
   - Verify all dependencies are included
   - Test in staging environment first

### Debug Mode

For debugging deployments:

1. Enable detailed logging in workflows
2. Add debug SSH session:
   ```yaml
   - name: Debug via SSH
     uses: mxschmitt/action-tmate@v3
   ```

## Security Considerations

- Never commit sensitive credentials to repository
- Use SSH keys instead of passwords
- Regularly rotate deployment credentials
- Monitor deployment logs for suspicious activity
- Keep server software updated

## Best Practices

1. **Always test in staging first**
2. **Use semantic versioning for tags**
3. **Keep deployment packages small**
4. **Monitor WordPress error logs**
5. **Have a rollback plan ready**
6. **Test critical functionality after deployment**

## Support

For deployment issues:
1. Check GitHub Actions logs
2. Review WordPress error logs
3. Verify server configuration
4. Test manually if automated deployment fails