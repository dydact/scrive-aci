<!-- Staff Productivity Report -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-user-clock me-2"></i>
            Staff Productivity Report
        </h5>
    </div>
    <div class="card-body">
        <!-- Summary Stats -->
        <?php if (!empty($reportData['summary'])): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center p-3 bg-primary text-white">
                    <h4><?= $reportData['summary']['active_staff'] ?? 0 ?></h4>
                    <small>Active Staff</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3 bg-success text-white">
                    <h4><?= $reportData['summary']['clients_served'] ?? 0 ?></h4>
                    <small>Clients Served</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3 bg-info text-white">
                    <h4><?= number_format($reportData['summary']['total_hours'] ?? 0, 1) ?></h4>
                    <small>Total Hours</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center p-3 bg-warning text-dark">
                    <h4><?= number_format($reportData['summary']['avg_session_hours'] ?? 0, 1) ?></h4>
                    <small>Avg Session Hours</small>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detailed Table -->
        <div class="table-responsive">
            <table class="table table-hover table-report">
                <thead class="table-light">
                    <tr>
                        <th>Employee ID</th>
                        <th>Staff Name</th>
                        <th class="text-center">Sessions</th>
                        <th class="text-center">Clients</th>
                        <th class="text-center">Total Hours</th>
                        <th class="text-center">Days Worked</th>
                        <th class="text-center">Approved</th>
                        <th class="text-center">Pending</th>
                        <th class="text-center">Productivity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['data'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['employee_id'] ?? 'N/A') ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?></strong>
                        </td>
                        <td class="text-center"><?= $row['total_sessions'] ?></td>
                        <td class="text-center"><?= $row['clients_served'] ?></td>
                        <td class="text-center"><?= number_format($row['total_hours'], 1) ?></td>
                        <td class="text-center"><?= $row['days_worked'] ?></td>
                        <td class="text-center">
                            <span class="badge bg-success"><?= $row['approved_sessions'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark"><?= $row['pending_sessions'] ?></span>
                        </td>
                        <td class="text-center">
                            <?php 
                            $productivity = $row['days_worked'] > 0 ? ($row['total_hours'] / $row['days_worked']) : 0;
                            $badgeClass = $productivity >= 6 ? 'bg-success' : ($productivity >= 4 ? 'bg-warning text-dark' : 'bg-danger');
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= number_format($productivity, 1) ?> hrs/day
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="2">TOTALS</th>
                        <th class="text-center">
                            <?= array_sum(array_column($reportData['data'], 'total_sessions')) ?>
                        </th>
                        <th class="text-center">-</th>
                        <th class="text-center">
                            <?= number_format(array_sum(array_column($reportData['data'], 'total_hours')), 1) ?>
                        </th>
                        <th class="text-center">-</th>
                        <th class="text-center">
                            <?= array_sum(array_column($reportData['data'], 'approved_sessions')) ?>
                        </th>
                        <th class="text-center">
                            <?= array_sum(array_column($reportData['data'], 'pending_sessions')) ?>
                        </th>
                        <th class="text-center">-</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Productivity Chart -->
        <div class="chart-container mt-4">
            <canvas id="productivityChart"></canvas>
        </div>
    </div>
</div>

<script>
// Productivity Chart
const productivityCtx = document.getElementById('productivityChart').getContext('2d');
const productivityChart = new Chart(productivityCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo "'" . implode("','", array_map(function($r) { 
            return $r['first_name'] . ' ' . $r['last_name']; 
        }, array_slice($reportData['data'], 0, 10))) . "'"; ?>],
        datasets: [{
            label: 'Total Hours',
            data: [<?php echo implode(',', array_map(function($r) { 
                return $r['total_hours']; 
            }, array_slice($reportData['data'], 0, 10))); ?>],
            backgroundColor: 'rgba(102, 126, 234, 0.8)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'Top 10 Staff by Total Hours'
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
</script>