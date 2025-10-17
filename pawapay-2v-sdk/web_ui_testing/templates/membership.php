<!-- Membership Plans Testing -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-crown"></i> Membership Plans Testing</h2>
            <span class="badge bg-info">Real PawaPay API Integration</span>
        </div>
    </div>
</div>

<!-- Information Alert -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle"></i> Testing Instructions</h5>
            <p class="mb-2">
                This page simulates <strong>Modesy marketplace membership plan purchases</strong> with real PawaPay API calls. 
                Use the test phone numbers provided for each operator to simulate different payment scenarios.
            </p>
            <ul class="mb-0">
                <li><strong>Success:</strong> Use success test numbers to complete payments</li>
                <li><strong>Failure:</strong> Use specific failure test numbers to test error handling</li>
                <li><strong>Fee Calculation:</strong> Fees are calculated in real-time based on Zambia operator structures</li>
            </ul>
        </div>
    </div>
</div>

<!-- Membership Plans Grid -->
<div class="row mb-4">
    <?php foreach ($membershipPlans as $planId => $plan): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100 <?= $planId === 'premium_vendor' ? 'border-primary' : '' ?>">
            <?php if ($planId === 'premium_vendor'): ?>
            <div class="card-header bg-primary text-white text-center">
                <small class="badge bg-warning text-dark">MOST POPULAR</small>
            </div>
            <?php endif; ?>
            
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="card-title"><?= htmlspecialchars($plan['name']) ?></h4>
                    <h3 class="text-primary mb-0">
                        ZMW <?= number_format($plan['price'], 2) ?>
                        <small class="text-muted fs-6">/ <?= $plan['duration'] ?></small>
                    </h3>
                </div>
                
                <p class="text-muted"><?= htmlspecialchars($plan['description']) ?></p>
                
                <ul class="list-unstyled mb-4">
                    <?php foreach ($plan['features'] as $feature): ?>
                    <li class="mb-1">
                        <i class="fas fa-check text-success me-2"></i>
                        <?= htmlspecialchars($feature) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="card-footer">
                <button class="btn btn-zambia w-100 select-plan-btn" 
                        data-plan-id="<?= $planId ?>" 
                        data-plan-name="<?= htmlspecialchars($plan['name']) ?>"
                        data-plan-price="<?= $plan['price'] ?>">
                    <i class="fas fa-crown"></i> Select Plan
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-credit-card"></i> Complete Membership Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <!-- Selected Plan Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="card-title">Selected Plan</h6>
                                <h5 id="selectedPlanName" class="text-primary mb-1"></h5>
                                <p class="text-muted mb-0" id="selectedPlanPrice"></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div id="pricingBreakdown" class="small text-muted">
                                    <!-- Fee breakdown will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Form -->
                <form id="membershipPaymentForm">
                    <input type="hidden" id="selectedPlanId" name="plan_id">
                    
                    <!-- Mobile Money Operator Selection -->
                    <div class="mb-4">
                        <label class="form-label">Select Mobile Money Operator</label>
                        <div class="row">
                            <?php foreach ($operators as $code => $operator): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card operator-card" data-operator="<?= $code ?>">
                                    <div class="card-body text-center py-3">
                                        <i class="fas fa-mobile-alt fa-2x text-primary mb-2"></i>
                                        <h6 class="card-title mb-1"><?= htmlspecialchars($operator['name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($operator['currency']) ?></small>
                                        
                                        <!-- Test Numbers Dropdown -->
                                        <div class="mt-2">
                                            <select class="form-select form-select-sm test-phone-select" data-operator="<?= $code ?>">
                                                <option value="">Select test scenario</option>
                                                <?php foreach ($operator['test_phones'] as $scenario => $phone): ?>
                                                <option value="<?= $phone ?>"><?= ucwords(str_replace('_', ' ', $scenario)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="operator" id="selectedOperator">
                    </div>
                    
                    <!-- Phone Number Input -->
                    <div class="mb-4">
                        <label class="form-label">Mobile Money Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">+260</span>
                            <input type="tel" class="form-control" name="phone_number" 
                                   placeholder="Enter phone number or select from test scenarios above" 
                                   pattern="[0-9]{9}" required>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> 
                            Use test numbers above for different payment scenarios, or enter your own Zambian number
                        </div>
                    </div>
                    
                    <!-- Fee Calculation Display -->
                    <div id="feeCalculationDisplay" class="mb-4" style="display: none;">
                        <div class="fee-breakdown p-3 rounded">
                            <h6><i class="fas fa-calculator"></i> Fee Breakdown</h6>
                            <div id="feeDetails"></div>
                        </div>
                    </div>
                    
                    <!-- Terms and Conditions -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label" for="agreeTerms">
                                I agree to the terms and conditions and understand this is a test transaction
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="submit" form="membershipPaymentForm" class="btn btn-zambia" id="processPaymentBtn">
                    <i class="fas fa-credit-card"></i> Process Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Processing Modal -->
<div class="modal fade" id="processingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5>Processing Payment...</h5>
                <p class="text-muted">Please wait while we process your membership payment with PawaPay</p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let selectedPlan = null;
    let selectedOperator = null;
    
    // Plan selection
    $('.select-plan-btn').on('click', function() {
        selectedPlan = {
            id: $(this).data('plan-id'),
            name: $(this).data('plan-name'),
            price: $(this).data('plan-price')
        };
        
        $('#selectedPlanName').text(selectedPlan.name);
        $('#selectedPlanPrice').text('ZMW ' + parseFloat(selectedPlan.price).toFixed(2));
        $('#selectedPlanId').val(selectedPlan.id);
        
        $('#paymentModal').modal('show');
    });
    
    // Operator selection
    $('.operator-card').on('click', function() {
        const operator = $(this).data('operator');
        selectedOperator = operator;
        
        $('.operator-card').removeClass('selected');
        $(this).addClass('selected');
        $('#selectedOperator').val(operator);
        
        // Calculate fees when operator is selected
        if (selectedPlan) {
            calculateMembershipFees();
        }
    });
    
    // Test phone number selection
    $('.test-phone-select').on('change', function() {
        const phoneNumber = $(this).val();
        const operator = $(this).data('operator');
        
        if (phoneNumber && operator === selectedOperator) {
            $('input[name="phone_number"]').val(phoneNumber);
        }
    });
    
    // Calculate membership fees
    function calculateMembershipFees() {
        if (!selectedPlan || !selectedOperator) return;
        
        calculateFees(selectedPlan.price, selectedOperator, function(response) {
            if (response.success) {
                const data = response.data;
                
                const feeHtml = `
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1"><strong>Plan Price:</strong></p>
                            <p class="mb-1"><strong>Operator Fee:</strong></p>
                            <p class="mb-1"><strong>PawaPay Fee:</strong></p>
                            <hr class="my-2">
                            <p class="mb-0 fs-6"><strong>Total to Pay:</strong></p>
                        </div>
                        <div class="col-6 text-end">
                            <p class="mb-1">${formatZMW(data.amount)}</p>
                            <p class="mb-1">${formatZMW(data.operator_fee)}</p>
                            <p class="mb-1">${formatZMW(data.pawapay_fee)}</p>
                            <hr class="my-2">
                            <p class="mb-0 fs-6 fw-bold text-primary">${formatZMW(data.amount_with_fees)}</p>
                        </div>
                    </div>
                `;
                
                $('#feeDetails').html(feeHtml);
                $('#feeCalculationDisplay').slideDown();
                
                // Update pricing breakdown in plan info
                $('#pricingBreakdown').html(`
                    <div>Plan: ${formatZMW(data.amount)}</div>
                    <div>Fees: ${formatZMW(data.total_fee)}</div>
                    <div class="fw-bold">Total: ${formatZMW(data.amount_with_fees)}</div>
                `);
            }
        });
    }
    
    // Form submission
    $('#membershipPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!selectedOperator) {
            showAlert('warning', 'Please select a mobile money operator');
            return;
        }
        
        const formData = $(this).serialize();
        
        $('#paymentModal').modal('hide');
        $('#processingModal').modal('show');
        
        $.post('', formData + '&action=initiate_membership_payment', function(response) {
            $('#processingModal').modal('hide');
            
            if (response.success) {
                showAlert('success', 'Payment initiated successfully! Redirecting to PawaPay...');
                
                // Redirect to PawaPay payment page
                setTimeout(function() {
                    window.open(response.data.payment_url, '_blank');
                }, 1500);
                
                // Show payment details
                const details = `
                    <div class="alert alert-info mt-3">
                        <h6>Payment Details:</h6>
                        <p class="mb-1"><strong>Payment ID:</strong> ${response.data.payment_id}</p>
                        <p class="mb-1"><strong>Plan:</strong> ${selectedPlan.name}</p>
                        <p class="mb-0"><strong>Total Amount:</strong> ${formatZMW(response.data.pricing.pricing.amount_with_fees)}</p>
                    </div>
                `;
                $('.container').append(details);
                
            } else {
                showAlert('danger', 'Payment initiation failed: ' + response.error);
            }
        }, 'json').fail(function() {
            $('#processingModal').modal('hide');
            showAlert('danger', 'Network error occurred. Please try again.');
        });
    });
});
</script>