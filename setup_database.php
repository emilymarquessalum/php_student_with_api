<?php 
require 'config.php';

echo "<h2>Database Setup</h2>";

try {
    // Check if database has tables
    $result = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'public'");
    $tableCount = $result->fetch()['table_count'];
    
    if ($tableCount == 0) {
        echo "<p>No tables found. Running init.sql...</p>";
        
        $initSqlPath = __DIR__ . '/database/init.sql';
        if (file_exists($initSqlPath)) {
            $initSql = file_get_contents($initSqlPath);
            $statements = explode(';', $initSql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            echo "<p style='color: green;'>Database initialized successfully!</p>";
        } else {
            echo "<p style='color: red;'>init.sql not found!</p>";
        }
    } else {
        echo "<p>Database already has $tableCount tables.</p>";
    }
    
    echo "<a href='login.php'>Go to Login</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>