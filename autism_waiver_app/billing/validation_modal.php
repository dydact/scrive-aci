<!-- Validation Modal -->
<div class="modal fade" id="validationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Validate Claims</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    This will validate all pending claims against Maryland Medicaid requirements.
                </div>
                
                <h6>Validation Checks:</h6>
                <ul class="list-group mb-3">
                    <li class="list-group-item">
                        <i class="bi bi-check-circle text-primary"></i> Required fields (Medicaid ID, DOB, NPI, etc.)
                    </li>
                    <li class="list-group-item">
                        <i class="bi bi-check-circle text-primary"></i> Medicaid ID format (9 alphanumeric characters)
                    </li>
                    <li class="list-group-item">
                        <i class="bi bi-check-circle text-primary"></i> Valid autism waiver service codes
                    </li>
                    <li class="list-group-item">
                        <i class="bi bi-check-circle text-primary"></i> Timely filing limits (95 days)
                    </li>
                    <li class="list-group-item">
                        <i class="bi bi-check-circle text-primary"></i> Authorization requirements
                    </li>
                    <li class="list-group-item">
                        <i class="bi bi-check-circle text-primary"></i> Client age eligibility
                    </li>
                    <li class="list-group-item">
                        <i class="bi bi-check-circle text-primary"></i> Duplicate claim detection
                    </li>
                </ul>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="fixErrors" checked>
                    <label class="form-check-label" for="fixErrors">
                        Attempt to fix common errors automatically
                    </label>
                </div>
                
                <div id="validationProgress" style="display: none;">
                    <div class="progress mb-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%"></div>
                    </div>
                    <div id="validationProgressText" class="text-center text-muted"></div>
                </div>
                
                <div id="validationResults" style="display: none;">
                    <h6>Validation Results:</h6>
                    <div id="validationResultsContent"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="runValidation()">
                    <i class="bi bi-check-circle"></i> Run Validation
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showValidationModal() {
    $('#validationModal').modal('show');
    $('#validationProgress').hide();
    $('#validationResults').hide();
}

function runValidation() {
    const fixErrors = $('#fixErrors').is(':checked');
    
    $('#validationProgress').show();
    $('#validationResults').hide();
    $('.progress-bar').css('width', '0%');
    
    $.ajax({
        url: 'validate_claim.php',
        method: 'POST',
        data: {
            batch: true,
            fix_errors: fixErrors
        },
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    var percentComplete = evt.loaded / evt.total * 100;
                    $('.progress-bar').css('width', percentComplete + '%');
                    $('#validationProgressText').text('Validating claims... ' + Math.round(percentComplete) + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            $('#validationProgress').hide();
            $('#validationResults').show();
            
            let html = '<div class="validation-summary mb-3">';
            html += '<div class="alert alert-' + (response.success ? 'success' : 'warning') + '">';
            html += response.message;
            html += '</div>';
            
            if (response.results && response.results.length > 0) {
                html += '<h6>Detailed Results:</h6>';
                html += '<div class="accordion" id="validationAccordion">';
                
                response.results.forEach((result, index) => {
                    const hasErrors = result.errors && result.errors.length > 0;
                    const hasWarnings = result.warnings && result.warnings.length > 0;
                    
                    html += '<div class="accordion-item">';
                    html += '<h2 class="accordion-header" id="heading' + index + '">';
                    html += '<button class="accordion-button ' + (index > 0 ? 'collapsed' : '') + '" type="button" ';
                    html += 'data-bs-toggle="collapse" data-bs-target="#collapse' + index + '">';
                    
                    if (result.valid) {
                        html += '<i class="bi bi-check-circle text-success me-2"></i>';
                    } else {
                        html += '<i class="bi bi-x-circle text-danger me-2"></i>';
                    }
                    
                    html += 'Claim #' + result.claim_number;
                    
                    if (hasErrors) {
                        html += ' <span class="badge bg-danger ms-2">' + result.errors.length + ' errors</span>';
                    }
                    if (hasWarnings) {
                        html += ' <span class="badge bg-warning ms-2">' + result.warnings.length + ' warnings</span>';
                    }
                    
                    html += '</button></h2>';
                    html += '<div id="collapse' + index + '" class="accordion-collapse collapse ' + (index === 0 ? 'show' : '') + '">';
                    html += '<div class="accordion-body">';
                    
                    if (hasErrors) {
                        html += '<h6 class="text-danger">Errors:</h6><ul>';
                        result.errors.forEach(error => {
                            html += '<li>' + error + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    if (hasWarnings) {
                        html += '<h6 class="text-warning">Warnings:</h6><ul>';
                        result.warnings.forEach(warning => {
                            html += '<li>' + warning + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    if (!hasErrors && !hasWarnings) {
                        html += '<p class="text-success">✓ All validation checks passed</p>';
                    }
                    
                    html += '</div></div></div>';
                });
                
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#validationResultsContent').html(html);
            
            // Refresh the main page after validation
            setTimeout(() => {
                location.reload();
            }, 5000);
        },
        error: function(xhr, status, error) {
            $('#validationProgress').hide();
            $('#validationResults').show();
            $('#validationResultsContent').html(
                '<div class="alert alert-danger">Validation failed: ' + error + '</div>'
            );
        }
    });
}
</script>