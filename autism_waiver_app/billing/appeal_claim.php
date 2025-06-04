<?php
require_once dirname(__DIR__) . '/config_sqlite.php';

// Check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /autism_waiver_app/simple_login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$denialId = $_GET['denial_id'] ?? null;

if (!$denialId) {
    header('Location: denial_management.php');
    exit;
}

// Get denial information
try {
    $stmt = $db->prepare("
        SELECT cd.*, c.client_name, c.medicaid_id, c.date_of_birth,
               u.full_name as assigned_to_name
        FROM claim_denials cd
        LEFT JOIN clients c ON cd.client_id = c.id
        LEFT JOIN users u ON cd.assigned_to = u.id
        WHERE cd.id = ?
    ");
    $stmt->execute([$denialId]);
    $denial = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$denial) {
        header('Location: denial_management.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching denial: " . $e->getMessage());
    header('Location: denial_management.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Create appeal record
        $stmt = $db->prepare("
            INSERT INTO claim_appeals (
                denial_id, appeal_date, appeal_type, appeal_reason,
                supporting_documentation, contact_person, contact_phone,
                expedited, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        
        $stmt->execute([
            $denialId,
            $_POST['appeal_date'],
            $_POST['appeal_type'],
            $_POST['appeal_reason'],
            $_POST['supporting_documentation'],
            $_POST['contact_person'],
            $_POST['contact_phone'],
            isset($_POST['expedited']) ? 1 : 0,
            $userId
        ]);
        
        $appealId = $db->lastInsertId();
        
        // Update denial status
        $stmt = $db->prepare("
            UPDATE claim_denials 
            SET status = 'appealed', 
                appeal_status = 'submitted',
                appeal_id = ?,
                updated_at = datetime('now')
            WHERE id = ?
        ");
        $stmt->execute([$appealId, $denialId]);
        
        // Create follow-up task
        $followUpDate = date('Y-m-d', strtotime('+30 days'));
        $stmt = $db->prepare("
            INSERT INTO denial_tasks (
                denial_id, task_type, description, due_date,
                assigned_to, created_by, created_at
            ) VALUES (?, 'follow_up', ?, ?, ?, ?, datetime('now'))
        ");
        
        $stmt->execute([
            $denialId,
            'Follow up on appeal for claim ' . $denial['claim_number'],
            $followUpDate,
            $_POST['assigned_to'] ?? $userId,
            $userId
        ]);
        
        // Handle file uploads
        if (!empty($_FILES['appeal_documents']['name'][0])) {
            $uploadDir = dirname(__DIR__) . '/uploads/appeals/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            foreach ($_FILES['appeal_documents']['name'] as $key => $filename) {
                if ($_FILES['appeal_documents']['error'][$key] == 0) {
                    $targetFile = $uploadDir . $appealId . '_' . basename($filename);
                    move_uploaded_file($_FILES['appeal_documents']['tmp_name'][$key], $targetFile);
                    
                    // Record file attachment
                    $stmt = $db->prepare("
                        INSERT INTO appeal_attachments (
                            appeal_id, filename, file_path, uploaded_by, uploaded_at
                        ) VALUES (?, ?, ?, ?, datetime('now'))
                    ");
                    $stmt->execute([$appealId, $filename, $targetFile, $userId]);
                }
            }
        }
        
        $db->commit();
        
        // Redirect with success message
        $_SESSION['flash_message'] = 'Appeal submitted successfully!';
        header('Location: denial_management.php');
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error creating appeal: " . $e->getMessage());
        $error = "Error submitting appeal. Please try again.";
    }
}

// Maryland Medicaid appeal types
$appealTypes = [
    'reconsideration' => 'Reconsideration Request',
    'peer_review' => 'Peer-to-Peer Review',
    'formal_appeal' => 'Formal Appeal',
    'expedited' => 'Expedited Appeal',
    'external_review' => 'External Review'
];

// Get previous appeals for this claim
$previousAppeals = [];
try {
    $stmt = $db->prepare("
        SELECT ca.*, u.full_name as created_by_name
        FROM claim_appeals ca
        LEFT JOIN users u ON ca.created_by = u.id
        WHERE ca.denial_id = ?
        ORDER BY ca.appeal_date DESC
    ");
    $stmt->execute([$denialId]);
    $previousAppeals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching previous appeals: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Appeal - Claim <?php echo htmlspecialchars($denial['claim_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .denial-header {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .form-section {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section h5 {
            color: #0066cc;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        .previous-appeal {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .template-btn {
            margin: 2px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/autism_waiver_app/billing/denial_management.php">
                <i class="bi bi-arrow-left"></i> Back to Denial Management
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">File Appeal</h1>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Denial Information -->
        <div class="denial-header">
            <div class="row">
                <div class="col-md-3">
                    <strong>Claim #:</strong> <?php echo htmlspecialchars($denial['claim_number']); ?>
                </div>
                <div class="col-md-3">
                    <strong>Client:</strong> <?php echo htmlspecialchars($denial['client_name']); ?>
                </div>
                <div class="col-md-3">
                    <strong>Amount:</strong> $<?php echo number_format($denial['amount'], 2); ?>
                </div>
                <div class="col-md-3">
                    <strong>Denial Code:</strong> <?php echo htmlspecialchars($denial['denial_code']); ?>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>Denial Reason:</strong> <?php echo htmlspecialchars($denial['denial_reason']); ?>
                </div>
                <div class="col-md-3">
                    <strong>Service Date:</strong> <?php echo date('m/d/Y', strtotime($denial['service_date'])); ?>
                </div>
                <div class="col-md-3">
                    <strong>Appeal Deadline:</strong> 
                    <span class="text-danger"><?php echo date('m/d/Y', strtotime($denial['appeal_deadline'])); ?></span>
                </div>
            </div>
        </div>

        <!-- Previous Appeals -->
        <?php if (!empty($previousAppeals)): ?>
        <div class="form-section">
            <h5><i class="bi bi-clock-history"></i> Previous Appeals</h5>
            <?php foreach ($previousAppeals as $appeal): ?>
            <div class="previous-appeal">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Date:</strong> <?php echo date('m/d/Y', strtotime($appeal['appeal_date'])); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Type:</strong> <?php echo htmlspecialchars($appealTypes[$appeal['appeal_type']] ?? $appeal['appeal_type']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Status:</strong> 
                        <span class="badge bg-<?php echo $appeal['status'] === 'approved' ? 'success' : ($appeal['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                            <?php echo ucfirst($appeal['status']); ?>
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>By:</strong> <?php echo htmlspecialchars($appeal['created_by_name']); ?>
                    </div>
                </div>
                <?php if ($appeal['appeal_reason']): ?>
                <div class="mt-2">
                    <strong>Reason:</strong> <?php echo htmlspecialchars($appeal['appeal_reason']); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Appeal Form -->
        <form method="POST" enctype="multipart/form-data" id="appealForm">
            <div class="form-section">
                <h5><i class="bi bi-file-earmark-text"></i> Appeal Information</h5>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="appeal_date" class="form-label">Appeal Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="appeal_date" name="appeal_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="appeal_type" class="form-label">Appeal Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="appeal_type" name="appeal_type" required>
                            <option value="">Select Type</option>
                            <?php foreach ($appealTypes as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="assigned_to" class="form-label">Assign Follow-up To</label>
                        <select class="form-select" id="assigned_to" name="assigned_to">
                            <option value="<?php echo $userId; ?>">Myself</option>
                            <?php
                            $stmt = $db->query("SELECT id, full_name FROM users WHERE role IN ('admin', 'billing_specialist') AND id != $userId ORDER BY full_name");
                            while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['full_name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="appeal_reason" class="form-label">Appeal Reason <span class="text-danger">*</span></label>
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary template-btn" onclick="useTemplate('auth')">
                            Prior Auth Template
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary template-btn" onclick="useTemplate('medical')">
                            Medical Necessity
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary template-btn" onclick="useTemplate('coding')">
                            Coding Error
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary template-btn" onclick="useTemplate('timely')">
                            Timely Filing
                        </button>
                    </div>
                    <textarea class="form-control" id="appeal_reason" name="appeal_reason" rows="6" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="supporting_documentation" class="form-label">Supporting Documentation Summary</label>
                    <textarea class="form-control" id="supporting_documentation" name="supporting_documentation" rows="3"
                              placeholder="List the documents being submitted with this appeal"></textarea>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="expedited" name="expedited">
                    <label class="form-check-label" for="expedited">
                        <strong>Request Expedited Review</strong> - Check if member's health could be seriously jeopardized by standard timeframe
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h5><i class="bi bi-person-lines-fill"></i> Contact Information</h5>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="contact_person" class="form-label">Contact Person <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" 
                               value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="contact_phone" class="form-label">Contact Phone <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                               placeholder="(xxx) xxx-xxxx" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h5><i class="bi bi-paperclip"></i> Attachments</h5>
                
                <div class="mb-3">
                    <label for="appeal_documents" class="form-label">Upload Supporting Documents</label>
                    <input type="file" class="form-control" id="appeal_documents" name="appeal_documents[]" multiple>
                    <small class="text-muted">
                        Accepted formats: PDF, JPG, PNG, DOC, DOCX. Max file size: 10MB each.
                    </small>
                </div>

                <div class="alert alert-info">
                    <h6>Required Documentation Checklist:</h6>
                    <ul class="mb-0">
                        <li>Copy of original claim</li>
                        <li>Copy of denial letter/EOB</li>
                        <li>Medical records supporting medical necessity (if applicable)</li>
                        <li>Prior authorization documentation (if applicable)</li>
                        <li>Proof of timely filing (if applicable)</li>
                        <li>Corrected claim form (if coding error)</li>
                    </ul>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="denial_management.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send"></i> Submit Appeal
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Appeal templates
        const templates = {
            auth: `Re: Claim #${<?php echo json_encode($denial['claim_number']); ?>} - Appeal for Prior Authorization Denial

Dear Maryland Medicaid Appeals Department,

We are formally appealing the denial of claim #${<?php echo json_encode($denial['claim_number']); ?>} for ${<?php echo json_encode($denial['client_name']); ?>} (Medicaid ID: ${<?php echo json_encode($denial['medicaid_id']); ?>}).

The services provided on ${<?php echo json_encode(date('m/d/Y', strtotime($denial['service_date']))); ?>} were medically necessary and appropriate for this member's autism spectrum disorder diagnosis. Prior authorization was obtained on [DATE] with authorization number [AUTH#].

We have attached the following supporting documentation:
- Copy of prior authorization approval
- Treatment plan showing medical necessity
- Progress notes demonstrating need for continued services

We respectfully request that this denial be reversed and the claim be paid according to the Maryland Autism Waiver fee schedule.

Thank you for your consideration.`,

            medical: `Re: Claim #${<?php echo json_encode($denial['claim_number']); ?>} - Appeal for Medical Necessity Denial

Dear Maryland Medicaid Appeals Department,

We are formally appealing the denial of claim #${<?php echo json_encode($denial['claim_number']); ?>} for services deemed not medically necessary.

The behavioral support services provided to ${<?php echo json_encode($denial['client_name']); ?>} are essential for managing their autism-related behaviors and improving their quality of life. The attached clinical documentation demonstrates:

1. Severity of behavioral challenges requiring intervention
2. Evidence-based treatment approach being utilized
3. Progress made with current treatment plan
4. Continued need for services to maintain gains

We request reconsideration of this denial based on the medical necessity criteria for autism spectrum disorder services.`,

            coding: `Re: Claim #${<?php echo json_encode($denial['claim_number']); ?>} - Appeal for Coding Error

Dear Maryland Medicaid Appeals Department,

We are appealing the denial of claim #${<?php echo json_encode($denial['claim_number']); ?>} due to a coding error.

Upon review, we identified that the original claim contained an incorrect [procedure code/modifier/diagnosis code]. The correct coding should be:
- Procedure Code: [CORRECT CODE]
- Modifier: [CORRECT MODIFIER]
- Diagnosis: [CORRECT DIAGNOSIS]

We have attached a corrected claim form with the proper coding. We request that this claim be reprocessed with the corrected information.`,

            timely: `Re: Claim #${<?php echo json_encode($denial['claim_number']); ?>} - Appeal for Timely Filing Denial

Dear Maryland Medicaid Appeals Department,

We are appealing the denial of claim #${<?php echo json_encode($denial['claim_number']); ?>} for exceeding the timely filing limit.

This claim was originally submitted on [DATE] within the required timeframe. Due to [REASON - e.g., system issues, incorrect member information, etc.], the claim was not processed correctly.

Attached please find:
- Proof of original submission date
- Documentation of attempts to resolve the issue
- Correspondence regarding the claim

We request that the timely filing requirement be waived and the claim be processed for payment.`
        };

        function useTemplate(type) {
            if (templates[type]) {
                document.getElementById('appeal_reason').value = templates[type];
            }
        }

        // Phone number formatting
        document.getElementById('contact_phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 6) {
                value = `(${value.slice(0,3)}) ${value.slice(3,6)}-${value.slice(6,10)}`;
            } else if (value.length >= 3) {
                value = `(${value.slice(0,3)}) ${value.slice(3)}`;
            }
            e.target.value = value;
        });

        // Form validation
        document.getElementById('appealForm').addEventListener('submit', function(e) {
            const deadline = new Date('<?php echo $denial['appeal_deadline']; ?>');
            const today = new Date();
            
            if (today > deadline) {
                if (!confirm('Warning: The appeal deadline has passed. Do you want to continue with a late appeal?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // Validate file sizes
            const files = document.getElementById('appeal_documents').files;
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            for (let i = 0; i < files.length; i++) {
                if (files[i].size > maxSize) {
                    alert(`File "${files[i].name}" exceeds the 10MB limit.`);
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>