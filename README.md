# SWUMS : _Simple Website Uptime Monitoring System_

This implementation provides the following features:

- Admin user management,
- Website monitoring with status tracking,
- Outage tracking with duration calculation,
- Visual reporting with charts,
- User access control,
- SHA256 password hashing,
- Cron job integration (through the use of [easycron](https://www.easycron.com/), but can be set for any cronjob tool or even locally).

> _(this solution was initially created for deployment on [infinityfree free hosting](https://www.infinityfree.com/))_

&nbsp;

## Installation and Setup Instructions

1. Upload Files: Upload all files to your InfinityFree hosting account.

2. Create Database:

> - Go to InfinityFree Control Panel
> - Create a new MySQL database
> - Note the database credentials (host, username, password, database name)

3. Configure:

> - Edit includes/config.php with your database credentials
> - Set BASE*URL to your InfinityFree URL (e.g., *`https://your-site.epizy.com`\_)

4. Install:

> - Visit install.php in your browser
> - Follow the installation wizard to set up the database and create an admin account

5. Set Up Cron Job:

> - Use an external service like EasyCron
> - Set up a job to call _`https://your-site.epizy.com/cron/checker.php?secret_key=YOUR_SECRET_KEY`_
> - Set the interval (e.g., every 30 minutes)
> - Change YOUR_SECRET_KEY in cron/checker.php to a long random string

6. Secure:

> - Consider password protecting the admin/ directory via .htaccess
> - Change default admin password after installation

&nbsp;

## Additional Security Recommendation:

Password Protect the cron directory:

> - Create a .htpasswd file in a secure location (not web accessible)
> - Add this to your .htaccess (uncomment and modify the Directory section)

&nbsp;

### For InfinityFree-specific optimizations:

- The file already includes settings that work well with InfinityFree's environment
- The rewrite rules handle their PHP hosting setup properly
- To create .htpasswd (if you want cron directory protection): `htpasswd -c /path/to/.htpasswd username`

> (Run this on your local machine and upload the file)
