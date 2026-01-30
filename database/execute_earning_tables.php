<?php
include 'config.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/create_earning_tables.sql');
    
    // Split SQL statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
            try {
                $db->exec($statement);
                echo "✅ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Ignore duplicate column errors
                if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                    echo "⚠️  Column already exists: " . substr($statement, 0, 50) . "...\n";
                } else {
                    throw $e;
                }
            }
        }
    }
    
    echo "\n🎉 All earning tables setup completed successfully!\n";
    echo "✅ partner_earnings table created\n";
    echo "✅ senior_partner_earnings table created\n";
    echo "✅ total_earnings column added to partners table\n";
    echo "✅ total_earnings column added to senior_partners table\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>