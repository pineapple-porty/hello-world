<?php
require __DIR__ . '/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$page = $slug !== '' ? find_page($slug) : null;
if (!$page && isset($_GET['id'])) {
    $page = find_page_by_id((int) $_GET['id']);
}
if (!$page) {
    http_response_code(404);
    layout_header('Page not found', 'explore');
    echo '<section class="not-found"><p class="eyebrow">404 / Missing page</p><h1>This page is still a blank space.</h1><p>It may have moved, or it may be the next article waiting for someone to write it.</p><div class="hero-actions"><a class="button primary" href="create.php">Create the page</a><a class="text-link" href="index.php">Return to the library <span>→</span></a></div></section>';
    layout_footer();
    exit;
}

$pdo = db();
$viewStmt = $pdo->prepare('UPDATE pages SET views = views + 1 WHERE id = :id');
$viewStmt->execute([':id' => (int) $page['id']]);
$page['views'] = (int) $page['views'] + 1;
$relatedStmt = $pdo->prepare('SELECT * FROM pages WHERE category = :category AND id != :id ORDER BY updated_at DESC LIMIT 3');
$relatedStmt->execute([':category' => $page['category'], ':id' => (int) $page['id']]);
$related = $relatedStmt->fetchAll();

layout_header((string) $page['title'], 'explore');
?>
<div class="article-layout">
    <article class="article-detail">
        <div class="breadcrumb"><a href="index.php">Explore</a><span>/</span><a href="index.php?category=<?= rawurlencode($page['category']) ?>"><?= e($page['category']) ?></a></div>
        <div class="article-heading"><p class="eyebrow"><?= e($page['category']) ?></p><h1><?= e($page['title']) ?></h1><?php if ($page['summary']): ?><p class="article-summary"><?= e($page['summary']) ?></p><?php endif; ?><div class="article-byline"><span class="avatar">A</span><span>Open contribution</span><span>·</span><span><?= e((int) $page['views']) ?> reads</span><span>·</span><a href="history.php?slug=<?= rawurlencode($page['slug']) ?>">View history</a></div></div>
        <div class="article-content"><?= render_markdown((string) $page['content']) ?></div>
        <div class="article-footer"><div><span class="eyebrow">Keep the thread going</span><p>See something to clarify? Every article can become a better starting point.</p></div><a class="button secondary" href="edit.php?slug=<?= rawurlencode($page['slug']) ?>">Edit this article <span>↗</span></a></div>
    </article>
    <aside class="article-aside"><div class="aside-card"><p class="eyebrow">On this page</p><div class="toc-line active">Overview</div><?php $headings = preg_match_all('/^#{1,3}\s+(.+)$/m', (string) $page['content'], $matches) ? $matches[1] : []; foreach (array_slice($headings, 0, 5) as $heading): ?><div class="toc-line"><?= e($heading) ?></div><?php endforeach; ?></div><?php if ($related): ?><div class="aside-card"><p class="eyebrow">Keep exploring</p><?php foreach ($related as $item): ?><a class="aside-related" href="<?= e(article_url($item)) ?>"><span><?= e($item['title']) ?></span><b>↗</b></a><?php endforeach; ?></div><?php endif; ?></aside>
</div>
<?php layout_footer(); ?>