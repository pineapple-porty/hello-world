<?php
// Initialize SQLite database for the wiki
$db = new SQLite3('wiki.db');

// Create the 'pages' table if it doesn't exist
$query = "
CREATE TABLE IF NOT EXISTS pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

$db->exec($query);
echo "Database initialized successfully!";
?>