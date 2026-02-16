<?php
require 'config/db.php';

echo "<h2>Migrating Prescription System Tables...</h2>";

try {
    // 1. Create lens_options table
    $sql_lens = "CREATE TABLE IF NOT EXISTS lens_options (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_lens);
    echo "✅ Table 'lens_options' created or already exists.<br>";

    // 2. Create order_prescriptions table
    $sql_rx = "CREATE TABLE IF NOT EXISTS order_prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        lens_option_id INT,
        
        od_sph VARCHAR(10),
        od_cyl VARCHAR(10),
        od_axis VARCHAR(10),
        od_add VARCHAR(10),
        
        os_sph VARCHAR(10),
        os_cyl VARCHAR(10),
        os_axis VARCHAR(10),
        os_add VARCHAR(10),
        
        pd VARCHAR(10),
        
        prescription_file VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (lens_option_id) REFERENCES lens_options(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql_rx);
    echo "✅ Table 'order_prescriptions' created or already exists.<br>";

    // 3. Insert default lens options if table is empty
    $stmt = $pdo->query("SELECT count(*) FROM lens_options");
    if ($stmt->fetchColumn() == 0) {
        $default_lenses = [
            ['Zero Power (Anti-Glare)', 'Blue Cut & Anti-Glare Lenses with Zero Power', 0.00],
            ['Single Vision (Power)', 'Standard Single Vision Lenses', 500.00],
            ['Blue Cut (Power)', 'Blue Light Blocking Lenses', 1000.00],
            ['Photochromic (Power)', 'Light Adaptive Lenses (Sun/Clear)', 1500.00],
            ['Bifocal / Progressive', 'Multifocal Lenses', 2500.00]
        ];

        $insert = $pdo->prepare("INSERT INTO lens_options (name, description, price) VALUES (?, ?, ?)");
        foreach ($default_lenses as $lens) {
            $insert->execute($lens);
        }
        echo "✅ Inserted default lens options.<br>";
    } else {
        echo "ℹ️ Lens options already exist, skipping default data insertion.<br>";
    }

    echo "<h3>🎉 Migration Completed Successfully!</h3>";
    echo "<a href='admin/index.php'>Go to Admin</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
