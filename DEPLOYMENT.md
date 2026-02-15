# Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying the environment-aware configuration and other changes to the live server.

## Pre-Deployment Checklist

- [ ] Connect to the Live Server (FTP/SFTP/cPanel File Manager).
- [ ] Ensure you have the production database credentials handy.

## Step 1: Backup Live Site

1.  Navigate to `config/` on the server.
2.  Rename `db.php` to `db.php.bak` (or download it as a backup).

## Step 2: Upload New Files

Upload the following files/folders to the server:

- `config/db.php` (The new refactored version)
- `sql/` folder (Optional, for reference)

**DO NOT UPLOAD:**

- `config/database.local.php`
- `.git/` folder
- `.gitignore`

## Step 3: Create Production Config

1.  On the server, inside `config/` directory, create a new file named `database.production.php`.
2.  Paste the following content (verify credentials from your backup or host):

```php
<?php
// Production Database Credentials
$host = 'localhost';
$port = '3306';
$db_name = 'superopt1_db';
$username = 'superopt1_user1';
$password = 'Saima@143143';
?>
```

## Step 4: Verify Deployment

1.  Visit the live website.
2.  Ensure it loads correctly.
3.  If you see "Site is undergoing maintenance", it means the connection failed or `IS_PRODUCTION` is true but the connection error occurred. Check credentials in `database.production.php`.

## Future Deployments

- When working locally, use git to track changes.
- When deploying, upload changed files.
- Never overwrite `config/database.production.php` on the server.
