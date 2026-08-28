<?php
// Homepage: List all wiki pages
$db = new SQLite3('wiki.db');

// Fetch all wiki pages from the database
$result = $db->query('SELECT id, title FROM pages ORDER BY created_at DESC');
$pages = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $pages[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wiki Website</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Wiki Website</h1>
        <p><a href="create.php">Create a New Wiki Page</a></p>
        
        <h2>Existing Pages</h2>
        <?php if (empty($pages)): ?>
            <p>No wiki pages yet. <a href="create.php">Create one!</a></p>
        <?php else: ?>
            <ul>
                <?php foreach ($pages as $page): ?>
                    <li>
                        <a href="page.php?id=<?php echo htmlspecialchars($page['id']); ?>">
                            <?php echo htmlspecialchars($page['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>