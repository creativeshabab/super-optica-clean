<?php
require 'config/db.php';

echo "<h2>Fixing Sliders Table...</h2>";

try {
    // 1. Add secondary_link column if it doesn't exist
    try {
        $pdo->query("SELECT secondary_link FROM sliders LIMIT 1");
        echo "✅ Column 'secondary_link' already exists.<br>";
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE sliders ADD COLUMN secondary_link VARCHAR(255) DEFAULT 'shop.php' AFTER link_text");
        echo "✅ Added column 'secondary_link'.<br>";
    }

    // 2. Add secondary_link_text column if it doesn't exist
    try {
        $pdo->query("SELECT secondary_link_text FROM sliders LIMIT 1");
        echo "✅ Column 'secondary_link_text' already exists.<br>";
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE sliders ADD COLUMN secondary_link_text VARCHAR(100) DEFAULT 'Explore Collection' AFTER secondary_link");
        echo "✅ Added column 'secondary_link_text'.<br>";
    }

    // 3. Add badge_text column if it doesn't exist
    try {
        $pdo->query("SELECT badge_text FROM sliders LIMIT 1");
        echo "✅ Column 'badge_text' already exists.<br>";
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE sliders ADD COLUMN badge_text VARCHAR(50) DEFAULT 'Visit Us' AFTER secondary_link_text");
        echo "✅ Added column 'badge_text'.<br>";
    }

    echo "<h3>🎉 Database successfully updated! You can now use the slider form.</h3>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
