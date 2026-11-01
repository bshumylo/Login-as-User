# Changelog

## 1.0.0

First release of this fork on the official extension template.

### Compatibility
- EspoCRM ≥ 10.0 (checked against 10.0.11, PHPStan level 8 with no errors), PHP 8.3 – 8.5.
- The navbar override of the old version relied on `getMenuDataList()`, which no longer exists; the logout item is now replaced through `clientNavbar` metadata and a handler.
- The *Login as User* action uses `actionFunction` / `checkVisibilityFunction`.
- Translations moved to `Resources/i18n` (they were not loaded before); Ukrainian added.
- Team-based ACL works for the user you act as (teams are loaded).
- Build and release moved from Grunt/GitLab CI to the extension template and a GitHub release workflow.

### Security
- The plain, script-readable `login-as-user-id` cookie is replaced by a server-signed **HttpOnly** ticket bound to the administrator, the session token and an expiry (1 hour by default, `loginAsUserTtl`). Forged, expired or foreign tickets are ignored.
- Acting as another user is refused for yourself, inactive, system and API users, and for super-admins unless you are one. The rules are checked when the ticket is issued and on every request.
- Starting and stopping are written to the EspoCRM log (who, as whom, IP address).
- A stale cookie no longer breaks the login of non-admin users (previously the request was rejected).

### Added
- *Acting as …* banner with a *Return to My Account* button.
- CI on PHP 8.3 / 8.4 / 8.5, Dependabot, pinned GitHub Actions, security policy.
