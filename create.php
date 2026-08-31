<?php
require __DIR__ . '/bootstrap.php';

$errors = [];
$title = trim((string) ($_POST['title'] ?? ''));
$summary = trim((string) ($_POST['summary'] ?? ''));
$content = trim((string) ($_POST['content'] ?? ''));
$category = trim((string) ($_POST['category'] ?? 'General'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($title === '' || strlen($title) > MAX_TITLE_LENGTH) { $errors[] = 'Give the article a title under 180 characters.'; }
    if ($content === '' || strlen($content) > MAX_CONTENT_LENGTH) { $errors[] = 'Add content under 200,000 characters.'; }
    if (strlen($summary) > 280) { $errors[] = 'Keep the summary under 280 characters.'; }
    if (!$errors) {
        try {
            $id = insert_page($title, $summary, $content, $category ?: 'General');
            $page = find_page_by_id($id);
            flash('success', 'Your article is now part of Atlas.');
            redirect($page ? article_url($page) : 'index.php');
        } catch (Throwable $exception) {
            $errors[] = 'The article could not be saved. Please try again.';
        }
    }
}

layout_header('Write an article', 'create');
?>
<section class="form-shell"><div class="form-intro"><p class="eyebrow">Open contribution</p><h1>Put something<br><em>worth knowing</em><br>on the map.</h1><p>Write for the person who finds this page six months from now. Start with what is true and useful; the structure can come later.</p><div class="writing-note"><span>✦</span><p><strong>Writing tip</strong><br>Use a short summary, then separate ideas with blank lines. You can use simple Markdown for headings, lists, emphasis, and links.</p></div></div><div class="form-card"><div class="form-card-top"><span>New article</span><span class="required-note">All fields marked * are required</span></div><?php if ($errors): ?><div class="form-errors"><strong>Almost there</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="post" action="create.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="field"><label for="title">Title <span>*</span></label><input id="title" name="title" value="<?= e($title) ?>" maxlength="180" placeholder="The clearest name for this idea" required></div><div class="field"><label for="summary">One-line summary</label><input id="summary" name="summary" value="<?= e($summary) ?>" maxlength="280" placeholder="What should a reader understand first?"></div><div class="field-row"><div class="field"><label for="category">Category</label><input id="category" name="category" value="<?= e($category) ?>" maxlength="80" placeholder="Technology, History, Guides…"></div><div class="field field-hint"><span>Be specific</span><small>Categories help readers discover related articles.</small></div></div><div class="field"><label for="content">Article content <span>*</span></label><textarea id="content" name="content" rows="16" placeholder="Begin with the central idea…" required><?= e($content) ?></textarea><small class="field-help">Markdown supported: ## headings, - lists, **bold**, *italics*, and [links](https://example.com).</small></div><div class="form-actions"><a class="text-link" href="index.php">Cancel</a><button class="button primary" type="submit">Publish article <span>↗</span></button></div></form></div></section>
<?php layout_footer(); ?>