<p align="center">
  <img src="openipam_banner.png" alt="OpenIPAM Banner">
</p>

# OpenIPAM - IP Address Management Tool

A modern IPAM (IP Address Management) tool built with Laravel, Filament Admin Panel, and Docker/DevContainer support.

- [OpenIPAM - IP Address Management Tool](#openipam---ip-address-management-tool)
  - [Features](#features)
  - [Technology Stack](#technology-stack)
  - [Installation](#installation)
    - [With Docker-Compose (Recommended for Production)](#with-docker-compose-recommended-for-production)
    - [With Docker (Recommended for Dev or Homelab)](#with-docker-recommended-for-dev-or-homelab)
    - [With Docker (Without Stack)](#with-docker-without-stack)
    - [Environment Variables for Docker](#environment-variables-for-docker)
  - [🌍 Internationalization (i18n)](#-internationalization-i18n)
    - [Supported Languages](#supported-languages)
    - [Adding New Languages](#adding-new-languages)
  - [Usage](#usage)
    - [Admin Panel](#admin-panel)
    - [Language Switching](#language-switching)
    - [Dashboard Tree Widget](#dashboard-tree-widget)
    - [CSV Export for Devices](#csv-export-for-devices)
    - [CSV Import for Devices 🆕](#csv-import-for-devices-)
      - [**Import Features:**](#import-features)
      - [**Usage:**](#usage-1)
      - [**IP Address Processing:**](#ip-address-processing)
      - [**Example CSV Formats:**](#example-csv-formats)
    - [IP Address Bulk Management](#ip-address-bulk-management)
    - [Mass IP Creation](#mass-ip-creation)
    - [IP Groups and Device Assignment](#ip-groups-and-device-assignment)
    - [Device URL Management](#device-url-management)
    - [User Features](#user-features)
  - [Contributing](#contributing)
  - [CLI Commands](#cli-commands)
    - [User Management Commands](#user-management-commands)
  - [Configuration](#configuration)
    - [Important Environment Variables](#important-environment-variables)
  - [Troubleshooting](#troubleshooting)
    - [Docker](#docker)
    - [Common Issues](#common-issues)
  - [Testing](#testing)
    - [Run PHPUnit Tests](#run-phpunit-tests)
  - [API](#api)
  - [API \& Integration](#api--integration)
  - [Support](#support)
  - [License](#license)


## Features

- ✅ **User Authentication** with Email 2FA and Gravatar integration
- ✅ **IPv4 and IPv6 Address Management** with intelligent group organization
- ✅ **Device Inventory Management** with flexible IP assignment and URL links
- ✅ **Bulk IP Generation** by subnet (CIDR) with range limiting
- ✅ **IP Groups** for VLAN/room organization with bulk management
- ✅ **Dashboard Tree Widget** with hierarchical device/IP overview
- ✅ **CSV Export** for device inventory with complete data
- ✅ **CSV Import** for devices with IP addresses (IPv4/IPv6) and intelligent automatic column mapping
- ✅ **Bulk Actions** for mass IP address editing
- ✅ **URL Management** for device dashboards and login interfaces
- ✅ **Modern Admin Panel** with Filament 3.x
- ✅ **CLI Tools** for user and system administration
- ✅ **Docker-Ready** with automatic admin user creation
- ✅ **DevContainer Support** for seamless development
- ✅ **Internationalization (i18n)** with English and German support
- ✅ **Language Switching** with navbar switcher and user preferences

## Technology Stack

- **Backend:** Laravel 11, PHP 8.4, Filament 3.x Admin Panel  
- **Frontend:** Filament Admin Panel (Pure Filament - no native Livewire)
- **Database:** MySQL/MariaDB with optimized indexes
- **Email:** SMTP with Mailpit for development, Email 2FA support
- **Container:** Docker with multi-stage builds, DevContainer
- **Testing:** PHPUnit 11.5 with 150+ comprehensive tests (including CSV import/export and i18n tests)
- **CI/CD:** GitLab CI with separate templates for DB/Non-DB jobs
- **Internationalization:** Laravel i18n with English (default) and German language support

## Installation

### With Docker-Compose (Recommended for Production)

Generate a strong password with:
```bash
openssl rand -base64 12
```

```bash
services:
  openipam:
    image: triopsi/openipam:latest
    container_name: ipam-app
    restart: unless-stopped
    ports:
      - "8080:80"
    environment:
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: ipam
      DB_USERNAME: ipam
      DB_PASSWORD: secret # Change me

      # Admin bootstrap (nur beim ersten Start sinnvoll)
      ADMIN_USERNAME: admin # Change me
      ADMIN_PASSWORD: admin123 # Change me
      ADMIN_EMAIL: admin@example.com # Change me

      # Redis (wenn du es nutzen willst)
      CACHE_STORE: redis
      SESSION_DRIVER: redis
      REDIS_HOST: redis
      REDIS_PORT: 6379

      # Mail (Beispiel)
      MAIL_MAILER: smtp
      MAIL_HOST: your-mail-host
      MAIL_PORT: 587
      MAIL_USERNAME: ""
      MAIL_PASSWORD: ""
      MAIL_ENCRYPTION: tls
      MAIL_FROM_ADDRESS: noreply@ipam.local
      MAIL_FROM_NAME: IPAM
    volumes:
      - openipam-storage:/var/www/html/storage
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - openipam-net

  db:
    image: mariadb:10.11
    container_name: ipam-db
    restart: unless-stopped
    environment:
      - MYSQL_DATABASE=ipam
      - MYSQL_USER=ipam
      - MYSQL_PASSWORD=secret # Change me
      - MYSQL_ROOT_PASSWORD=root
    volumes:
      - openipam-db-storage:/var/lib/mysql
    networks:
      - openipam-net
    healthcheck:
      test: ["CMD-SHELL", "mariadb-admin ping -h 127.0.0.1 -uroot -p$$MYSQL_ROOT_PASSWORD --silent"]
      interval: 5s
      timeout: 3s
      retries: 20
      start_period: 20s

  redis:
    image: redis:7-alpine
    container_name: ipam-redis
    restart: unless-stopped
    networks:
      - openipam-net
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 3s
      retries: 20

networks:
  openipam-net:

volumes:
  openipam-storage:
  openipam-db-storage:
```

### With Docker (Recommended for Dev or Homelab)

```bash
# Clone repository
git clone https://github.com/triopsi/OpenIPAM.git
cd openipam

# Start with Docker Compose
docker-compose up -d

# Access the application
# Web: http://localhost:8080

# Default admin login:
# Email: admin@example.com
# Password: admin123
```

### With Docker (Without Stack)

```bash
docker run -d \
  -p 8080:80 \
  -e ADMIN_USERNAME=admin \
  -e ADMIN_PASSWORD=secure_password \
  -e ADMIN_EMAIL=admin@yourdomain.com \
  -e DB_HOST=your-db-host \
  -e DB_DATABASE=ipam \
  -e DB_USERNAME=ipam \
  -e MAIL_HOST=your-mail-host \
  -e MAIL_USERNAME=your-mail-port \
  -e MAIL_PASSWORD=your-mail-pass \
  -e MAIL_FROM_ADDRESS=your-mail-from-address \
  git.triopsi.dev:5050/triopsi/openipam:latest
```

### Environment Variables for Docker

```yaml
# Admin user configuration (automatic creation on first start)
ADMIN_USERNAME=admin          # Admin user name
ADMIN_PASSWORD=admin123       # Password (minimum 8 characters recommended)
ADMIN_EMAIL=admin@example.com # Email address

# Database configuration
DB_CONNECTION=mysql
DB_HOST=db                    # Database hostname
DB_PORT=3306                  # Database port
DB_DATABASE=ipam              # Database name
DB_USERNAME=ipam              # Database user
DB_PASSWORD=secret            # Database password

# Email configuration (for 2FA)
MAIL_MAILER=smtp
MAIL_HOST=mailpit             # SMTP server (or external like SendGrid)
MAIL_PORT=1025                # SMTP port
MAIL_USERNAME=null            # SMTP username (if required)
MAIL_PASSWORD=null            # SMTP password (if required)
MAIL_ENCRYPTION=null          # SMTP encryption (tls/ssl)
MAIL_FROM_ADDRESS=noreply@ipam.local
MAIL_FROM_NAME=IPAM

# App configuration
APP_ENV=production            # Environment (local/staging/production)
APP_DEBUG=false               # Debug mode (only for development)
APP_URL=http://localhost:8080 # Base URL of application

# Internationalization
APP_LOCALE=en                 # Default language (en/de)
APP_FALLBACK_LOCALE=en        # Fallback language
```

## 🌍 Internationalization (i18n)

OpenIPAM supports multiple languages with comprehensive translation coverage:

### Supported Languages
- **English (en)** - Default language
- **German (de)** - Full translation

### Adding New Languages

OpenIPAM uses a centralized language configuration system that makes adding new languages extremely simple:
See [CONTRIBUTING.md](CONTRIBUTING.md) for more information.

## Usage

### Admin Panel

After installation, you can log in at `/admin` with your admin credentials.

**Main Features:**
- **Dashboard:** Overview of IP addresses and devices with statistics
  - **Tree Widget:** Hierarchical display of all devices and IP groups
  - **Clickable URLs:** Direct links to device dashboards/logins
- **User Management:** Create, edit users, manage 2FA
- **IP Addresses:** Create and manage individual IPs or entire subnets
  - **Bulk Actions:** Mass editing of gateway, groups, status, etc.
  - **Group Column:** Clear display of IP group membership
- **IP Groups:** Organize IPs into VLAN/room groups
- **Devices:** Device inventory with intelligent IP assignment
  - **URL Field:** Store dashboard/login URLs for quick access
  - **CSV Export:** Complete export of all device data including IPs
- **Profile:** Personal settings, password, 2FA, Gravatar, language preference

### Language Switching

**Navbar Language Switcher:**
1. Click the language dropdown in the top-right navbar
2. Select your preferred language (English/German)
3. The interface changes immediately

**Profile Language Setting:**
1. Go to your user profile
2. Select your preferred language in the language dropdown
3. Click "Save" to persist your preference
4. The system will remember your choice for future sessions

### Dashboard Tree Widget

The new Tree Widget provides an intuitive overview of your network:

1. **Folder Organization:** IP groups are displayed as expandable folders
2. **Hierarchical View:** Devices are organized under their IP groups
3. **Device URLs:** Click on URL icons for direct access to device dashboards
4. **Quick Actions:** Edit button for quick device editing
5. **Live Statistics:** Overview of device count and IP usage

### CSV Export for Devices

Export your device inventory with all relevant data:

1. Select devices in the table (checkboxes)
2. Click "Export as CSV" in the bulk actions
3. Confirm export in the modal dialog
4. The CSV file will be downloaded automatically

**Exported Data:** Name, hostname, MAC address, type, location, status, URL, description, IP addresses, primary IP, creation/modification dates

### CSV Import for Devices 🆕

Import devices with IP addresses from CSV files:

#### **Import Features:**
- ✅ **Flexible Delimiters:** Comma, semicolon, tab or pipe
- ✅ **Header Detection:** CSV with or without headers
- ✅ **Live Preview:** Real-time preview of first 5 rows
- ✅ **Intelligent Automatic Column Mapping:** 
  - **Automatic Detection:** Columns are automatically mapped to appropriate fields
  - **Multi-Language Support:** Supports German and English column names
  - **Smart Matching:** Recognizes patterns like "ip", "mac", "hostname", "location", etc.
  - **With Header:** Automatic recognition of column names
  - **Without Header:** Column number with example values
- ✅ **IPv4 & IPv6 Support:** Complete IP address processing
- ✅ **Primary & Secondary IPs:** Multiple IP addresses per device
- ✅ **Duplicate Handling:** Skip, Overwrite or Merge modes

#### **Usage:**

1. **Prepare CSV file** - Example format:
```csv
Name,Hostname,Primary IP,Secondary IPs,Type,Location,Status,Description
Router-01,router01.example.com,192.168.1.1,10.0.0.1;172.16.0.1,router,Server Room,active,Main gateway
Server-Web,web01.example.com,192.168.10.10,2001:db8::1;fe80::1,server,Data Center,active,Web server
```

2. **Start import:**
   - Click "CSV Import" in the device table
   - Upload your CSV file
   - Select delimiter and header option
   - See the live preview of your data

3. **Automatic column mapping:**
   - The system automatically recognizes matching columns based on names
   - **Supported Patterns:**
     - **Name:** name, device_name, title, titel
     - **Hostname:** hostname, host_name, host, fqdn, server_name
     - **MAC:** mac, mac_address, macaddress, mac_addr
     - **IP:** ip, ip_address, primary_ip, ipaddress, ipv4
     - **Type:** type, device_type, typ, category, kategorie
     - **Location:** location, standort, site, ort, position
     - **Status:** status, state, zustand, condition
     - **URL:** url, link, website, web, address
     - **Description:** description, beschreibung, desc, comment, notizen
   - **Manual Adjustment:** Mappings can be changed manually at any time

4. **Final mapping:**
   - **Device Data:** Name (required), hostname, MAC address, type, location, status, URL, description
   - **IP Addresses:** Primary IP address, secondary IP addresses (semicolon-separated)
   - **Ignore:** Unused columns can be skipped

5. **Choose duplicate behavior:**
   - **Skip:** Skip existing devices
   - **Overwrite:** Completely overwrite
   - **Merge:** Only fill empty fields

#### **IP Address Processing:**
- **Automatic IPv4/IPv6 detection** and validation
- **Reuse:** Existing IP addresses are automatically recognized
- **Primary/Secondary marking:** Correct pivot assignment in database
- **Error handling:** Invalid IPs are skipped
- **Group assignment:** Imported IPs automatically get "imported" group

#### **Example CSV Formats:**

**With comma delimiter:**
```csv
Name,Primary IP,Secondary IPs
Router-01,192.168.1.1,10.0.0.1;172.16.0.1
Server-01,192.168.10.10,2001:db8::1;fe80::1
```

**With semicolon delimiter:**
```csv
Name;Hostname;Type;Primary IP
Router-01;router01.example.com;router;192.168.1.1
Switch-01;switch01.example.com;switch;192.168.1.2
```

**Without header:**
```
Router-01,router01.example.com,192.168.1.1,router,active
Switch-01,switch01.example.com,192.168.1.2,switch,active
```

### IP Address Bulk Management

Edit multiple IP addresses simultaneously:

1. **Change Gateway:** Set a new gateway for selected IPs
2. **Change IP Group:** Mass assignment to IP groups  
3. **Change Subnet:** Bulk update of subnet information
4. **Change Status:** Mass status change (Available/Assigned/Reserved)
5. **Change Description:** Flexible text management (Replace/Append/Prepend/Clear)
6. **Advanced Editing:** Combined change of all fields in one dialog

### Mass IP Creation

1. Navigate to "IP Addresses" → "Add"
2. Select "Generate entire subnet"
3. Enter a CIDR (e.g. `192.168.1.0/24`)
4. Optional: Limit start index and count
5. All subnet IPs will be created automatically (excluding network/broadcast)

### IP Groups and Device Assignment

1. Create IP groups (e.g. "VLAN 100", "Server Room")
2. Assign IP addresses to groups
3. When creating devices, first select the group, then the IP
4. Optional: Store URLs for device dashboards or login interfaces
5. Supports many-to-many relationships for complex networks

### Device URL Management

For each device you can store URLs:

1. **Dashboard URLs:** Direct links to monitoring dashboards (Grafana, etc.)
2. **Login Interfaces:** Router/Switch/Server web interfaces  
3. **Management Tools:** IPMI, iDRAC, other hardware management URLs

**Access:**
- **Table Column:** URL column with clickable links (optionally visible)
- **Tree Widget:** URL icons next to device names for quick access
- **Automatic Target="_blank":** All URLs open in new tab

### User Features

- **Gravatar Integration:** Automatic avatar display based on email
- **2FA:** Email-based two-factor authentication
- **Profile Management:** Complete self-management of user settings
- **Language Preference:** Set preferred language (English/German)

## Contributing

Thank you for your interest in contributing! Instructions for contributions can be found [here](CONTRIBUTING.md).

## CLI Commands

The system provides comprehensive console commands for user management:

### User Management Commands

```bash
# Create user
php artisan user:create
php artisan user:create --name="Admin" --email="admin@example.com" --password="password123"

# Edit user
php artisan user:update admin@example.com
php artisan user:update admin@example.com --name="New Name"
php artisan user:update admin@example.com --password="new_password"
php artisan user:update admin@example.com --reset-2fa

# List users
php artisan user:list
php artisan user:list --filter="admin"
php artisan user:list --with-2fa

# Show user details
php artisan user:info admin@example.com

# Delete user
php artisan user:delete admin@example.com
php artisan user:delete admin@example.com --force
```

## Configuration

### Important Environment Variables

```env
# App basic configuration
APP_NAME="OpenIPAM"
APP_ENV=local                 # local, staging, production
APP_KEY=                      # Laravel App Key (auto-generated)
APP_DEBUG=true                # Debug mode for development
APP_URL=http://localhost

# Internationalization
APP_LOCALE=en                 # Default language (en/de)
APP_FALLBACK_LOCALE=en        # Fallback language

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ipam
DB_USERNAME=root
DB_PASSWORD=

# Email (for 2FA)
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@ipam.local
MAIL_FROM_NAME="${APP_NAME}"

# Cache & Sessions (optional)
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

## Troubleshooting

### Docker

```bash
# Show container logs
docker-compose logs -f app

# Log into container
docker-compose exec app bash

# Reset database
docker-compose down -v
docker-compose up -d

# Fix permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Common Issues

- **500 Error:** Check file permissions (`storage/` and `bootstrap/cache/`) and `.env` configuration
- **DB Connection:** Check database credentials and network connectivity
- **Email 2FA not working:** Check SMTP configuration and Mailpit container
- **Assets missing/not loaded:** Run `npm run build` or check Vite config
- **Gravatar not visible:** Check DNS resolution to gravatar.com
- **Permission Denied:** Run `chown -R www-data:www-data storage bootstrap/cache`
- **Language not switching:** Check if user has language preference set and middleware is working

## Testing

### Run PHPUnit Tests

To run all tests for models, IPAM features, authentication, CSV export/import, internationalization, and helpers:

```bash
php artisan test --testdox
```

The test suite includes:
- **Unit Tests:** Device, IpAddress, User models (including URL field and language tests)
- **Feature Tests:** IPAM device & IP management, network discovery
- **Authentication Tests:** Login, 2FA, session handling
- **CSV Import Tests:** Comprehensive tests for CSV import with IP addresses
- **CSV Export Tests:** Complete test suite for device CSV export
- **Internationalization Tests:** i18n middleware, language switching, translation coverage
- **Language Switcher Tests:** Livewire component functionality
- **Profile Language Tests:** User language preferences and profile integration
- **Helper Tests:** Network utilities (IPv4/IPv6 validation, MAC addresses, etc.)

## API

OpenIPAM provides a comprehensive RESTful API for programmatic access to all IPAM functionality:

- **Full CRUD operations** for devices, IP addresses, users, and groups
- **Token-based authentication** with customizable permissions
- **Bulk operations** for IP address management
- **Advanced filtering and search** capabilities
- **Pagination support** for large datasets
- **Rate limiting** and security features

📖 **[Complete API Documentation & Examples](API-Tests-Dokumentation.md)**

The API documentation includes:
- Authentication examples with cURL
- Detailed endpoint documentation
- Request/response examples
- Error handling
- Rate limiting information

## API & Integration

The system provides a solid foundation for:
- **REST API** (via Laravel API Resources)
- **Webhooks** for external systems
- **LDAP/AD Integration** (extensible)
- **Import/Export** of IP inventories
- **Monitoring Integration** (SNMP, etc.)

## Support

For issues or questions:

1. Check the logs: `storage/logs/laravel.log`
2. Review environment variables
3. Consult Laravel/Filament documentation

## License

OpenIPAM is open-sourced software licensed under the [MIT license](https://opensource.org/license/MIT).
