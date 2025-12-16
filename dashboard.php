<?php
session_start();

$serverName = "LAPTOP-T6HO0TLL\\SQLEXPRESS";
$connectionOptions = [
  "Database" => "DLSU",
  "Uid" => "",
  "PWD" => ""
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) die(print_r(sqlsrv_errors(), true));

$sql = "SELECT 
          o.PARENT_ORDER_ID,
          CONVERT(date, o.DATE_ORDERED) AS ORDER_DATE,
          p.PRODUCT_NAME,
          o.QUANTITY,
          o.ORDERED_BY,
          o.PLACED_BY,
          (o.QUANTITY * p.PRICE) AS LINE_TOTAL,
          o.TOTAL_AMOUNT
        FROM CAFEORDERS o
        JOIN CAFEPRODUCTS p ON o.PRODUCT_ID = p.PRODUCT_ID
        ORDER BY o.PARENT_ORDER_ID DESC, p.PRODUCT_NAME";

$query = sqlsrv_query($conn, $sql);
$orders = [];

while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
  $orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>Manhattan Café</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

  <link rel="stylesheet" href="dashboardstyle.css">
  <link rel="icon" type="image/png" href="assets/logonotext.png">
</head>

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
    <a href="takeorders.php">Take Orders</a>
    <a href="modifyproducts.php">Modify Products</a>
    <a href="login.html">Log Out</a>
  </div>

  <div class="container-fluid main-layout">

    <section class="pt-4 px-4 chart-section">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Sales Overview</h3>
        <div class="btn-group cafe-range-btns">
          <button class="btn" onclick="filterRange('today')">Today</button>
          <button class="btn" onclick="filterRange('week')">This Week</button>
          <button class="btn" onclick="filterRange('month')">This Month</button>
          <button class="btn" onclick="filterRange('all')">All</button>
        </div>
      </div>
      <div class="card p-3 shadow-sm">
        <canvas id="salesChart"></canvas>
      </div>
      <div class="total-sales-summary mt-4">
        <div class="row g-3">
          <div class="col-md-4">
            <div class="card summary-card text-center p-3" style="background-color: #e3cfc5; border: none; border-radius: 10px;">
              <h6 class="text-muted mb-2">Total Orders</h6>
              <h3 id="totalOrdersCount" class="fw-bold" style="color: #8b5a2b;">0</h3>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card summary-card text-center p-3" style="background-color: #d2b49c; border: none; border-radius: 10px;">
              <h6 class="text-muted mb-2">Total Items Sold</h6>
              <h3 id="totalItemsSold" class="fw-bold" style="color: #8b5a2b;">0</h3>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card summary-card text-center p-3" style="background-color: #b08c6a; border: none; border-radius: 10px;">
              <h6 class="text-muted mb-2">Total Sales</h6>
              <h3 id="totalSalesAmount" class="fw-bold">₱0.00</h3>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="px-4 mt-5">
      <h4 class="mb-3">Sales Records</h4>

      <div class="row mb-3 g-2">
        <div class="col-md-3">
          <div class="input-group">
            <span class="input-group-text bg-brown text-white border-brown">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" class="form-control" id="searchInput" placeholder="Search records...">
          </div>
        </div>

        <div class="col-md-3">
          <select class="form-select" id="columnFilter">
            <option value="all">All Columns</option>
            <option value="0">Order ID</option>
            <option value="1">Date</option>
            <option value="2">Product</option>
            <option value="3">Quantity</option>
            <option value="4">Ordered By</option>
            <option value="5">Placed By</option>
            <option value="6">Total</option>
          </select>
        </div>

        <div class="col-md-3">
          <input type="date" class="form-control" id="startDate" placeholder="Start Date">
        </div>
        <div class="col-md-3">
          <input type="date" class="form-control" id="endDate" placeholder="End Date">
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle" id="salesTable">
          <thead class="table-cafe">
            <tr>
              <th onclick="sortTable(0)" style="cursor: pointer;">Order ID <span class="sort-indicator"></span></th>
              <th onclick="sortTable(1)" style="cursor: pointer;">Date <span class="sort-indicator"></span></th>
              <th onclick="sortTable(2)" style="cursor: pointer;">Product <span class="sort-indicator"></span></th>
              <th onclick="sortTable(3)" style="cursor: pointer;">Quantity <span class="sort-indicator"></span></th>
              <th onclick="sortTable(4)" style="cursor: pointer;">Ordered By <span class="sort-indicator"></span></th>
              <th onclick="sortTable(5)" style="cursor: pointer;">Placed By <span class="sort-indicator"></span></th>
              <th onclick="sortTable(6)" style="cursor: pointer;">Total <span class="sort-indicator"></span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr class="order-row" data-order-id="<?= $order['PARENT_ORDER_ID'] ?>">
                <td>#<?= $order['PARENT_ORDER_ID'] ?></td>
                <td><?= $order['ORDER_DATE']->format('Y-m-d') ?></td>
                <td><?= htmlspecialchars($order['PRODUCT_NAME']) ?></td>
                <td>
                  <span class="badge" style="background-color: #8b5a2b;"><?= $order['QUANTITY'] ?></span>
                </td>
                <td><?= htmlspecialchars($order['ORDERED_BY']) ?></td>
                <td><?= htmlspecialchars($order['PLACED_BY']) ?></td>
                <td>
                  <span class="fw-bold" style="color: #8b5a2b;">₱<?= number_format($order['LINE_TOTAL'], 2) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>
      <nav>
        <ul class="pagination justify-content-center mt-3" id="pagination"></ul>
      </nav>
    </section>
  </div>

  <script>
    function toggleMenu() {
      document.getElementById('menuPanel').classList.toggle('show');
    }

    let sortDirections = {};
    let currentPage = 1;
    const rowsPerPage = 5;

    let currentSortColumn = null;
    let sortDirection = 'asc';

    function sortTable(colIndex) {
      const table = document.getElementById('salesTable');
      const tbody = table.tBodies[0];

      document.querySelectorAll('#salesTable thead th .sort-indicator').forEach(indicator => {
        indicator.textContent = '';
      });

      if (currentSortColumn === colIndex) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        currentSortColumn = colIndex;
        sortDirection = 'asc';
      }

      const header = table.tHead.rows[0].cells[colIndex];
      const indicator = header.querySelector('.sort-indicator');
      indicator.textContent = sortDirection === 'asc' ? '▲' : '▼';

      document.querySelectorAll('#salesTable thead th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
      });

      header.classList.add(sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');

      const rows = Array.from(tbody.rows)
        .filter(row => row.dataset.visible !== 'false');

      rows.sort((a, b) => {
        let A = a.cells[colIndex].innerText
          .replace(/[₱,#]/g, '')
          .trim()
          .toLowerCase();
        let B = b.cells[colIndex].innerText
          .replace(/[₱,#]/g, '')
          .trim()
          .toLowerCase();

        const isNumber = !isNaN(parseFloat(A)) && !isNaN(parseFloat(B));

        if (isNumber) {
          return sortDirection === 'asc' ?
            parseFloat(A) - parseFloat(B) :
            parseFloat(B) - parseFloat(A);
        } else {
          if (colIndex === 1) {
            const dateA = new Date(A);
            const dateB = new Date(B);
            return sortDirection === 'asc' ?
              dateA - dateB :
              dateB - dateA;
          }
          return sortDirection === 'asc' ?
            A.localeCompare(B) :
            B.localeCompare(A);
        }
      });

      rows.forEach(row => tbody.appendChild(row));

      currentPage = 1;
      paginate();
    }

    function filterRows() {
      const search = document.getElementById('searchInput').value.toLowerCase();
      const column = document.getElementById('columnFilter').value;
      const start = document.getElementById('startDate').value;
      const end = document.getElementById('endDate').value;

      const rows = Array.from(document.querySelectorAll('#salesTable tbody tr'));

      rows.forEach(row => {
        let matchSearch = true;

        if (search) {
          if (column === 'all') {
            matchSearch = row.innerText.toLowerCase().includes(search);
          } else {
            matchSearch = row.cells[column].innerText.toLowerCase().includes(search);
          }
        }

        let matchDate = true;
        if (start || end) {
          const dateStr = row.cells[1].innerText;

          const rowDate = new Date(dateStr);
          const startDate = start ? new Date(start) : null;
          const endDate = end ? new Date(end) : null;

          matchDate = true;

          if (startDate) {
            startDate.setHours(0, 0, 0, 0);
            matchDate = matchDate && rowDate >= startDate;
          }

          if (endDate) {
            endDate.setHours(23, 59, 59, 999);
            matchDate = matchDate && rowDate <= endDate;
          }
        }

        const visible = matchSearch && matchDate;

        row.dataset.visible = visible;
      });

      currentPage = 1;
      paginate();
      updateChart();
    }

    function filterRange(type) {
      document.querySelectorAll('.cafe-range-btns .btn')
        .forEach(b => b.classList.remove('active'));

      event.target.classList.add('active');

      const today = new Date();

      document.getElementById('searchInput').value = '';
      document.getElementById('columnFilter').value = 'all';

      if (type === 'today') {
        const start = formatDate(today);
        document.getElementById('startDate').value = start;
        document.getElementById('endDate').value = start;
      } else if (type === 'week') {
        const first = new Date(today);
        const day = today.getDay();

        const diffToMonday = day === 0 ? -6 : 1 - day;
        first.setDate(today.getDate() + diffToMonday);
        const start = formatDate(first);

        const last = new Date(first);
        last.setDate(first.getDate() + 6);
        const end = formatDate(last);

        document.getElementById('startDate').value = start;
        document.getElementById('endDate').value = end;
      } else if (type === 'month') {
        const first = new Date(today.getFullYear(), today.getMonth(), 1);
        const start = formatDate(first);

        const last = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        const end = formatDate(last);

        document.getElementById('startDate').value = start;
        document.getElementById('endDate').value = end;
      } else if (type === 'all') {
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
      }

      filterRows();
    }

    function formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    function generateDateRange(startDate, endDate) {
      const dates = [];
      const current = new Date(startDate);
      const end = new Date(endDate);

      while (current <= end) {
        dates.push(formatDate(new Date(current)));
        current.setDate(current.getDate() + 1);
      }

      return dates;
    }

    function paginate() {
      const allRows = Array.from(document.querySelectorAll('#salesTable tbody tr'));
      const visibleRows = allRows.filter(r => r.dataset.visible !== 'false');

      const pagination = document.getElementById('pagination');
      pagination.innerHTML = '';

      const totalPages = Math.ceil(visibleRows.length / rowsPerPage);

      allRows.forEach(row => {
        row.style.display = 'none';
      });

      visibleRows.forEach((row, index) => {
        if (index >= (currentPage - 1) * rowsPerPage &&
          index < currentPage * rowsPerPage) {
          row.style.display = '';
        }
      });

      for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link">${i}</a>`;
        li.onclick = () => {
          currentPage = i;
          paginate();
          updateChart();
        };
        pagination.appendChild(li);
      }
    }

    function updateChart() {
      console.log("updateChart() called");

      const rows = Array.from(document.querySelectorAll('#salesTable tbody tr'))
        .filter(r => r.dataset.visible !== 'false');

      console.log("Total visible rows for chart:", rows.length);

      const dailyTotals = {};

      rows.forEach(row => {
        try {
          const dateCell = row.cells[1];
          const totalCell = row.cells[6];

          if (!dateCell || !totalCell) return;

          const date = dateCell.innerText.trim();
          const valueText = totalCell.innerText.replace('₱', '').replace(/,/g, '').trim();
          const value = parseFloat(valueText);

          if (date && !isNaN(value)) {
            dailyTotals[date] = (dailyTotals[date] || 0) + value;
          }
        } catch (e) {
          console.error('Error processing row:', e);
        }
      });

      console.log("Daily totals:", dailyTotals);

      const startDateInput = document.getElementById('startDate').value;
      const endDateInput = document.getElementById('endDate').value;

      let chartData = [];

      if (startDateInput && endDateInput) {
        const startDate = parseLocalDate(startDateInput);
        const endDate = parseLocalDate(endDateInput);

        const allDatesInRange = generateDateRange(startDate, endDate);

        chartData = allDatesInRange.map(date => ({
          x: date,
          y: dailyTotals[date] || 0
        }));

        console.log("Generated data for date range:", allDatesInRange.length, "days");
        console.log("Date range:", formatDate(startDate), "to", formatDate(endDate));
      } else {
        const sortedDates = Object.keys(dailyTotals).sort((a, b) => {
          return new Date(a) - new Date(b);
        });

        chartData = sortedDates.map(date => ({
          x: date,
          y: dailyTotals[date]
        }));
      }

      console.log("Chart data:", chartData);

      if (salesChart) {
        if (chartData.length === 1) {
          salesChart.config.type = 'bar';
          salesChart.config.options.scales.x.type = 'category';
        } else {
          salesChart.config.type = 'line';
          salesChart.config.options.scales.x.type = 'time';
        }

        salesChart.data.datasets[0].data = chartData;

        if (chartData.length > 0) {
          const maxValue = Math.max(...chartData.map(d => d.y));
          salesChart.config.options.scales.y.suggestedMax = maxValue * 1.1; // Add 10% padding
        }

        salesChart.update();
        console.log("Chart updated with", chartData.length, "data points");
      } else {
        console.error("salesChart is null!");
      }
    }

    function parseLocalDate(dateString) {
      const parts = dateString.split('-');
      return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    let salesChart = null;

    function initializeChart() {
      const ctx = document.getElementById('salesChart');

      if (!ctx) {
        console.error('Chart canvas not found');
        return;
      }

      if (salesChart) {
        salesChart.destroy();
      }

      const rows = Array.from(document.querySelectorAll('#salesTable tbody tr'));
      const initialData = [];
      const tempTotals = {};

      rows.forEach(row => {
        const date = row.cells[1].innerText.trim();
        const valueText = row.cells[6].innerText.replace('₱', '').replace(/,/g, '').trim();
        const value = parseFloat(valueText);

        if (date && !isNaN(value)) {
          tempTotals[date] = (tempTotals[date] || 0) + value;
        }
      });

      salesChart = new Chart(ctx, {
        type: 'line',
        data: {
          datasets: [{
            label: 'Sales (₱)',
            data: initialData,
            borderColor: '#8b5a2b',
            backgroundColor: 'rgba(139, 90, 43, 0.1)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 4,
            pointBackgroundColor: '#8b5a2b',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              type: 'time',
              time: {
                unit: 'day',
                displayFormats: {
                  day: 'MMM d'
                },
                tooltipFormat: 'MM-dd-yyyy'
              },
              title: {
                display: true,
                text: 'Date'
              }
            },
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Amount (₱)'
              },
              ticks: {
                callback: function(value) {
                  return '₱' + value.toLocaleString();
                }
              }
            }
          },
          plugins: {
            legend: {
              display: true,
              position: 'top'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return `₱${context.parsed.y.toLocaleString()}`;
                }
              }
            }
          }
        }
      });
    }

    function setDefaultWeekFilter() {
      const today = new Date();

      const first = new Date(today);
      const day = today.getDay();

      const diffToMonday = day === 0 ? -6 : 1 - day;
      first.setDate(today.getDate() + diffToMonday);
      const start = formatDate(first);

      const last = new Date(first);
      last.setDate(first.getDate() + 6);
      const end = formatDate(last);

      document.getElementById('startDate').value = start;
      document.getElementById('endDate').value = end;

      const weekButton = document.querySelector('.cafe-range-btns .btn:nth-child(2)'); // Second button
      if (weekButton) {
        weekButton.classList.add('active');
      }
    }

    document.addEventListener('DOMContentLoaded', function() {

      setDefaultWeekFilter();

      initializeChart();

      filterRows();

      document.getElementById('searchInput').addEventListener('input', function() {
        filterRows();
        setTimeout(updateChart, 100);
      });

      document.getElementById('startDate').addEventListener('change', function() {
        filterRows();
        setTimeout(updateChart, 100);
      });

      document.getElementById('endDate').addEventListener('change', function() {
        filterRows();
        setTimeout(updateChart, 100);
      });

      document.querySelectorAll('#salesTable tbody tr').forEach(row => {
        row.addEventListener('click', function() {
          const orderId = this.dataset.orderId;
          if (orderId) {
            window.open(`receipt.php?order_id=${orderId}`, '_blank');
          }
        });
      });

      paginate();

      let lastRowCount = document.querySelectorAll('#salesTable tbody tr').length;

      setInterval(() => {
        fetch('fetchorders.php')
          .then(res => res.json())
          .then(data => {
            console.log("Fetched Data:", data);
            if (data.length !== lastRowCount) {
              lastRowCount = data.length;
              rebuildTable(data);
              filterRows();
              updateChart();
            }
          })
          .catch(error => {
            console.error('Fetch error:', error);
          });
      }, 4000);
    });
    
    function updateTotalSalesSummary() {
      const rows = Array.from(document.querySelectorAll('#salesTable tbody tr'))
        .filter(r => r.dataset.visible !== 'false');

      let totalOrders = 0;
      let totalItems = 0;
      let totalSales = 0;

      const uniqueOrderIds = new Set();

      rows.forEach(row => {
        try {
          const orderIdCell = row.cells[0];
          const quantityCell = row.cells[3];
          const totalCell = row.cells[6];

          if (!orderIdCell || !quantityCell || !totalCell) return;

          const orderId = orderIdCell.innerText.replace('#', '').trim();
          uniqueOrderIds.add(orderId);

          const quantityText = quantityCell.innerText.replace(/\D/g, '');
          const quantity = parseInt(quantityText) || 0;
          totalItems += quantity;

          const totalText = totalCell.innerText.replace('₱', '').replace(/,/g, '').trim();
          const total = parseFloat(totalText) || 0;
          totalSales += total;

        } catch (e) {
          console.error('Error processing row for summary:', e);
        }
      });

      totalOrders = uniqueOrderIds.size;

      document.getElementById('totalOrdersCount').textContent = totalOrders;
      document.getElementById('totalItemsSold').textContent = totalItems.toLocaleString();
      document.getElementById('totalSalesAmount').textContent = `₱${totalSales.toFixed(2)}`;

      console.log("Summary updated:", {
        orders: totalOrders,
        items: totalItems,
        sales: totalSales
      });
    }

    function filterRows() {
      const search = document.getElementById('searchInput').value.toLowerCase();
      const column = document.getElementById('columnFilter').value;
      const start = document.getElementById('startDate').value;
      const end = document.getElementById('endDate').value;

      const rows = Array.from(document.querySelectorAll('#salesTable tbody tr'));

      rows.forEach(row => {
        let matchSearch = true;

        if (search) {
          if (column === 'all') {
            matchSearch = row.innerText.toLowerCase().includes(search);
          } else {
            matchSearch = row.cells[column].innerText.toLowerCase().includes(search);
          }
        }

        let matchDate = true;
        if (start || end) {
          const dateStr = row.cells[1].innerText;

          const rowDate = new Date(dateStr);
          const startDate = start ? new Date(start) : null;
          const endDate = end ? new Date(end) : null;

          matchDate = true;

          if (startDate) {
            startDate.setHours(0, 0, 0, 0);
            matchDate = matchDate && rowDate >= startDate;
          }

          if (endDate) {
            endDate.setHours(23, 59, 59, 999);
            matchDate = matchDate && rowDate <= endDate;
          }
        }

        const visible = matchSearch && matchDate;

        row.dataset.visible = visible;
      });

      currentPage = 1;
      paginate();
      updateChart();
      updateTotalSalesSummary();
    }

    function rebuildTable(data) {
      const tbody = document.querySelector('#salesTable tbody');
      tbody.innerHTML = '';

      data.forEach(o => {
        let dateString;
        if (typeof o.ORDER_DATE === 'object' && o.ORDER_DATE !== null) {
          if (o.ORDER_DATE.date) {
            dateString = o.ORDER_DATE.date.split(' ')[0];
          } else {
            dateString = new Date(o.ORDER_DATE).toISOString().split('T')[0];
          }
        } else if (o.ORDER_DATE) {
          dateString = o.ORDER_DATE.toString().split(' ')[0];
        } else {
          dateString = 'N/A';
        }

        const tr = document.createElement('tr');
        tr.className = 'order-row';
        tr.dataset.visible = 'true';
        tr.dataset.orderId = o.PARENT_ORDER_ID;

        tr.innerHTML = `
            <td>#${o.PARENT_ORDER_ID}</td>
            <td>${dateString}</td> 
            <td>${o.PRODUCT_NAME}</td>
            <td>${o.QUANTITY}</td>
            <td>${o.ORDERED_BY}</td>
            <td>${o.PLACED_BY}</td>
            <td>₱${parseFloat(o.LINE_TOTAL).toFixed(2)}</td>
        `;

        tbody.appendChild(tr);
      });

      filterRows();
      updateTotalSalesSummary();
    }

    document.addEventListener('DOMContentLoaded', function() {
      setDefaultWeekFilter();
      initializeChart();

      filterRows();

      updateTotalSalesSummary();

    });

    function updateChart() {
      console.log("updateChart() called");
      const rows = Array.from(document.querySelectorAll('#salesTable tbody tr'))
        .filter(r => r.dataset.visible !== 'false');

      console.log("Total visible rows for chart:", rows.length);

      const dailyTotals = {};

      rows.forEach(row => {
        try {
          const dateCell = row.cells[1];
          const totalCell = row.cells[6];

          if (!dateCell || !totalCell) return;

          const date = dateCell.innerText.trim();
          const valueText = totalCell.innerText.replace('₱', '').replace(/,/g, '').trim();
          const value = parseFloat(valueText);

          if (date && !isNaN(value)) {
            dailyTotals[date] = (dailyTotals[date] || 0) + value;
          }
        } catch (e) {
          console.error('Error processing row:', e);
        }
      });

      console.log("Daily totals:", dailyTotals);

      const startDateInput = document.getElementById('startDate').value;
      const endDateInput = document.getElementById('endDate').value;

      let chartData = [];

      if (startDateInput && endDateInput) {
        const startDate = parseLocalDate(startDateInput);
        const endDate = parseLocalDate(endDateInput);

        const allDatesInRange = generateDateRange(startDate, endDate);

        chartData = allDatesInRange.map(date => ({
          x: date,
          y: dailyTotals[date] || 0
        }));

        console.log("Generated data for date range:", allDatesInRange.length, "days");
        console.log("Date range:", formatDate(startDate), "to", formatDate(endDate));
      } else {
        const sortedDates = Object.keys(dailyTotals).sort((a, b) => {
          return new Date(a) - new Date(b);
        });

        chartData = sortedDates.map(date => ({
          x: date,
          y: dailyTotals[date]
        }));
      }

      console.log("Chart data:", chartData);

      if (salesChart) {
        if (chartData.length === 1) {
          salesChart.config.type = 'bar';
          salesChart.config.options.scales.x.type = 'category';
        } else {
          salesChart.config.type = 'line';
          salesChart.config.options.scales.x.type = 'time';
        }

        salesChart.data.datasets[0].data = chartData;

        if (chartData.length > 0) {
          const maxValue = Math.max(...chartData.map(d => d.y));
          salesChart.config.options.scales.y.suggestedMax = maxValue * 1.1;
        }

        salesChart.update();
        console.log("Chart updated with", chartData.length, "data points");
      } else {
        console.error("salesChart is null!");
      }

      updateTotalSalesSummary();
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>