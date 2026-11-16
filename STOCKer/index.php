<?php
require_once 'config.php';
require_login();
$pdo = db();
$pageTitle = 'Dashboard';

$stats = [
    'products'   => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_archived = 0')->fetchColumn(),
    'categories' => (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'suppliers'  => (int)$pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn(),
    'low'        => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_archived = 0 AND quantity <= reorder_level')->fetchColumn(),
    'value'      => (float)$pdo->query('SELECT COALESCE(SUM(quantity * cost_price), 0) FROM products WHERE is_archived = 0')->fetchColumn(),
];

$recent = $pdo->query(
    "SELECT sm.created_at, sm.movement_type, sm.quantity, p.name AS product_name, p.sku
     FROM stock_movements sm
     JOIN products p ON p.id = sm.product_id
     ORDER BY sm.id DESC LIMIT 6"
)->fetchAll();

$lowItems = $pdo->query(
    "SELECT id, name, sku, quantity, reorder_level
     FROM products
     WHERE is_archived = 0 AND quantity <= reorder_level
     ORDER BY quantity ASC LIMIT 5"
)->fetchAll();

$movementRows = $pdo->query(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') AS month_key,
            SUM(CASE WHEN movement_type='IN' THEN quantity ELSE 0 END) AS qty_in,
            SUM(CASE WHEN movement_type='OUT' THEN quantity ELSE 0 END) AS qty_out
     FROM stock_movements
     WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m-01')
     GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY month_key"
)->fetchAll();

$movementByMonth = [];
foreach ($movementRows as $row) $movementByMonth[$row['month_key']] = $row;

$monthLabels = $stockInData = $stockOutData = [];
$currentMonth = new DateTimeImmutable(date('Y-m-01'));
for ($i = 5; $i >= 0; $i--) {
    $m = $currentMonth->modify("-{$i} months");
    $k = $m->format('Y-m');
    $monthLabels[] = $m->format('M Y');
    $stockInData[]  = (int)($movementByMonth[$k]['qty_in']  ?? 0);
    $stockOutData[] = (int)($movementByMonth[$k]['qty_out'] ?? 0);
}

$categoryRows = $pdo->query(
    "SELECT COALESCE(c.name,'Unassigned') AS category_name, SUM(p.quantity) AS total_quantity
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_archived = 0 GROUP BY c.id, c.name HAVING SUM(p.quantity) > 0
     ORDER BY total_quantity DESC LIMIT 8"
)->fetchAll();

$categoryLabels = array_column($categoryRows, 'category_name');
$categoryValues = array_map('intval', array_column($categoryRows, 'total_quantity'));

require 'includes/header.php';
?>

<!-- Page heading row -->
<div class="page-actions">
  <div>
    <p style="margin:0;font-size:14px;color:var(--ink)">Welcome back, <b><?= e(current_user()) ?></b> &mdash; here is your inventory at a glance.</p>
  </div>
  <a class="btn primary" href="products.php?action=add">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Product
  </a>
</div>

<!-- KPI cards -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Active Products</span>
      <span class="stat-icon teal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      </span>
    </div>
    <div class="stat-number"><?= number_format($stats['products']) ?></div>
    <div class="stat-foot">Products in active catalog</div>
  </div>

  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Inventory Value</span>
      <span class="stat-icon purple">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </span>
    </div>
    <div class="stat-number" style="font-size:22px"><?= '&#8377;' . number_format($stats['value'], 2) ?></div>
    <div class="stat-foot">Total cost value of stock</div>
  </div>

  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Low Stock Alerts</span>
      <span class="stat-icon orange">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </span>
    </div>
    <div class="stat-number"><?= number_format($stats['low']) ?></div>
    <div class="stat-foot">At or below reorder level</div>
  </div>

  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Suppliers</span>
      <span class="stat-icon blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/></svg>
      </span>
    </div>
    <div class="stat-number"><?= number_format($stats['suppliers']) ?></div>
    <div class="stat-foot"><?= number_format($stats['categories']) ?> product categories</div>
  </div>
</div>

<!-- Charts -->
<div class="charts-grid">
  <article class="panel">
    <div class="panel-head">
      <div>
        <h3>Stock Movement Trend</h3>
        <p>Monthly stock-in vs stock-out (last 6 months)</p>
      </div>
      <span class="pill info">6 months</span>
    </div>
    <div class="chart-wrap"><canvas id="movementChart"></canvas></div>
  </article>

  <article class="panel">
    <div class="panel-head">
      <div>
        <h3>Stock by Category</h3>
        <p>Units available per category</p>
      </div>
    </div>
    <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
    <?php if (!$categoryRows): ?>
      <p class="empty-state">No stock data yet.</p>
    <?php endif; ?>
  </article>
</div>

<!-- Recent activity + Low stock watchlist -->
<div class="feature-grid">
  <article class="panel">
    <div class="panel-head">
      <div>
        <h3>Recent Stock Activity</h3>
        <p>Latest inventory movements</p>
      </div>
      <a href="stock.php" class="action-link">View all &rarr;</a>
    </div>
    <?php if (!$recent): ?>
      <p class="empty-state">No stock activity yet.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Product</th><th>Type</th><th>Qty</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><b><?= e($r['product_name']) ?></b><small class="subline"><?= e($r['sku']) ?></small></td>
            <td><span class="pill <?= $r['movement_type'] === 'IN' ? 'good' : 'warning' ?>"><?= $r['movement_type'] === 'IN' ? 'Stock In' : 'Stock Out' ?></span></td>
            <td><?= (int)$r['quantity'] ?></td>
            <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </article>

  <article class="panel">
    <div class="panel-head">
      <div>
        <h3>Low Stock Watchlist</h3>
        <p>Items needing replenishment</p>
      </div>
      <span class="pill warning"><?= $stats['low'] ?> alerts</span>
    </div>
    <?php if (!$lowItems): ?>
      <p class="empty-state" style="padding:20px 20px 16px">All products above reorder level.</p>
    <?php else: ?>
    <div class="watch-list">
      <?php foreach ($lowItems as $p): ?>
      <div class="watch-item">
        <span class="product-dot"><?= e(strtoupper(substr($p['name'],0,1))) ?></span>
        <div class="watch-name">
          <b><?= e($p['name']) ?></b>
          <small><?= e($p['sku']) ?></small>
        </div>
        <div class="watch-qty">
          <b><?= (int)$p['quantity'] ?></b>
          <small>Reorder: <?= (int)$p['reorder_level'] ?></small>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="inventory-value">
      <span>Total inventory value</span>
      <b><?= '&#8377;' . number_format($stats['value'], 2) ?></b>
    </div>
  </article>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Chart === 'undefined') return;

  const monthLabels  = <?= json_encode($monthLabels,  JSON_HEX_TAG|JSON_HEX_AMP) ?>;
  const stockIn      = <?= json_encode($stockInData,  JSON_HEX_TAG|JSON_HEX_AMP) ?>;
  const stockOut     = <?= json_encode($stockOutData, JSON_HEX_TAG|JSON_HEX_AMP) ?>;
  const catLabels    = <?= json_encode($categoryLabels, JSON_HEX_TAG|JSON_HEX_AMP) ?>;
  const catValues    = <?= json_encode($categoryValues, JSON_HEX_TAG|JSON_HEX_AMP) ?>;

  const lineDefaults = { fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 3 };

  new Chart(document.getElementById('movementChart'), {
    type: 'line',
    data: {
      labels: monthLabels,
      datasets: [
        { label: 'Stock In',  data: stockIn,  borderColor:'#0d9488', backgroundColor:'rgba(13,148,136,.1)', ...lineDefaults },
        { label: 'Stock Out', data: stockOut, borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,.08)', ...lineDefaults },
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode:'index', intersect: false },
      plugins: { legend: { position:'bottom', labels: { boxWidth:10, padding:14, font:{size:11} } } },
      scales: { y: { beginAtZero: true, ticks: { precision:0, font:{size:11} } }, x: { ticks: { font:{size:11} } } }
    }
  });

  new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
      labels: catLabels,
      datasets: [{ data: catValues,
        backgroundColor:['#0d9488','#3b82f6','#f59e0b','#8b5cf6','#ef4444','#10b981','#06b6d4','#f97316'],
        borderWidth: 2, borderColor:'#ffffff'
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout:'60%',
      plugins: { legend: { position:'bottom', labels:{ boxWidth:10, padding:12, font:{size:11} } } }
    }
  });
});
</script>

<?php require 'includes/footer.php'; ?>

