# Library API

A RESTful API for managing libraries and user accounts, built with [Laravel](https://laravel.com).

## Overview

This project provides token-based authentication (via [Laravel Sanctum](https://laravel.com/docs/sanctum)) and a library management module. All API endpoints return a consistent JSON response structure.

> 📖 For full endpoint documentation, see [API.md](API.md).

### Tech Stack

- **PHP** ^8.3
- **Laravel** ^13.17
- **Laravel Sanctum** ^4.3 (API token authentication)
- **SQLite** (default; configurable via `.env`)
- **PHPUnit** ^12 (feature tests)

---

## Requirements

- PHP 8.3+
- Composer 2.x

---

## Installation

```bash
# 1. Clone and install dependencies
composer install

# 2. Set up environment
copy .env.example .env
php artisan key:generate

# 3. Run migrations
php artisan migrate

# 4. Seed the test user
php artisan db:seed

# 5. Start the development server
php artisan serve
```

The API is served at `http://127.0.0.1:8000/api`.

### Seeded Test User

| Field    | Value               |
|----------|---------------------|
| Email    | `test@example.com`  |
| Password | `password`          |

---

## Data Model

### Users

| Column                | Type      | Notes                             |
|-----------------------|-----------|-----------------------------------|
| `name`                | string    |                                   |
| `email`               | string    | unique                            |
| `password`            | string    | hashed                            |
| `library_id`          | bigint    | nullable foreign key              |
| `user_type`           | string    | `super_admin` \| `library_user`   |
| `phone`               | string    | nullable                          |
| `account_status`      | string    | `active` \| `inactive` \| `suspended` |
| `account_valid_until` | timestamp | nullable                          |
| `last_login_at`       | timestamp | nullable                          |
| `is_active`           | boolean   |                                   |
| `avatar`              | string    | nullable                          |
| `notes`               | text      | nullable                          |

### Libraries

| Column        | Type    | Notes    |
|---------------|---------|----------|
| `name`        | string  |          |
| `code`        | string  | unique   |
| `slug`        | string  | unique   |
| `description` | text    | nullable |
| `address`     | string  | nullable |
| `phone`       | string  | nullable |
| `email`       | string  | nullable |
| `is_active`   | boolean |          |

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php      # login, me, profile, password, logout
│   │   └── LibraryController.php   # library CRUD (create/update/show)
│   └── Requests/
│       ├── LoginRequest.php
│       ├── ChangePasswordRequest.php
│       ├── StoreLibraryRequest.php
│       └── UpdateProfileRequest.php
├── Models/
│   ├── Library.php
│   └── User.php
└── Traits/
    └── ApiResponse.php             # unified JSON response helper
```

---

## Testing

Run the automated test suite:

```bash
php artisan test
```

The suite covers authentication (login/logout), profile management, password change, and the library module. See `tests/Feature/`.

---

## Documentation

- [API.md](API.md) — full API endpoint reference

---

## License

MIT