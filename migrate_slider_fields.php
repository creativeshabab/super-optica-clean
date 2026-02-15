<?php
require_once 'config/db.php';

echo "=== Slider Secondary Button & Badge Migration ===\n\n";

try {
    // Check if columns already exist
    $stmt = $pdo->query("DESCRIBE sliders");
    $columns = $stmt->fetchAll();
    $existing_columns = array_column($columns, 'Field');
    
    $migrations_needed = [];
    
    if (!in_array('secondary_link', $existing_columns)) {
        $migrations_needed[] = "secondary_link";
    }
    if (!in_array('secondary_link_text', $existing_columns)) {
        $migrations_needed[] = "secondary_link_text";
    }
    if (!in_array('badge_text', $existing_columns)) {
        $migrations_needed[] = "badge_text";
    }
    
    if (empty($migrations_needed)) {
        echo "✓ All columns already exist. No migration needed.\n";
        exit;
    }
    
    echo "Columns to add: " . implode(', ', $migrations_needed) . "\n\n";
    
    // Run migrations
    if (in_array('secondary_link', $migrations_needed)) {
        echo "Adding secondary_link column...\n";
        $pdo->exec("ALTER TABLE sliders ADD COLUMN secondary_link VARCHAR(255) DEFAULT 'shop.php' AFTER link_text");
        echo "✓ secondary_link added\n";
    }
    
    if (in_array('secondary_link_text', $migrations_needed)) {
        echo "Adding secondary_link_text column...\n";
        $pdo->exec("ALTER TABLE sliders ADD COLUMN secondary_link_text VARCHAR(100) DEFAULT 'Explore Collection' AFTER secondary_link");
        echo "✓ secondary_link_text added\n";
    }
    
    if (in_array('badge_text', $migrations_needed)) {
        echo "Adding badge_text column...\n";
        $pdo->exec("ALTER TABLE sliders ADD COLUMN badge_text VARCHAR(50) DEFAULT 'Visit Us' AFTER secondary_link_text");
        echo "✓ badge_text added\n";
    }
    
    echo "\n=== Migration Complete! ===\n";
    echo "✓ Sliders table updated successfully\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
