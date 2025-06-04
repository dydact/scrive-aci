<!-- Billing Performance Report -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-dollar-sign me-2"></i>
            Billing Performance Report
        </h5>
    </div>
    <div class="card-body">
        <!-- Summary Stats -->
        <?php if (!empty($reportData['summary'])): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center p-3 bg-primary text-white">
                    <h4><?= $reportData['summary']['total_claims'] ?? 0 ?></h4>
                    <small>Total Claims</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3 bg-info text-white">
                    <h4>$<?= number_format($reportData['summary']['total_billed'] ?? 0, 0) ?></h4>
                    <small>Total Billed</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3 bg-success text-white">
                    <h4>$<?= number_format($reportData['summary']['total_collected'] ?? 0, 0) ?></h4>
                    <small>Total Collected</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3 bg-warning text-dark">
                    <h4><?= number_format($reportData['summary']['collection_rate'] ?? 0, 1) ?>%</h4>
                    <small>Collection Rate</small>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Monthly Performance Table -->
        <h6 class="mb-3">Monthly Billing Performance</h6>
        <div class="table-responsive mb-4">
            <table class="table table-hover table-report">
                <thead class="table-light">
                    <tr>
                        <th>Month</th>
                        <th class="text-center">Total Claims</th>
                        <th class="text-end">Amount Billed</th>
                        <th class="text-center">Paid</th>
                        <th class="text-center">Denied</th>
                        <th class="text-center">Pending</th>
                        <th class="text-end">Collected</th>
                        <th class="text-center">Avg Days to Pay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['monthly_data'] as $month): ?>
                    <tr>
                        <td>
                            <strong><?= date('F Y', strtotime($month['month'] . '-01')) ?></strong>
                        </td>
                        <td class="text-center"><?= $month['total_claims'] ?></td>
                        <td class="text-end">$<?= number_format($month['total_billed'], 2) ?></td>
                        <td class="text-center">
                            <span class="badge bg-success"><?= $month['paid_claims'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger"><?= $month['denied_claims'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark"><?= $month['pending_claims'] ?></span>
                        </td>
                        <td class="text-end">
                            <strong>$<?= number_format($month['total_paid'], 2) ?></strong>
                        </td>
                        <td class="text-center">
                            <?= $month['avg_payment_days'] ? number_format($month['avg_payment_days'], 0) . ' days' : 'N/A' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th>TOTALS</th>
                        <th class="text-center">
                            <?= array_sum(array_column($reportData['monthly_data'], 'total_claims')) ?>
                        </th>
                        <th class="text-end">
                            $<?= number_format(array_sum(array_column($reportData['monthly_data'], 'total_billed')), 2) ?>
                        </th>
                        <th class="text-center">
                            <?= array_sum(array_column($reportData['monthly_data'], 'paid_claims')) ?>
                        </th>
                        <th class="text-center">
                            <?= array_sum(array_column($reportData['monthly_data'], 'denied_claims')) ?>
                        </th>
                        <th class="text-center">
                            <?= array_sum(array_column($reportData['monthly_data'], 'pending_claims')) ?>
                        </th>
                        <th class="text-end">
                            $<?= number_format(array_sum(array_column($reportData['monthly_data'], 'total_paid')), 2) ?>
                        </th>
                        <th class="text-center">-</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row">
            <!-- Denial Reasons -->
            <div class="col-md-6">
                <h6 class="mb-3">Top Denial Reasons</h6>
                <?php if (!empty($reportData['denial_reasons'])): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Reason</th>
                                <th class="text-center">Count</th>
                                <th class="text-center">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalDenials = array_sum(array_column($reportData['denial_reasons'], 'count'));
                            foreach ($reportData['denial_reasons'] as $reason): 
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($reason['denial_reason']) ?></td>
                                <td class="text-center"><?= $reason['count'] ?></td>
                                <td class="text-center">
                                    <?= number_format(($reason['count'] / $totalDenials) * 100, 1) ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No denial data available for this period.</p>
                <?php endif; ?>
            </div>

            <!-- Revenue Trend Chart -->
            <div class="col-md-6">
                <h6 class="mb-3">Revenue Trend</h6>
                <div class="chart-container">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Collection Performance Chart -->
        <div class="mt-4">
            <h6 class="mb-3">Billing Status Distribution</h6>
            <div class="chart-container" style="height: 250px;">
                <canvas id="billingStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue Trend Chart
const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
new Chart(revenueTrendCtx, {
    type: 'line',
    data: {
        labels: [<?php echo "'" . implode("','", array_map(function($m) { 
            return date('M Y', strtotime($m['month'] . '-01')); 
        }, $reportData['monthly_data'])) . "'"; ?>],
        datasets: [{
            label: 'Billed',
            data: [<?php echo implode(',', array_column($reportData['monthly_data'], 'total_billed')); ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4
        }, {
            label: 'Collected',
            data: [<?php echo implode(',', array_column($reportData['monthly_data'], 'total_paid')); ?>],
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Billing Status Distribution Chart
const totalPaid = <?= array_sum(array_column($reportData['monthly_data'], 'paid_claims')) ?>;
const totalDenied = <?= array_sum(array_column($reportData['monthly_data'], 'denied_claims')) ?>;
const totalPending = <?= array_sum(array_column($reportData['monthly_data'], 'pending_claims')) ?>;

const billingStatusCtx = document.getElementById('billingStatusChart').getContext('2d');
new Chart(billingStatusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Paid', 'Denied', 'Pending'],
        datasets: [{
            data: [totalPaid, totalDenied, totalPending],
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 206, 86, 0.8)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
</script>