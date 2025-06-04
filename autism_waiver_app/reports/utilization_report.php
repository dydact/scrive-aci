<!-- Service Utilization Report -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-chart-pie me-2"></i>
            Client Service Utilization Report
        </h5>
    </div>
    <div class="card-body">
        <!-- Summary Stats -->
        <?php if (!empty($reportData['summary'])): ?>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card text-center p-3 bg-primary text-white">
                    <h4><?= $reportData['summary']['total_authorizations'] ?? 0 ?></h4>
                    <small>Active Authorizations</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center p-3 bg-info text-white">
                    <h4><?= number_format($reportData['summary']['total_authorized_hours'] ?? 0, 0) ?></h4>
                    <small>Total Authorized Hours</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center p-3 bg-success text-white">
                    <h4><?= number_format($reportData['summary']['avg_utilization'] ?? 0, 1) ?>%</h4>
                    <small>Average Utilization</small>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Utilization Alerts -->
        <?php 
        $highUtilization = array_filter($reportData['data'], function($row) {
            return $row['utilization_percent'] >= 80;
        });
        $lowUtilization = array_filter($reportData['data'], function($row) {
            return $row['utilization_percent'] < 50 && $row['utilization_percent'] > 0;
        });
        ?>
        
        <?php if (!empty($highUtilization)): ?>
        <div class="alert alert-warning-custom alert-card mb-3">
            <h6 class="alert-heading">
                <i class="fas fa-exclamation-triangle me-2"></i>
                High Utilization Alerts (≥80%)
            </h6>
            <p class="mb-0 small">
                <?= count($highUtilization) ?> authorization(s) approaching limit. Consider renewal requests.
            </p>
        </div>
        <?php endif; ?>

        <?php if (!empty($lowUtilization)): ?>
        <div class="alert alert-info alert-card mb-3">
            <h6 class="alert-heading">
                <i class="fas fa-info-circle me-2"></i>
                Low Utilization Notice (&lt;50%)
            </h6>
            <p class="mb-0 small">
                <?= count($lowUtilization) ?> authorization(s) showing low utilization. Review service delivery.
            </p>
        </div>
        <?php endif; ?>

        <!-- Detailed Table -->
        <div class="table-responsive">
            <table class="table table-hover table-report">
                <thead class="table-light">
                    <tr>
                        <th>Client Name</th>
                        <th>MA Number</th>
                        <th>Service</th>
                        <th class="text-center">Auth Period</th>
                        <th class="text-center">Authorized</th>
                        <th class="text-center">Used</th>
                        <th class="text-center">Remaining</th>
                        <th class="text-center">Utilization</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['data'] as $row): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($row['ma_number']) ?></td>
                        <td><?= htmlspecialchars($row['service_name']) ?></td>
                        <td class="text-center small">
                            <?= date('m/d/y', strtotime($row['auth_start'])) ?> - 
                            <?= date('m/d/y', strtotime($row['auth_end'])) ?>
                        </td>
                        <td class="text-center"><?= number_format($row['authorized_hours'], 0) ?></td>
                        <td class="text-center"><?= number_format($row['hours_used'], 1) ?></td>
                        <td class="text-center"><?= number_format($row['hours_remaining'], 1) ?></td>
                        <td class="text-center">
                            <?php 
                            $utilPercent = $row['utilization_percent'];
                            $progressClass = $utilPercent >= 90 ? 'bg-danger' : 
                                           ($utilPercent >= 75 ? 'bg-warning' : 
                                           ($utilPercent >= 50 ? 'bg-info' : 'bg-success'));
                            ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar <?= $progressClass ?>" 
                                     role="progressbar" 
                                     style="width: <?= $utilPercent ?>%">
                                    <?= $utilPercent ?>%
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php
                            $daysUntilExpiry = (strtotime($row['auth_end']) - time()) / 86400;
                            if ($daysUntilExpiry < 0) {
                                echo '<span class="badge bg-danger">Expired</span>';
                            } elseif ($daysUntilExpiry <= 30) {
                                echo '<span class="badge bg-warning text-dark">Expiring Soon</span>';
                            } elseif ($utilPercent >= 90) {
                                echo '<span class="badge bg-danger">Near Limit</span>';
                            } else {
                                echo '<span class="badge bg-success">Active</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Utilization by Service Chart -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <canvas id="utilizationByServiceChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <canvas id="utilizationDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Group data by service type for chart
const serviceData = {};
<?php foreach ($reportData['data'] as $row): ?>
    if (!serviceData['<?= $row['service_name'] ?>']) {
        serviceData['<?= $row['service_name'] ?>'] = {
            authorized: 0,
            used: 0
        };
    }
    serviceData['<?= $row['service_name'] ?>'].authorized += <?= $row['authorized_hours'] ?>;
    serviceData['<?= $row['service_name'] ?>'].used += <?= $row['hours_used'] ?>;
<?php endforeach; ?>

// Utilization by Service Type Chart
const utilizationCtx = document.getElementById('utilizationByServiceChart').getContext('2d');
new Chart(utilizationCtx, {
    type: 'bar',
    data: {
        labels: Object.keys(serviceData),
        datasets: [{
            label: 'Authorized Hours',
            data: Object.values(serviceData).map(d => d.authorized),
            backgroundColor: 'rgba(102, 126, 234, 0.5)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 1
        }, {
            label: 'Used Hours',
            data: Object.values(serviceData).map(d => d.used),
            backgroundColor: 'rgba(255, 99, 132, 0.5)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'Utilization by Service Type'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Hours'
                }
            }
        }
    }
});

// Utilization Distribution Chart
const distributionData = {
    '0-25%': 0,
    '26-50%': 0,
    '51-75%': 0,
    '76-90%': 0,
    '91-100%': 0,
    'Over 100%': 0
};

<?php foreach ($reportData['data'] as $row): ?>
    const util = <?= $row['utilization_percent'] ?>;
    if (util <= 25) distributionData['0-25%']++;
    else if (util <= 50) distributionData['26-50%']++;
    else if (util <= 75) distributionData['51-75%']++;
    else if (util <= 90) distributionData['76-90%']++;
    else if (util <= 100) distributionData['91-100%']++;
    else distributionData['Over 100%']++;
<?php endforeach; ?>

const distributionCtx = document.getElementById('utilizationDistributionChart').getContext('2d');
new Chart(distributionCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(distributionData),
        datasets: [{
            data: Object.values(distributionData),
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(153, 102, 255, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'Utilization Distribution'
            },
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>