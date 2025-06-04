<!-- Authorization Alerts Report -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Authorization Alerts & Expiration Management
        </h5>
    </div>
    <div class="card-body">
        <!-- Alert Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="alert alert-danger-custom alert-card">
                    <h4 class="alert-heading">
                        <i class="fas fa-calendar-times me-2"></i>
                        <?= count($reportData['expiring']) ?>
                    </h4>
                    <p class="mb-0">Authorizations Expiring Within 30 Days</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-warning-custom alert-card">
                    <h4 class="alert-heading">
                        <i class="fas fa-chart-line me-2"></i>
                        <?= count($reportData['high_utilization']) ?>
                    </h4>
                    <p class="mb-0">High Utilization Authorizations (≥80%)</p>
                </div>
            </div>
        </div>

        <!-- Expiring Authorizations -->
        <h6 class="mb-3">
            <i class="fas fa-clock text-danger me-2"></i>
            Authorizations Expiring Soon
        </h6>
        <?php if (!empty($reportData['expiring'])): ?>
        <div class="table-responsive mb-4">
            <table class="table table-hover table-report">
                <thead class="table-light">
                    <tr>
                        <th>Client Name</th>
                        <th>MA Number</th>
                        <th>Service</th>
                        <th>Auth Number</th>
                        <th class="text-center">Expires In</th>
                        <th class="text-center">Hours Remaining</th>
                        <th class="text-center">Action Required</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['expiring'] as $auth): ?>
                    <tr class="<?= $auth['days_until_expiry'] <= 7 ? 'table-danger' : ($auth['days_until_expiry'] <= 14 ? 'table-warning' : '') ?>">
                        <td>
                            <strong><?= htmlspecialchars($auth['last_name'] . ', ' . $auth['first_name']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($auth['ma_number']) ?></td>
                        <td><?= htmlspecialchars($auth['service_name']) ?></td>
                        <td><?= htmlspecialchars($auth['auth_number']) ?></td>
                        <td class="text-center">
                            <?php if ($auth['days_until_expiry'] <= 7): ?>
                                <span class="badge bg-danger">
                                    <?= $auth['days_until_expiry'] ?> days
                                </span>
                            <?php elseif ($auth['days_until_expiry'] <= 14): ?>
                                <span class="badge bg-warning text-dark">
                                    <?= $auth['days_until_expiry'] ?> days
                                </span>
                            <?php else: ?>
                                <span class="badge bg-info">
                                    <?= $auth['days_until_expiry'] ?> days
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?= number_format($auth['hours_remaining'], 1) ?> / <?= $auth['authorized_hours'] ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-primary" onclick="initiateRenewal(<?= $auth['id'] ?>)">
                                <i class="fas fa-redo me-1"></i>
                                Initiate Renewal
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle me-2"></i>
            No authorizations expiring within the next 30 days.
        </div>
        <?php endif; ?>

        <!-- High Utilization Authorizations -->
        <h6 class="mb-3">
            <i class="fas fa-chart-line text-warning me-2"></i>
            High Utilization Authorizations
        </h6>
        <?php if (!empty($reportData['high_utilization'])): ?>
        <div class="table-responsive mb-4">
            <table class="table table-hover table-report">
                <thead class="table-light">
                    <tr>
                        <th>Client Name</th>
                        <th>MA Number</th>
                        <th>Service</th>
                        <th>Auth Number</th>
                        <th class="text-center">Utilization</th>
                        <th class="text-center">Hours Used</th>
                        <th class="text-center">Days Remaining</th>
                        <th class="text-center">Projected Depletion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['high_utilization'] as $auth): ?>
                    <?php
                    // Calculate projected depletion
                    $daysElapsed = max(1, (time() - strtotime($auth['start_date'])) / 86400);
                    $dailyRate = $auth['hours_used'] / $daysElapsed;
                    $hoursRemaining = $auth['authorized_hours'] - $auth['hours_used'];
                    $daysToDepletion = $dailyRate > 0 ? floor($hoursRemaining / $dailyRate) : 999;
                    ?>
                    <tr class="<?= $auth['utilization_percent'] >= 95 ? 'table-danger' : ($auth['utilization_percent'] >= 90 ? 'table-warning' : '') ?>">
                        <td>
                            <strong><?= htmlspecialchars($auth['last_name'] . ', ' . $auth['first_name']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($auth['ma_number']) ?></td>
                        <td><?= htmlspecialchars($auth['service_name']) ?></td>
                        <td><?= htmlspecialchars($auth['auth_number']) ?></td>
                        <td class="text-center">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar <?= $auth['utilization_percent'] >= 95 ? 'bg-danger' : ($auth['utilization_percent'] >= 90 ? 'bg-warning' : 'bg-info') ?>" 
                                     role="progressbar" 
                                     style="width: <?= $auth['utilization_percent'] ?>%">
                                    <strong><?= number_format($auth['utilization_percent'], 1) ?>%</strong>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <?= number_format($auth['hours_used'], 1) ?> / <?= $auth['authorized_hours'] ?>
                        </td>
                        <td class="text-center">
                            <?= $auth['days_remaining'] ?> days
                        </td>
                        <td class="text-center">
                            <?php if ($daysToDepletion < $auth['days_remaining'] && $daysToDepletion < 30): ?>
                                <span class="badge bg-danger">
                                    ~<?= $daysToDepletion ?> days
                                </span>
                            <?php elseif ($daysToDepletion < $auth['days_remaining']): ?>
                                <span class="badge bg-warning text-dark">
                                    ~<?= $daysToDepletion ?> days
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success">
                                    On Track
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            No authorizations currently at high utilization levels.
        </div>
        <?php endif; ?>

        <!-- Action Summary -->
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-tasks me-2"></i>
                    Recommended Actions
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-danger">Immediate Actions Required:</h6>
                        <ul>
                            <?php 
                            $criticalExpiring = array_filter($reportData['expiring'], function($a) { 
                                return $a['days_until_expiry'] <= 7; 
                            });
                            $criticalUtilization = array_filter($reportData['high_utilization'], function($a) { 
                                return $a['utilization_percent'] >= 95; 
                            });
                            ?>
                            <?php if (count($criticalExpiring) > 0): ?>
                            <li>Submit renewal requests for <?= count($criticalExpiring) ?> authorization(s) expiring within 7 days</li>
                            <?php endif; ?>
                            <?php if (count($criticalUtilization) > 0): ?>
                            <li>Review service delivery for <?= count($criticalUtilization) ?> authorization(s) at 95%+ utilization</li>
                            <?php endif; ?>
                            <?php if (count($criticalExpiring) == 0 && count($criticalUtilization) == 0): ?>
                            <li class="text-muted">No immediate actions required</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-warning">Upcoming Actions (This Week):</h6>
                        <ul>
                            <?php 
                            $weekExpiring = array_filter($reportData['expiring'], function($a) { 
                                return $a['days_until_expiry'] > 7 && $a['days_until_expiry'] <= 14; 
                            });
                            ?>
                            <?php if (count($weekExpiring) > 0): ?>
                            <li>Prepare renewal documentation for <?= count($weekExpiring) ?> authorization(s)</li>
                            <?php endif; ?>
                            <li>Contact case managers for high utilization clients</li>
                            <li>Schedule authorization review meeting</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Renewal Modal -->
<div class="modal fade" id="renewalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Initiate Authorization Renewal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This feature will guide you through the Maryland Medicaid authorization renewal process.</p>
                <div class="alert alert-info">
                    <h6>Renewal Checklist:</h6>
                    <ul class="mb-0">
                        <li>Current utilization report</li>
                        <li>Updated treatment plan</li>
                        <li>Progress notes from last 90 days</li>
                        <li>Physician certification (if required)</li>
                        <li>Client/guardian consent form</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="proceedWithRenewal()">
                    <i class="fas fa-arrow-right me-2"></i>
                    Proceed with Renewal
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function initiateRenewal(authId) {
    $('#renewalModal').modal('show');
    // Store the auth ID for later use
    window.currentRenewalAuthId = authId;
}

function proceedWithRenewal() {
    alert('Authorization renewal process would be initiated here. This would generate the necessary forms and documentation for Maryland Medicaid submission.');
    $('#renewalModal').modal('hide');
}

// Auto-refresh this page every 5 minutes since it shows real-time alerts
setTimeout(function() {
    location.reload();
}, 300000);
</script>