<!-- Dashboard Overview -->
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <h4 class="alert-heading"><i class="fas fa-info-circle"></i> Welcome to PawaPay Testing Environment</h4>
            <p>This interface simulates the <strong>Modesy marketplace</strong> (like myzuwa.com) for comprehensive PawaPay SDK testing with real API integration.</p>
            <hr>
            <p class="mb-0">
                <strong>Environment:</strong> <?= $_ENV['PAWAPAY_ENVIRONMENT'] ?? 'sandbox' ?> | 
                <strong>Country:</strong> Zambia (ZM) |
                <strong>Fees Enabled:</strong> <?= $_ENV['PAWAPAY_ZAMBIA_ENABLE_FEES'] ?? 'true' ?>
            </p>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Membership Plans</h6>
                        <h3><?= count($membershipPlans) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-crown fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Test Products</h6>
                        <h3><?= count($testProducts) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">MoMo Operators</h6>
                        <h3><?= count($operators) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-mobile-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">API Status</h6>
                        <h5>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle"></i> Online
                            </span>
                        </h5>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-server fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Available Operators -->
<div class="row mb-4">
    <div class="col-12">
        <h4><i class="fas fa-mobile-alt"></i> Available Mobile Money Operators</h4>
        <div class="row">
            <?php foreach ($operators as $code => $operator): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-signal"></i> <?= htmlspecialchars($operator['display_name']) ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <strong>Code:</strong> <?= htmlspecialchars($code) ?><br>
                            <strong>Currency:</strong> <?= htmlspecialchars($operator['currency']) ?><br>
                            <strong>Fee Structure:</strong> 
                            <span class="badge bg-secondary"><?= htmlspecialchars($operator['fee_structure']) ?></span>
                        </p>
                        
                        <!-- Test Phone Numbers -->
                        <div class="mt-2">
                            <small class="text-muted"><strong>Test Numbers:</strong></small>
                            <?php foreach ($operator['test_phones'] as $scenario => $phone): ?>
                            <div class="small">
                                <span class="badge badge-outline-primary"><?= $scenario ?></span>: 
                                <code><?= $phone ?></code>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <h4><i class="fas fa-rocket"></i> Quick Test Actions</h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-crown fa-3x text-primary mb-3"></i>
                        <h5>Test Membership Payment</h5>
                        <p class="text-muted">Test vendor membership plan purchases with real PawaPay API calls</p>
                        <a href="?page=membership" class="btn btn-primary">
                            <i class="fas fa-play"></i> Start Test
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-bag fa-3x text-success mb-3"></i>
                        <h5>Test Product Purchase</h5>
                        <p class="text-muted">Simulate marketplace product purchases with commission calculations</p>
                        <a href="?page=products" class="btn btn-success">
                            <i class="fas fa-play"></i> Start Test
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-wallet fa-3x text-warning mb-3"></i>
                        <h5>Test Wallet Deposit</h5>
                        <p class="text-muted">Test user wallet top-up functionality with fee calculations</p>
                        <a href="?page=wallet" class="btn btn-warning">
                            <i class="fas fa-play"></i> Start Test
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fee Calculator Preview -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calculator"></i> Quick Fee Calculator
                </h5>
            </div>
            <div class="card-body">
                <form id="quickFeeForm">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Amount (ZMW)</label>
                            <input type="number" class="form-control" id="quickAmount" 
                                   step="0.01" min="1" placeholder="Enter amount" value="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile Money Operator</label>
                            <select class="form-select" id="quickOperator">
                                <?php foreach ($operators as $code => $operator): ?>
                                <option value="<?= $code ?>" <?= $code === 'MTN_MOMO_ZMB' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($operator['display_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-zambia w-100" id="calculateBtn">
                                <i class="fas fa-calculator"></i> Calculate Fees
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Results -->
                <div id="quickFeeResults" class="mt-4" style="display: none;">
                    <div class="fee-breakdown p-3 rounded">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Fee Breakdown</h6>
                                <div id="feeDetails"></div>
                            </div>
                            <div class="col-md-6">
                                <h6>Payment Summary</h6>
                                <div id="paymentSummary"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Quick fee calculator
    $('#quickFeeForm').on('submit', function(e) {
        e.preventDefault();
        
        const amount = $('#quickAmount').val();
        const operator = $('#quickOperator').val();
        
        if (!amount || amount <= 0) {
            showAlert('warning', 'Please enter a valid amount');
            return;
        }
        
        showLoading('#calculateBtn', 'Calculating...');
        
        calculateFees(amount, operator, function(response) {
            hideLoading('#calculateBtn', '<i class="fas fa-calculator"></i> Calculate Fees');
            
            if (response.success) {
                const data = response.data;
                
                $('#feeDetails').html(`
                    <p class="mb-1"><strong>Operator Fee:</strong> ${formatZMW(data.operator_fee)}</p>
                    <p class="mb-1"><strong>PawaPay Fee:</strong> ${formatZMW(data.pawapay_fee)}</p>
                    <p class="mb-0"><strong>Total Fees:</strong> ${formatZMW(data.total_fee)}</p>
                `);
                
                $('#paymentSummary').html(`
                    <p class="mb-1"><strong>Original Amount:</strong> ${formatZMW(data.amount)}</p>
                    <p class="mb-1"><strong>Total Fees:</strong> ${formatZMW(data.total_fee)}</p>
                    <p class="mb-0 fs-5"><strong>Amount to Pay:</strong> ${formatZMW(data.amount_with_fees)}</p>
                `);
                
                $('#quickFeeResults').slideDown();
            } else {
                showAlert('danger', 'Error: ' + response.error);
            }
        });
    });
});
</script>