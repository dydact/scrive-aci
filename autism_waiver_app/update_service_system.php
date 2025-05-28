<?php

/**
 * Enhanced Service System Update - Program-Specific Services & Weekly Units
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

require_once 'auth.php';
initScriveAuth();

echo "<h2>🔧 Enhanced Service System Update</h2>";

try {
    // 1. Create program-service mapping table
    echo "<h3>Step 1: Creating program-service mapping table...</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS autism_program_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_id INT NOT NULL,
        service_type_id INT NOT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        default_weekly_units DECIMAL(5,2) DEFAULT 0,
        max_weekly_units DECIMAL(5,2) DEFAULT 0,
        unit_type ENUM('hours', 'sessions', 'visits') DEFAULT 'hours',
        billing_increment_minutes INT DEFAULT 15,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (program_id) REFERENCES autism_programs(program_id),
        FOREIGN KEY (service_type_id) REFERENCES autism_service_types(service_type_id),
        UNIQUE KEY unique_program_service (program_id, service_type_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    sqlStatement($sql);
    echo "<p>✅ Program-service mapping table created</p>";

    // 2. Update client authorizations table for weekly tracking
    echo "<h3>Step 2: Enhancing client authorizations for weekly units...</h3>";
    
    $alterSql = [
        "ADD COLUMN weekly_authorized_units DECIMAL(5,2) DEFAULT 0 COMMENT 'Units allocated per week'",
        "ADD COLUMN current_week_used DECIMAL(5,2) DEFAULT 0 COMMENT 'Units used in current week'",
        "ADD COLUMN warning_threshold DECIMAL(3,2) DEFAULT 0.80 COMMENT 'Warning when usage exceeds this percentage'",
        "ADD COLUMN alert_level ENUM('none', 'warning', 'critical', 'exhausted') DEFAULT 'none'",
        "ADD COLUMN last_week_reset DATE NULL COMMENT 'Last date weekly counter was reset'",
        "ADD COLUMN rollover_unused BOOLEAN DEFAULT FALSE COMMENT 'Allow unused units to rollover'"
    ];
    
    foreach ($alterSql as $alter) {
        try {
            sqlStatement("ALTER TABLE autism_client_authorizations " . $alter);
            echo "<p>✅ Added column: " . htmlspecialchars($alter) . "</p>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), "Duplicate column") === false) {
                echo "<p>⚠️ Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    // 3. Insert program-specific service mappings
    echo "<h3>Step 3: Setting up program-specific service mappings...</h3>";
    
    // Get program and service IDs
    $programs = [];
    $programsResult = sqlStatement("SELECT program_id, abbreviation FROM autism_programs");
    while ($row = sqlFetchArray($programsResult)) {
        $programs[$row['abbreviation']] = $row['program_id'];
    }
    
    $services = [];
    $servicesResult = sqlStatement("SELECT service_type_id, abbreviation FROM autism_service_types");
    while ($row = sqlFetchArray($servicesResult)) {
        $services[$row['abbreviation']] = $row['service_type_id'];
    }
    
    // Define program-specific service availability and default units
    $programServiceMap = [
        'AW' => [  // Autism Waiver
            'IISS' => ['weekly_units' => 20, 'max_units' => 40, 'unit_type' => 'hours'],
            'TI' => ['weekly_units' => 15, 'max_units' => 30, 'unit_type' => 'hours'],
            'RESP' => ['weekly_units' => 8, 'max_units' => 16, 'unit_type' => 'hours'],
            'FC' => ['weekly_units' => 2, 'max_units' => 4, 'unit_type' => 'hours']
        ],
        'DDA' => [  // Developmental Disabilities Administration
            'IISS' => ['weekly_units' => 25, 'max_units' => 40, 'unit_type' => 'hours'],
            'TI' => ['weekly_units' => 12, 'max_units' => 25, 'unit_type' => 'hours'],
            'RESP' => ['weekly_units' => 12, 'max_units' => 24, 'unit_type' => 'hours'],
            'PC' => ['weekly_units' => 3, 'max_units' => 6, 'unit_type' => 'hours']  // Personal Care
        ],
        'CFC' => [  // Community First Choice
            'IISS' => ['weekly_units' => 15, 'max_units' => 30, 'unit_type' => 'hours'],
            'PC' => ['weekly_units' => 20, 'max_units' => 40, 'unit_type' => 'hours'],
            'RESP' => ['weekly_units' => 6, 'max_units' => 12, 'unit_type' => 'hours'],
            'TC' => ['weekly_units' => 1, 'max_units' => 2, 'unit_type' => 'sessions']  // Transportation
        ],
        'CS' => [  // Community Supports
            'IISS' => ['weekly_units' => 10, 'max_units' => 20, 'unit_type' => 'hours'],
            'FC' => ['weekly_units' => 2, 'max_units' => 4, 'unit_type' => 'hours'],
            'TC' => ['weekly_units' => 2, 'max_units' => 4, 'unit_type' => 'sessions'],
            'CM' => ['weekly_units' => 1, 'max_units' => 2, 'unit_type' => 'sessions']  // Case Management
        ]
    ];
    
    // Clear existing mappings
    sqlStatement("DELETE FROM autism_program_services");
    
    foreach ($programServiceMap as $programAbbr => $serviceList) {
        if (!isset($programs[$programAbbr])) continue;
        
        $programId = $programs[$programAbbr];
        echo "<p><strong>{$programAbbr} Program Services:</strong></p>";
        
        foreach ($serviceList as $serviceAbbr => $config) {
            if (!isset($services[$serviceAbbr])) {
                echo "<p>⚠️ Service {$serviceAbbr} not found, skipping...</p>";
                continue;
            }
            
            $serviceId = $services[$serviceAbbr];
            
            $mappingData = [
                'program_id' => $programId,
                'service_type_id' => $serviceId,
                'is_available' => 1,
                'default_weekly_units' => $config['weekly_units'],
                'max_weekly_units' => $config['max_units'],
                'unit_type' => $config['unit_type'],
                'billing_increment_minutes' => 15
            ];
            
            $fields = implode(', ', array_keys($mappingData));
            $placeholders = str_repeat('?,', count($mappingData) - 1) . '?';
            $values = array_values($mappingData);
            
            $sql = "INSERT INTO autism_program_services ({$fields}) VALUES ({$placeholders})";
            sqlStatement($sql, $values);
            
            echo "<p>✅ {$serviceAbbr}: {$config['weekly_units']} {$config['unit_type']}/week (max: {$config['max_units']})</p>";
        }
    }

    // 4. Create weekly unit tracking function
    echo "<h3>Step 4: Creating weekly unit tracking procedures...</h3>";
    
    $procedureSql = "
    CREATE OR REPLACE VIEW autism_client_unit_status AS
    SELECT 
        a.client_id,
        a.service_type_id,
        st.service_name,
        st.abbreviation as service_abbr,
        prog.abbreviation as program_abbr,
        a.weekly_authorized_units,
        a.current_week_used,
        (a.weekly_authorized_units - a.current_week_used) as remaining_units,
        CASE 
            WHEN a.current_week_used >= a.weekly_authorized_units THEN 'exhausted'
            WHEN (a.current_week_used / a.weekly_authorized_units) >= 0.90 THEN 'critical'
            WHEN (a.current_week_used / a.weekly_authorized_units) >= a.warning_threshold THEN 'warning'
            ELSE 'normal'
        END as status,
        ROUND((a.current_week_used / a.weekly_authorized_units) * 100, 1) as usage_percentage,
        a.last_week_reset,
        DATEDIFF(CURDATE(), a.last_week_reset) as days_since_reset
    FROM autism_client_authorizations a
    JOIN autism_service_types st ON a.service_type_id = st.service_type_id
    JOIN autism_client_enrollments e ON a.client_id = e.client_id
    JOIN autism_programs prog ON e.program_id = prog.program_id
    WHERE a.status = 'active'";
    
    sqlStatement($procedureSql);
    echo "<p>✅ Weekly unit tracking view created</p>";

    // 5. Update existing authorizations with weekly units
    echo "<h3>Step 5: Updating existing authorizations with weekly units...</h3>";
    
    $updateSql = "
    UPDATE autism_client_authorizations a
    JOIN autism_client_enrollments e ON a.client_id = e.client_id
    JOIN autism_programs prog ON e.program_id = prog.program_id
    JOIN autism_program_services ps ON prog.program_id = ps.program_id AND a.service_type_id = ps.service_type_id
    SET 
        a.weekly_authorized_units = ps.default_weekly_units,
        a.current_week_used = 0,
        a.last_week_reset = CURDATE(),
        a.alert_level = 'none'
    WHERE a.weekly_authorized_units = 0";
    
    $updated = sqlStatement($updateSql);
    echo "<p>✅ Updated existing authorizations with weekly units</p>";

    echo "<h3>✅ Enhanced Service System Update Complete!</h3>";
    echo "<p><strong>New Features:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Program-specific service types (different services per waiver program)</li>";
    echo "<li>✅ Weekly unit allocation system with customizable limits</li>";
    echo "<li>✅ Real-time unit depletion warnings (warning/critical/exhausted)</li>";
    echo "<li>✅ Automatic weekly reset tracking</li>";
    echo "<li>✅ Unit rollover capability</li>";
    echo "<li>✅ Visual progress tracking with percentage calculations</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Update client management interface to show program-specific services</li>";
    echo "<li>Add weekly unit entry fields in add client form</li>";
    echo "<li>Implement warning alerts in scheduling system</li>";
    echo "<li>Create unit management dashboard for administrators</li>";
    echo "</ol>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>";
}

?> 