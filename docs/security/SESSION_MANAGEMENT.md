# Session management - ZL-19

Status: PARTIAL. NativeSession and SessionState are executed in an isolated
fixture. Legacy admin/config.php still has its existing 100-year session settings
and raw remember-token flow. No real session was opened, migrated or invalidated.

The replacement uses a separate ZL19SID cookie, strict PHP IDs, cookie-only
transport, no URL propagation, HttpOnly, SameSite=Lax, host-only scope and browser
session lifetime. Production requires trusted HTTPS evidence; forwarded headers
are not trusted. The HTTP exception is limited to configured development/test,
127.0.0.1 origin and directly observed 127.0.0.1 peer. Secure-cookie parameters
are checked in PHP; actual production TLS/proxy handling is NOT VERIFIED.

Authentication rotates the ID before storing identity. Logout clears the cookie
and destroys the session in the same configured store. The isolated HTTP tests
show that the old ID cannot authenticate. Browser tests confirm HttpOnly behavior,
ID change and cookie removal. Idle timeout is 1,800 seconds; absolute timeout is
28,800 seconds, both exclusive at the expiry boundary. Backward time denies.
There is no sliding absolute renewal. Renew through fresh authentication.

Every resolve rereads active/verified state and identity epoch. Password change,
verification and reset increment epoch and invalidate all old sessions. Normal
logout destroys this session only. A future privilege-change adapter must update
epoch atomically, never just change a session role. All privileged operations
also require fresh session-bound MFA, currently unavailable and denied.

No shared session adapter, device listing, remote logout, periodic rotation,
remember-me replacement or clustered revocation latency has been implemented.
Immediate old-session deletion may interrupt concurrent requests; no seamless
legacy-session migration is claimed. Production activation requires verified
store ownership, locking, TLS, time synchronization, recovery and approved rollout.

Reference: [PHP session security](https://www.php.net/manual/en/features.session.security.management.php).
