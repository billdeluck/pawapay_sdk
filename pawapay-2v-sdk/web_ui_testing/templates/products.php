<!-- Products Testing -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-shopping-cart"></i> Product Purchase Testing</h2>
            <span class="badge bg-info">Modesy Marketplace Simulation</span>
        </div>
    </div>
</div>

<!-- Marketplace Info -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-success">
            <h5><i class="fas fa-store"></i> Marketplace Product Testing</h5>
            <p class="mb-2">
                Test product purchases with vendor commission calculations, just like in a real Modesy marketplace. 
                This simulates multi-vendor transactions with real PawaPay payment processing.
            </p>
            <div class="row">
                <div class="col-md-6">
                    <strong>Features Tested:</strong>
                    <ul class="mb-0">
                        <li>Product purchases with quantity selection</li>
                        <li>Vendor commission calculations</li>
                        <li>Real-time fee calculations by operator</li>
                        <li>Multi-vendor cart simulations</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <strong>Payment Processing:</strong>
                    <ul class="mb-0">
                        <li>Real PawaPay API integration</li>
                        <li>Zambian mobile money operators</li>
                        <li>Fee transparency and breakdown</li>
                        <li>Test scenarios with different outcomes</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Categories -->
<div class="row mb-4">
    <div class="col-12">
        <nav class="navbar navbar-expand-lg navbar-light bg-light rounded">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="fas fa-tags"></i> Product Categories
                </span>
                <div class="navbar-nav">
                    <a class="nav-link active category-filter" href="#" data-category="all">All Products</a>
                    <a class="nav-link category-filter" href="#" data-category="Electronics">Electronics</a>
                    <a class="nav-link category-filter" href="#" data-category="Fashion">Fashion</a>
                    <a class="nav-link category-filter" href="#" data-category="Food & Beverages">Food & Beverages</a>
                </div>
            </div>
        </nav>
    </div>
</div>

<!-- Shopping Cart -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-shopping-cart"></i> Shopping Cart</h6>
                    <span class="badge bg-primary" id="cartItemCount">0 items</span>
                </div>
            </div>
            <div class="card-body" id="cartContent">
                <div class="text-center text-muted">
                    <i class="fas fa-shopping-cart fa-3x mb-3 opacity-25"></i>
                    <p>Your cart is empty. Add some products to get started!</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Products Grid -->
<div class="row" id="productsGrid">
    <?php foreach ($testProducts as $productId => $product): ?>
    <div class="col-md-4 mb-4 product-item" data-category="<?= htmlspecialchars($product['category']) ?>">
        <div class="card h-100">
            <!-- Product Image Placeholder -->
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                <?php
                $categoryIcons = [
                    'Electronics' => 'fas fa-laptop',
                    'Fashion' => 'fas fa-tshirt', 
                    'Food & Beverages' => 'fas fa-utensils'
                ];
                $icon = $categoryIcons[$product['category']] ?? 'fas fa-box';
                ?>
                <i class="<?= $icon ?> fa-4x text-muted opacity-25"></i>
            </div>
            
            <div class="card-body d-flex flex-column">
                <div class="mb-2">
                    <span class="badge bg-secondary"><?= htmlspecialchars($product['category']) ?></span>
                </div>
                
                <h6 class="card-title"><?= htmlspecialchars($product['name']) ?></h6>
                
                <div class="mb-3">
                    <h5 class="text-primary mb-0">ZMW <?= number_format($product['price'], 2) ?></h5>
                    <small class="text-muted">
                        Vendor Commission: <?= ($product['vendor_commission'] * 100) ?>%
                    </small>
                </div>
                
                <!-- Quantity and Add to Cart -->
                <div class="mt-auto">
                    <div class="row align-items-center mb-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">Quantity:</label>
                            <select class="form-select form-select-sm" data-product-id="<?= $productId ?>">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-zambia btn-sm w-100 add-to-cart-btn" 
                                    data-product-id="<?= $productId ?>"
                                    data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                    data-product-price="<?= $product['price'] ?>"
                                    data-product-commission="<?= $product['vendor_commission'] ?>"
                                    data-product-category="<?= htmlspecialchars($product['category']) ?>">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                    
                    <!-- Quick Buy -->
                    <button class="btn btn-outline-primary btn-sm w-100 quick-buy-btn"
                            data-product-id="<?= $productId ?>"
                            data-product-name="<?= htmlspecialchars($product['name']) ?>"
                            data-product-price="<?= $product['price'] ?>"
                            data-product-commission="<?= $product['vendor_commission'] ?>">
                        <i class="fas fa-bolt"></i> Quick Buy
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-credit-card"></i> Complete Purchase
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <!-- Order Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-receipt"></i> Order Summary</h6>
                    </div>
                    <div class="card-body">
                        <div id="orderSummary"></div>
                    </div>
                </div>
                
                <!-- Payment Form -->
                <form id="productPaymentForm">
                    <input type="hidden" id="cartData" name="cart_data">
                    
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
                                        
                                        <!-- Fee Preview -->
                                        <div class="mt-2">
                                            <span class="badge bg-light text-dark product-fee-preview" data-operator="<?= $code ?>">
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
                        <input type="hidden" name="operator" id="selectedOperatorProduct">
                    </div>
                    
                    <!-- Phone Number -->
                    <div class="mb-4">
                        <label class="form-label">Mobile Money Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">+260</span>
                            <input type="tel" class="form-control" name="phone_number" 
                                   placeholder="Enter phone number" pattern="[0-9]{9}" required>
                        </div>
                    </div>
                    
                    <!-- Payment Breakdown -->
                    <div id="paymentBreakdown" class="mb-4" style="display: none;">
                        <div class="fee-breakdown p-3 rounded">
                            <h6><i class="fas fa-calculator"></i> Payment Breakdown</h6>
                            <div id="breakdownDetails"></div>
                        </div>
                    </div>
                    
                    <!-- Terms -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeProductTerms" required>
                            <label class="form-check-label" for="agreeProductTerms">
                                I agree to the terms and conditions and understand this is a test transaction
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="productPaymentForm" class="btn btn-zambia" id="processProductPaymentBtn">
                    <i class="fas fa-credit-card"></i> Process Payment
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let cart = [];
    let selectedOperatorProduct = null;
    
    // Category filtering
    $('.category-filter').on('click', function(e) {
        e.preventDefault();
        const category = $(this).data('category');
        
        $('.category-filter').removeClass('active');
        $(this).addClass('active');
        
        if (category === 'all') {
            $('.product-item').show();
        } else {
            $('.product-item').hide();
            $(`.product-item[data-category="${category}"]`).show();
        }
    });
    
    // Add to cart
    $('.add-to-cart-btn').on('click', function() {
        const productId = $(this).data('product-id');
        const quantity = parseInt($(this).closest('.card-body').find('select').val());
        const productData = {
            id: productId,
            name: $(this).data('product-name'),
            price: $(this).data('product-price'),
            commission: $(this).data('product-commission'),
            category: $(this).data('product-category'),
            quantity: quantity
        };
        
        addToCart(productData);
        showAlert('success', `${productData.name} added to cart!`);
    });
    
    // Quick buy
    $('.quick-buy-btn').on('click', function() {
        const productData = {
            id: $(this).data('product-id'),
            name: $(this).data('product-name'),
            price: $(this).data('product-price'),
            commission: $(this).data('product-commission'),
            quantity: 1
        };
        
        cart = [productData]; // Replace cart with single item
        updateCartDisplay();
        $('#checkoutModal').modal('show');
        updateOrderSummary();
    });
    
    // Add product to cart
    function addToCart(product) {
        const existingIndex = cart.findIndex(item => item.id === product.id);
        
        if (existingIndex !== -1) {
            cart[existingIndex].quantity += product.quantity;
        } else {
            cart.push(product);
        }
        
        updateCartDisplay();
    }
    
    // Update cart display
    function updateCartDisplay() {
        const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
        const totalValue = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        $('#cartItemCount').text(`${itemCount} items`);
        
        if (cart.length === 0) {
            $('#cartContent').html(`
                <div class="text-center text-muted">
                    <i class="fas fa-shopping-cart fa-3x mb-3 opacity-25"></i>
                    <p>Your cart is empty. Add some products to get started!</p>
                </div>
            `);
        } else {
            let cartHtml = `
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            cart.forEach((item, index) => {
                cartHtml += `
                    <tr>
                        <td>
                            <div class="fw-bold">${item.name}</div>
                            <small class="text-muted">${item.category}</small>
                        </td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-end">${formatZMW(item.price)}</td>
                        <td class="text-end">${formatZMW(item.price * item.quantity)}</td>
                        <td>
                            <button class="btn btn-outline-danger btn-sm remove-item" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            cartHtml += `
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th colspan="3">Total:</th>
                                <th class="text-end">${formatZMW(totalValue)}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="text-end mt-3">
                    <button class="btn btn-outline-danger me-2" id="clearCartBtn">
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                    <button class="btn btn-zambia" id="checkoutBtn">
                        <i class="fas fa-credit-card"></i> Checkout
                    </button>
                </div>
            `;
            
            $('#cartContent').html(cartHtml);
        }
    }
    
    // Remove item from cart
    $(document).on('click', '.remove-item', function() {
        const index = parseInt($(this).data('index'));
        cart.splice(index, 1);
        updateCartDisplay();
    });
    
    // Clear cart
    $(document).on('click', '#clearCartBtn', function() {
        cart = [];
        updateCartDisplay();
    });
    
    // Checkout
    $(document).on('click', '#checkoutBtn', function() {
        if (cart.length === 0) {
            showAlert('warning', 'Your cart is empty!');
            return;
        }
        
        $('#checkoutModal').modal('show');
        updateOrderSummary();
    });
    
    // Update order summary
    function updateOrderSummary() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const totalCommission = cart.reduce((sum, item) => sum + (item.price * item.quantity * item.commission), 0);
        
        let summaryHtml = `
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Commission</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            const itemCommission = itemTotal * item.commission;
            
            summaryHtml += `
                <tr>
                    <td>
                        <div class="fw-bold">${item.name}</div>
                        <small class="text-muted">${item.category}</small>
                    </td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-end">${formatZMW(item.price)}</td>
                    <td class="text-end">${formatZMW(itemTotal)}</td>
                    <td class="text-end">${formatZMW(itemCommission)}</td>
                </tr>
            `;
        });
        
        summaryHtml += `
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th colspan="3">Subtotal:</th>
                            <th class="text-end">${formatZMW(subtotal)}</th>
                            <th class="text-end">${formatZMW(totalCommission)}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
        
        $('#orderSummary').html(summaryHtml);
    }
    
    // Operator selection for products
    $('.operator-card').on('click', function() {
        const operator = $(this).data('operator');
        selectedOperatorProduct = operator;
        
        $('.operator-card').removeClass('selected');
        $(this).addClass('selected');
        $('#selectedOperatorProduct').val(operator);
        
        // Calculate payment breakdown
        calculateProductPaymentBreakdown();
    });
    
    // Calculate product payment breakdown
    function calculateProductPaymentBreakdown() {
        if (!selectedOperatorProduct || cart.length === 0) return;
        
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        calculateFees(subtotal, selectedOperatorProduct, function(response) {
            if (response.success) {
                const data = response.data;
                const totalCommission = cart.reduce((sum, item) => sum + (item.price * item.quantity * item.commission), 0);
                
                const breakdownHtml = `
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1"><strong>Subtotal:</strong></p>
                            <p class="mb-1"><strong>Vendor Commission:</strong></p>
                            <p class="mb-1"><strong>Processing Fee:</strong></p>
                            <hr class="my-2">
                            <p class="mb-0"><strong>Total to Pay:</strong></p>
                        </div>
                        <div class="col-6 text-end">
                            <p class="mb-1">${formatZMW(data.amount)}</p>
                            <p class="mb-1">${formatZMW(totalCommission)}</p>
                            <p class="mb-1">${formatZMW(data.total_fee)}</p>
                            <hr class="my-2">
                            <p class="mb-0 fs-5 fw-bold text-primary">${formatZMW(data.amount_with_fees)}</p>
                        </div>
                    </div>
                `;
                
                $('#breakdownDetails').html(breakdownHtml);
                $('#paymentBreakdown').slideDown();
                
                // Update fee previews
                updateProductFeePreviews(subtotal);
            }
        });
    }
    
    // Update fee previews for products
    function updateProductFeePreviews(amount) {
        $('.product-fee-preview').each(function() {
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
    
    // Test phone selection
    $('.test-phone-select').on('change', function() {
        const phoneNumber = $(this).val();
        const operator = $(this).data('operator');
        
        if (phoneNumber && operator === selectedOperatorProduct) {
            $('input[name="phone_number"]').val(phoneNumber);
        }
    });
    
    // Form submission - This would be implemented similar to membership payment
    $('#productPaymentForm').on('submit', function(e) {
        e.preventDefault();
        showAlert('info', 'Product payment integration would be implemented here with real PawaPay API calls');
        // Implementation similar to membership payment but with cart data
    });
    
    // Initialize
    updateCartDisplay();
});
</script>