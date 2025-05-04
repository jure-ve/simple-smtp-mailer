# Simple SMTP Mailer Plugin

A lightweight WordPress plugin to send emails reliably using PHPMailer and an SMTP server. No bloat, just the essentials.

[![License: GPL-2.0](https://img.shields.io/badge/License-GPL--2.0-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress Version](https://img.shields.io/badge/WordPress-5.0%2B-blue)](https://wordpress.org)

## Description

I created the **Simple SMTP Mailer Plugin** because I was tired of WordPress emails getting lost in spam or not arriving at all when using PHP's `mail()` function. Other SMTP plugins were too complex. This plugin is my solution: a simple, secure, and reliable way to configure WordPress to send emails via an SMTP server using PHPMailer.

Whether you're running a blog, a small site, or an online store, this plugin ensures your emails (notifications, password resets, or contact forms) reach their destination. It's perfect for users on shared hosting or anyone who wants a straightforward SMTP setup without external tools.

## Features

- **Easy SMTP Setup**: Configure your SMTP server directly from the WordPress admin panel.
- **PHPMailer Powered**: Uses the trusted PHPMailer library for reliable email delivery.
- **Secure Password Storage**: Encrypts your SMTP password using OpenSSL and WordPress salts.
- **Debugging Tools**: Offers PHPMailer debug levels (0-4) to troubleshoot issues.
- **Test Email Feature**: Send test emails from the admin panel to verify your setup.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- OpenSSL PHP extension (required for password encryption)

## Installation

1. **Download the Plugin**:
   - Clone this repository: `git clone https://github.com/jure-ve/simple-smtp-mailer.git`
   - Or download the ZIP file from the [Releases](https://github.com/jure-ve/simple-smtp-mailer/releases) page.
2. **Install in WordPress**:
   - Go to **Plugins > Add New > Upload Plugin** in your WordPress admin panel.
   - Upload the ZIP file and click **Install Now**, then activate the plugin.
3. **Manual Installation**:
   - Copy the `simple-smtp-mailer` folder to `wp-content/plugins/`.
   - Activate the plugin from the **Plugins** page in WordPress.

## Configuration

1. Go to **SMTP Mailer > Configuration** in the WordPress admin menu.
2. Enter your SMTP server details:
   - **SMTP Host**: e.g., `smtp.gmail.com`
   - **SMTP Port**: e.g., `587` (TLS) or `465` (SSL)
   - **Security**: Choose `None`, `SSL`, or `TLS`
   - **Authentication**: Enable if your server requires it (usually yes)
   - **SMTP Username**: Your email or username
   - **SMTP Password**: Enter it here; it will be encrypted
   - **From Email**: Optional; defaults to the admin email
   - **From Name**: Optional; defaults to the site title
   - **PHPMailer Debug**: Set to 0 for production, or 1-4 for troubleshooting
3. Click **Save Changes**.

**Example for Gmail**:
- SMTP Host: `smtp.gmail.com`
- SMTP Port: `587`
- Security: `TLS`
- Authentication: Yes
- Username: `your.email@gmail.com`
- Password: Use an [App Password](https://support.google.com/accounts/answer/185833) if 2FA is enabled

**Note**: Ensure the OpenSSL PHP extension is enabled on your server. Contact your hosting provider if you're unsure.

## Usage

Once configured, the plugin automatically sends all WordPress emails (e.g., notifications, password resets, or form submissions) via your SMTP server using PHPMailer.

### Sending a Test Email

1. Go to **SMTP Mailer > Send Email** in the WordPress admin panel.
2. Fill in:
   - **Recipient**: The email address to send the test to
   - **Subject**: The email subject
   - **Message**: The content (HTML supported)
3. Click **Send Email** and check if the email arrives.
4. If it fails, enable debugging (see below) and check your settings.

## Troubleshooting

If emails aren't sending:

1. **Enable Debugging**:
   - In **SMTP Mailer > Configuration**, set **PHPMailer Debug** to 2, 3, or 4.
   - Send a test email again.
   - Check PHP error logs (usually in `wp-content/debug.log`).
2. **Enable WordPress Debug Logs**:
   Add these lines to `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```
3. **Common Issues**:
   - Incorrect SMTP credentials
   - Wrong port or security settings
   - Gmail blocking (use an App Password for 2FA accounts)

You can also check the [WordPress Codex](https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/) for debugging tips.

## Contributing

I welcome contributions! Here's how you can help:

1. Fork the repository.
2. Create a new branch: `git checkout -b feature/your-feature`.
3. Make your changes and commit: `git commit -m "Add your feature"`.
4. Push to your branch: `git push origin feature/your-feature`.
5. Open a Pull Request.

Please follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) and include a clear description of your changes.

## License

This plugin is licensed under the [GPL-2.0 License](https://www.gnu.org/licenses/gpl-2.0.html).

## Support

Have questions or need help? Leave a comment on my blog at [juredev.com](https://juredev.com) or open an issue on this repository. I'm happy to assist!

## Credits

Created by Jure ([juredev.com](https://juredev.com)). Inspired by my own struggles with WordPress email delivery and a desire for simplicity.