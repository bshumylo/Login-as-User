# Login as User

EspoCRM extension that lets administrators log in as another user — for example, to debug ACL.

## Requirements

- EspoCRM ≥ 10.0
- PHP 8.3 – 8.5

## Installation

1. Download the latest zip from the [Releases page](https://github.com/bshumylo/Login-as-User/releases/latest).
2. Go to **Administration → Extensions**, upload the zip and install it.

## Usage

Open a user record (regular, admin or portal user) and choose **Login as User** in the dropdown of the top-right
actions. While you act as that user, the **Log Out** item of the user menu turns into **Return to My Account**.

Only administrators can use the feature. If a non-admin session carries the `login-as-user-id` cookie, the request is
refused and logged.

## Build from source

```bash
npm ci
node build --extension   # -> build/login-as-user-<version>.zip
```

Pushing a new version in `extension.json` (and `package.json`) to `main` creates a tag and a GitHub release with the
zip automatically (see `.github/workflows/release.yml`).

## License

GPL-3.0-or-later. Originally created by Apertia Tech s.r.o.
