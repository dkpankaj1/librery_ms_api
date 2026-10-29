# Library API — API Documentation

This document describes every API endpoint in the Library API, including request/response formats and authentication.

## Base URL

```
http://127.0.0.1:8000/api
```

## Authentication

All endpoints except `POST /api/login` require a Bearer token issued by the login endpoint:

```
Authorization: Bearer <token>
```

---

## Response Structure

Every endpoint returns JSON in one of the following shapes:

**Success:**
```json
{
  "success": true,
  "message": "...",
  "data": { }
}
```

**Error:**
```json
{
  "success": false,
  "message": "...",
  "errors": {
    "field": ["validation message"]
  }
}
```

Response handling is centralized in the `ApiResponse` trait
([`app/Traits/ApiResponse.php`](app/Traits/ApiResponse.php)).

---

## Endpoints

### Auth

#### 1. Login

```
POST /api/login
```

Authenticates a user and returns an access token along with the user and their associated library.

**Request body:**
```json
{
  "email": "test@example.com",
  "password": "password"
}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Test User",
      "email": "test@example.com",
      "library": { "id": 1, "name": "Central Library", "code": "CENTRAL" }
    },
    "token": "2|abc123..."
  }
}
```

| Status | Condition |
|--------|-----------|
| `200`  | Success |
| `401`  | Invalid credentials |
| `403`  | Inactive or expired account |
| `422`  | Validation failed |

---

#### 2. Get Authenticated User

```
GET /api/me
```

Returns the authenticated user and their library.

**Response `200`:**
```json
{
  "success": true,
  "message": "Authenticated user",
  "data": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com",
    "library": { "id": 1, "name": "Central Library" }
  }
}
```

| Status | Condition |
|--------|-----------|
| `200`  | Success |
| `401`  | Unauthenticated |

---

#### 3. Update Profile

```
PUT /api/me/profile
```

Updates the authenticated user's profile. All fields are optional.

**Request body:**
```json
{
  "name": "New Name",
  "email": "new@example.com",
  "phone": "1234567890",
  "avatar": "https://example.com/avatar.jpg"
}
```

| Field     | Rule                          |
|-----------|-------------------------------|
| `name`    | optional, string, max 255     |
| `email`   | optional, email, unique       |
| `phone`   | optional, nullable, max 20    |
| `avatar`  | optional, nullable, max 255   |

**Response `200`:** the updated user object.

| Status | Condition |
|--------|-----------|
| `200`  | Success |
| `422`  | Validation failed (e.g. duplicate email) |

---

#### 4. Change Password

```
PUT /api/me/password
```

**Request body:**
```json
{
  "current_password": "password",
  "password": "new-secret-123",
  "password_confirmation": "new-secret-123"
}
```

| Field                  | Rule                              |
|------------------------|-----------------------------------|
| `current_password`     | required, must match current      |
| `password`             | required, min 8, must be confirmed |
| `password_confirmation`| required, must match `password`   |

**Response `200`:**
```json
{
  "success": true,
  "message": "Password changed successfully",
  "data": null
}
```

| Status | Condition |
|--------|-----------|
| `200`  | Success |
| `422`  | Wrong current password, mismatch confirmation, or weak password |

---

#### 5. Logout

```
POST /api/logout
```

Revokes the current access token.

**Response `200`:**
```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null
}
```

---

### Libraries

All library endpoints require authentication.

#### 6. Create Library

```
POST /api/libraries
```

**Request body:**
```json
{
  "name": "Central Library",
  "code": "CENTRAL",
  "slug": "central-library",
  "description": "Main branch",
  "address": "123 Main St",
  "phone": "1234567890",
  "email": "central@example.com",
  "is_active": true
}
```

| Field         | Rule                                |
|---------------|-------------------------------------|
| `name`        | required, string, max 255           |
| `code`        | required, string, max 50, unique    |
| `slug`        | optional, unique (auto from `name`) |
| `description` | optional, string                    |
| `address`     | optional, max 255                   |
| `phone`       | optional, max 20                    |
| `email`       | optional, email, max 255            |
| `is_active`   | optional, boolean                   |

**Response `201`:** the created library object.

| Status | Condition |
|--------|-----------|
| `201`  | Created |
| `422`  | Validation failed (e.g. duplicate/missing code) |

---

#### 7. Show Library

```
GET /api/libraries/{library}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Library retrieved successfully",
  "data": {
    "id": 1,
    "name": "Central Library",
    "code": "CENTRAL",
    "slug": "central-library",
    "is_active": true
  }
}
```

| Status | Condition |
|--------|-----------|
| `200`  | Success |
| `404`  | Library not found |

---

#### 8. Update Library

```
PUT /api/libraries/{library}
```

**Request body:** same as create; fields accept partial updates, and `code`/`slug` unique validation ignores the current record.

**Response `200`:** the updated library object.

---

## Endpoint Summary

| Method | Path                    | Auth   | Description            |
|--------|-------------------------|--------|------------------------|
| POST   | `/api/login`            | No     | Authenticate & get token |
| GET    | `/api/me`               | Yes    | Authenticated user + library |
| PUT    | `/api/me/profile`       | Yes    | Update profile         |
| PUT    | `/api/me/password`      | Yes    | Change password        |
| POST   | `/api/logout`           | Yes    | Revoke token           |
| POST   | `/api/libraries`        | Yes    | Create library         |
| GET    | `/api/libraries/{library}` | Yes | Show library         |
| PUT    | `/api/libraries/{library}` | Yes | Update library       |

---

## Testing

Run the automated test suite:

```bash
php artisan test
```

The suite covers all endpoints described above. See `tests/Feature/`.
