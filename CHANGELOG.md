# Changelog

All notable changes to the **Simple SMTP Mailer Plugin** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2] - 2025-05-03

### Added
- Initial release of the Simple SMTP Mailer Plugin.
- SMTP configuration via WordPress admin panel, including host, port, security (SSL/TLS), and authentication settings.
- Secure password encryption using OpenSSL and WordPress salts (`AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`).
- Integration with PHPMailer for reliable email delivery.
- Test email feature to verify SMTP setup from the admin panel.
- PHPMailer debug levels (0-4) for troubleshooting.
- Customizable "From Email" and "From Name" fields for outgoing emails.
- Admin menu with dedicated "Configuration" and "Send Email" pages.
- Activation and deactivation hooks for clean setup and cleanup.
- Comprehensive error logging for encryption and PHPMailer issues.

### Notes
- Requires PHP 7.4+ and the OpenSSL extension.
- Compatible with WordPress 5.0+.
- Fallback to PHP `mail()` if SMTP configuration is incomplete or invalid.

## [Unreleased]

### Planned
- Support for additional SMTP providers with predefined settings.
- Option to send test emails with predefined templates.
- Enhanced debug log viewer within the admin panel.