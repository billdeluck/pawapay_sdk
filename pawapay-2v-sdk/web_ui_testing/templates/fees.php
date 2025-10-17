<!-- Fee Calculator Testing -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-calculator"></i> Zambia Fee Calculator</h2>
            <div>
                <span class="badge bg-<?= $_ENV['PAWAPAY_ZAMBIA_ENABLE_FEES'] === 'true' ? 'success' : 'danger' ?>">
                    Fees <?= $_ENV['PAWAPAY_ZAMBIA_ENABLE_FEES'] === 'true' ? 'Enabled' : 'Disabled' ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Fee Configuration Info -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Zambia Mobile Money Fee Structure</h5>
            <p>This calculator uses real PawaPay fee structures for Zambian mobile money operators:</p>
            <div class="row">
                <div class="col-md-4">
                    <strong>Airtel Money:</strong>
                    <ul class="mb-0">
                        <li>Fixed Fee: ZMW 2.00</li>
                        <li>Percentage: 0.5% of amount</li>
                        <li>Min/Max: ZMW 2.00 - 50.00</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <strong>MTN Mobile Money:</strong>
                    <ul class="mb-0">
                        <li>Percentage: 1% of amount</li>
                        <li>Min/Max: ZMW 1.00 - 100.00</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <strong>Zamtel Kwacha:</strong>
                    <ul class="mb-0">
                        <li>Tiered structure by amount</li>
                        <li>ZMW 1-50: ZMW 1.00</li>
                        <li>ZMW 50-200: ZMW 2.50</li>
                        <li>ZMW 200+: 1.5% then 1%</li>
                    </ul>
                </div>
            </div>
            <p class="mb-0 mt-2">
                <strong>Plus PawaPay Platform Fee:</strong> 1% on all transactions
            </p>
        </div>
    </div>
</div>

<!-- Fee Calculator Form -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calculator"></i> Calculate Transaction Fees</h5>
            </div>
            <div class="card-body">
                <form id="feeCalculatorForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction Amount (ZMW)</label>
                            <input type="number" class="form-control" id="calcAmount" 
                                   step="0.01" min="1" max="10000" value="100" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile Money Operator</label>
                            <select class="form-select" id="calcOperator" required>
                                <?php foreach ($operators as $code => $operator): ?>
                                <option value="<?= $code ?>" <?= $code === 'MTN_MOMO_ZMB' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($operator['display_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-zambia w-100">
                                <i class="fas fa-calculator"></i> Calculate Fees
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-secondary w-100" id="compareAllBtn">
                                <i class="fas fa-balance-scale"></i> Compare All Operators
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Quick Amount Buttons -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Quick Amount Selection</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary quick-amount-btn" data-amount="25">ZMW 25</button>
                    <button class="btn btn-outline-primary quick-amount-btn" data-amount="50">ZMW 50</button>
                    <button class="btn btn-outline-primary quick-amount-btn" data-amount="100">ZMW 100</button>
                    <button class="btn btn-outline-primary quick-amount-btn" data-amount="250">ZMW 250</button>
                    <button class="btn btn-outline-primary quick-amount-btn" data-amount="500">ZMW 500</button>
                    <button class="btn btn-outline-primary quick-amount-btn" data-amount="1000">ZMW 1,000</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fee Calculation Results -->
<div id="feeResults" style="display: none;">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Fee Calculation Results</h5>
                </div>
                <div class="card-body">
                    <div id="singleFeeResult"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Operator Comparison Results -->
<div id="comparisonResults" style="display: none;">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-balance-scale"></i> Operator Fee Comparison</h5>
                </div>
                <div class="card-body">
                    <div id="comparisonTable"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fee Testing Scenarios -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-vial"></i> Pre-defined Testing Scenarios</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Click on any scenario below to automatically test fee calculations:</p>
                
                <div class="row">
                    <?php
                    $testScenarios = [
                        ['name' => 'Small Purchase', 'amount' => 15.50, 'description' => 'Small item purchase'],
                        ['name' => 'Membership Plan', 'amount' => 75.00, 'description' => 'Premium vendor membership'],
                        ['name' => 'Product Sale', 'amount' => 125.00, 'description' => 'Average product price'],
                        ['name' => 'High Value', 'amount' => 500.00, 'description' => 'High-value transaction'],
                        ['name' => 'Wallet Top-up', 'amount' => 200.00, 'description' => 'Wallet deposit'],
                        ['name' => 'Large Order', 'amount' => 1250.00, 'description' => 'Large marketplace order']
                    ];
                    ?>
                    
                    <?php foreach ($testScenarios as $scenario): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light scenario-card" data-amount="<?= $scenario['amount'] ?>">
                            <div class="card-body text-center py-3">
                                <h6 class="card-title"><?= $scenario['name'] ?></h6>
                                <p class="text-primary mb-1 fs-4">ZMW <?= number_format($scenario['amount'], 2) ?></p>
                                <small class="text-muted"><?= $scenario['description'] ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Fee calculator form submission
    $('#feeCalculatorForm').on('submit', function(e) {
        e.preventDefault();
        
        const amount = $('#calcAmount').val();
        const operator = $('#calcOperator').val();
        
        if (!amount || amount <= 0) {
            showAlert('warning', 'Please enter a valid amount');
            return;
        }
        
        const submitBtn = $(this).find('button[type="submit"]');
        showLoading(submitBtn, 'Calculating...');
        
        calculateFees(amount, operator, function(response) {
            hideLoading(submitBtn, '<i class="fas fa-calculator"></i> Calculate Fees');
            
            if (response.success) {
                displaySingleFeeResult(response.data);
                $('#comparisonResults').hide();
            } else {
                showAlert('danger', 'Error: ' + response.error);
            }
        });
    });
    
    // Compare all operators
    $('#compareAllBtn').on('click', function() {
        const amount = $('#calcAmount').val();
        
        if (!amount || amount <= 0) {
            showAlert('warning', 'Please enter a valid amount');
            return;
        }
        
        showLoading($(this), 'Comparing...');
        
        compareOperators(amount, function(response) {
            hideLoading($('#compareAllBtn'), '<i class="fas fa-balance-scale"></i> Compare All Operators');
            
            if (response.success) {
                displayComparisonResults(response.data, amount);
                $('#feeResults').hide();
            } else {
                showAlert('danger', 'Error: ' + response.error);
            }
        });
    });
    
    // Quick amount selection
    $('.quick-amount-btn').on('click', function() {
        const amount = $(this).data('amount');
        $('#calcAmount').val(amount);
        $('.quick-amount-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
    });
    
    // Scenario testing
    $('.scenario-card').on('click', function() {
        const amount = $(this).data('amount');
        $('#calcAmount').val(amount);
        $('#feeCalculatorForm').submit();
    });
    
    // Display single fee result
    function displaySingleFeeResult(data) {
        const html = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-info-circle"></i> Transaction Details</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Operator:</strong></td><td>${data.operator_name}</td></tr>
                        <tr><td><strong>Currency:</strong></td><td>${data.currency}</td></tr>
                        <tr><td><strong>Original Amount:</strong></td><td>${formatZMW(data.amount)}</td></tr>
                        <tr><td><strong>Fee Structure:</strong></td><td><span class="badge bg-secondary">${data.fee_breakdown.operator_fee_structure}</span></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-calculator"></i> Fee Breakdown</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Operator Fee:</strong></td><td class="text-end">${formatZMW(data.operator_fee)}</td></tr>
                        <tr><td><strong>PawaPay Platform Fee:</strong></td><td class="text-end">${formatZMW(data.pawapay_fee)}</td></tr>
                        <tr class="table-active"><td><strong>Total Fees:</strong></td><td class="text-end">${formatZMW(data.total_fee)}</td></tr>
                        <tr class="table-success"><td><strong>Amount to Pay:</strong></td><td class="text-end fs-5">${formatZMW(data.amount_with_fees)}</td></tr>
                    </table>
                </div>
            </div>
            
            ${data.fee_breakdown.applied_tiers && Object.keys(data.fee_breakdown.applied_tiers).length > 0 ? `
            <div class="alert alert-info mt-3">
                <h6><i class="fas fa-layer-group"></i> Tier Information</h6>
                <p class="mb-1"><strong>Applied Tier:</strong> ${data.fee_breakdown.applied_tiers.tier_range}</p>
                <p class="mb-0"><strong>Tier Fee:</strong> ${data.fee_breakdown.applied_tiers.tier_fee}</p>
            </div>
            ` : ''}
        `;
        
        $('#singleFeeResult').html(html);
        $('#feeResults').slideDown();
    }
    
    // Display comparison results
    function displayComparisonResults(data, amount) {
        let tableHtml = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Operator</th>
                            <th>Fee Structure</th>
                            <th class="text-end">Total Fee</th>
                            <th class="text-end">Amount to Pay</th>
                            <th class="text-center">Ranking</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        let rank = 1;
        for (const [operatorCode, info] of Object.entries(data)) {
            if (info.error) {
                tableHtml += `
                    <tr class="table-danger">
                        <td>${info.operator_name}</td>
                        <td colspan="4" class="text-center">Error: ${info.error}</td>
                    </tr>
                `;
            } else {
                const rankClass = rank === 1 ? 'table-success' : (rank === 2 ? 'table-warning' : '');
                tableHtml += `
                    <tr class="${rankClass}">
                        <td>
                            <strong>${info.operator_name}</strong>
                            ${rank === 1 ? '<span class="badge bg-success ms-2">Best</span>' : ''}
                        </td>
                        <td><span class="badge bg-secondary">${info.fee_structure}</span></td>
                        <td class="text-end">${formatZMW(info.total_fee)}</td>
                        <td class="text-end"><strong>${formatZMW(info.total_to_pay)}</strong></td>
                        <td class="text-center">
                            ${rank === 1 ? '<i class="fas fa-trophy text-warning"></i>' : '#' + rank}
                        </td>
                    </tr>
                `;
                rank++;
            }
        }
        
        tableHtml += `
                    </tbody>
                </table>
            </div>
            <div class="alert alert-success">
                <h6><i class="fas fa-lightbulb"></i> Recommendation</h6>
                <p class="mb-0">Based on the amount of <strong>${formatZMW(amount)}</strong>, the operator with the lowest total cost is highlighted in green above.</p>
            </div>
        `;
        
        $('#comparisonTable').html(tableHtml);
        $('#comparisonResults').slideDown();
    }
});
</script>