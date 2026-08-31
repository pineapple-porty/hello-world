# Atlas Wiki

Atlas is a production-minded PHP wiki foundation: a fast, calm reference site where people can discover, write, and improve connected knowledge.

## Included

- Responsive editorial interface with search, categories, popular articles, and related reading
- Article creation and editing with revision history
- Prepared statements, output escaping, CSRF protection, secure session cookies, and security headers
- PostgreSQL or MySQL through DATABASE_URL / DB_DSN, with SQLite as a zero-config fallback
- Automatic schema initialization and seed content on first request
- JSON health endpoint at /health.php
- GitHub Actions PHP syntax checks

## Run locally

Requires PHP 8.1+ with PDO and the SQLite driver.

    php -S localhost:8000

Then open http://localhost:8000. The first request creates storage/atlas.sqlite. To use a server database, set DATABASE_URL or DB_DSN before starting PHP.

## Production notes

Set a managed PostgreSQL or MySQL connection in DATABASE_URL, keep storage outside the web root when possible, serve behind HTTPS, and configure backups and monitoring around health.php. The current open-contribution flow is intentionally frictionless; add authentication, moderation, abuse controls, and source citation workflows before opening it to an untrusted public audience at scale.

The old phys-test.py file is retained as a historical scratch file and is not used by the application.

## Direction

The next scale-up path is a proper application layer around this foundation: accounts and roles, moderation queues, full-text search, object storage for media, immutable revision diffs, citations, page redirects, caching, and a queue-backed search index.
