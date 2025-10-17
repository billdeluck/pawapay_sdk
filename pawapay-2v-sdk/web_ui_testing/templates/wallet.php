<!-- Wallet Testing -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-wallet"></i> Wallet Deposit Testing</h2>
            <span class="badge bg-info">Real PawaPay Integration</span>
        </div>
    </div>
</div>

<!-- Wallet Overview -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Current Wallet Balance</h6>
                        <h3 class="mb-0">ZMW 1,247.83</h3>
                        <small class="opacity-75">Last updated: 2 hours ago</small>
                    </div>
                    <div>
                        <i class="fas fa-wallet fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Total Deposits (Test)</h6>
                        <h3 class="mb-0">ZMW 3,450.00</h3>
                        <small class="opacity-75">This testing session</small>
                    </div>
                    <div>
                        <i class="fas fa-plus-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deposit Instructions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Wallet Deposit Testing</h5>
            <p class="mb-2">
                Test wallet deposit functionality with real PawaPay API integration. This simulates the Modesy marketplace wallet top-up feature.
            </p>
            <ul class="mb-0">
                <li><strong>Real API Integration:</strong> All deposits use actual PawaPay payment processing</li>
                <li><strong>Fee Calculation:</strong> Fees are calculated based on selected mobile money operator</li>
                <li><strong>Test Environment:</strong> Use provided test phone numbers for different scenarios</li>
                <li><strong>Instant Processing:</strong> Successful deposits are immediately reflected in wallet balance</li>
            </ul>
        </div>
    </div>
</div>

<!-- Quick Deposit Amounts -->
<div class="row mb-4">
    <div class="col-12">
        <h5><i class="fas fa-zap"></i> Quick Deposit Options</h5>
        <div class="row">
            <?php
            $quickAmounts = [
                ['amount' => 50, 'label' => 'Basic Top-up', 'icon' => 'fas fa-coins'],
                ['amount' => 100, 'label' => 'Standard', 'icon' => 'fas fa-money-bill-wave', 'popular' => true],
                ['amount' => 250, 'label' => 'Premium', 'icon' => 'fas fa-gem'],
                ['amount' => 500, 'label' => 'Business', 'icon' => 'fas fa-briefcase']
            ];
            ?>
            
            <?php foreach ($quickAmounts as $quick): ?>
            <div class="col-md-3 mb-3">
                <div class="card h-100 quick-deposit-card <?= isset($quick['popular']) ? 'border-primary' : '' ?>" 
                     data-amount="<?= $quick['amount'] ?>">
                    <?php if (isset($quick['popular'])): ?>
                    <div class="card-header bg-primary text-white text-center py-1">
                        <small class="badge bg-warning text-dark">POPULAR</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body text-center">
                        <i class="<?= $quick['icon'] ?> fa-2x text-primary mb-2"></i>
                        <h5 class="card-title">ZMW <?= number_format($quick['amount'], 2) ?></h5>
                        <p class="text-muted small"><?= $quick['label'] ?></p>
                        <button class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-plus"></i> Deposit
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Custom Deposit Form -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Custom Wallet Deposit</h5>
            </div>
            <div class="card-body">
                <form id="walletDepositForm">
                    <!-- Amount Input -->
                    <div class="mb-4">
                        <label class="form-label">Deposit Amount (ZMW)</label>
                        <div class="input-group">
                            <span class="input-group-text">ZMW</span>
                            <input type="number" class="form-control" name="amount" 
                                   id="depositAmount" step="0.01" min="10" max="5000" 
                                   placeholder="Enter amount" required>
                        </div>
                        <div class="form-text">
                            Minimum: ZMW 10.00 | Maximum: ZMW 5,000.00
                        </div>
                    </div>
                    
                    <!-- Operator Selection -->
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
                                        
                                        <!-- Fee Preview -->
                                        <div class="mt-2">
                                            <span class="badge bg-light text-dark fee-preview" data-operator="<?= $code ?>">
                                                Fee: Calculating...
                                            </span>
                                        </div>
                                        
                                        <!-- Test Numbers -->
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
                        <input type="hidden" name="operator" id="selectedOperatorWallet">
                    </div>
                    
                    <!-- Phone Number -->
                    <div class="mb-4">
                        <label class="form-label">Mobile Money Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">+260</span>
                            <input type="tel" class="form-control" name="phone_number" 
                                   placeholder="Enter phone number" pattern="[0-9]{9}" required>
                        </div>
                        <div class="form-text">
                            Use test numbers above for different scenarios, or enter your own Zambian number
                        </div>
                    </div>
                    
                    <!-- Fee Display -->
                    <div id="walletFeeDisplay" class="mb-4" style="display: none;">
                        <div class="fee-breakdown p-3 rounded">
                            <h6><i class="fas fa-calculator"></i> Deposit Summary</h6>
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-1"><strong>Deposit Amount:</strong></p>
                                    <p class="mb-1"><strong>Processing Fee:</strong></p>
                                    <hr class="my-2">
                                    <p class="mb-0"><strong>Total to Pay:</strong></p>
                                </div>
                                <div class="col-6 text-end">
                                    <p class="mb-1" id="walletDepositAmount">ZMW 0.00</p>
                                    <p class="mb-1" id="walletTotalFee">ZMW 0.00</p>
                                    <hr class="my-2">
                                    <p class="mb-0 fs-5 fw-bold text-primary" id="walletTotalToPay">ZMW 0.00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Terms -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeWalletTerms" required>
                            <label class="form-check-label" for="agreeWalletTerms">
                                I understand this is a test transaction and agree to the terms and conditions
                            </label>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-zambia w-100" id="processWalletDepositBtn">
                        <i class="fas fa-plus-circle"></i> Process Wallet Deposit
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Recent Deposits -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-history"></i> Recent Test Deposits</h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold">ZMW 100.00</div>
                            <small class="text-muted">MTN Mobile Money</small>
                        </div>
                        <span class="badge bg-success">Success</span>
                    </div>
                    
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold">ZMW 250.00</div>
                            <small class="text-muted">Airtel Money</small>
                        </div>
                        <span class="badge bg-success">Success</span>
                    </div>
                    
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold">ZMW 50.00</div>
                            <small class="text-muted">Zamtel Kwacha</small>
                        </div>
                        <span class="badge bg-warning">Pending</span>
                    </div>
                    
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold">ZMW 75.00</div>
                            <small class="text-muted">MTN Mobile Money</small>
                        </div>
                        <span class="badge bg-danger">Failed</span>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <button class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-list"></i> View All Deposits
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let selectedOperatorWallet = null;
    
    // Quick deposit selection
    $('.quick-deposit-card').on('click', function() {
        const amount = $(this).data('amount');
        $('#depositAmount').val(amount);
        
        $('.quick-deposit-card').removeClass('bg-light');
        $(this).addClass('bg-light');
        
        // Trigger fee calculation if operator is selected
        if (selectedOperatorWallet) {
            calculateWalletFees();
        }
    });
    
    // Operator selection
    $('.operator-card').on('click', function() {
        const operator = $(this).data('operator');
        selectedOperatorWallet = operator;
        
        $('.operator-card').removeClass('selected');
        $(this).addClass('selected');
        $('#selectedOperatorWallet').val(operator);
        
        // Calculate fees if amount is entered
        const amount = $('#depositAmount').val();
        if (amount && amount > 0) {
            calculateWalletFees();
        }
    });
    
    // Amount input change
    $('#depositAmount').on('input', function() {
        const amount = $(this).val();
        if (amount && amount > 0 && selectedOperatorWallet) {
            calculateWalletFees();
            updateFeePreviews(amount);
        } else {
            $('#walletFeeDisplay').hide();
        }
    });
    
    // Test phone selection
    $('.test-phone-select').on('change', function() {
        const phoneNumber = $(this).val();
        const operator = $(this).data('operator');
        
        if (phoneNumber && operator === selectedOperatorWallet) {
            $('input[name="phone_number"]').val(phoneNumber);
        }
    });
    
    // Calculate wallet fees
    function calculateWalletFees() {
        const amount = $('#depositAmount').val();
        if (!amount || !selectedOperatorWallet) return;
        
        calculateFees(amount, selectedOperatorWallet, function(response) {
            if (response.success) {
                const data = response.data;
                
                $('#walletDepositAmount').text(formatZMW(data.amount));
                $('#walletTotalFee').text(formatZMW(data.total_fee));
                $('#walletTotalToPay').text(formatZMW(data.amount_with_fees));
                
                $('#walletFeeDisplay').slideDown();
            }
        });
    }
    
    // Update fee previews for all operators
    function updateFeePreviews(amount) {
        $('.fee-preview').each(function() {
            const operator = $(this).data('operator');
            const $preview = $(this);
            
            calculateFees(amount, operator, function(response) {
                if (response.success) {
                    $preview.text(`Fee: ${formatZMW(response.data.total_fee)}`);
                    $preview.removeClass('bg-light text-dark').addClass('bg-primary text-white');
                }
            });
        });
    }
    
    // Form submission
    $('#walletDepositForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!selectedOperatorWallet) {
            showAlert('warning', 'Please select a mobile money operator');
            return;
        }
        
        const formData = $(this).serialize();
        showLoading('#processWalletDepositBtn', 'Processing Deposit...');
        
        $.post('', formData + '&action=initiate_wallet_deposit', function(response) {
            hideLoading('#processWalletDepositBtn', '<i class="fas fa-plus-circle"></i> Process Wallet Deposit');
            
            if (response.success) {
                showAlert('success', 'Wallet deposit initiated successfully! Redirecting to PawaPay...');
                
                // Redirect to PawaPay
                setTimeout(function() {
                    window.open(response.data.payment_url, '_blank');
                }, 1500);
                
                // Add to recent deposits (simulated)
                const amount = $('#depositAmount').val();
                const operatorName = $('.operator-card.selected h6').text();
                addRecentDeposit(amount, operatorName);
                
                // Reset form
                $('#walletDepositForm')[0].reset();
                $('.operator-card').removeClass('selected');
                $('.quick-deposit-card').removeClass('bg-light');
                $('#walletFeeDisplay').hide();
                selectedOperatorWallet = null;
                
            } else {
                showAlert('danger', 'Wallet deposit failed: ' + response.error);
            }
        }, 'json').fail(function() {
            hideLoading('#processWalletDepositBtn', '<i class="fas fa-plus-circle"></i> Process Wallet Deposit');
            showAlert('danger', 'Network error occurred. Please try again.');
        });
    });
    
    // Add recent deposit to list (simulated)
    function addRecentDeposit(amount, operator) {
        const newDeposit = `
            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                <div>
                    <div class="fw-bold">${formatZMW(amount)}</div>
                    <small class="text-muted">${operator}</small>
                </div>
                <span class="badge bg-warning">Processing</span>
            </div>
        `;
        $('.list-group').prepend(newDeposit);
        
        // Keep only last 4 deposits
        $('.list-group-item').slice(4).remove();
    }
});
</script>