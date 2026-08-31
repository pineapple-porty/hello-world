<?php
require __DIR__ . '/bootstrap.php';

$q = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$pdo = db();

if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE (title LIKE :query OR summary LIKE :query OR content LIKE :query) AND (:category_filter = '' OR category = :category_value) ORDER BY CASE WHEN title LIKE :title_query THEN 0 ELSE 1 END, updated_at DESC LIMIT 40");
    $stmt->execute([':query' => '%' . $q . '%', ':title_query' => $q . '%', ':category_filter' => $category, ':category_value' => $category]);
    $pages = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE (:category = '' OR category = :category) ORDER BY updated_at DESC LIMIT 40");
    $stmt->execute([':category_filter' => $category, ':category_value' => $category]);
    $pages = $stmt->fetchAll();
}

$categories = $pdo->query('SELECT category, COUNT(*) AS total FROM pages GROUP BY category ORDER BY total DESC, category ASC')->fetchAll();
$articleCount = (int) $pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
$revisionCount = (int) $pdo->query('SELECT COUNT(*) FROM revisions')->fetchColumn();
$popular = $pdo->query('SELECT * FROM pages ORDER BY views DESC, updated_at DESC LIMIT 3')->fetchAll();

layout_header($q !== '' ? 'Search results' : 'Explore', 'explore');
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">A living reference, made together</p>
        <h1>Find the thread<br><em>worth following.</em></h1>
        <p class="hero-lede">Atlas is a growing library of clear, connected ideas. Read deeply, wander freely, and leave every page a little better.</p>
        <div class="hero-actions"><a class="button primary" href="create.php">Write an article <span>↗</span></a><a class="text-link" href="#articles">Browse the library <span>↓</span></a></div>
    </div>
    <div class="hero-orbit" aria-hidden="true"><div class="orbit-ring ring-one"></div><div class="orbit-ring ring-two"></div><div class="orbit-dot dot-one"></div><div class="orbit-dot dot-two"></div><div class="orbit-dot dot-three"></div><span>knowledge<br>is connected</span></div>
</section>

<section class="stats-strip" aria-label="Atlas statistics"><div><strong><?= e($articleCount) ?></strong><span>articles</span></div><div><strong><?= e($revisionCount) ?></strong><span>edits recorded</span></div><div><strong><?= e(count($categories)) ?></strong><span>open categories</span></div><div class="stats-note">Built for the curious <span>✦</span></div></section>

<section class="library-section" id="articles">
    <div class="section-heading"><div><p class="eyebrow"><?= $q !== '' ? 'Your search' : 'The library' ?></p><h2><?= $q !== '' ? 'Results for “' . e($q) . '”' : 'Start anywhere.' ?></h2></div><div class="section-filter"><a class="filter-chip <?= $category === '' ? 'selected' : '' ?>" href="index.php<?= $q !== '' ? '?q=' . rawurlencode($q) : '' ?>">All</a><?php foreach ($categories as $item): ?><a class="filter-chip <?= $category === $item['category'] ? 'selected' : '' ?>" href="index.php?category=<?= rawurlencode($item['category']) ?><?= $q !== '' ? '&q=' . rawurlencode($q) : '' ?>"><?= e($item['category']) ?> <small><?= e($item['total']) ?></small></a><?php endforeach; ?></div></div>
    <?php if (!$pages): ?>
        <div class="empty-state"><span class="empty-icon">⌕</span><h3>No pages found</h3><p>Try a broader search, or create the first article on this topic.</p><a class="button primary" href="create.php">Create an article</a></div>
    <?php else: ?>
        <div class="article-grid"><?php foreach ($pages as $page): ?><article class="article-card"><div class="card-meta"><span><?= e($page['category']) ?></span><span><?= e((int) $page['views']) ?> reads</span></div><h3><a href="<?= e(article_url($page)) ?>"><?= e($page['title']) ?></a></h3><p><?= e($page['summary'] ?: excerpt($page['content'])) ?></p><a class="card-arrow" href="<?= e(article_url($page)) ?>" aria-label="Read <?= e($page['title']) ?>">↗</a></article><?php endforeach; ?></div>
    <?php endif; ?>
</section>

<?php if ($q === '' && $popular): ?><section class="popular-section"><div class="section-heading"><div><p class="eyebrow">Reader paths</p><h2>Popular right now.</h2></div><a class="text-link" href="index.php">See all articles <span>→</span></a></div><div class="popular-list"><?php foreach ($popular as $index => $page): ?><a class="popular-row" href="<?= e(article_url($page)) ?>"><span class="popular-number">0<?= $index + 1 ?></span><span class="popular-title"><?= e($page['title']) ?><small><?= e($page['category']) ?></small></span><span class="popular-views"><?= e((int) $page['views']) ?> reads <b>↗</b></span></a><?php endforeach; ?></div></section><?php endif; ?>
<?php layout_footer(); ?>