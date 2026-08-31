# Copilot instructions for Atlas

## Purpose

Atlas is a plain PHP 8.1+ wiki application. When changing the application, validate the result by running the PHP server and exercising the real HTTP endpoints. Do not treat static inspection as a substitute for a smoke test.

## Runtime requirements

- PHP 8.1 or newer
- PDO with the SQLite driver
- Bash and curl
- No Composer packages are required

Check the environment before testing:

    php -v
    php -m | grep -E 'PDO|pdo_sqlite'

If PHP or pdo_sqlite is unavailable, report that clearly instead of claiming the tests passed.

## Start an isolated test server

Never use a production DATABASE_URL or a real shared database for tests. Use a temporary SQLite file and a non-public bind address:

    TEST_DB="$(mktemp -u /tmp/atlas-copilot.XXXXXX.sqlite)"
    DB_DSN="sqlite:$TEST_DB" php -S 127.0.0.1:8000 -t . > /tmp/atlas-copilot-server.log 2>&1 &
    SERVER_PID=$!
    trap 'kill "$SERVER_PID" 2>/dev/null || true; rm -f "$TEST_DB"' EXIT
    sleep 1

The first request initializes the schema and starter content automatically. If port 8000 is busy, use another local port and update every curl command consistently.

## Required checks

Run the syntax check first:

    bash check.sh

Then verify the running application:

    curl --fail --silent --show-error http://127.0.0.1:8000/health.php
    curl --fail --silent --show-error http://127.0.0.1:8000/index.php > /tmp/atlas-home.html
    grep -q 'ATLAS' /tmp/atlas-home.html
    curl --fail --silent --show-error 'http://127.0.0.1:8000/index.php?q=open' > /tmp/atlas-search.html
    grep -qi 'open web' /tmp/atlas-search.html
    curl --fail --silent --show-error 'http://127.0.0.1:8000/page.php?slug=atlas' > /tmp/atlas-page.html
    grep -q 'living reference' /tmp/atlas-page.html
    curl --fail --silent --show-error http://127.0.0.1:8000/create.php > /tmp/atlas-create.html
    grep -q 'New article' /tmp/atlas-create.html

The health response must contain a successful status. The homepage, search page, article page, and create form must return HTTP 200 and their expected content.

## Behavior to check when changing features

For changes involving:

- Search: test an exact title match, a body-term match, and a no-results query.
- Articles: test an article with a title, summary, category, and Markdown body; confirm it appears on the homepage and can be opened by slug.
- Editing: confirm the updated article renders and history.php contains a new revision.
- Database code: test with the isolated SQLite DSN above and run the app with native PDO prepares enabled.
- Security: confirm user-provided HTML is escaped and POST forms include a CSRF token.

Use prepared statements for all values, escape rendered user content, keep CSRF validation on every state-changing POST, and do not add shell execution of user input.

## Completion report

Before saying a change is complete, report:

1. The exact validation commands run.
2. Whether PHP and pdo_sqlite were available.
3. Whether the server smoke tests passed or failed.
4. Any test that could not be run and why.

Do not claim production readiness solely because the server starts. Keep the test database isolated and clean it up when the test finishes.
