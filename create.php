<?php
// Create a new wiki page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (!empty($title) && !empty($content)) {
        $db = new SQLite3('wiki.db');
        $stmt = $db->prepare('INSERT INTO pages (title, content) VALUES (:title, :content)');
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':content', $content, SQLITE3_TEXT);
        $stmt->execute();

        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Wiki Page</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Create a New Wiki Page</h1>
        
        <form method="POST">
            <div>
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div>
                <label for="content">Content:</label>
                <textarea id="content" name="content" rows="10" required></textarea>
            </div>
            <button type="submit">Save</button>
        </form>
        
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>