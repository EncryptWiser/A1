<?php
require_once 'config.php';
require_login();
$pdo = db();
$error = '';
$action = (string)($_GET['action'] ?? '');
$id = (int)($_GET['id'] ?? 0);

$form = ['sku'=>'','name'=>'','category_id'=>'','supplier_id'=>'','description'=>'',
         'cost_price'=>'0.00','selling_price'=>'0.00','quantity'=>'0','reorder_level'=>'5','image'=>null];

if ($action === 'edit' && $id) {
    $s = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $s->execute([$id]);
    $found = $s->fetch();
    if ($found) { $form = $found; } else { flash('Product not found.','danger'); redirect('products.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mode = (string)($_POST['mode'] ?? 'save');
    $allowedModes = ['save','update','archive','unarchive','delete'];
    if (!in_array($mode, $allowedModes, true)) { http_response_code(400); exit('Invalid action.'); }
    $id = (int)($_POST['id'] ?? 0);

    // Archive / Unarchive
    if (in_array($mode, ['archive','unarchive'], true)) {
        if ($id <= 0) { flash('Invalid product.','danger'); redirect('products.php'); }
        try {
            $archived = $mode === 'archive' ? 1 : 0;
            $s = $pdo->prepare('UPDATE products SET is_archived = ? WHERE id = ?');
            $s->execute([$archived, $id]);
            flash($archived ? 'Product archived.' : 'Product restored.');
        } catch (PDOException $ex) { flash('Could not update product.','danger'); }
        redirect('products.php');
    }

    // Delete
    if ($mode === 'delete') {
        if ($id <= 0) { flash('Invalid product.','danger'); redirect('products.php'); }
        try {
            $pdo->beginTransaction();
            $check = $pdo->prepare('SELECT 1 FROM stock_movements WHERE product_id = ? LIMIT 1');
            $check->execute([$id]);
            if ($check->fetchColumn() !== false) {
                $pdo->rollBack();
                flash('Cannot delete: stock history exists. Archive instead.','danger');
                redirect('products.php');
            }
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            $pdo->commit();
            flash('Product deleted.');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('Could not delete product.','danger');
        }
        redirect('products.php');
    }

    // Handle image upload
    $imagePath = null;
    if ($mode === 'update' && $id > 0) {
        $old = $pdo->prepare('SELECT image FROM products WHERE id = ?');
        $old->execute([$id]);
        $imagePath = $old->fetchColumn() ?: null;
    }

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['product_image'];
        if ($file['error'] !== UPLOAD_ERR_OK) { $error = 'Image upload failed.'; }
        elseif ($file['size'] > 2 * 1024 * 1024) { $error = 'Image must be under 2 MB.'; }
        else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!isset($allowed[$mime]) || getimagesize($file['tmp_name']) === false) {
                $error = 'Only valid JPG, PNG or WEBP images.';
            } else {
                $uploadDir = __DIR__ . '/Upload/Product/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'Upload/Product/' . $filename;
                } else { $error = 'Could not save image.'; }
            }
        }
    }

    // Build form values
    $form = [
        'sku'          => trim((string)($_POST['sku'] ?? '')),
        'name'         => trim((string)($_POST['name'] ?? '')),
        'category_id'  => ($_POST['category_id'] ?? '') ?: null,
        'supplier_id'  => ($_POST['supplier_id'] ?? '') ?: null,
        'description'  => trim((string)($_POST['description'] ?? '')),
        'cost_price'   => $_POST['cost_price'] ?? '',
        'selling_price'=> $_POST['selling_price'] ?? '',
        'quantity'     => $_POST['quantity'] ?? '',
        'reorder_level'=> $_POST['reorder_level'] ?? '',
    ];

    if ($form['sku'] === '' || $form['name'] === '') {
        $error = 'SKU and product name are required.';
    } elseif (!is_numeric($form['cost_price']) || $form['cost_price'] < 0
           || !is_numeric($form['selling_price']) || $form['selling_price'] < 0
           || filter_var($form['quantity'], FILTER_VALIDATE_INT) === false || $form['quantity'] < 0
           || filter_var($form['reorder_level'], FILTER_VALIDATE_INT) === false || $form['reorder_level'] < 0) {
        $error = 'Enter valid non-negative prices and whole-number quantities.';
    } else {
        if ($mode === 'update') {
            $cur = $pdo->prepare('SELECT quantity FROM products WHERE id = ?');
            $cur->execute([$id]);
            $curQty = $cur->fetchColumn();
            if ($curQty === false) { $error = 'Product not found.'; }
            else { $form['quantity'] = (string)$curQty; }
        }

        if ($error === '') {
            $dupe = $pdo->prepare('SELECT id FROM products WHERE sku = ? AND id <> ?');
            $dupe->execute([$form['sku'], $id]);
            if ($dupe->fetch()) { $error = 'SKU already exists. Use a unique SKU.'; }
        }

        if ($error === '') {
            try {
                $pdo->beginTransaction();
                if ($mode === 'update') {
                    $s = $pdo->prepare('UPDATE products SET sku=?,name=?,category_id=?,supplier_id=?,description=?,cost_price=?,selling_price=?,quantity=?,reorder_level=?,image=? WHERE id=?');
                    $s->execute([$form['sku'],$form['name'],$form['category_id'],$form['supplier_id'],$form['description'],$form['cost_price'],$form['selling_price'],$form['quantity'],$form['reorder_level'],$imagePath,$id]);
                    $pdo->commit();
                    flash('Product updated.');
                    redirect('products.php');
                } else {
                    $s = $pdo->prepare('INSERT INTO products (sku,name,category_id,supplier_id,description,cost_price,selling_price,quantity,reorder_level,image) VALUES (?,?,?,?,?,?,?,?,?,?)');
                    $s->execute([$form['sku'],$form['name'],$form['category_id'],$form['supplier_id'],$form['description'],$form['cost_price'],$form['selling_price'],$form['quantity'],$form['reorder_level'],$imagePath]);
                    $newId = (int)$pdo->lastInsertId();
                    if ((int)$form['quantity'] > 0) {
                        $m = $pdo->prepare("INSERT INTO stock_movements (product_id,movement_type,quantity,notes,created_by) VALUES (?,'IN',?,'Opening stock',?)");
                        $m->execute([$newId, $form['quantity'], $_SESSION['user_id']]);
                    }
                    $pdo->commit();
                    flash('Product created successfully.');
                    redirect('products.php');
                }
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Product save error: ' . $ex->getMessage());
                $error = 'Could not save product. ' . $ex->getMessage();
            }
        }
    }
    $action = ($mode === 'update') ? 'edit' : 'add';
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$suppliers  = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();

$q              = trim((string)($_GET['q'] ?? ''));
$categoryFilter = (int)($_GET['category'] ?? 0);
$stockFilter    = (string)($_GET['stock'] ?? '');
$archivedFilter = (int)($_GET['archived'] ?? 0) === 1 ? 1 : 0;

$sql = 'SELECT p.*, c.name AS category_name, s.name AS supplier_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN suppliers s ON s.id = p.supplier_id
        WHERE p.is_archived = ? AND (p.name LIKE ? OR p.sku LIKE ?)';
$params = [$archivedFilter, '%'.$q.'%', '%'.$q.'%'];
if ($categoryFilter) { $sql .= ' AND p.category_id = ?'; $params[] = $categoryFilter; }
if ($stockFilter === 'low') $sql .= ' AND p.quantity <= p.reorder_level';
if ($stockFilter === 'available') $sql .= ' AND p.quantity > 0';
$sql .= ' ORDER BY p.id DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

$pageTitle = 'Products';
require 'includes/header.php';
?>

<div class="page-actions">
  <p><?= $archivedFilter ? 'Viewing archived products.' : 'Manage your catalog, prices and reorder thresholds.' ?></p>
  <div class="btn-group">
    <a class="btn secondary" href="products.php?archived=<?= $archivedFilter ? 0 : 1 ?>">
      <?= $archivedFilter ? '&larr; Active Products' : 'View Archived' ?>
    </a>
    <a class="btn primary" href="products.php?action=add">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Product
    </a>
  </div>
</div>

<?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>

<?php if (in_array($action, ['add','edit'], true)): ?>
<article class="panel">
  <div class="panel-head">
    <div>
      <h3><?= $action === 'edit' ? 'Edit Product' : 'Create New Product' ?></h3>
      <p>Fields marked * are required.</p>
    </div>
    <a class="btn secondary" href="products.php">Cancel</a>
  </div>
  <form method="post" class="form-grid" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="mode" value="<?= $action === 'edit' ? 'update' : 'save' ?>">
    <input type="hidden" name="id" value="<?= (int)$id ?>">

    <label>SKU *
      <input name="sku" required maxlength="50" value="<?= e($form['sku']) ?>" placeholder="e.g. ELEC-001">
    </label>
    <label>Product Name *
      <input name="name" required maxlength="150" value="<?= e($form['name']) ?>" placeholder="Product name">
    </label>
    <label>Category
      <select name="category_id">
        <option value="">Unassigned</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= (string)$form['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Supplier
      <select name="supplier_id">
        <option value="">Unassigned</option>
        <?php foreach ($suppliers as $s): ?>
          <option value="<?= $s['id'] ?>" <?= (string)$form['supplier_id'] === (string)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Cost Price (&#8377;) *
      <input type="number" name="cost_price" min="0" step="0.01" required value="<?= e($form['cost_price']) ?>">
    </label>
    <label>Selling Price (&#8377;) *
      <input type="number" name="selling_price" min="0" step="0.01" required value="<?= e($form['selling_price']) ?>">
    </label>
    <label><?= $action === 'edit' ? 'Current Quantity (use Stock Movement to change)' : 'Opening Quantity *' ?>
      <input type="number" name="quantity" min="0" step="1" required value="<?= e($form['quantity']) ?>" <?= $action === 'edit' ? 'readonly' : '' ?>>
    </label>
    <label>Reorder Level *
      <input type="number" name="reorder_level" min="0" step="1" required value="<?= e($form['reorder_level']) ?>">
    </label>
    <label class="span-2">Description
      <textarea name="description" rows="3" maxlength="1000" placeholder="Optional product details"><?= e($form['description']) ?></textarea>
    </label>
    <label class="span-2">Product Image
      <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp">
      <small style="color:var(--muted);font-weight:400">JPG, PNG or WEBP &mdash; max 2 MB</small>
    </label>
    <div class="span-2 form-actions">
      <button class="btn primary" type="submit"><?= $action === 'edit' ? 'Save Changes' : 'Create Product' ?></button>
      <a class="btn secondary" href="products.php">Cancel</a>
    </div>
  </form>
</article>
<?php endif; ?>

<article class="panel">
  <div class="panel-head">
    <div>
      <h3>Product Catalog <span class="count-badge"><?= count($products) ?></span></h3>
      <p>Search and filter your inventory records.</p>
    </div>
  </div>

  <form method="get" class="filters">
    <input name="q" value="<?= e($q) ?>" placeholder="Search by name or SKU...">
    <select name="category">
      <option value="0">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $categoryFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="stock">
      <option value="">All Stock Levels</option>
      <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low Stock</option>
      <option value="available" <?= $stockFilter === 'available' ? 'selected' : '' ?>>In Stock</option>
    </select>
    <input type="hidden" name="archived" value="<?= $archivedFilter ?>">
    <button class="btn secondary" type="submit">Search</button>
    <a class="btn ghost" href="products.php">Reset</a>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Image</th>
          <th>Product / SKU</th>
          <th>Category</th>
          <th>Cost / Sell</th>
          <th>Stock</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <?php if (!empty($p['image'])): ?>
              <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" width="48" height="48" style="object-fit:cover;border-radius:6px;border:1px solid var(--line)">
            <?php else: ?>
              <div style="width:48px;height:48px;border-radius:6px;background:var(--bg);border:1px solid var(--line);display:grid;place-items:center;color:var(--muted);font-size:18px">&#128230;</div>
            <?php endif; ?>
          </td>
          <td><b><?= e($p['name']) ?></b><small class="subline"><?= e($p['sku']) ?></small></td>
          <td><?= e($p['category_name'] ?? '&mdash;') ?></td>
          <td>
            &#8377;<?= number_format((float)$p['cost_price'], 2) ?>
            <small class="subline">Sell &#8377;<?= number_format((float)$p['selling_price'], 2) ?></small>
          </td>
          <td>
            <b><?= (int)$p['quantity'] ?></b>
            <small class="subline">Reorder: <?= (int)$p['reorder_level'] ?></small>
          </td>
          <td>
            <span class="pill <?= (int)$p['quantity'] <= (int)$p['reorder_level'] ? 'warning' : 'good' ?>">
              <?= (int)$p['quantity'] <= (int)$p['reorder_level'] ? 'Low Stock' : 'Healthy' ?>
            </span>
          </td>
          <td>
            <div class="actions">
            <?php if (!(int)$p['is_archived']): ?>
              <a class="action-link" href="products.php?action=edit&id=<?= (int)$p['id'] ?>">Edit</a>
              <form method="post" class="inline-form" data-confirm="Archive this product?">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="mode" value="archive">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="action-link" type="submit">Archive</button>
              </form>
              <form method="post" class="inline-form" data-confirm="Permanently delete this product? This cannot be undone.">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="mode" value="delete">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="action-link danger-link" type="submit">Delete</button>
              </form>
            <?php else: ?>
              <form method="post" class="inline-form" data-confirm="Restore this product?">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="mode" value="unarchive">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="action-link" type="submit">Restore</button>
              </form>
            <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?>
        <tr><td colspan="7" class="empty-cell">No products found. Add a product or adjust your filters.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</article>

<?php require 'includes/footer.php'; ?>

