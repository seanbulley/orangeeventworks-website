# ORANGE EventWorks website

Website source for [ORANGE EventWorks](https://orangeeventworks.co.uk/).

## Deployment flow

The site uses the same demo-first deployment model as the ORANGE Kit website:

- `develop` deploys to `https://orangeeventworks.co.uk/demo/`
- `main` deploys to `https://orangeeventworks.co.uk/`

Normal changes should go to `develop` first, be checked on the demo site, then be merged into `main` through a pull request.

The production deployment explicitly excludes the `demo/` directory, so deploying `main` will not remove the preview site.

## Private PHP configuration

The waiting-list form stores submissions in MySQL. Production and demo use separate configuration files and separate databases.

Keep both files outside `public_html`:

```text
/home/.../domains/orangeeventworks.co.uk/
├── orangeeventworks-config.php
├── orangeeventworks-demo-config.php
└── public_html/
    └── demo/
```

Production uses:

```text
orangeeventworks-config.php
```

Demo uses:

```text
orangeeventworks-demo-config.php
```

Copy `config.example.php` as the basis for each private config and use different MySQL database credentials in each.

The application automatically creates the `waiting_list` table on the first successful submission.

## GitHub Actions settings

Configure these at:

**Repository → Settings → Secrets and variables → Actions**

### Variables

- `HOSTINGER_SSH_HOST` — Hostinger SSH hostname or server IP
- `HOSTINGER_SSH_PORT` — SSH port shown in hPanel
- `HOSTINGER_SSH_USER` — SSH username shown in hPanel
- `HOSTINGER_PRODUCTION_DEPLOY_PATH` — e.g. `/home/u123456789/domains/orangeeventworks.co.uk/public_html`
- `HOSTINGER_DEMO_DEPLOY_PATH` — e.g. `/home/u123456789/domains/orangeeventworks.co.uk/public_html/demo`

### Secret

- `HOSTINGER_SSH_PASSWORD` — Hostinger SSH password

The workflow uses password authentication via `sshpass`.

## Demo indexing protection

The demo environment sends:

```text
X-Robots-Tag: noindex, nofollow, noarchive, nosnippet
```

and renders a matching HTML robots meta tag. The deploy workflow also creates a demo-only `.htaccess` with the same no-index header.

This prevents indexing but is not access control. Add HTTP authentication separately if the demo needs to be private.

## Recommended GitHub rule

Protect `main` with a branch ruleset requiring a pull request before merge. This prevents accidental direct pushes from immediately deploying to production.
