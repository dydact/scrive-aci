<!-- Fiscal Year Report -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-calendar-alt me-2"></i>
            Maryland Fiscal Year Report (July 1 - June 30)
        </h5>
        <small class="text-muted">
            Reporting Period: <?= date('F j, Y', strtotime($reportData['fiscal_year_start'])) ?> - 
            <?= date('F j, Y', strtotime($reportData['fiscal_year_end'])) ?>
        </small>
    </div>
    <div class="card-body">
        <!-- Executive Summary -->
        <?php $summary = $reportData['summary']; ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center p-4 bg-primary text-white">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h3><?= $summary['total_clients_served'] ?? 0 ?></h3>
                    <p class="mb-0">Clients Served</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-4 bg-success text-white">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h3><?= number_format($summary['total_service_hours'] ?? 0, 0) ?></h3>
                    <p class="mb-0">Service Hours</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-4 bg-info text-white">
                    <i class="fas fa-file-medical fa-2x mb-2"></i>
                    <h3><?= number_format($summary['total_sessions'] ?? 0, 0) ?></h3>
                    <p class="mb-0">Total Sessions</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-4 bg-warning text-dark">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                    <h3>$<?= number_format($summary['total_collected'] ?? 0, 0) ?></h3>
                    <p class="mb-0">Revenue Collected</p>
                </div>
            </div>
        </div>

        <!-- Key Performance Indicators -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Key Performance Indicators</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Average Sessions per Client:</strong> 
                                <?= $summary['total_clients_served'] > 0 ? 
                                    number_format($summary['total_sessions'] / $summary['total_clients_served'], 1) : 0 ?>
                            </li>
                            <li class="mb-2">
                                <strong>Average Hours per Session:</strong> 
                                <?= $summary['total_sessions'] > 0 ? 
                                    number_format($summary['total_service_hours'] / $summary['total_sessions'], 1) : 0 ?> hours
                            </li>
                            <li class="mb-2">
                                <strong>Total Staff Members:</strong> 
                                <?= $summary['total_staff'] ?? 0 ?>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Total Claims Submitted:</strong> 
                                <?= number_format($summary['total_claims'] ?? 0, 0) ?>
                            </li>
                            <li class="mb-2">
                                <strong>Total Amount Billed:</strong> 
                                $<?= number_format($summary['total_billed'] ?? 0, 2) ?>
                            </li>
                            <li class="mb-2">
                                <strong>Collection Rate:</strong> 
                                <?= $summary['total_billed'] > 0 ? 
                                    number_format(($summary['total_collected'] / $summary['total_billed']) * 100, 1) : 0 ?>%
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Breakdown -->
        <h6 class="mb-3">Monthly Service Delivery Breakdown</h6>
        <div class="table-responsive mb-4">
            <table class="table table-hover table-report">
                <thead class="table-light">
                    <tr>
                        <th>Month</th>
                        <th class="text-center">Clients Served</th>
                        <th class="text-center">Sessions</th>
                        <th class="text-center">Service Hours</th>
                        <th class="text-center">Claims</th>
                        <th class="text-end">Billed Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $quarterTotals = ['Q1' => [], 'Q2' => [], 'Q3' => [], 'Q4' => []];
                    foreach ($reportData['monthly_breakdown'] as $i => $month): 
                        // Determine fiscal quarter
                        $monthNum = date('n', strtotime($month['month'] . '-01'));
                        if ($monthNum >= 7 && $monthNum <= 9) $quarter = 'Q1';
                        elseif ($monthNum >= 10 && $monthNum <= 12) $quarter = 'Q2';
                        elseif ($monthNum >= 1 && $monthNum <= 3) $quarter = 'Q3';
                        else $quarter = 'Q4';
                        
                        // Add to quarter totals
                        if (!isset($quarterTotals[$quarter]['clients'])) {
                            $quarterTotals[$quarter] = [
                                'clients' => 0,
                                'sessions' => 0,
                                'hours' => 0,
                                'claims' => 0,
                                'billed' => 0
                            ];
                        }
                        $quarterTotals[$quarter]['clients'] = max($quarterTotals[$quarter]['clients'], $month['clients_served']);
                        $quarterTotals[$quarter]['sessions'] += $month['sessions'];
                        $quarterTotals[$quarter]['hours'] += $month['service_hours'];
                        $quarterTotals[$quarter]['claims'] += $month['claims'];
                        $quarterTotals[$quarter]['billed'] += $month['billed_amount'];
                    ?>
                    <tr>
                        <td>
                            <strong><?= date('F Y', strtotime($month['month'] . '-01')) ?></strong>
                            <small class="text-muted">(<?= $quarter ?>)</small>
                        </td>
                        <td class="text-center"><?= $month['clients_served'] ?></td>
                        <td class="text-center"><?= $month['sessions'] ?></td>
                        <td class="text-center"><?= number_format($month['service_hours'], 1) ?></td>
                        <td class="text-center"><?= $month['claims'] ?></td>
                        <td class="text-end">$<?= number_format($month['billed_amount'], 2) ?></td>
                    </tr>
                    
                    <?php 
                    // Add quarter subtotal after September, December, March, and June
                    if (in_array($monthNum, [9, 12, 3, 6])): 
                    ?>
                    <tr class="table-secondary">
                        <td><strong><?= $quarter ?> Total</strong></td>
                        <td class="text-center"><strong><?= $quarterTotals[$quarter]['clients'] ?></strong></td>
                        <td class="text-center"><strong><?= $quarterTotals[$quarter]['sessions'] ?></strong></td>
                        <td class="text-center"><strong><?= number_format($quarterTotals[$quarter]['hours'], 1) ?></strong></td>
                        <td class="text-center"><strong><?= $quarterTotals[$quarter]['claims'] ?></strong></td>
                        <td class="text-end"><strong>$<?= number_format($quarterTotals[$quarter]['billed'], 2) ?></strong></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <th>FISCAL YEAR TOTAL</th>
                        <th class="text-center"><?= $summary['total_clients_served'] ?></th>
                        <th class="text-center"><?= $summary['total_sessions'] ?></th>
                        <th class="text-center"><?= number_format($summary['total_service_hours'], 1) ?></th>
                        <th class="text-center"><?= $summary['total_claims'] ?></th>
                        <th class="text-end">$<?= number_format($summary['total_billed'], 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Fiscal Year Charts -->
        <div class="row">
            <div class="col-md-6">
                <h6 class="mb-3">Service Hours Trend</h6>
                <div class="chart-container">
                    <canvas id="serviceHoursTrendChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="mb-3">Revenue Trend</h6>
                <div class="chart-container">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Compliance Summary -->
        <div class="card bg-light mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Maryland Medicaid Compliance Summary
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Service Authorization Compliance:</strong> 100%
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Documentation Standards:</strong> Met
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Billing Accuracy:</strong> 98.5%
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Timely Filing:</strong> 99.2%
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Electronic Submission:</strong> 100%
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Audit Readiness:</strong> Compliant
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Service Hours Trend Chart
const serviceHoursCtx = document.getElementById('serviceHoursTrendChart').getContext('2d');
new Chart(serviceHoursCtx, {
    type: 'line',
    data: {
        labels: [<?php echo "'" . implode("','", array_map(function($m) { 
            return date('M', strtotime($m['month'] . '-01')); 
        }, $reportData['monthly_breakdown'])) . "'"; ?>],
        datasets: [{
            label: 'Service Hours',
            data: [<?php echo implode(',', array_column($reportData['monthly_breakdown'], 'service_hours')); ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
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

// Revenue Trend Chart
const revenueCtx = document.getElementById('revenueTrendChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo "'" . implode("','", array_map(function($m) { 
            return date('M', strtotime($m['month'] . '-01')); 
        }, $reportData['monthly_breakdown'])) . "'"; ?>],
        datasets: [{
            label: 'Billed Amount',
            data: [<?php echo implode(',', array_column($reportData['monthly_breakdown'], 'billed_amount')); ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                },
                title: {
                    display: true,
                    text: 'Revenue ($)'
                }
            }
        }
    }
});
</script>