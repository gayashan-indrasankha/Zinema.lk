# Auth API

## Overview

The Auth API provides the authentication foundation for Zinema.lk – Modern Full-Stack Movie Streaming Platform.

It supports:

- Public user registration.
- User login with JWT access token response.
- Current user profile lookup.
- Admin role verification through a protected ping endpoint.

It does not include frontend code, admin catalog features, video upload, or FFmpeg processing.

## Endpoints

```text
POST /api/auth/register
POST /api/auth/login
GET /api/auth/me
GET /api/admin/ping
```

## POST /api/auth/register

Creates a standard user account and assigns the `User` role.

Public registration never creates `Admin` accounts.

### Request

```json
{
  "displayName": "Demo User",
  "email": "demo.user@example.test",
  "password": "LocalPass123"
}
```

### Response

```json
{
  "accessToken": "<jwt-access-token>",
  "expiresAt": "2026-06-28T12:00:00Z",
  "user": {
    "id": "00000000-0000-0000-0000-000000000001",
    "displayName": "Demo User",
    "email": "demo.user@example.test",
    "roles": [
      "User"
    ]
  }
}
```

The response never returns the password or password hash.

## POST /api/auth/login

Validates user credentials and returns a JWT access token.

### Request

```json
{
  "email": "demo.user@example.test",
  "password": "LocalPass123"
}
```

### Response

```json
{
  "accessToken": "<jwt-access-token>",
  "expiresAt": "2026-06-28T12:00:00Z",
  "user": {
    "id": "00000000-0000-0000-0000-000000000001",
    "displayName": "Demo User",
    "email": "demo.user@example.test",
    "roles": [
      "User"
    ]
  }
}
```

Invalid credentials return a `401` Problem Details response.

## GET /api/auth/me

Returns the current authenticated user profile and roles.

### Authorization

```http
Authorization: Bearer <jwt-access-token>
```

### Response

```json
{
  "id": "00000000-0000-0000-0000-000000000001",
  "displayName": "Demo User",
  "email": "demo.user@example.test",
  "roles": [
    "User"
  ]
}
```

Unauthenticated requests return `401`.

## GET /api/admin/ping

Verifies the role-based authorization foundation.

This endpoint does not provide admin catalog features.

### Authorization

```http
Authorization: Bearer <admin-jwt-access-token>
```

### Required Role

```text
Admin
```

### Response

```json
{
  "message": "Admin authorization is working."
}
```

Unauthenticated requests return `401`. Authenticated users without the `Admin` role return `403`.

## Local Testing Flow

Run the API:

```powershell
dotnet run --project backend/src/Zinema.Api
```

Register a user:

```http
POST http://localhost:5145/api/auth/register
Content-Type: application/json

{
  "displayName": "Demo User",
  "email": "demo.user@example.test",
  "password": "LocalPass123"
}
```

Login:

```http
POST http://localhost:5145/api/auth/login
Content-Type: application/json

{
  "email": "demo.user@example.test",
  "password": "LocalPass123"
}
```

Use the returned access token:

```http
GET http://localhost:5145/api/auth/me
Authorization: Bearer <jwt-access-token>
```

## Response Safety

Auth responses expose only:

- JWT access token.
- Expiration.
- User id.
- Display name.
- Email.
- Roles.

Passwords and password hashes are never returned.
