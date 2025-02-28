# Groupon Project Documentation

## 📝 Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Technologies Used](#technologies-used)
- [Installation](#installation)
- [Configuration](#configuration)
- [Testing and Verification](#testing-and-verification)
- [Roadmap](#roadmap)
- [Contribution](#contribution)

## Overview
This project is a group order management platform inspired by Groupon, allowing users to create, manage, and track their orders. The system includes a complete administration interface and supports multiple languages.

## Features
- 🔐 Complete authentication system (login, registration, logout)
- 👤 User profile management
- 📦 Order creation and management
- 🛠️ Administration interface
- 🌍 Multilingual support
- 🎨 Light/dark theme
- 📱 Responsive interface

## Technologies Used
- PHP
- JavaScript
- HTML5/CSS3
- API REST
- Bootstrap
- JSON data storage

## Installation
1. Clone the repository
2. Configure your web server (Apache/Nginx)
3. Configure the settings in `includes/config.php`
4. Launch the application

## Configuration
### Prerequisites
- PHP 7.4 or higher
- Web server (Apache/Nginx)

### Data Storage Configuration
1. Make sure the `data/users`, `data/commandes`, and `data/exports` folders exist and are writable
2. No SQL database is needed, all data is stored in JSON files

For more details on configuration, see the [detailed configuration guide](CONFIGURATION.md).

## Testing and Verification

The application includes test scripts to verify that critical features are properly configured:

### Cloudflare Turnstile Test

To verify if the Turnstile anti-robot protection is correctly configured:

1. Configure Turnstile in `includes/config.php` according to the instructions in the [configuration guide](CONFIGURATION.md#configuration-de-cloudflare-turnstile)
2. Access `http://your-site/test_turnstile.php`
3. Follow the on-screen instructions to test the functionality

### SMTP Configuration Test

To verify if email sending is correctly configured:

1. Configure SMTP parameters in `includes/config.php` according to the instructions in the [configuration guide](CONFIGURATION.md#configuration-smtp-pour-lenvoi-demails)
2. Modify `test_email.php` to indicate your email address
3. Access `http://your-site/test_email.php`
4. The script will attempt to send a test email and display the result

> **Security Note**: Once testing is complete, it is recommended to delete or restrict access to these test files.

## Roadmap

### Phase 1 - Q1 2025
- [x] Basic authentication system
- [x] Basic order management
- [x] Administration interface
- [x] Initial multilingual support

### Phase 2 - Q2 2025
- [ ] Improved user interface
- [ ] Notification system
- [ ] Secure payment integration
- [ ] Advanced search system

### Phase 3 - Q3 2025
- [ ] Mobile application
- [ ] Public API
- [ ] Recommendation system
- [ ] Analytics and dashboards

## Contribution
Contributions are welcome! Here's how you can contribute:

1. Fork the project
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

---

[🔙 Back to language selection](../README.md) 