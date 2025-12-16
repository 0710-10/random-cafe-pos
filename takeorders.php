<?php
include 'global.php';
session_start();
$serverName = "LAPTOP-T6HO0TLL\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn == false)
    die(print_r(sqlsrv_errors(), true));

global $loggeduser;
if (isset($_GET['action']) && $_GET['action'] === 'getProducts') {
    global $categories;
    $categories = $_GET['category'] ?? '';

    $sql = "SELECT *
        FROM CAFEPRODUCTS
        WHERE CATEGORY = ?";
    $sqlquery = sqlsrv_query($conn, $sql, [$categories]);

    $products = [];
    while ($fetchquery = sqlsrv_fetch_array($sqlquery, SQLSRV_FETCH_ASSOC)) {
        $products[] = $fetchquery;
    }

    header('Content-Type: application/json');
    echo json_encode($products);
    exit;
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Manhattan Café</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="takeorderstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="assets/logonotext.png">
</head>

<script>
    let cart = [];

    function loadProducts(categories) {
        document.getElementById('categoryTitle').innerText = categories;

        fetch(`takeorders.php?action=getProducts&category=${encodeURIComponent(categories)}`)
            .then(res => res.json())
            .then(products => {
                const grid = document.getElementById('productGrid');
                grid.innerHTML = '';

                if (products.length === 0) {
                    grid.innerHTML = '<p class="text-muted">No products found.</p>';
                    return;
                }

                products.forEach(p => {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-4 col-lg-3 mb-4'; // Responsive columns

                    const btn = document.createElement('button');
                    btn.className = 'product-card w-100 border-0 d-flex flex-column';
                    btn.innerHTML = `<img src="productimages/${p.IMAGE_NAME}" class="product-img mb-3" 
                                        onerror="this.src='assets/default-product.png'"
                                        style="aspect-ratio: 1/1; object-fit: cover;">
                                    <div class="fw-bold mb-1 text-truncate">${p.PRODUCT_NAME}</div>
                                    <div class="mb-2" style="color: #8b5a2b;">₱${parseFloat(p.PRICE).toFixed(2)}</div>
                                    <div class="text-muted small">Qty: ${getCartQty(p.PRODUCT_ID)}</div>`;
                    btn.addEventListener('click', () => openQtyModal(p.PRODUCT_ID, p.PRODUCT_NAME, p.PRICE, p.IMAGE_NAME));

                    col.appendChild(btn);
                    grid.appendChild(col);
                });
            });
    }

    function getCartQty(id) {
        const item = cart.find(i => i.id === id);
        return item ? item.qty : 0;
    }

    let tempProduct = {};
    let tempQty = 1;

    function openQtyModal(id, name, price, image) {
        const existing = cart.find(i => i.id === id);
        tempQty = existing ? existing.qty : 1;

        tempProduct = {
            id,
            name,
            price,
            image
        };

        document.getElementById('qtyProductName').innerText = name;
        document.getElementById('qtyValue').innerText = tempQty;
        document.getElementById('qtyProductPrice').innerText = `₱${price}`;
        document.getElementById('qtyProductImage').src = `productimages/${image}`;
        document.getElementById('qtyProductImage').onerror = "this.src='assets/default-product.png'";

        new bootstrap.Modal(document.getElementById('qtyModal')).show();
    }

    function changeQty(val) {
        tempQty = Math.max(0, tempQty + val);
        document.getElementById('qtyValue').innerText = tempQty;
    }

    function confirmQty() {
        const existing = cart.find(i => i.id === tempProduct.id);

        if (existing) {
            if (tempQty === 0) {
                cart = cart.filter(i => i.id !== tempProduct.id);
            } else {
                existing.qty = tempQty;
            }
        } else if (tempQty > 0) {
            cart.push({
                ...tempProduct,
                qty: tempQty
            });
        }

        updateSubtotal();
        loadProducts(document.getElementById('categoryTitle').innerText);
        bootstrap.Modal.getInstance(document.getElementById('qtyModal')).hide();
    }

    function updateSubtotal() {
        const subtotal = cart.reduce((sum, i) => sum + i.price * i.qty, 0);
        document.getElementById('subtotalText').innerText = `Order Subtotal: ₱${subtotal}`;
    }

    function clearOrder() {
        cart = [];
        updateSubtotal();
        loadProducts(document.getElementById('categoryTitle').innerText);
    }

    function seeOrder() {
        const list = document.getElementById('orderList');

        const renderModalContent = () => {
            list.innerHTML = '';

            if (cart.length === 0) {
                list.innerHTML = '<p class="text-muted text-center">No items in the order.</p>';
                document.getElementById('orderSubtotal').innerText = 'Subtotal: ₱0';
                updateSubtotal();
                return;
            }

            cart.forEach(i => {
                const col = document.createElement('div');
                col.className = 'col-12 col-md-6 col-lg-4 mb-3';

                const card = document.createElement('div');
                card.className = 'product-card d-flex align-items-center justify-content-between p-3';

                card.innerHTML = `
                <img src="productimages/${i.image}" class="order-img" 
                     onerror="this.src='assets/placeholder.jpg'">
                <div class="flex-grow-1">
                    <div class="fw-bold text-truncate">${i.name}</div>
                    <div class="text-muted small">₱${i.price} × <span class="item-qty">${i.qty}</span></div>
                    <div class="fw-bold mt-1" style="color: #8b5a2b;">₱${(i.price * i.qty).toFixed(2)}</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-secondary btn-sm" 
                            style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: #8b5a2b; color: white; border: none;"
                            title="Decrease quantity">
                        <i class="bi bi-dash"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" 
                            style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: #ab8b7b; color: white; border: none;"
                            title="Increase quantity">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            `;

                const buttons = card.querySelectorAll('button.btn-secondary.btn-sm');
                const btnMinus = buttons[0];
                const btnPlus = buttons[1];

                btnMinus.addEventListener('click', () => {
                    i.qty -= 1;
                    if (i.qty <= 0) {
                        cart = cart.filter(item => item.id !== i.id);
                    }
                    renderModalContent();
                    updateSubtotal();
                    loadProducts(document.getElementById('categoryTitle').innerText);
                });

                btnPlus.addEventListener('click', () => {
                    i.qty += 1;
                    renderModalContent();
                    updateSubtotal();
                    loadProducts(document.getElementById('categoryTitle').innerText);
                });

                col.appendChild(card);
                list.appendChild(col);
            });

            document.getElementById('orderSubtotal').innerText =
                `Subtotal: ₱${cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)}`;
        };

        const modalEl = document.getElementById('orderModal');
        const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        bsModal.show();

        renderModalContent();
    }


    function checkoutTotal() {
        const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
        const discount = document.getElementById('discountCheck').checked ? 0.8 : 1;
        return subtotal * discount;
    }

    function openCheckout() {
        if (cart.length === 0) {
            alert("Cart is empty!");
            return;
        }
        const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
        const total = checkoutTotal();
        document.getElementById('amountPaid').value = '';
        document.getElementById('discountCheck').checked = false;
        document.getElementById('changeText').innerText = `Change: ₱0`;
        document.getElementById('checkOrderSubtotal').innerText = 'Subtotal: ₱' + subtotal;
        document.getElementById('orderTotal').innerText = 'Total: ₱' + total;
        new bootstrap.Modal(document.getElementById('checkoutModal')).show();
    }

    function updateChange() {
        const paid = Number(document.getElementById('amountPaid').value);
        const total = checkoutTotal();
        document.getElementById('changeText').innerText =
            paid >= total ? `Change: ₱${(paid - total).toFixed(2)}` : 'Insufficient payment';
        document.getElementById('orderTotal').innerText = 'Total: ₱' + total;
    }

    function finalizeCheckout() {
        const customer = document.getElementById('customerName').value.trim();
        if (!customer) {
            alert('Please enter customer name!');
            return;
        }

        const paid = Number(document.getElementById('amountPaid').value);
        const total = checkoutTotal();
        if (paid < total) {
            alert('Insufficient payment!');
            return;
        }

        const change = (paid - total).toFixed(2);

        const orderData = {
            cart: cart,
            orderedBy: customer,
            totalAmount: total,
            paid: paid
        };

        fetch('placeorder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const toastEl = document.getElementById('paymentToast');
                    const toastBody = document.getElementById('paymentToastBody');
                    toastBody.innerHTML = `<strong>Payment Successful</strong><br>
                                            Customer: ${customer}<br>
                                            Order ID: #${data.order_id}<br><br>
                                            <button class="btn btn-light btn-sm"
                                            style="background-color:#e3cfc5;color:#000;" onclick="openReceipt(${data.order_id})">View Receipt</button>`;
                    const toast = new bootstrap.Toast(toastEl, {
                        delay: 4000
                    });
                    toast.show();

                    cart = [];
                    updateSubtotal();
                    loadProducts(document.getElementById('categoryTitle').innerText);
                    bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
                } else {
                    const toastEl = document.getElementById('paymentToast');
                    const toastBody = document.getElementById('paymentToastBody');
                    toastBody.innerText = `Error placing order: ${data.message}`;
                    const toast = new bootstrap.Toast(toastEl, {
                        delay: 6000
                    });
                    toast.show();
                }
            })
            .catch(err => {
                const toastEl = document.getElementById('paymentToast');
                const toastBody = document.getElementById('paymentToastBody');
                toastBody.innerText = `Network error: ${err}`;
                const toast = new bootstrap.Toast(toastEl, {
                    delay: 6000
                });
                toast.show();
            });
    }

    function openReceipt(orderId) {
        window.open(`receipt.php?order_id=${orderId}`, '_blank');
    }
</script>

<body style="font-family: KeiJi, sans-serif; font-weight: 600;">

    <nav class="navbar navbar-expand bg-brown fixed-top px-3">
        <div class="container-fluid">
            <a class="navbar-brand">
                <img src="assets/logonotext.png" alt="Manhattan Café" height="40">
                <a style="font-family:HonyaJi"><span class="navbar-brand text-white fw-bold">manhattan cafe</span></a>
            </a>

            <div class="d-flex align-items-center ms-auto gap-3">
                <span class="text-white" style="font-family: KeiJi, sans-serif; font-weight: 580;">Logged in as <?php echo $_SESSION['sessionUser']; ?></span>
                <button class="btn text-white fs-3" onclick="toggleMenu()" style="font-family:Arial, Helvetica, sans-serif">☰</button>
            </div>
        </div>
    </nav>

    <div id="menuPanel" class="menu-panel">
        <?php if ($_SESSION['userType'] === 'admin'): ?>
            <a href="dashboard.php">Sales Dashboard</a>
            <a href="modifyproducts.php">Modify Products</a>
        <?php endif; ?>
        <a href="login.html">Log Out</a>
    </div>

    <div class="container-fluid main-layout">
        <div class="row h-100">

            <aside class="col-2 categories" style="font-family: KeiJi, sans-serif; font-weight: 600;">
                <h5 class="text-center mb-3" style="font-weight: bold;">Categories</h5>
                <button class="category-btn" onclick="loadProducts('Coffee', this)">Coffee</button>
                <button class="category-btn" onclick="loadProducts('Pizza', this)">Pizza</button>
                <button class="category-btn" onclick="loadProducts('Burger', this)">Burger</button>
                <button class="category-btn" onclick="loadProducts('Pastries', this)">Pastries</button>
                <button class="category-btn" onclick="loadProducts('Sweets', this)">Sweets</button>
            </aside>

            <main class="col-10 content">
                <h3 class="mb-3" id="categoryTitle" style="font-family: KeiJi, sans-serif; font-weight: 620;">Coffee</h3>
                <div class="row g-4" id="productGrid"> </div>
            </main>

        </div>
    </div>

    <div class="bottom-bar">
        <span id="subtotalText" style="font-family: KeiJi, sans-serif; font-weight: 580;">Order Subtotal: ₱0</span>
        <div>
            <button class="action-btn" onclick="clearOrder()" style="font-family: KeiJi, sans-serif; font-weight: 580;">Clear Order</button>
            <button class="action-btn" onclick="seeOrder()" style="font-family: KeiJi, sans-serif; font-weight: 580;">See Order</button>
            <button class="action-btn btn-primary" onclick="openCheckout()" style="font-family: KeiJi, sans-serif; font-weight: 580;">Check Out</button>
        </div>
    </div>

    <div class="modal fade" id="qtyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3 text-center" style="max-width: 300px; margin: auto; background-color: #cbc0b5;">

                <img id="qtyProductImage" src="" alt="Product Image"
                    class="img-fluid mb-3 rounded"
                    style="width: 150px; height: 150px; object-fit: cover; aspect-ratio: 1/1; margin: 0 auto;">

                <h5 id="qtyProductName" class="fw-bold mb-1"></h5>

                <div id="qtyProductPrice" class="mb-3 text-muted" style="color: #8b5a2b !important;"></div>

                <div class="d-flex align-items-center justify-content-center gap-3 mb-4">
                    <button class="btn btn-secondary btn-sm" onclick="changeQty(-1)"
                        style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-dash"></i>
                    </button>
                    <span id="qtyValue" class="fs-4 fw-bold" style="min-width: 40px;">1</span>
                    <button class="btn btn-secondary btn-sm" onclick="changeQty(1)"
                        style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>

                <button class="btn w-100" style="background-color: #8b5a2b; color: white; border: none; border-radius: 8px; padding: 10px;"
                    onclick="confirmQty()">Confirm</button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="orderModal" style="font-family: KeiJi, sans-serif; font-weight: 580;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content p-4" style="background-color: #cbc0b5ff;">
                <h5 class="mb-3 text-center fw-bold">Current Order</h5>
                <div id="orderList" class="row g-3"></div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <strong id="orderSubtotal" class="fs-5"></strong>
                    <button class="btn btn-secondary" style="background-color: #ab8b7bff;" onclick="bootstrap.Modal.getInstance(document.getElementById('orderModal')).hide()">Close</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3" style="background-color: #cbc0b5ff;">
                <h5 class="mb-3 text-center fw-bold">Checkout</h5>
                <div class="mb-3">
                    <input type="text" id="customerName" class="form-control" placeholder="Enter customer name">
                </div>
                <div class="mb-3">
                    <input type="number" id="amountPaid" class="form-control" placeholder="Enter amount paid" oninput="updateChange()">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="" id="discountCheck" onchange="updateChange()">
                    <label class="form-check-label" for="discountCheck">
                        Apply 20% discount
                    </label>
                </div>
                <strong id="checkOrderSubtotal">Order Subtotal: ₱0</strong>
                <strong id="orderTotal">Order Total: ₱0</strong>
                <strong id="changeText">Change: ₱0</strong>
                <div class="mt-3 d-flex justify-content-end gap-2">
                    <button class="btn btn-secondary" data-bs-dismiss="modal" type="reset">Cancel</button>
                    <button class="btn btn-primary" style="background-color: #ab8b7bff;" onclick="finalizeCheckout()" type="reset">Confirm Order</button>
                </div>
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
        <div id="paymentToast" class="toast toast-custom align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="paymentToastBody">
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>



    <script>
        function toggleMenu() {
            const panel = document.getElementById('menuPanel');
            panel.classList.toggle('show');
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const coffeeBtn = document.querySelector('.category-btn');
            loadProducts('Coffee', coffeeBtn);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>