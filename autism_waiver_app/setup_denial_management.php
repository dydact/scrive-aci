<?php
/**
 * Setup script for Denial Management System
 * Creates all necessary tables and inserts sample data for testing
 */

require_once 'config_sqlite.php';

echo "<h2>Setting up Denial Management System</h2>";

try {
    // Read and execute the denial management SQL schema
    $sqlFile = __DIR__ . '/../sql/denial_management_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements and execute them
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $db->beginTransaction();
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            $successCount++;
            echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
        } catch (PDOException $e) {
            $errorCount++;
            echo "<p style='color: red;'>✗ Error in: " . substr($statement, 0, 50) . "...<br>Error: " . $e->getMessage() . "</p>";
            
            // Continue on error for ALTER statements that might already exist
            if (strpos($statement, 'ALTER TABLE') === false && strpos($statement, 'CREATE TRIGGER') === false) {
                throw $e;
            }
        }
    }
    
    // Insert sample denial data for testing
    echo "<h3>Inserting Sample Data</h3>";
    
    // First, ensure we have some clients and users
    $stmt = $db->query("SELECT COUNT(*) as count FROM clients");
    $clientCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($clientCount == 0) {
        // Insert sample clients
        $db->exec("
            INSERT INTO clients (client_name, medicaid_id, date_of_birth, phone, address, emergency_contact_name, emergency_contact_phone)
            VALUES 
            ('John Smith', 'MD123456789', '2010-05-15', '240-555-0101', '123 Main St, Gaithersburg, MD 20877', 'Jane Smith', '240-555-0102'),
            ('Emily Johnson', 'MD987654321', '2012-08-22', '301-555-0201', '456 Oak Ave, Rockville, MD 20850', 'Robert Johnson', '301-555-0202'),
            ('Michael Brown', 'MD555666777', '2008-12-10', '410-555-0301', '789 Pine Rd, Baltimore, MD 21201', 'Sarah Brown', '410-555-0302')
        ");
        echo "<p style='color: green;'>✓ Inserted sample clients</p>";
    }
    
    // Insert sample denial data
    $sampleDenials = [
        [
            'claim_number' => 'CLM202401001',
            'client_id' => 1,
            'service_type' => 'W1727',
            'service_date' => date('Y-m-d', strtotime('-15 days')),
            'denial_date' => date('Y-m-d', strtotime('-10 days')),
            'denial_code' => 'M01',
            'denial_reason' => 'Missing or Invalid Prior Authorization',
            'amount' => 540.00,
            'appeal_deadline' => date('Y-m-d', strtotime('+80 days')),
            'status' => 'pending',
            'priority' => 'high',
            'created_by' => 1
        ],
        [
            'claim_number' => 'CLM202401002',
            'client_id' => 2,
            'service_type' => 'W1728',
            'service_date' => date('Y-m-d', strtotime('-25 days')),
            'denial_date' => date('Y-m-d', strtotime('-20 days')),
            'denial_code' => 'M07',
            'denial_reason' => 'Invalid Procedure Code',
            'amount' => 324.00,
            'appeal_deadline' => date('Y-m-d', strtotime('+70 days')),
            'status' => 'in_progress',
            'priority' => 'medium',
            'assigned_to' => 1,
            'assigned_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'created_by' => 1
        ],
        [
            'claim_number' => 'CLM202401003',
            'client_id' => 3,
            'service_type' => 'W7061',
            'service_date' => date('Y-m-d', strtotime('-45 days')),
            'denial_date' => date('Y-m-d', strtotime('-40 days')),
            'denial_code' => 'M12',
            'denial_reason' => 'Service Limit Exceeded',
            'amount' => 180.00,
            'appeal_deadline' => date('Y-m-d', strtotime('+50 days')),
            'status' => 'appealed',
            'priority' => 'medium',
            'assigned_to' => 1,
            'assigned_date' => date('Y-m-d H:i:s', strtotime('-30 days')),
            'appeal_status' => 'submitted',
            'appeal_submission_date' => date('Y-m-d', strtotime('-7 days')),
            'created_by' => 1
        ],
        [
            'claim_number' => 'CLM202401004',
            'client_id' => 1,
            'service_type' => 'W7060',
            'service_date' => date('Y-m-d', strtotime('-60 days')),
            'denial_date' => date('Y-m-d', strtotime('-55 days')),
            'denial_code' => 'M14',
            'denial_reason' => 'Missing Documentation',
            'amount' => 160.00,
            'appeal_deadline' => date('Y-m-d', strtotime('+35 days')),
            'status' => 'resolved',
            'priority' => 'low',
            'assigned_to' => 1,
            'assigned_date' => date('Y-m-d H:i:s', strtotime('-45 days')),
            'resolution_type' => 'paid',
            'resolution_amount' => 160.00,
            'resolution_date' => date('Y-m-d', strtotime('-5 days')),
            'resolution_notes' => 'Documentation provided and claim reprocessed successfully',
            'created_by' => 1
        ],
        [
            'claim_number' => 'CLM202401005',
            'client_id' => 2,
            'service_type' => 'W1727',
            'service_date' => date('Y-m-d', strtotime('-95 days')),
            'denial_date' => date('Y-m-d', strtotime('-90 days')),
            'denial_code' => 'M09',
            'denial_reason' => 'Timely Filing Limit Exceeded',
            'amount' => 432.00,
            'appeal_deadline' => date('Y-m-d', strtotime('-1 days')),
            'status' => 'pending',
            'priority' => 'high',
            'created_by' => 1
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO claim_denials (
            claim_number, client_id, service_type, service_date, denial_date,
            denial_code, denial_reason, amount, appeal_deadline, status, priority,
            assigned_to, assigned_date, appeal_status, appeal_submission_date,
            resolution_type, resolution_amount, resolution_date, resolution_notes,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleDenials as $denial) {
        $stmt->execute([
            $denial['claim_number'],
            $denial['client_id'],
            $denial['service_type'],
            $denial['service_date'],
            $denial['denial_date'],
            $denial['denial_code'],
            $denial['denial_reason'],
            $denial['amount'],
            $denial['appeal_deadline'],
            $denial['status'],
            $denial['priority'],
            $denial['assigned_to'] ?? null,
            $denial['assigned_date'] ?? null,
            $denial['appeal_status'] ?? null,
            $denial['appeal_submission_date'] ?? null,
            $denial['resolution_type'] ?? null,
            $denial['resolution_amount'] ?? null,
            $denial['resolution_date'] ?? null,
            $denial['resolution_notes'] ?? null,
            $denial['created_by']
        ]);
    }
    
    echo "<p style='color: green;'>✓ Inserted " . count($sampleDenials) . " sample denials</p>";
    
    // Insert sample activities for the denials
    $sampleActivities = [
        [
            'denial_id' => 1,
            'activity_type' => 'status_update',
            'description' => 'Denial received from Maryland Medicaid. Prior authorization not found in system.',
            'created_by' => 1
        ],
        [
            'denial_id' => 2,
            'activity_type' => 'assignment',
            'description' => 'Assigned to billing specialist for code verification.',
            'created_by' => 1
        ],
        [
            'denial_id' => 2,
            'activity_type' => 'note',
            'description' => 'Verified that W1728 is correct code. Checking with provider for documentation.',
            'created_by' => 1
        ],
        [
            'denial_id' => 3,
            'activity_type' => 'appeal_filed',
            'description' => 'Appeal submitted to Maryland Medicaid with utilization tracking documentation.',
            'created_by' => 1
        ],
        [
            'denial_id' => 4,
            'activity_type' => 'resolution',
            'description' => 'Missing progress notes submitted. Claim approved for full payment.',
            'created_by' => 1
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO denial_activities (denial_id, activity_type, description, created_by)
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($sampleActivities as $activity) {
        $stmt->execute([
            $activity['denial_id'],
            $activity['activity_type'],
            $activity['description'],
            $activity['created_by']
        ]);
    }
    
    echo "<p style='color: green;'>✓ Inserted " . count($sampleActivities) . " sample activities</p>";
    
    // Insert sample appeals
    $sampleAppeals = [
        [
            'denial_id' => 3,
            'appeal_date' => date('Y-m-d', strtotime('-7 days')),
            'appeal_type' => 'reconsideration',
            'appeal_reason' => 'The service limits were not exceeded according to our tracking. Member was authorized for 32 units per day and only received 28 units on the service date. Please review attached utilization logs.',
            'supporting_documentation' => 'Utilization tracking report, Authorization approval letter, Daily service logs',
            'contact_person' => 'Jane Doe',
            'contact_phone' => '(240) 264-0044',
            'status' => 'pending',
            'expedited' => 0,
            'created_by' => 1
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO claim_appeals (
            denial_id, appeal_date, appeal_type, appeal_reason, supporting_documentation,
            contact_person, contact_phone, status, expedited, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleAppeals as $appeal) {
        $stmt->execute([
            $appeal['denial_id'],
            $appeal['appeal_date'],
            $appeal['appeal_type'],
            $appeal['appeal_reason'],
            $appeal['supporting_documentation'],
            $appeal['contact_person'],
            $appeal['contact_phone'],
            $appeal['status'],
            $appeal['expedited'],
            $appeal['created_by']
        ]);
    }
    
    echo "<p style='color: green;'>✓ Inserted " . count($sampleAppeals) . " sample appeals</p>";
    
    // Insert sample tasks
    $sampleTasks = [
        [
            'denial_id' => 1,
            'task_type' => 'follow_up',
            'description' => 'Contact provider to obtain prior authorization documentation',
            'due_date' => date('Y-m-d', strtotime('+3 days')),
            'assigned_to' => 1,
            'status' => 'pending',
            'created_by' => 1
        ],
        [
            'denial_id' => 5,
            'task_type' => 'appeal_deadline',
            'description' => 'Appeal deadline approaching - file appeal or accept denial',
            'due_date' => date('Y-m-d', strtotime('+1 days')),
            'assigned_to' => 1,
            'status' => 'pending',
            'created_by' => 1
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO denial_tasks (
            denial_id, task_type, description, due_date, assigned_to, status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleTasks as $task) {
        $stmt->execute([
            $task['denial_id'],
            $task['task_type'],
            $task['description'],
            $task['due_date'],
            $task['assigned_to'],
            $task['status'],
            $task['created_by']
        ]);
    }
    
    echo "<p style='color: green;'>✓ Inserted " . count($sampleTasks) . " sample tasks</p>";
    
    $db->commit();
    
    echo "<h3 style='color: green;'>Setup Complete!</h3>";
    echo "<p>Successfully executed $successCount SQL statements";
    if ($errorCount > 0) {
        echo " with $errorCount non-critical errors";
    }
    echo ".</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li><a href='billing/denial_management.php'>Access the Denial Management Dashboard</a></li>";
    echo "<li><a href='billing/denial_analytics.php'>View Denial Analytics</a></li>";
    echo "<li>Configure denial prevention strategies in the system</li>";
    echo "<li>Train staff on the new denial management workflow</li>";
    echo "</ul>";
    
    echo "<h3>System Features:</h3>";
    echo "<ul>";
    echo "<li>✓ Comprehensive denial tracking with Maryland Medicaid codes</li>";
    echo "<li>✓ Appeal management with template generation</li>";
    echo "<li>✓ Task management and follow-up scheduling</li>";
    echo "<li>✓ Detailed analytics and reporting</li>";
    echo "<li>✓ Staff productivity tracking</li>";
    echo "<li>✓ Preventable denial identification</li>";
    echo "<li>✓ Document attachment support</li>";
    echo "<li>✓ Automated deadline tracking</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "<h3 style='color: red;'>Setup Failed!</h3>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check the error details and try again.</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h2, h3 {
    color: #0066cc;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

p {
    margin: 5px 0;
    padding: 5px;
    border-radius: 4px;
}

ul {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

li {
    margin: 10px 0;
}

a {
    color: #0066cc;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>