<?php
// Display a single wiki page
$db = new SQLite3('wiki.db');

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];
$stmt = $db->prepare('SELECT title, content FROM pages WHERE id = :id');
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();

$page = $result->fetchArray(SQLITE3_ASSOC);

if (!$page) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['title']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($page['title']); ?></h1>
        <div class="content">
            <?php echo nl2br(htmlspecialchars($page['content'])); ?>
        </div>
        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>