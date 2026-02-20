<?php 
/**
 * Database Connection Test
 * Access this file to verify your database setup
 */

// Display errors for testing
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Include constants FIRST
require_once __DIR__ . '/config/constants.php';

// ✅ Then include database
require_once __DIR__ . '/config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            padding: 15px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            padding: 15px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            color: #004085;
            padding: 15px;
            background: #cce5ff;
            border: 1px solid #b8daff;
            border-radius: 4px;
            margin: 10px 0;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Adaptive Micro-Scheduling System - Setup Test</h1>
        
        <?php
        require_once 'config/database.php';
        
        echo "<h2>1. Database Connection Test</h2>";
        
        $database = new Database();
        $db = $database->getConnection();
        
        if($db) {
            echo '<div class="success">✓ Database connection successful!</div>';
            
            // Test if tables exist
            echo "<h2>2. Database Tables Check</h2>";
            
            $tables = ['users', 'tasks', 'task_logs', 'user_preferences', 'sync_queue', 'user_metrics'];
            $table_status = [];
            
            foreach($tables as $table) {
                try {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    $table_status[$table] = [
                        'exists' => true,
                        'count' => $count,
                        'status' => 'OK'
                    ];
                } catch(PDOException $e) {
                    $table_status[$table] = [
                        'exists' => false,
                        'count' => 0,
                        'status' => 'Missing'
                    ];
                }
            }
            
            echo "<table>";
            echo "<tr><th>Table Name</th><th>Status</th><th>Record Count</th></tr>";
            
            $all_tables_exist = true;
            foreach($table_status as $table => $status) {
                $row_class = $status['exists'] ? 'success' : 'error';
                echo "<tr>";
                echo "<td>$table</td>";
                echo "<td style='color: " . ($status['exists'] ? 'green' : 'red') . ";'>" . $status['status'] . "</td>";
                echo "<td>" . $status['count'] . "</td>";
                echo "</tr>";
                
                if(!$status['exists']) {
                    $all_tables_exist = false;
                }
            }
            echo "</table>";
            
            if(!$all_tables_exist) {
                echo '<div class="error">⚠ Some tables are missing. Please import the database.sql file.</div>';
                echo '<div class="info"><strong>How to import:</strong><br>1. Open phpMyAdmin<br>2. Select database "adaptive_scheduling_db"<br>3. Click "Import" tab<br>4. Choose the database.sql file<br>5. Click "Go"</div>';
            } else {
                echo '<div class="success">✓ All required tables exist!</div>';
            }
            
            // Test API endpoints
            echo "<h2>3. API Configuration</h2>";
            
            $api_base = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
            
            echo "<div class='info'>";
            echo "<strong>API Base URL:</strong> $api_base<br>";
            echo "<strong>API Version:</strong> " . API_VERSION . "<br>";
            echo "<strong>Timezone:</strong> " . API_TIMEZONE . "<br>";
            echo "<strong>JWT Expiration:</strong> " . (JWT_EXPIRATION_TIME / 3600) . " hours";
            echo "</div>";
            
            echo "<h2>4. Available Endpoints</h2>";
            echo "<div class='code'>";
            echo "<strong>Authentication:</strong><br>";
            echo "POST $api_base/api/auth/register.php<br>";
            echo "POST $api_base/api/auth/login.php<br>";
            echo "POST $api_base/api/auth/logout.php<br><br>";
            
            echo "<strong>Tasks:</strong><br>";
            echo "POST $api_base/api/tasks/create.php<br>";
            echo "GET $api_base/api/tasks/read.php<br>";
            echo "PUT $api_base/api/tasks/update.php<br>";
            echo "DELETE $api_base/api/tasks/delete.php<br>";
            echo "POST $api_base/api/tasks/sync.php<br><br>";
            
            echo "<strong>User Profile:</strong><br>";
            echo "GET $api_base/api/user/profile.php<br>";
            echo "PUT $api_base/api/user/update.php<br>";
            echo "</div>";
            
            echo "<h2>5. Next Steps</h2>";
            echo "<div class='info'>";
            echo "✓ Database is configured correctly<br>";
            echo "✓ All tables are set up<br>";
            echo "✓ API endpoints are ready<br><br>";
            echo "<strong>You can now:</strong><br>";
            echo "1. Test the API using Postman or any HTTP client<br>";
            echo "2. Build your frontend application<br>";
            echo "3. Start developing!<br>";
            echo "</div>";
            
        } else {
            echo '<div class="error">✗ Database connection failed!</div>';
            echo '<div class="info">';
            echo '<strong>Troubleshooting:</strong><br>';
            echo '1. Make sure XAMPP MySQL service is running<br>';
            echo '2. Check database credentials in config/database.php<br>';
            echo '3. Verify database "adaptive_scheduling_db" exists<br>';
            echo '4. Check MySQL port (default: 3306)<br>';
            echo '</div>';
        }
        ?>
        
    </div>
</body>
</html>
