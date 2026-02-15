<?php
// Fix for missing columns in sliders table
require 'config/db.php';

echo "<h2>Database Migration: Sliders Table</h2>";
echo "<p>Attempting to add missing columns...</p>";

try {
    $columnsToAdd = [
        'secondary_link' => "VARCHAR(255) DEFAULT 'shop.php' AFTER link_text",
        'secondary_link_text' => "VARCHAR(100) DEFAULT 'Explore Collection' AFTER secondary_link",
        'badge_text' => "VARCHAR(50) DEFAULT 'Visit Us' AFTER secondary_link_text"
    ];

    foreach ($columnsToAdd as $col => $definition) {
        try {
            // Check if column exists
            $result = $pdo->query("SHOW COLUMNS FROM sliders LIKE '$col'");
            if ($result->rowCount() > 0) {
                echo "<div style='color:green'>✓ Column <strong>$col</strong> already exists.</div>";
            } else {
                // Add column
                $pdo->exec("ALTER TABLE sliders ADD COLUMN $col $definition");
                echo "<div style='color:blue'>✓ Added column <strong>$col</strong> successfully.</div>";
            }
        } catch (PDOException $e) {
            echo "<div style='color:red'>✗ Error checking/adding $col: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h3>Done! You can now use the slider form.</h3>";
    echo "<a href='index.php'>Go to Homepage</a> | <a href='admin/slider_form.php'>Go to Slider Admin</a>";

} catch (PDOException $e) {
    echo "<div style='color:red; font-weight:bold'>Critical Error: " . $e->getMessage() . "</div>";
}
?>
