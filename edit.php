<?php
require __DIR__ . '/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? $_POST['slug'] ?? ''));
$page = $slug !== '' ? find_page($slug) : null;
if (!$page) { http_response_code(404); exit('Page not found.'); }
$errors = [];
$title = (string) ($_POST['title'] ?? $page['title']);
$summary = trim((string) ($_POST['summary'] ?? $page['summary']));
$content = trim((string) ($_POST['content'] ?? $page['content']));
$category = trim((string) ($_POST['category'] ?? $page['category']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($title === '' || strlen($title) > MAX_TITLE_LENGTH) { $errors[] = 'Give the article a title under 180 characters.'; }
    if ($content === '' || strlen($content) > MAX_CONTENT_LENGTH) { $errors[] = 'Add content under 200,000 characters.'; }
    if (strlen($summary) > 280) { $errors[] = 'Keep the summary under 280 characters.'; }
    if (!$errors) {
        try {
            update_page((int) $page['id'], $title, $summary, $content, $category ?: 'General');
            $updated = find_page_by_id((int) $page['id']);
            flash('success', 'Revision saved. Thanks for improving the reference.');
            redirect($updated ? article_url($updated) : 'index.php');
        } catch (Throwable $exception) {
            $errors[] = 'The revision could not be saved. Please try again.';
        }
    }
}

layout_header('Edit ' . (string) $page['title'], 'explore');
?>
<section class="form-shell compact"><div class="form-intro"><p class="eyebrow">Revision <?= e($page['title']) ?></p><h1>Make the next<br><em>version clearer.</em></h1><p>Small edits compound. Keep the useful context, improve the explanation, and save a revision for the next reader.</p><a class="text-link" href="<?= e(article_url($page)) ?>">← Back to article</a></div><div class="form-card"><div class="form-card-top"><span>Edit article</span><span class="required-note">Revision history is automatic</span></div><?php if ($errors): ?><div class="form-errors"><strong>Almost there</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="post" action="edit.php?slug=<?= rawurlencode($page['slug']) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="slug" value="<?= e($page['slug']) ?>"><div class="field"><label for="title">Title <span>*</span></label><input id="title" name="title" value="<?= e($title) ?>" maxlength="180" required></div><div class="field"><label for="summary">One-line summary</label><input id="summary" name="summary" value="<?= e($summary) ?>" maxlength="280"></div><div class="field"><label for="category">Category</label><input id="category" name="category" value="<?= e($category) ?>" maxlength="80"></div><div class="field"><label for="content">Article content <span>*</span></label><textarea id="content" name="content" rows="18" required><?= e($content) ?></textarea><small class="field-help">Markdown supported. Every save becomes a revision in the article history.</small></div><div class="form-actions"><a class="text-link" href="<?= e(article_url($page)) ?>">Cancel</a><button class="button primary" type="submit">Save revision <span>↗</span></button></div></form></div></section>
<?php layout_footer(); ?>