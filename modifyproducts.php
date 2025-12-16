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
if ($conn == false) die(print_r(sqlsrv_errors(), true));

if (isset($_GET['action']) && $_GET['action'] == 'getProducts') {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $sort = $_GET['sort'] ?? 'PRODUCT_ID';
    $dir = $_GET['dir'] ?? 'asc';

    $allowedSortColumns = ['PRODUCT_ID', 'PRODUCT_NAME', 'CATEGORY', 'PRICE'];
    if (!in_array($sort, $allowedSortColumns)) {
        $sort = 'PRODUCT_ID';
    }

    $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

    $sql = "SELECT PRODUCT_ID, PRODUCT_NAME, CATEGORY, PRICE, IMAGE_NAME 
            FROM CAFEPRODUCTS 
            WHERE (PRODUCT_NAME LIKE ? OR CATEGORY LIKE ?)";

    $params = ["%$search%", "%$search%"];

    if ($category && $category !== 'all') {
        $sql .= " AND CATEGORY = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY $sort $dir";

    $query = sqlsrv_query($conn, $sql, $params);

    $products = [];
    while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
        $products[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($products);
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manhattan Café</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="modifyproductstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logonotext.png">
</head>

<body style="font-family: KeiJi, sans-serif; font-weight: 600;">

    <!-- navbar -->
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

    <div id="toast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>

    <div id="menuPanel" class="menu-panel">
        <a href="takeorders.php">Take Orders</a>
        <a href="dashboard.php">Sales Dashboard</a>
        <a href="login.html">Log Out</a>
    </div>

    <div class="container mt-5 pt-4">
        <br>
        <h2 class="mb-4">Modify Products</h2>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex gap-2 w-75">
                <div class="input-group">
                    <span class="input-group-text bg-brown text-white border-brown">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search products by name or category..." oninput="loadProducts()">
                </div>
                <div class="category-dropdown">
                    <button class="btn btn-brown dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-filter me-2"></i>Filter by Category
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="categoryDropdown">
                        <li><a class="dropdown-item" href="#" onclick="setCategory('all')">All Categories</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#" onclick="setCategory('Coffee')">Coffee</a></li>
                        <li><a class="dropdown-item" href="#" onclick="setCategory('Pizza')">Pizza</a></li>
                        <li><a class="dropdown-item" href="#" onclick="setCategory('Burger')">Burger</a></li>
                        <li><a class="dropdown-item" href="#" onclick="setCategory('Pastries')">Pastries</a></li>
                        <li><a class="dropdown-item" href="#" onclick="setCategory('Sweets')">Sweets</a></li>
                    </ul>
                </div>
            </div>
            <button class="btn btn-brown" onclick="openAddModal()">
                <i class="bi bi-plus-circle me-2"></i>Add Product
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-cafe">
                    <tr>
                        <th onclick="sortTable('PRODUCT_ID')" style="cursor: pointer;">Product ID</th>
                        <th>Image</th>
                        <th onclick="sortTable('PRODUCT_NAME')" style="cursor: pointer;">Product Name</th>
                        <th onclick="sortTable('CATEGORY')" style="cursor: pointer;">Category</th>
                        <th onclick="sortTable('PRICE')" style="cursor: pointer;">Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productTable">
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modifyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    
                    <input type="hidden" id="modifyProductId">

                    <h4 class="modal-title" id="modifyModalTitle">Modify Product</h4>

                    <div class="text-center mb-4 image-preview-container">
                        <img id="modifyImagePreview" src="" class="img-thumbnail clickable-image" style="width: 200px; height: 200px; object-fit: cover;" onclick="document.getElementById('modifyImage').click()">
                        <input type="file" id="modifyImage" class="d-none" accept="image/*" onchange="previewModifyImage(event)">
                        <div class="form-text mt-2">Click image to change</div>
                    </div>

                    <div class="mb-3">
                        <label for="modifyName" class="form-label">Product Name</label>
                        <input type="text" id="modifyName" class="form-control" placeholder="Enter product name">
                    </div>
                    <div class="mb-3">
                        <label for="modifyCategory" class="form-label">Category</label>
                        <select id="modifyCategory" class="form-select">
                            <option value="">Select Category</option>
                            <option value="Coffee">Coffee</option>
                            <option value="Pizza">Pizza</option>
                            <option value="Burger">Burger</option>
                            <option value="Pastries">Pastries</option>
                            <option value="Sweets">Sweets</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modifyPrice" class="form-label">Price (₱)</label>
                        <input type="number" id="modifyPrice" class="form-control" placeholder="0.00" step="0.01" min="0">
                    </div>

                    <div class="button-container">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-brown" onclick="saveProductChanges()">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="removeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    
                    <input type="hidden" id="removeProductId">

                    <div class="text-center mb-4">
                        <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: #8b5a2b;"></i>
                    </div>

                    <h4 class="modal-title">Remove Product</h4>

                    <p id="removeProductMessage" class="text-center fs-5 mb-4">Are you sure you want to remove this product?</p>

                    <div class="button-container">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                        <button type="button" class="btn btn-danger" onclick="confirmRemove()">Yes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">

                    <h4 class="modal-title">Add New Product</h4>

                    <div class="text-center mb-4 image-preview-container">
                        <img id="addImagePreview" src="assets/default-product.png" class="img-thumbnail clickable-image" style="width: 200px; height: 200px; object-fit: cover;" onclick="document.getElementById('addImage').click()">
                        <input type="file" id="addImage" class="d-none" accept="image/*" onchange="previewAddImage(event)">
                        <div class="form-text mt-2">Click image to upload</div>
                    </div>

                    <div class="mb-3">
                        <label for="addName" class="form-label">Product Name</label>
                        <input type="text" id="addName" class="form-control" placeholder="Enter product name">
                    </div>
                    <div class="mb-3">
                        <label for="addCategory" class="form-label">Category</label>
                        <select id="addCategory" class="form-select">
                            <option value="">Select Category</option>
                            <option value="Coffee">Coffee</option>
                            <option value="Pizza">Pizza</option>
                            <option value="Burger">Burger</option>
                            <option value="Pastries">Pastries</option>
                            <option value="Sweets">Sweets</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addPrice" class="form-label">Price (₱)</label>
                        <input type="number" id="addPrice" class="form-control" placeholder="0.00" step="0.01" min="0">
                    </div>

                    <div class="button-container">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-brown" onclick="addNewProduct()">Add Product</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.head.insertAdjacentHTML('beforeend', '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">');

        let toast = null;
        let selectedCategory = 'all';

        function toggleMenu() {
            document.getElementById('menuPanel').classList.toggle('show');
        }

        function showToast(message, type = 'success') {
            const toastEl = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');

            toastMessage.textContent = message;
            toastEl.style.backgroundColor = '#b08c6a';
            toastEl.style.color = 'white';

            if (!toast) {
                toast = new bootstrap.Toast(toastEl);
            }
            toast.show();
        }

        function setCategory(category) {
            selectedCategory = category;
            document.getElementById('categoryDropdown').innerHTML = `
                <i class="bi bi-filter me-2"></i>${category === 'all' ? 'All Categories' : category}
            `;
            loadProducts();
        }

        function loadProducts() {
            const search = document.getElementById('searchInput').value;

            fetch(`modifyproducts.php?action=getProducts&search=${encodeURIComponent(search)}&category=${encodeURIComponent(selectedCategory)}`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(products => {
                    const table = document.getElementById('productTable');
                    table.innerHTML = '';

                    if (products.length === 0) {
                        table.innerHTML = `
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-box-seam display-6 d-block mb-2"></i>
                                    No products found
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    products.forEach(p => {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td>${p.PRODUCT_ID}</td>
                                        <td>
                                            <img src="productimages/${p.IMAGE_NAME || 'default-product.png'}" 
                                                class="product-image" 
                                                alt="${p.PRODUCT_NAME}"
                                                onerror="this.src='assets/default-product.png'">
                                        </td>
                                        <td>${p.PRODUCT_NAME}</td>
                                        <td><span class="badge" style="background-color: #8b5a2b;">${p.CATEGORY}</span></td>
                                        <td>₱${parseFloat(p.PRICE).toFixed(2)}</td>
                                        <td>
                                            <button class="btn btn-sm me-2" style="background-color: #ab8b7b; color: white; border: none;" 
                                                    onclick="openModifyModal(${p.PRODUCT_ID}, '${p.PRODUCT_NAME.replace(/'/g, "\\'")}', '${p.CATEGORY.replace(/'/g, "\\'")}', ${p.PRICE}, '${p.IMAGE_NAME}')">
                                                <i class="bi bi-pencil me-1"></i> Modify
                                            </button>
                                            <button class="btn btn-sm" style="background-color: #8b5a2b; color: white; border: none;" 
                                                    onclick="openRemoveModal(${p.PRODUCT_ID}, '${p.PRODUCT_NAME.replace(/'/g, "\\'")}')">
                                                <i class="bi bi-trash me-1"></i> Remove
                                            </button>
                                        </td>`;
                        table.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    showToast('Error loading products', 'error');
                });
        }


        let currentSortColumn = 'PRODUCT_ID';
        let sortDirection = 'asc';
        function sortTable(column) {
            if (currentSortColumn === column) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortColumn = column;
                sortDirection = 'asc';
            }

            updateSortIndicators();

            loadProducts();
        }

        function updateSortIndicators() {
            document.querySelectorAll('thead th').forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');
                const existingArrow = th.querySelector('.sort-arrow');
                if (existingArrow) {
                    existingArrow.remove();
                }
            });

            const headers = document.querySelectorAll('thead th');
            let targetHeader = null;

            headers.forEach((th, index) => {
                const text = th.textContent.trim();

                if ((text === 'Product ID' && currentSortColumn === 'PRODUCT_ID') ||
                    (text === 'Product Name' && currentSortColumn === 'PRODUCT_NAME') ||
                    (text === 'Category' && currentSortColumn === 'CATEGORY') ||
                    (text === 'Price' && currentSortColumn === 'PRICE')) {
                    targetHeader = th;

                    th.classList.add(sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');

                    const arrow = document.createElement('span');
                    arrow.className = 'sort-arrow ms-1';
                    arrow.innerHTML = sortDirection === 'asc' ? '↑' : '↓';
                    th.appendChild(arrow);
                }
            });
        }

        function loadProducts() {
            const search = document.getElementById('searchInput').value;

            fetch(`modifyproducts.php?action=getProducts&search=${encodeURIComponent(search)}&category=${encodeURIComponent(selectedCategory)}&sort=${encodeURIComponent(currentSortColumn)}&dir=${encodeURIComponent(sortDirection)}`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(products => {
                    const table = document.getElementById('productTable');
                    table.innerHTML = '';

                    if (products.length === 0) {
                        table.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-box-seam display-6 d-block mb-2"></i>
                            No products found
                        </td>
                    </tr>
                `;
                        return;
                    }

                    products.forEach(p => {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td>${p.PRODUCT_ID}</td>
                                        <td>
                                            <img src="productimages/${p.IMAGE_NAME || 'default-product.png'}" 
                                                class="product-image" 
                                                alt="${p.PRODUCT_NAME}"
                                                onerror="this.src='assets/default-product.png'">
                                        </td>
                                        <td>${p.PRODUCT_NAME}</td>
                                        <td><span class="badge" style="background-color: #8b5a2b;">${p.CATEGORY}</span></td>
                                        <td>₱${parseFloat(p.PRICE).toFixed(2)}</td>
                                        <td>
                                            <button class="btn btn-sm me-2" style="background-color: #ab8b7b; color: white; border: none;" 
                                                    onclick="openModifyModal(${p.PRODUCT_ID}, '${p.PRODUCT_NAME.replace(/'/g, "\\'")}', '${p.CATEGORY.replace(/'/g, "\\'")}', ${p.PRICE}, '${p.IMAGE_NAME}')">
                                                <i class="bi bi-pencil me-1"></i> Modify
                                            </button>
                                            <button class="btn btn-sm" style="background-color: #8b5a2b; color: white; border: none;" 
                                                    onclick="openRemoveModal(${p.PRODUCT_ID}, '${p.PRODUCT_NAME.replace(/'/g, "\\'")}')">
                                                <i class="bi bi-trash me-1"></i> Remove
                                            </button>
                                        </td>`;
                        table.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    showToast('Error loading products', 'error');
                });
        }

        function openModifyModal(id, name, category, price, imageName) {
            document.getElementById('modifyProductId').value = id;
            document.getElementById('modifyName').value = name;
            document.getElementById('modifyCategory').value = category;
            document.getElementById('modifyPrice').value = price;

            const imagePreview = document.getElementById('modifyImagePreview');
            imagePreview.src = imageName ? `productimages/${imageName}` : 'assets/default-product.png';
            imagePreview.alt = name;

            document.getElementById('modifyModalTitle').textContent = `Modify ${name}`;

            document.getElementById('modifyImage').value = '';

            const modal = new bootstrap.Modal(document.getElementById('modifyModal'));
            modal.show();
        }

        function previewModifyImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                document.getElementById('modifyImagePreview').src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        function saveProductChanges() {
            const id = document.getElementById('modifyProductId').value;
            const name = document.getElementById('modifyName').value.trim();
            const category = document.getElementById('modifyCategory').value;
            const price = document.getElementById('modifyPrice').value;
            const imageFile = document.getElementById('modifyImage').files[0];

            if (!name || !category || !price) {
                showToast('Please fill in all required fields', 'error');
                return;
            }

            if (price <= 0) {
                showToast('Price must be greater than 0', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('id', id);
            formData.append('name', name);
            formData.append('category', category);
            formData.append('price', parseFloat(price).toFixed(2));
            if (imageFile) {
                formData.append('image', imageFile);
            }

            fetch('modify.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        showToast(`Product "${name}" has been modified`);
                        loadProducts();
                        bootstrap.Modal.getInstance(document.getElementById('modifyModal')).hide();
                    } else {
                        showToast(data.message || 'Error saving product', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error saving product changes', 'error');
                });
        }

        function openRemoveModal(id, name) {
            document.getElementById('removeProductId').value = id;
            document.getElementById('removeProductMessage').textContent = `Are you sure you want to remove the "${name}" product?`;

            const modal = new bootstrap.Modal(document.getElementById('removeModal'));
            modal.show();
        }

        function confirmRemove() {
            const id = document.getElementById('removeProductId').value;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('modify.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(data => {
                    console.log('Delete response:', data);
                    if (data.status === 'success') {
                        showToast('Product has been removed');
                        loadProducts();
                        bootstrap.Modal.getInstance(document.getElementById('removeModal')).hide();
                    } else {
                        showToast(data.message || 'Error removing product', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error removing product: ' + error.message, 'error');
                });
        }
        function openAddModal() {
            document.getElementById('addName').value = '';
            document.getElementById('addCategory').value = '';
            document.getElementById('addPrice').value = '';
            document.getElementById('addImage').value = '';
            document.getElementById('addImagePreview').src = 'assets/default-product.png';

            const modal = new bootstrap.Modal(document.getElementById('addModal'));
            modal.show();
        }

        function previewAddImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                document.getElementById('addImagePreview').src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        function addNewProduct() {
            const name = document.getElementById('addName').value.trim();
            const category = document.getElementById('addCategory').value;
            const price = document.getElementById('addPrice').value;
            const imageFile = document.getElementById('addImage').files[0];

            if (!name || !category || !price) {
                showToast('Please fill in all required fields', 'error');
                return;
            }

            if (price <= 0) {
                showToast('Price must be greater than 0', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('name', name);
            formData.append('category', category);
            formData.append('price', parseFloat(price).toFixed(2));
            if (imageFile) {
                formData.append('image', imageFile);
            }

            fetch('modify.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        showToast(`Product "${name}" has been created`);
                        loadProducts();
                        bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
                    } else {
                        showToast(data.message || 'Error adding product', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error in addNewProduct:', error);
                    showToast('Error adding product. Please try again.', 'error');
                });
        }

        document.addEventListener('DOMContentLoaded', loadProducts);
    </script>
</body>

</html>