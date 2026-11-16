<?php
require_once 'config.php';
require_login();
$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $type      = (string)($_POST['movement_type'] ?? '');
    $qty       = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    $notes     = trim((string)($_POST['notes'] ?? ''));

    if (!$productId || !in_array($type, ['IN','OUT'], true) || $qty === false || $qty < 1) {
        $error = 'Select a product, movement type and a quantity greater than zero.';
    } else {
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare('SELECT quantity, is_archived FROM products WHERE id = ? FOR UPDATE');
            $s->execute([$productId]);
            $p = $s->fetch();
            if (!$p) throw new RuntimeException('Product not found.');
            if ((int)$p['is_archived'] === 1) throw new RuntimeException('Archived products cannot have stock movements.');
            if ($type === 'OUT' && (int)$p['quantity'] < $qty) throw new RuntimeException('Insufficient stock. Available: ' . $p['quantity']);
            $delta = $type === 'IN' ? $qty : -$qty;
            $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?')->execute([$delta, $productId]);
            $pdo->prepare('INSERT INTO stock_movements (product_id,movement_type,quantity,notes,created_by) VALUES (?,?,?,?,?)')->execute([$productId,$type,$qty,$notes,$_SESSION['user_id']]);
            $pdo->commit();
            flash($type === 'IN' ? 'Stock received successfully.' : 'Stock issued successfully.');
            redirect('stock.php');
        } catch (Throwable $x) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $x instanceof RuntimeException ? $x->getMessage() : 'Could not record movement.';
        }
    }
}

$products = $pdo->query('SELECT id, name, sku, quantity FROM products WHERE is_archived = 0 ORDER BY name')->fetchAll();

// History with optional filters
$filterType = (string)($_GET['type'] ?? '');
$filterProduct = (int)($_GET['product'] ?? 0);

$histSql = 'SELECT sm.*, p.name AS product_name, p.sku, u.full_name
            FROM stock_movements sm
            JOIN products p ON p.id = sm.product_id
            LEFT JOIN users u ON u.id = sm.created_by
            WHERE 1=1';
$histParams = [];
if ($filterType === 'IN' || $filterType === 'OUT') { $histSql .= ' AND sm.movement_type = ?'; $histParams[] = $filterType; }
if ($filterProduct > 0) { $histSql .= ' AND sm.product_id = ?'; $histParams[] = $filterProduct; }
$histSql .= ' ORDER BY sm.id DESC LIMIT 100';
$stmt = $pdo->prepare($histSql);
$stmt->execute($histParams);
$rows = $stmt->fetchAll();

$pageTitle = 'Stock Movement';
require 'includes/header.php';
?>

<div class="page-actions">
  <p>Record stock receipts and issues. Stock-out is blocked when quantity is insufficient.</p>
</div>

<?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>

<!-- Record form -->
<article class="panel">
  <div class="panel-head">
    <div>
      <h3>Record a Movement</h3>
      <p>Every movement is permanently logged in the audit history below.</p>
    </div>
  </div>
  <form method="post" class="form-grid">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Product *
      <select name="product_id" required>
        <option value="">Select product</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= $p['id'] ?>"><?= e($p['name'] . ' &mdash; ' . $p['sku'] . ' (Stock: ' . $p['quantity'] . ')') ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Movement Type *
      <select name="movement_type" required>
        <option value="IN">Stock In &mdash; Received from supplier</option>
        <option value="OUT">Stock Out &mdash; Issued / sold</option>
      </select>
    </label>
    <label>Quantity *
      <input type="number" name="quantity" min="1" step="1" required placeholder="e.g. 10">
    </label>
    <label>Notes / Reference
      <input name="notes" maxlength="500" placeholder="e.g. Supplier invoice #123">
    </label>
    <div class="span-2 form-actions">
      <button class="btn primary" type="submit">Save Movement</button>
    </div>
  </form>
</article>

<!-- History -->
<article class="panel">
  <div class="panel-head">
    <div>
      <h3>Movement History <span class="count-badge"><?= count($rows) ?></span></h3>
      <p>Latest 100 transactions. Filter by type or product.</p>
    </div>
    <button class="btn secondary" onclick="window.print()" type="button">Print</button>
  </div>

  <form method="get" class="filters" style="padding-bottom:14px">
    <select name="type">
      <option value="">All Types</option>
      <option value="IN" <?= $filterType === 'IN' ? 'selected' : '' ?>>Stock In</option>
      <option value="OUT" <?= $filterType === 'OUT' ? 'selected' : '' ?>>Stock Out</option>
    </select>
    <select name="product">
      <option value="0">All Products</option>
      <?php foreach ($products as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $filterProduct === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn secondary" type="submit">Filter</button>
    <a class="btn ghost" href="stock.php">Reset</a>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Date &amp; Time</th><th>Product</th><th>Type</th><th>Quantity</th><th>Notes</th><th>Recorded By</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></td>
          <td><b><?= e($r['product_name']) ?></b><small class="subline"><?= e($r['sku']) ?></small></td>
          <td><span class="pill <?= $r['movement_type'] === 'IN' ? 'good' : 'warning' ?>"><?= $r['movement_type'] === 'IN' ? 'Stock In' : 'Stock Out' ?></span></td>
          <td><b><?= $r['movement_type'] === 'IN' ? '+' : '-' ?><?= (int)$r['quantity'] ?></b></td>
          <td><?= e($r['notes'] ?: '&mdash;') ?></td>
          <td><?= e($r['full_name'] ?? 'System') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="empty-cell">No movements recorded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</article>

<?php require 'includes/footer.php'; ?>

