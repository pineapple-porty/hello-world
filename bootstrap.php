<?php
declare(strict_types=1);

const APP_NAME = 'Atlas';
const MAX_TITLE_LENGTH = 180;
const MAX_CONTENT_LENGTH = 200000;

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secureCookie,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = getenv('DB_DSN') ?: '';
    $databaseUrl = getenv('DATABASE_URL') ?: '';
    if ($dsn === '' && $databaseUrl !== '') {
        $parts = parse_url($databaseUrl);
        if ($parts && isset($parts['scheme'], $parts['host'])) {
            $scheme = strtolower($parts['scheme']);
            $port = isset($parts['port']) ? ';port=' . $parts['port'] : '';
            $user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
            $password = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';
            $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
            if ($scheme === 'postgres' || $scheme === 'postgresql') {
                $dsn = 'pgsql:host=' . $parts['host'] . $port . ';dbname=' . $dbname;
            } elseif ($scheme === 'mysql') {
                $dsn = 'mysql:host=' . $parts['host'] . $port . ';dbname=' . $dbname . ';charset=utf8mb4';
            }
            $dbUser = $user;
            $dbPassword = $password;
        }
    }

    if ($dsn === '') {
        $storage = __DIR__ . DIRECTORY_SEPARATOR . 'storage';
        if (!is_dir($storage)) {
            mkdir($storage, 0775, true);
        }
        $dsn = 'sqlite:' . $storage . DIRECTORY_SEPARATOR . 'atlas.sqlite';
        $dbUser = null;
        $dbPassword = null;
    }

    $pdo = new PDO($dsn, $dbUser ?? (getenv('DB_USER') ?: null), $dbPassword ?? (getenv('DB_PASSWORD') ?: null), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    initialize_schema($pdo);
    return $pdo;
}

function initialize_schema(PDO $pdo): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $id = $driver === 'pgsql' ? 'BIGSERIAL PRIMARY KEY' : ($driver === 'mysql' ? 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT');
    $pageId = $driver === 'pgsql' ? 'BIGINT NOT NULL' : ($driver === 'mysql' ? 'BIGINT UNSIGNED NOT NULL' : 'INTEGER NOT NULL');

    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id $id,
        slug VARCHAR(180) NOT NULL UNIQUE,
        title VARCHAR(180) NOT NULL,
        summary TEXT NOT NULL DEFAULT '',
        content TEXT NOT NULL,
        category VARCHAR(80) NOT NULL DEFAULT 'General',
        views INTEGER NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS revisions (
        id $id,
        page_id $pageId,
        title VARCHAR(180) NOT NULL,
        summary TEXT NOT NULL DEFAULT '',
        content TEXT NOT NULL,
        category VARCHAR(80) NOT NULL DEFAULT 'General',
        editor VARCHAR(120) NOT NULL DEFAULT 'Anonymous editor',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_pages_updated_at ON pages (updated_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_pages_category ON pages (category)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_revisions_page_id ON revisions (page_id)');

    $count = (int) $pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
    if ($count === 0) {
        $seed = [
            ['Atlas', 'A living reference built by curious people.', "Atlas is an open, community-maintained reference for ideas, places, people, and the questions that connect them.\n\nStart with a search, follow a link, or create the page you wish existed. Every article is a draft that can become clearer through many small contributions.\n\n### How to contribute\n\nUse plain language, cite primary sources when you can, and leave the next editor a better starting point than the one you found.", 'About'],
            ['The open web', 'A shared layer of knowledge, creativity, and connection.', "The open web is the part of the internet built on public standards and links rather than a single platform. It lets anyone publish, anyone browse, and many independent projects work together.\n\nIts strength comes from interoperability: a page can point to another page, a browser can understand both, and people can build new tools on top of the same foundations.\n\n### Why it matters\n\nOpen systems make knowledge easier to preserve, remix, and pass between generations.", 'Technology'],
            ['How to write a great article', 'A practical guide to making information easier to trust and use.', "A useful article answers the reader's question early, then gives enough context to help them explore further. Prefer specific nouns and active verbs. Define unfamiliar terms before relying on them.\n\n### A simple structure\n\n- Lead with the clearest one-paragraph answer.\n- Add the important context and competing viewpoints.\n- Link to related pages and primary sources.\n- Re-read it as someone who knows less than you do.\n\nGood reference writing feels calm, precise, and generous.", 'Guides'],
        ];
        foreach ($seed as $article) {
            insert_page($article[0], $article[1], $article[2], $article[3], 'Atlas editorial team');
        }
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid form token. Please go back and try again.');
    }
}

function slugify(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = function_exists('iconv') ? (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug) : $slug;
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    return trim($slug, '-') ?: 'untitled';
}

function unique_slug(string $title, ?int $ignoreId = null): string
{
    $base = substr(slugify($title), 0, 170);
    $slug = $base;
    $number = 2;
    while (true) {
        $sql = 'SELECT id FROM pages WHERE slug = :slug' . ($ignoreId ? ' AND id != :ignore_id' : '');
        $stmt = db()->prepare($sql);
        $stmt->bindValue(':slug', $slug);
        if ($ignoreId) {
            $stmt->bindValue(':ignore_id', $ignoreId, PDO::PARAM_INT);
        }
        $stmt->execute();
        if (!$stmt->fetch()) {
            return $slug;
        }
        $suffix = '-' . $number++;
        $slug = substr($base, 0, 180 - strlen($suffix)) . $suffix;
    }
}

function insert_page(string $title, string $summary, string $content, string $category, string $editor = 'Anonymous editor'): int
{
    $slug = unique_slug($title);
    $stmt = db()->prepare('INSERT INTO pages (slug, title, summary, content, category) VALUES (:slug, :title, :summary, :content, :category)');
    $stmt->execute([':slug' => $slug, ':title' => $title, ':summary' => $summary, ':content' => $content, ':category' => $category ?: 'General']);
    $page = find_page($slug);
    if (!$page) {
        throw new RuntimeException('Could not create the page.');
    }
    save_revision((int) $page['id'], $title, $summary, $content, $category ?: 'General', $editor);
    return (int) $page['id'];
}

function update_page(int $id, string $title, string $summary, string $content, string $category, string $editor = 'Anonymous editor'): void
{
    $page = find_page_by_id($id);
    if (!$page) {
        throw new RuntimeException('Page not found.');
    }
    $slug = unique_slug($title, $id);
    $stmt = db()->prepare('UPDATE pages SET slug = :slug, title = :title, summary = :summary, content = :content, category = :category, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([':slug' => $slug, ':title' => $title, ':summary' => $summary, ':content' => $content, ':category' => $category ?: 'General', ':id' => $id]);
    save_revision($id, $title, $summary, $content, $category ?: 'General', $editor);
}

function save_revision(int $pageId, string $title, string $summary, string $content, string $category, string $editor): void
{
    $stmt = db()->prepare('INSERT INTO revisions (page_id, title, summary, content, category, editor) VALUES (:page_id, :title, :summary, :content, :category, :editor)');
    $stmt->execute([':page_id' => $pageId, ':title' => $title, ':summary' => $summary, ':content' => $content, ':category' => $category, ':editor' => $editor]);
}

function find_page(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM pages WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    return $stmt->fetch() ?: null;
}

function find_page_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM pages WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

function article_url(array $page): string
{
    return 'page.php?slug=' . rawurlencode((string) $page['slug']);
}

function excerpt(string $text, int $length = 155): string
{
    $text = trim(preg_replace('/[#*_\[\]()>-]+/', ' ', $text) ?? $text);
    return strlen($text) > $length ? substr($text, 0, $length - 1) . '…' : $text;
}

function inline_markup(string $text): string
{
    $safe = e($text);
    $safe = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $safe) ?? $safe;
    $safe = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $safe) ?? $safe;
    $safe = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/', '<a href="$2" rel="nofollow noopener">$1</a>', $safe) ?? $safe;
    return $safe;
}

function render_markdown(string $markdown): string
{
    $lines = preg_split('/\R/', trim($markdown)) ?: [];
    $html = '';
    $inList = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            continue;
        }
        if (preg_match('/^###\s+(.+)$/', $line, $match)) {
            if ($inList) { $html .= '</ul>'; $inList = false; }
            $html .= '<h3>' . inline_markup($match[1]) . '</h3>';
        } elseif (preg_match('/^##\s+(.+)$/', $line, $match)) {
            if ($inList) { $html .= '</ul>'; $inList = false; }
            $html .= '<h2>' . inline_markup($match[1]) . '</h2>';
        } elseif (preg_match('/^#\s+(.+)$/', $line, $match)) {
            if ($inList) { $html .= '</ul>'; $inList = false; }
            $html .= '<h2>' . inline_markup($match[1]) . '</h2>';
        } elseif (preg_match('/^[-*]\s+(.+)$/', $line, $match)) {
            if (!$inList) { $html .= '<ul>'; $inList = true; }
            $html .= '<li>' . inline_markup($match[1]) . '</li>';
        } else {
            if ($inList) { $html .= '</ul>'; $inList = false; }
            $html .= '<p>' . inline_markup($line) . '</p>';
        }
    }
    if ($inList) { $html .= '</ul>'; }
    return $html;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function layout_header(string $title = '', string $active = ''): void
{
    $flash = take_flash();
    $fullTitle = $title ? e($title) . ' — ' . APP_NAME : APP_NAME . ' — A living reference for curious people';
    $query = e($_GET['q'] ?? '');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Atlas is a calm, community-built reference for ideas, people, places, and the questions between them."><title>' . $fullTitle . '</title><link rel="stylesheet" href="style.css"></head><body>';
    echo '<header class="site-header"><div class="nav-shell"><a class="brand" href="index.php" aria-label="Atlas home"><span class="brand-mark">✦</span><span>ATLAS</span></a><form class="search-form" action="index.php" method="get" role="search"><label class="sr-only" for="site-search">Search Atlas</label><input id="site-search" name="q" value="' . $query . '" placeholder="Search the reference" autocomplete="off"><button type="submit" aria-label="Search">⌕</button></form><nav class="nav-links" aria-label="Primary"><a class="' . ($active === 'explore' ? 'active' : '') . '" href="index.php">Explore</a><a class="' . ($active === 'create' ? 'active' : '') . '" href="create.php">Contribute</a></nav></div></header><main class="site-main">';
    if ($flash) {
        echo '<div class="flash ' . e($flash['type']) . '" role="status">' . e($flash['message']) . '</div>';
    }
}

function layout_footer(): void
{
    echo '</main><footer class="site-footer"><div class="footer-shell"><div><span class="brand small"><span class="brand-mark">✦</span><span>ATLAS</span></span><p>A calmer way to learn together.</p></div><div class="footer-links"><a href="index.php">Explore</a><a href="create.php">Write an article</a><a href="health.php">System status</a></div></div></footer></body></html>';
}

// Open the database for every web request so schema setup and demo seeding happen once.
db();
?>