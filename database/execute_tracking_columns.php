<?php
/**
 * Execute SQL script to add tracking_number and courier_name columns to orders table
 * Run this file once to add the missing columns for order tracking functionality
 */

require_once 'config.php';

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Adding Tracking Columns to Orders Table</h2>\n";
    echo "<p>Starting database modification...</p>\n";
    
    // Read and execute the SQL file
    $sql_file = __DIR__ . '/add_tracking_columns.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: " . $sql_file);
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL commands by semicolon and execute each one
    $sql_commands = array_filter(array_map('trim', explode(';', $sql_content)));
    
    foreach ($sql_commands as $sql) {
        if (empty($sql) || strpos($sql, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            
            // If it's a SELECT statement, fetch and display results
            if (stripos($sql, 'SELECT') === 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    echo "<p style='color: green;'><strong>" . $result['message'] . "</strong></p>\n";
                }
            } else {
                echo "<p style='color: blue;'>✓ Executed: " . substr($sql, 0, 50) . "...</p>\n";
            }
            
        } catch (PDOException $e) {
            // Check if column already exists error
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p style='color: orange;'>⚠ Column already exists, skipping...</p>\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "<h3 style='color: green;'>✅ Database modification completed successfully!</h3>\n";
    echo "<p><strong>Changes made:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Added 'tracking_number' column (VARCHAR 255) to orders table</li>\n";
    echo "<li>Added 'courier_name' column (VARCHAR 100) to orders table</li>\n";
    echo "<li>Created indexes for better performance</li>\n";
    echo "</ul>\n";
    echo "<p><em>You can now use the tracking functionality in the admin panel.</em></p>\n";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error occurred:</h3>\n";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database connection and try again.</p>\n";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update - Add Tracking Columns</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #6f42c1;
            padding-bottom: 10px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #6f42c1;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background-color: #5a2d91;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../admin/manage-orders.php" class="back-link">← Back to Manage Orders</a>
    </div>
</body>
</html>