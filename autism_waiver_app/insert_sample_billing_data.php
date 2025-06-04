<?php
// Insert sample billing data for testing reports
require_once '../src/init.php';

try {
    $pdo = getDatabase();
    
    echo "<h2>Inserting Sample Billing Data</h2>\n";
    
    // First check if we have clients
    $clientCount = $pdo->query("SELECT COUNT(*) FROM autism_clients")->fetchColumn();
    if ($clientCount == 0) {
        echo "<p style='color: red;'>No clients found. Please run the setup first.</p>";
        exit;
    }
    
    // Get client IDs
    $clients = $pdo->query("SELECT id, first_name, last_name FROM autism_clients LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Creating Sample Claims:</h3>\n";
    
    // Insert various claims with different statuses and dates
    $statuses = ['draft', 'generated', 'submitted', 'paid', 'denied'];
    $claimCount = 0;
    
    foreach ($clients as $client) {
        // Create 3-5 claims per client over the past 6 months
        $numClaims = rand(3, 5);
        
        for ($i = 0; $i < $numClaims; $i++) {
            // Random date in the past 6 months
            $daysAgo = rand(1, 180);
            $serviceDate = date('Y-m-d', strtotime("-$daysAgo days"));
            $serviceEndDate = date('Y-m-d', strtotime($serviceDate . ' +6 days'));
            
            // Random status with weighted distribution
            $statusRand = rand(1, 100);
            if ($statusRand <= 40) {
                $status = 'paid';
            } elseif ($statusRand <= 60) {
                $status = 'submitted';
            } elseif ($statusRand <= 75) {
                $status = 'generated';
            } elseif ($statusRand <= 90) {
                $status = 'draft';
            } else {
                $status = 'denied';
            }
            
            // Generate claim number
            $claimNumber = 'CLM' . date('Ymd', strtotime($serviceDate)) . sprintf('%04d', $claimCount + 1);
            
            // Random amount between $500 and $3000
            $totalAmount = rand(500, 3000);
            $paymentAmount = ($status === 'paid') ? $totalAmount * (rand(80, 100) / 100) : 0;
            
            // Insert claim
            $stmt = $pdo->prepare("
                INSERT INTO autism_claims 
                (claim_number, client_id, service_date_from, service_date_to, total_amount, status, payment_amount, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $createdAt = date('Y-m-d H:i:s', strtotime($serviceEndDate . ' +' . rand(1, 7) . ' days'));
            $updatedAt = ($status === 'paid') ? date('Y-m-d H:i:s', strtotime($createdAt . ' +' . rand(15, 45) . ' days')) : $createdAt;
            
            $stmt->execute([
                $claimNumber,
                $client['id'],
                $serviceDate,
                $serviceEndDate,
                $totalAmount,
                $status,
                $paymentAmount,
                $createdAt,
                $updatedAt
            ]);
            
            $claimCount++;
            
            // If denied, add denial reason
            if ($status === 'denied') {
                $claimId = $pdo->lastInsertId();
                $denialReasons = [
                    'Invalid authorization number',
                    'Service not covered',
                    'Duplicate claim',
                    'Missing documentation',
                    'Timely filing limit exceeded'
                ];
                $reason = $denialReasons[array_rand($denialReasons)];
                
                $stmt = $pdo->prepare("
                    INSERT INTO autism_claim_denials 
                    (claim_id, denial_code, denial_reason, denial_date)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $claimId,
                    'D' . rand(100, 999),
                    $reason,
                    date('Y-m-d', strtotime($updatedAt))
                ]);
            }
        }
        
        echo "- Created $numClaims claims for {$client['first_name']} {$client['last_name']}\n";
    }
    
    echo "\n<h3>Summary:</h3>\n";
    echo "- Total claims created: $claimCount\n";
    
    // Show distribution
    $distribution = $pdo->query("
        SELECT status, COUNT(*) as count, SUM(total_amount) as total
        FROM autism_claims
        GROUP BY status
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n<h3>Claims Distribution:</h3>\n";
    foreach ($distribution as $dist) {
        echo "- {$dist['status']}: {$dist['count']} claims, $" . number_format($dist['total'], 2) . "\n";
    }
    
    // Create some sample prior authorizations
    echo "\n<h3>Creating Prior Authorizations:</h3>\n";
    $serviceTypes = $pdo->query("SELECT id FROM autism_service_types LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($serviceTypes)) {
        foreach ($clients as $client) {
            $serviceTypeId = $serviceTypes[array_rand($serviceTypes)];
            $authNumber = 'AUTH' . date('Y') . sprintf('%06d', $client['id']);
            
            $stmt = $pdo->prepare("
                INSERT INTO autism_prior_authorizations 
                (client_id, service_type_id, authorization_number, start_date, end_date, authorized_units, used_units, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE used_units = VALUES(used_units)
            ");
            
            $stmt->execute([
                $client['id'],
                $serviceTypeId,
                $authNumber,
                date('Y-01-01'),
                date('Y-12-31'),
                1040, // 20 hours/week * 52 weeks
                rand(100, 800), // Random usage
                'active'
            ]);
        }
        echo "- Created prior authorizations for " . count($clients) . " clients\n";
    }
    
    echo "\n<p style='color: green;'>Sample data inserted successfully!</p>";
    echo "<p><a href='billing_reports.php'>View Billing Reports</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; white-space: pre-wrap; }
h2, h3 { color: #333; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>