<?php
session_start();
require_once '../auth_helper.php';
require_once '../billing_integration.php';

checkAuthorization(['admin', 'billing_specialist']);

$conn = getConnection();
$claim_id = $_GET['id'] ?? 0;

// Get claim details
$query = "SELECT bc.*, c.name as client_name, c.medicaid_id, c.date_of_birth,
          c.address, c.city, c.state, c.zip, c.phone,
          s.name as staff_name, s.npi, s.employee_id,
          st.description as service_description
          FROM billing_claims bc
          INNER JOIN clients c ON bc.client_id = c.id
          INNER JOIN staff s ON bc.staff_id = s.id
          LEFT JOIN service_types st ON bc.service_code = st.service_code
          WHERE bc.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $claim_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$claim = $result->fetch_assoc()) {
    echo "<div class='alert alert-danger'>Claim not found</div>";
    exit;
}

// Get related sessions
$sessions_query = "SELECT cs.*, st.description 
    FROM client_sessions cs
    LEFT JOIN service_types st ON cs.service_type_id = st.id
    WHERE cs.claim_id = ?
    ORDER BY cs.service_date";

$stmt = $conn->prepare($sessions_query);
$stmt->bind_param("i", $claim_id);
$stmt->execute();
$sessions_result = $stmt->get_result();

// Get claim activity log
$activity_query = "SELECT cal.*, u.username 
    FROM claim_activity_log cal
    LEFT JOIN users u ON cal.created_by = u.id
    WHERE cal.claim_id = ?
    ORDER BY cal.created_at DESC";

$stmt = $conn->prepare($activity_query);
$stmt->bind_param("i", $claim_id);
$stmt->execute();
$activity_result = $stmt->get_result();
?>

<div class="row">
    <div class="col-md-6">
        <h6>Claim Information</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Claim Number:</th>
                <td><?php echo htmlspecialchars($claim['claim_number']); ?></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="claim-status <?php echo $claim['status']; ?>">
                        <?php echo ucfirst($claim['status']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Service Dates:</th>
                <td>
                    <?php echo date('m/d/Y', strtotime($claim['service_start_date'])); ?>
                    <?php if ($claim['service_end_date'] != $claim['service_start_date']): ?>
                        - <?php echo date('m/d/Y', strtotime($claim['service_end_date'])); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Service Code:</th>
                <td><?php echo htmlspecialchars($claim['service_code']); ?></td>
            </tr>
            <tr>
                <th>Units:</th>
                <td><?php echo number_format($claim['units'], 2); ?></td>
            </tr>
            <tr>
                <th>Rate:</th>
                <td>$<?php echo number_format($claim['rate'], 2); ?></td>
            </tr>
            <tr>
                <th>Total Amount:</th>
                <td><strong>$<?php echo number_format($claim['total_amount'], 2); ?></strong></td>
            </tr>
            <?php if ($claim['authorization_number']): ?>
            <tr>
                <th>Authorization:</th>
                <td><?php echo htmlspecialchars($claim['authorization_number']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6>Client Information</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Client Name:</th>
                <td><?php echo htmlspecialchars($claim['client_name']); ?></td>
            </tr>
            <tr>
                <th>Medicaid ID:</th>
                <td><?php echo htmlspecialchars($claim['medicaid_id']); ?></td>
            </tr>
            <tr>
                <th>Date of Birth:</th>
                <td><?php echo date('m/d/Y', strtotime($claim['date_of_birth'])); ?></td>
            </tr>
            <tr>
                <th>Address:</th>
                <td>
                    <?php echo htmlspecialchars($claim['address']); ?><br>
                    <?php echo htmlspecialchars($claim['city'] . ', ' . $claim['state'] . ' ' . $claim['zip']); ?>
                </td>
            </tr>
        </table>
        
        <h6 class="mt-3">Provider Information</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Provider Name:</th>
                <td><?php echo htmlspecialchars($claim['staff_name']); ?></td>
            </tr>
            <tr>
                <th>NPI:</th>
                <td><?php echo htmlspecialchars($claim['npi']); ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if ($claim['status'] == 'denied' && $claim['denial_reason']): ?>
<div class="alert alert-danger mt-3">
    <h6>Denial Information</h6>
    <p><strong>Reason:</strong> <?php echo htmlspecialchars($claim['denial_reason']); ?></p>
    <?php if ($claim['denial_codes']): ?>
    <p><strong>Denial Codes:</strong> <?php echo htmlspecialchars($claim['denial_codes']); ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="mt-4">
    <h6>Related Sessions</h6>
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Date</th>
                <th>Service</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Units</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($session = $sessions_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('m/d/Y', strtotime($session['service_date'])); ?></td>
                <td><?php echo htmlspecialchars($session['description']); ?></td>
                <td><?php echo date('g:i A', strtotime($session['start_time'])); ?></td>
                <td><?php echo date('g:i A', strtotime($session['end_time'])); ?></td>
                <td><?php echo number_format($session['units'], 2); ?></td>
                <td><?php echo htmlspecialchars(substr($session['notes'], 0, 50)); ?>...</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="mt-4">
    <h6>Activity Log</h6>
    <div class="activity-log" style="max-height: 200px; overflow-y: auto;">
        <?php while ($activity = $activity_result->fetch_assoc()): ?>
        <div class="activity-item mb-2 p-2 bg-light">
            <small class="text-muted">
                <?php echo date('m/d/Y g:i A', strtotime($activity['created_at'])); ?> - 
                <?php echo htmlspecialchars($activity['username'] ?? 'System'); ?>
            </small><br>
            <strong><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?>:</strong>
            <?php echo htmlspecialchars($activity['description']); ?>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php if ($claim['status'] == 'pending'): ?>
<div class="mt-4">
    <h6>Edit Claim</h6>
    <form id="editClaimForm">
        <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label>Service Code</label>
                    <select class="form-select" name="service_code">
                        <option value="W1727" <?php echo $claim['service_code'] == 'W1727' ? 'selected' : ''; ?>>W1727 - Intensive Individual Support Services</option>
                        <option value="W1728" <?php echo $claim['service_code'] == 'W1728' ? 'selected' : ''; ?>>W1728 - Therapeutic Integration</option>
                        <option value="W7061" <?php echo $claim['service_code'] == 'W7061' ? 'selected' : ''; ?>>W7061 - Respite Care</option>
                        <option value="W7060" <?php echo $claim['service_code'] == 'W7060' ? 'selected' : ''; ?>>W7060 - Family Consultation</option>
                        <option value="W7069" <?php echo $claim['service_code'] == 'W7069' ? 'selected' : ''; ?>>W7069 - Adult Life Planning</option>
                        <option value="W7235" <?php echo $claim['service_code'] == 'W7235' ? 'selected' : ''; ?>>W7235 - Environmental Accessibility</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label>Units</label>
                    <input type="number" class="form-control" name="units" 
                           value="<?php echo $claim['units']; ?>" step="0.25" min="0">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label>Rate</label>
                    <input type="number" class="form-control" name="rate" 
                           value="<?php echo $claim['rate']; ?>" step="0.01" min="0">
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label>Authorization Number</label>
            <input type="text" class="form-control" name="authorization_number" 
                   value="<?php echo htmlspecialchars($claim['authorization_number'] ?? ''); ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>
<?php endif; ?>

<script>
$('#editClaimForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'update_claim.php',
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if (response.success) {
                alert('Claim updated successfully');
                $('#claimDetailsModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }
    });
});
</script>