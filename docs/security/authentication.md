# Authentication And Authorization

## Overview

Zinema.lk – Modern Full-Stack Movie Streaming Platform uses ASP.NET Core Identity for users and roles, plus JWT Bearer authentication for API access.

This branch establishes the security foundation only. It does not add admin catalog features, frontend flows, video upload, or FFmpeg processing.

## Identity Model

Users and roles are Guid-based.

Roles:

- `User`
- `Admin`

Public registration creates standard users only and assigns the `User` role. Admin accounts are not created through public registration.

## JWT Configuration

JWT settings live under the `Jwt` configuration section:

```json
{
  "Jwt": {
    "Issuer": "Zinema.Local",
    "Audience": "Zinema.Local",
    "SigningKey": "development-only-signing-key-change-me-minimum-32-characters",
    "ExpirationMinutes": 60
  }
}
```

The Development signing key is a local placeholder. Real signing keys must be supplied through environment variables or secret storage.

Environment variable examples:

```powershell
$env:Jwt__Issuer = "Zinema.Local"
$env:Jwt__Audience = "Zinema.Local"
$env:Jwt__SigningKey = "<strong-local-or-deployed-secret>"
$env:Jwt__ExpirationMinutes = "60"
```

## Role Seeding

Role seeding is idempotent.

Seeded roles:

- `User`
- `Admin`

Role seeding runs as part of Development startup seeding when explicitly enabled:

```powershell
$env:Database__SeedOnStartup = "true"
dotnet run --project backend/src/Zinema.Api
```

## Development Admin Seeding

Development admin seeding is disabled by default.

To enable it locally, provide all required values through environment variables or local configuration:

```powershell
$env:Database__SeedOnStartup = "true"
$env:Auth__SeedDevelopmentAdmin = "true"
$env:Auth__DevelopmentAdminEmail = "admin@example.test"
$env:Auth__DevelopmentAdminPassword = "<local-dev-password>"
$env:Auth__DevelopmentAdminDisplayName = "Development Admin"
dotnet run --project backend/src/Zinema.Api
```

No default admin password is stored in source control.

## Protected Endpoints

Current authenticated endpoint:

```text
GET /api/auth/me
```

Current Admin-only endpoint:

```text
GET /api/admin/ping
```

`/api/admin/ping` exists only to verify the RBAC foundation.

## Password Handling

Passwords are handled by ASP.NET Core Identity.

Auth responses never return passwords or password hashes.

Current development password rules:

- Minimum length: 8.
- Requires digit.
- Requires lowercase.
- Requires uppercase.
- Does not require non-alphanumeric characters.

## Security Notes

- Keep real signing keys out of Git.
- Rotate deployed signing keys according to operational policy.
- Use HTTPS outside local development.
- Keep admin creation behind explicit configuration and trusted operational processes.
- Expand authorization policies as admin features are introduced.
