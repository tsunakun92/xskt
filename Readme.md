# **BASE**

Laravel 12 application framework with modular architecture, multilingual support, and comprehensive admin panel.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Setup Local Environment](#setup-local-environment)
3. [Install Dependencies](#install-dependencies)
4. [Access Application](#access-application)

---

## System Requirements

### Hardware

-   CPU: 2 cores minimum
-   RAM: 4GB minimum (8GB recommended)
-   Storage: 20GB free space

### Software

-   OS: Linux/Windows/macOS
-   PHP: 8.2+ (8.4 recommended)
-   MySQL/MariaDB: 10.3+
-   Node.js: 22.x
-   Composer: 2.0+
-   Git
-   Redis: 6.x+

---

## Setup Local Environment

### Option 1: Laravel Herd (Windows/macOS)

**Step 1: Install Requirements**

-   Download Laravel Herd: <https://herd.laravel.com/>
-   Download MySQL: <https://dev.mysql.com/downloads/mysql/>
-   Download MySQL Workbench: <https://dev.mysql.com/downloads/workbench/>

**Step 2: Clone Repository**

```bash
cd D:\Source
git clone https://github.com/bisync/laravel_core.git
```

**Step 3: Add to Herd**

-   Open Laravel Herd
-   Go to "Sites" → "Add" → "Link existing project"
-   Select project folder: `D:\Source\base_project\web`
-   Set project name: `base`
-   Select PHP: 8.4
-   Domain will be: `http://base.test`

### Option 2: Laravel Homestead

**Step 1: Install VirtualBox & Vagrant**

-   VirtualBox: <https://www.virtualbox.org/wiki/Download_Old_Builds_6_1>
-   Vagrant: <https://www.vagrantup.com/>

**Step 2: Clone Repository**

-   Clone repository to `path/to/source`

```bash
cd  path/to/source
git clone https://github.com/bisync/laravel_core.git
```

**Step 3: Setup Homestead**

-   Download Laravel Homestead 14.0.2
-   Copy vagrant box to: `C:\Users\{username}\.vagrant.d\boxes`
-   Copy SSH keys to: `C:\Users\{username}\.ssh`

**Step 4: Configure Homestead.yaml**

```yaml
ip: "192.168.19.19"
memory: 8096
cpus: 2
folders:
    - map: D:/Source/base
      to: /home/vagrant/base/
sites:
    - map: base.test
      to: /home/vagrant/base/web/public
```

**Step 5: Update Hosts File**

```
192.168.19.19  base.test
```

**Step 6: Start Homestead**

```bash
cd {path/to/homestead}
vagrant up
```

### Option 3: Docker

See [DockerSetup](DockerSetup.md) for Docker configuration.

---

## Setup Development Environment

### Extensions recommendation

-   When using vscode, install extension recommended in modal popup when open project for first time.
-   Or install extension manually by open `extensions` and search `@recommended`
    See [vscode/extensions.json](.vscode/extensions.json) for extension recommendation.

### Setting recommended PHP path (Windows)

#### Option 1: Use Laravel Herd

-   In vscode: enter Ctrl + Shift + P and select "Preferences: Open User Settings (JSON)"
-   Add 2 line setting:

```json
"php.validate.executablePath": "C:\\Users\\<username>\\.config\\herd\\bin\\php\\php8.2\\php.exe",
"phpfmt.php_bin": "C:\\Users\\<username>\\.config\\herd\\bin\\php\\php8.2\\php.exe",
```

#### Option 2: Download PHP 8.2 (Windows)

-   Download PHP 8.2: <https://windows.php.net/download#php-8.2>
-   Extract to: `C:\php8.2`
-   In vscode: enter Ctrl + Shift + P and select "Preferences: Open User Settings (JSON)"
-   Add 2 line setting:

```json
"php.validate.executablePath": "C:\\php8.2\\php.exe",
"phpfmt.php_bin": "C:\\php8.2\\php.exe",
```

---

## Install Dependencies

```bash
# For Homestead
vagrant ssh
cd base/web
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p bootstrap/cache

# For Herd/Local
cd path/to/project
New-Item -ItemType Directory -Path storage\framework\sessions, storage\framework\views, storage\framework\cache, bootstrap\cache -Force

# Install PHP dependencies
composer install
# Logging config
php artisan logging:config

# Setup environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate --seed
php artisan migrate --env=testing --seed

# Generate IDE helpers
php artisan ide-helper:generate

# Install Node.js 22.x
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
nvm install 22
nvm use 22

# Install and build assets
yarn
yarn build
```

---

## Access Application

### URLs

-   **Local**: <http://base.test>
-   **Homestead**: <http://192.168.19.19>

### Default Accounts

```
Super Admin:
  Username: sadmin
  Password: sadminsadmin

Admin:
  Username: admin
  Password: adminsadmin

User:
  Username: user
  Password: usersuser
```

### User Roles

-   **Super Admin**: Full system access
-   **Admin**: System management with restrictions
-   **User**: Basic system access

---

## Documentation

-   [Cheatsheet](CHEATSHEET.md) - Commands and guides
-   [Laravel Documentation](https://laravel.com/docs)
-   [Laravel Modules](https://laravelmodules.com/docs/v11/introduction)

---

**Version**: 1.0  
**Last Updated**: 2025-01-02
