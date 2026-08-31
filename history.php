<?php
require __DIR__ . '/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$page = $slug !== '' ? find_page($slug) : null;
if (!$page) { http_response_code(404); exit('Page not found.'); }
$stmt = db()->prepare('SELECT * FROM revisions WHERE page_id = :page_id ORDER BY created_at DESC, id DESC');
$stmt->execute([':page_id' => (int) $page['id']]);
$revisions = $stmt->fetchAll();

layout_header('History — ' . (string) $page['title'], 'explore');
?>
<section class="history-shell"><div class="breadcrumb"><a href="<?= e(article_url($page)) ?>">← <?= e($page['title']) ?></a></div><div class="history-heading"><div><p class="eyebrow">Transparent by default</p><h1>Article history.</h1><p>Every saved revision gives this page a memory and every contributor a place to start.</p></div><a class="button secondary" href="edit.php?slug=<?= rawurlencode($page['slug']) ?>">Edit article <span>↗</span></a></div><div class="revision-list"><?php foreach ($revisions as $index => $revision): ?><div class="revision-row"><div class="revision-mark <?= $index === 0 ? 'latest' : '' ?>"></div><div class="revision-main"><div><strong><?= $index === 0 ? 'Current version' : 'Revision ' . e(count($revisions) - $index) ?></strong><span><?= e(date('M j, Y · g:i A', strtotime((string) $revision['created_at']))) ?></span></div><p><?= e(excerpt((string) $revision['content'], 130)) ?></p></div><div class="revision-editor"><?= e($revision['editor']) ?><small><?= e($revision['category']) ?></small></div></div><?php endforeach; ?></div></section>
<?php layout_footer(); ?>