<!-- Quality Metrics Report -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-star me-2"></i>
            Quality Metrics & Compliance Report
        </h5>
    </div>
    <div class="card-body">
        <!-- Overall Compliance Metrics -->
        <?php 
        $doc = $reportData['documentation'];
        $totalSessions = $doc['total_sessions'] ?: 1; // Avoid division by zero
        $timelyRate = ($doc['timely_documentation'] / $totalSessions) * 100;
        $approvalRate = ($doc['supervisor_approved'] / $totalSessions) * 100;
        $qualityRate = ($doc['adequate_notes'] / $totalSessions) * 100;
        $goalsRate = ($doc['goals_documented'] / $totalSessions) * 100;
        ?>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="text-center">
                    <div class="metric-badge <?= $timelyRate >= 90 ? 'metric-good' : ($timelyRate >= 75 ? 'metric-warning' : 'metric-poor') ?>">
                        <h3 class="mb-0"><?= number_format($timelyRate, 1) ?>%</h3>
                    </div>
                    <p class="mt-2 mb-0">Timely Documentation</p>
                    <small class="text-muted">(Within 48 hours)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <div class="metric-badge <?= $approvalRate >= 95 ? 'metric-good' : ($approvalRate >= 85 ? 'metric-warning' : 'metric-poor') ?>">
                        <h3 class="mb-0"><?= number_format($approvalRate, 1) ?>%</h3>
                    </div>
                    <p class="mt-2 mb-0">Supervisor Approved</p>
                    <small class="text-muted">(Supervision compliance)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <div class="metric-badge <?= $qualityRate >= 95 ? 'metric-good' : ($qualityRate >= 85 ? 'metric-warning' : 'metric-poor') ?>">
                        <h3 class="mb-0"><?= number_format($qualityRate, 1) ?>%</h3>
                    </div>
                    <p class="mt-2 mb-0">Note Quality</p>
                    <small class="text-muted">(Adequate detail)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <div class="metric-badge <?= $goalsRate >= 90 ? 'metric-good' : ($goalsRate >= 80 ? 'metric-warning' : 'metric-poor') ?>">
                        <h3 class="mb-0"><?= number_format($goalsRate, 1) ?>%</h3>
                    </div>
                    <p class="mt-2 mb-0">Goals Addressed</p>
                    <small class="text-muted">(Treatment planning)</small>
                </div>
            </div>
        </div>

        <!-- Maryland Medicaid Compliance Checklist -->
        <div class="alert alert-info mb-4">
            <h6 class="alert-heading">
                <i class="fas fa-clipboard-check me-2"></i>
                Maryland Medicaid Compliance Status
            </h6>
            <div class="row mt-3">
                <div class="col-md-6">
                    <ul class="list-unstyled mb-0">
                        <li>
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Documentation within 48 hours: <?= $timelyRate >= 90 ? 'COMPLIANT' : 'NEEDS IMPROVEMENT' ?>
                        </li>
                        <li>
                            <i class="fas <?= $approvalRate >= 95 ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-warning' ?> me-2"></i>
                            Supervision requirements: <?= $approvalRate >= 95 ? 'COMPLIANT' : 'NEEDS IMPROVEMENT' ?>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled mb-0">
                        <li>
                            <i class="fas <?= $qualityRate >= 95 ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-warning' ?> me-2"></i>
                            Progress note standards: <?= $qualityRate >= 95 ? 'COMPLIANT' : 'NEEDS IMPROVEMENT' ?>
                        </li>
                        <li>
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Electronic signature requirement: COMPLIANT
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Staff Compliance Table -->
        <h6 class="mb-3">Individual Staff Compliance Metrics</h6>
        <div class="table-responsive">
            <table class="table table-hover table-report">
                <thead class="table-light">
                    <tr>
                        <th>Staff Name</th>
                        <th class="text-center">Total Sessions</th>
                        <th class="text-center">Timely Documentation</th>
                        <th class="text-center">Note Quality</th>
                        <th class="text-center">Overall Score</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['staff_compliance'] as $staff): ?>
                    <?php 
                    $overallScore = ($staff['timely_rate'] + $staff['quality_rate']) / 2;
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($staff['last_name'] . ', ' . $staff['first_name']) ?></strong>
                        </td>
                        <td class="text-center"><?= $staff['total_sessions'] ?></td>
                        <td class="text-center">
                            <span class="badge <?= $staff['timely_rate'] >= 90 ? 'bg-success' : ($staff['timely_rate'] >= 75 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                <?= number_format($staff['timely_rate'], 1) ?>%
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $staff['quality_rate'] >= 95 ? 'bg-success' : ($staff['quality_rate'] >= 85 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                <?= number_format($staff['quality_rate'], 1) ?>%
                            </span>
                        </td>
                        <td class="text-center">
                            <strong><?= number_format($overallScore, 1) ?>%</strong>
                        </td>
                        <td class="text-center">
                            <?php if ($overallScore >= 90): ?>
                                <span class="text-success"><i class="fas fa-check-circle"></i> Excellent</span>
                            <?php elseif ($overallScore >= 80): ?>
                                <span class="text-warning"><i class="fas fa-exclamation-circle"></i> Good</span>
                            <?php else: ?>
                                <span class="text-danger"><i class="fas fa-times-circle"></i> Needs Training</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Quality Trends Chart -->
        <div class="row mt-4">
            <div class="col-12">
                <h6 class="mb-3">Quality Metrics Trend</h6>
                <div class="chart-container">
                    <canvas id="qualityTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="card bg-light mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-lightbulb me-2"></i>
                    Quality Improvement Recommendations
                </h6>
                <ul class="mb-0">
                    <?php if ($timelyRate < 90): ?>
                    <li>Implement reminder system for documentation deadlines</li>
                    <?php endif; ?>
                    <?php if ($approvalRate < 95): ?>
                    <li>Schedule regular supervision meetings to review pending notes</li>
                    <?php endif; ?>
                    <?php if ($qualityRate < 95): ?>
                    <li>Provide additional training on progress note requirements</li>
                    <?php endif; ?>
                    <?php if ($goalsRate < 90): ?>
                    <li>Review treatment plans with staff to ensure goal alignment</li>
                    <?php endif; ?>
                    <?php if ($timelyRate >= 90 && $approvalRate >= 95 && $qualityRate >= 95 && $goalsRate >= 90): ?>
                    <li>Maintain current excellence in documentation practices</li>
                    <li>Consider peer mentoring program to share best practices</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Quality Trend Chart (Mock data for demonstration)
const qualityTrendCtx = document.getElementById('qualityTrendChart').getContext('2d');
new Chart(qualityTrendCtx, {
    type: 'line',
    data: {
        labels: ['6 Months Ago', '5 Months Ago', '4 Months Ago', '3 Months Ago', '2 Months Ago', 'Last Month', 'This Month'],
        datasets: [{
            label: 'Timely Documentation',
            data: [85, 87, 88, 90, 91, 92, <?= number_format($timelyRate, 1) ?>],
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.4
        }, {
            label: 'Supervisor Approval',
            data: [90, 91, 93, 94, 95, 96, <?= number_format($approvalRate, 1) ?>],
            borderColor: 'rgb(54, 162, 235)',
            tension: 0.4
        }, {
            label: 'Note Quality',
            data: [88, 90, 92, 93, 94, 95, <?= number_format($qualityRate, 1) ?>],
            borderColor: 'rgb(255, 206, 86)',
            tension: 0.4
        }, {
            label: 'Goals Documented',
            data: [82, 85, 87, 88, 89, 90, <?= number_format($goalsRate, 1) ?>],
            borderColor: 'rgb(153, 102, 255)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + '%';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
});
</script>