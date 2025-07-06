# RealSatisfied Blocks - Safety Protocol

## 🚨 CRITICAL: Follow These Steps Before ANY Changes

### 1. **BACKUP FIRST** (Every Single Time)
```bash
# Create a backup branch
git branch backup-$(date +%Y%m%d-%H%M%S)

# Or create a specific backup
git branch backup-before-[description]
```

### 2. **WORKING STATE DOCUMENTATION**
As of commit `529c6e3` - "office-reviews block complete":

#### ✅ **WORKING BLOCKS:**
- **Office Ratings Block** (`realsatisfied-blocks/office-ratings`) - FULLY FUNCTIONAL
- **Office Testimonials Block** (`realsatisfied-blocks/office-testimonials`) - FULLY FUNCTIONAL

#### ⚠️ **PLACEHOLDER BLOCKS:**
- **Office Agents Block** (`realsatisfied-blocks/office-agents`) - Placeholder only
- **Office Stats Block** (`realsatisfied-blocks/office-stats`) - Placeholder only

#### 🔧 **CORE DEPENDENCIES:**
- `RealSatisfied_Office_RSS_Parser` - Working RSS parser
- `RealSatisfied_Custom_Fields` - Working custom fields
- `RealSatisfied_Widget_Compatibility` - Working compatibility layer

### 3. **CHANGE WORKFLOW** (Mandatory Process)

#### Before Making ANY Changes:
1. **Create feature branch:** `git checkout -b feature/[description]`
2. **Document the change:** What exactly are you changing and why?
3. **Test plan:** How will you verify it works?
4. **Rollback plan:** How will you undo if it breaks?

#### During Changes:
1. **Make minimal changes:** One small change at a time
2. **Test immediately:** After each change, test the blocks
3. **Commit frequently:** `git commit -m "Small change description"`

#### After Changes:
1. **Test all blocks:** Ensure ratings and testimonials still work
2. **Test admin area:** Check for CSS bleeding
3. **Test frontend:** Verify blocks render properly
4. **Only merge if 100% working**

### 4. **TESTING CHECKLIST** (Before Every Commit)

#### ✅ **Block Editor Test:**
- [ ] Can insert Office Ratings block
- [ ] Can insert Office Testimonials block
- [ ] Block editor loads without errors
- [ ] Block settings panel works

#### ✅ **Frontend Test:**
- [ ] Office Ratings displays correctly
- [ ] Office Testimonials displays correctly
- [ ] CSS styles load properly
- [ ] No JavaScript errors in console

#### ✅ **Admin Area Test:**
- [ ] No CSS bleeding into WordPress admin
- [ ] Plugin pages load correctly
- [ ] No PHP errors in logs

### 5. **EMERGENCY ROLLBACK COMMANDS**

If something breaks:

```bash
# Quick rollback to last working commit
git reset --hard 529c6e3

# Or rollback to backup branch
git reset --hard backup-working-state

# Or rollback specific files
git checkout 529c6e3 -- realsatisfied-blocks.php
git checkout 529c6e3 -- blocks/office-ratings/office-ratings.php
git checkout 529c6e3 -- blocks/office-testimonials/office-testimonials.php
```

### 6. **FORBIDDEN ACTIONS** (Never Do These)

❌ **Never modify these core files without extreme caution:**
- `realsatisfied-blocks.php` (main plugin file)
- `blocks/office-ratings/office-ratings.php` (working block)
- `blocks/office-testimonials/office-testimonials.php` (working block)
- `includes/class-office-rss-parser.php` (RSS parser)

❌ **Never make large changes:**
- Don't refactor multiple files at once
- Don't change plugin structure
- Don't modify working blocks "for improvement"

❌ **Never commit without testing:**
- Every commit must be tested
- Every change must be verified

### 7. **SAFE DEVELOPMENT PRACTICES**

✅ **Always:**
- Work in feature branches
- Make small, incremental changes
- Test after every change
- Document what you're doing
- Keep backups

✅ **When adding new features:**
- Add to placeholder blocks first (office-agents, office-stats)
- Don't touch working blocks
- Create new files rather than modifying existing ones

✅ **When fixing bugs:**
- Identify the exact problem
- Make minimal fix
- Test extensively
- Verify no regression

### 8. **CURRENT WORKING BACKUP**

**Backup Branch:** `backup-working-state`
**Last Working Commit:** `529c6e3` - "office-reviews block complete"
**Date:** July 5, 2025

### 9. **CONTACT PROTOCOL**

If you need to make changes:
1. **Always ask:** "What's the safest way to do this?"
2. **Always confirm:** "Should I create a backup first?"
3. **Always verify:** "Are the blocks still working?"

## 🎯 **REMEMBER: It's better to be overly cautious than to break working functionality!** 