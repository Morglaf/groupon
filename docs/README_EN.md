# Groupon - Group Orders Application

## 📝 Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Technologies Used](#technologies-used)
- [Installation](#installation)
- [Configuration](#configuration)
- [MySQL Database](#mysql-database)
- [Testing and Verification](#testing-and-verification)
- [Roadmap](#roadmap)
- [Contribution](#contribution)

## Overview
**Groupon** is a modern web application for organizing group orders between friends, colleagues, or neighbors. Optimize your delivery costs by grouping your purchases! The application uses MySQL as the primary database for optimal performance and reliability.

## Features
- 🔐 Complete authentication system (login, registration, logout)
- 👤 User profile management
- 📦 Group order creation and management
- 👥 Participant management via unique links
- 💰 Automatic shipping cost distribution
- 📊 Dashboard to track orders
- 🛠️ Administration interface
- 🌍 Multilingual support (FR, EN, ES, DE)
- 🎨 Light/dark theme
- 📱 Responsive interface
- 🔒 Security with Cloudflare Turnstile

## Technologies Used
- **PHP 8.0+** - Main backend
- **MySQL** - Relational database
- **JavaScript** - Frontend interactivity
- **HTML5/CSS3** - Structure and styling
- **Bootstrap 5** - CSS framework
- **PDO** - Secure database access
- **Cloudflare Turnstile** - Anti-robot protection

## Installation
1. **Download** the project
2. **Configure** your web server with PHP 8.0+ and MySQL
3. **Access** `install.php` for automatic installation
4. **Configure** your MySQL database
5. **Start** creating your orders!

## Configuration
### Prerequisites
- **PHP 8.0 or higher**
- **MySQL 5.7 or higher**
- **Web server** (Apache/Nginx)
- **PHP Extensions**: PDO, PDO_MySQL, mbstring, openssl

### Automatic Installation
The application includes an automatic installation script similar to WordPress:

1. Access `http://your-site/install.php`
2. Configure your MySQL database parameters
3. Installation will automatically create the necessary tables
4. Create your administrator account
5. Delete `install.php` after installation

For more details on configuration, see the [detailed configuration guide](CONFIGURATION.md).

## MySQL Database
The application uses MySQL as the primary database with:

### Table Structure
- **`groupon_users`** - Users and authentication
- **`groupon_commandes`** - Group orders
- **`groupon_participants`** - Order participants

### Advantages
- **Performance** - Optimized queries and indexing
- **Reliability** - Transactions and data integrity
- **Backup** - Standard MySQL backup tools
- **Scalability** - Support for thousands of users
- **Security** - Prepared statements and SQL injection protection

## Testing and Verification

The application includes test scripts to verify that critical features are properly configured:

### MySQL Database Test

To verify if the database connection works:

1. Access your dashboard after login
2. Check that orders display correctly
3. Test creating a new order
4. Verify that participants can join orders

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

### Phase 1 - Q1 2025 ✅
- [x] Basic authentication system
- [x] Group order management
- [x] Administration interface
- [x] Initial multilingual support
- [x] MySQL migration
- [x] Automatic installation

### Phase 2 - Q2 2025
- [ ] Improved user interface
- [ ] Email notification system
- [ ] Secure payment integration
- [ ] Advanced search system
- [ ] PDF order export

### Phase 3 - Q3 2025
- [ ] Mobile application
- [ ] Public REST API
- [ ] Recommendation system
- [ ] Advanced analytics and dashboards
- [ ] Automatic backup system

## Contribution
Contributions are welcome! Here's how you can contribute:

1. Fork the project
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

---

[🔙 Back to language selection](../README.md) 