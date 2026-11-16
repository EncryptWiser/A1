<?php
require_once 'config.php';
require_login();
$pdo = db();

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    $stmt = $pdo->query("
        SELECT p.sku, p.name AS product_name,
               COALESCE(c.name,'Uncategorized') AS category_name,
               COALESCE(s.name,'No supplier') AS supplier_name,
               p.quantity, p.reorder_level,
               p.cost_price, p.selling_price,
               (p.quantity * p.cost_price) AS cost_value,
               (p.quantity * p.selling_price) AS retail_value,
               CASE WHEN p.quantity <= p.reorder_level THEN 'LOW STOCK' ELSE 'OK' END AS stock_status
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN suppliers s ON s.id = p.supplier_id
        WHERE p.is_archived = 0 ORDER BY p.name ASC
    ");
    $products = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="STOCKer_Report_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-store');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['SKU','Product','Category','Supplier','Qty','Reorder Level','Cost Price','Selling Price','Cost Value','Retail Value','Status']);
    $safe = fn($v) => preg_match('/^[\s]*[=+\-@]/u', (string)$v) ? "'".$v : $v;
    foreach ($products as $p) {
        fputcsv($out, [$safe($p['sku']),$safe($p['product_name']),$safe($p['category_name']),$safe($p['supplier_name']),
            (int)$p['quantity'],(int)$p['reorder_level'],
            number_format((float)$p['cost_price'],2,'.',''),number_format((float)$p['selling_price'],2,'.',''),
            number_format((float)$p['cost_value'],2,'.',''),number_format((float)$p['retail_value'],2,'.',''),
            $p['stock_status']]);
    }
    fclose($out);
    exit;
}

$summary = $pdo->query("
    SELECT COUNT(*) AS products,
           COALESCE(SUM(quantity),0) AS units,
           COALESCE(SUM(quantity * cost_price),0) AS cost_value,
           COALESCE(SUM(quantity * selling_price),0) AS retail_value
    FROM products WHERE is_archived = 0
")->fetch();

$byCategory = $pdo->query("
    SELECT COALESCE(c.name,'Uncategorized') AS category_name,
           COUNT(p.id) AS product_count,
           COALESCE(SUM(p.quantity),0) AS units,
           COALESCE(SUM(p.quantity * p.cost_price),0) AS cost_value
    FROM products p LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_archived = 0
    GROUP BY COALESCE(c.name,'Uncategorized') ORDER BY cost_value DESC
")->fetchAll();

$low = $pdo->query("
    SELECT p.name, p.sku, p.quantity, p.reorder_level, c.name AS category_name
    FROM products p LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_archived = 0 AND p.quantity <= p.reorder_level
    ORDER BY p.quantity ASC, p.name ASC
")->fetchAll();

$pageTitle = 'Reports';
require 'includes/header.php';
?>

<div class="page-actions report-actions">
  <p>Inventory valuation, category summaries and low-stock replenishment alerts.</p>
  <div class="btn-group">
    <a class="btn primary" href="reports.php?export=csv">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </a>
    <button class="btn secondary" type="button" onclick="window.print()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print / PDF
    </button>
  </div>
</div>

<div class="print-heading">
  <h1>STOCKer</h1>
  <h2>Inventory Report</h2>
  <p>Generated: <?= e(date('d M Y, h:i A')) ?></p>
</div>

<!-- KPI Summary -->
<div class="stats-grid three">
  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Active Products</span>
      <span class="stat-icon teal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      </span>
    </div>
    <div class="stat-number"><?= number_format((int)$summary['products']) ?></div>
    <div class="stat-foot">Archived products excluded</div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Units in Stock</span>
      <span class="stat-icon purple">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      </span>
    </div>
    <div class="stat-number"><?= number_format((int)$summary['units']) ?></div>
    <div class="stat-foot">Total units across all active products</div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Inventory Cost Value</span>
      <span class="stat-icon blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </span>
    </div>
    <div class="stat-number" style="font-size:22px">&#8377;<?= number_format((float)$summary['cost_value'],2) ?></div>
    <div class="stat-foot">Quantity &times; cost price</div>
  </div>
</div>

<!-- By Category -->
<article class="panel">
  <div class="panel-head">
    <div>
      <h3>Inventory by Category</h3>
      <p>Stock quantity and cost valuation per category</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Category</th><th>Products</th><th>Units</th><th>Cost Value</th></tr></thead>
      <tbody>
      <?php foreach ($byCategory as $r): ?>
        <tr>
          <td><b><?= e($r['category_name']) ?></b></td>
          <td><?= number_format((int)$r['product_count']) ?></td>
          <td><?= number_format((int)$r['units']) ?></td>
          <td>&#8377;<?= number_format((float)$r['cost_value'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$byCategory): ?><tr><td colspan="4" class="empty-cell">No active products available.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</article>

<!-- Low stock -->
<article class="panel">
  <div class="panel-head">
    <div>
      <h3>Low Stock Report</h3>
      <p>Active products at or below their reorder level</p>
    </div>
    <span class="pill <?= count($low) > 0 ? 'warning' : 'good' ?>"><?= count($low) ?> items</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Current Stock</th><th>Reorder Level</th><th>Deficit</th></tr></thead>
      <tbody>
      <?php foreach ($low as $r): ?>
        <tr>
          <td><b><?= e($r['name']) ?></b></td>
          <td><?= e($r['sku']) ?></td>
          <td><?= e($r['category_name'] ?? 'Uncategorized') ?></td>
          <td><span class="pill warning"><?= (int)$r['quantity'] ?></span></td>
          <td><?= (int)$r['reorder_level'] ?></td>
          <td><?= max(0, (int)$r['reorder_level'] - (int)$r['quantity']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$low): ?><tr><td colspan="6" class="empty-cell">All active products are above reorder levels.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</article>

<!-- Retail valuation note -->
<article class="panel">
  <div class="panel-head">
    <div>
      <h3>Potential Retail Valuation</h3>
      <p>This is not actual revenue. Actual profit requires sales and purchase records.</p>
    </div>
  </div>
  <div class="inventory-value" style="margin:0 20px 20px">
    <span>Potential retail value (quantity &times; selling price)</span>
    <b>&#8377;<?= number_format((float)$summary['retail_value'],2) ?></b>
  </div>
</article>

<?php require 'includes/footer.php'; ?>


