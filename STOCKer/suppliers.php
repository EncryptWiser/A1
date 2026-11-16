<?php
require_once 'config.php';
require_login();
$pdo = db();
$error = '';
$editId = (int)($_GET['edit'] ?? 0);
$edit = ['name'=>'','contact_person'=>'','email'=>'','phone'=>'','address'=>''];

if ($editId) {
    $s = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?');
    $s->execute([$editId]);
    $edit = $s->fetch() ?: $edit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mode = $_POST['mode'] ?? 'save';
    $id   = (int)($_POST['id'] ?? 0);
    $f = [];
    foreach (['name','contact_person','email','phone','address'] as $k) {
        $f[$k] = trim((string)($_POST[$k] ?? ''));
    }

    if ($mode === 'delete') {
        try {
            $pdo->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
            flash('Supplier deleted.');
        } catch (PDOException $x) {
            flash('Supplier is linked to products. Reassign those products first.','danger');
        }
        redirect('suppliers.php');
    }

    if ($f['name'] === '') {
        $error = 'Supplier name is required.';
    } elseif ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        try {
            if ($mode === 'update') {
                $pdo->prepare('UPDATE suppliers SET name=?,contact_person=?,email=?,phone=?,address=? WHERE id=?')->execute([...array_values($f),$id]);
                flash('Supplier updated.');
            } else {
                $pdo->prepare('INSERT INTO suppliers(name,contact_person,email,phone,address) VALUES(?,?,?,?,?)')->execute(array_values($f));
                flash('Supplier created.');
            }
            redirect('suppliers.php');
        } catch (PDOException $x) {
            $error = 'Could not save supplier.';
            $edit = $f;
        }
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$s = $pdo->prepare('SELECT s.*, COUNT(p.id) AS product_count FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id WHERE s.name LIKE ? OR s.email LIKE ? OR s.phone LIKE ? GROUP BY s.id ORDER BY s.id DESC');
$s->execute(['%'.$q.'%','%'.$q.'%','%'.$q.'%']);
$rows = $s->fetchAll();

$pageTitle = 'Suppliers';
require 'includes/header.php';
?>

<div class="page-actions">
  <p>Manage vendors and supplier contact information.</p>
</div>

<?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>

<div class="two-col">
  <!-- Form -->
  <article class="panel">
    <div class="panel-head">
      <div>
        <h3><?= $editId ? 'Edit Supplier' : 'Add Supplier' ?></h3>
        <p>Supplier name is required.</p>
      </div>
    </div>
    <form method="post" class="form-stack">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="mode" value="<?= $editId ? 'update' : 'save' ?>">
      <input type="hidden" name="id" value="<?= $editId ?>">
      <label>Supplier Name *
        <input name="name" required maxlength="150" value="<?= e($edit['name']) ?>" placeholder="Company or individual name">
      </label>
      <label>Contact Person
        <input name="contact_person" maxlength="100" value="<?= e($edit['contact_person']) ?>" placeholder="Full name">
      </label>
      <label>Email Address
        <input type="email" name="email" maxlength="150" value="<?= e($edit['email']) ?>" placeholder="email@example.com">
      </label>
      <label>Phone Number
        <input name="phone" maxlength="30" value="<?= e($edit['phone']) ?>" placeholder="+91 00000 00000">
      </label>
      <label>Address
        <textarea name="address" rows="3" maxlength="500"><?= e($edit['address']) ?></textarea>
      </label>
      <div class="form-actions">
        <button class="btn primary" type="submit"><?= $editId ? 'Save Changes' : 'Create Supplier' ?></button>
        <?php if ($editId): ?><a class="btn secondary" href="suppliers.php">Cancel</a><?php endif; ?>
      </div>
    </form>
  </article>

  <!-- List -->
  <article class="panel">
    <div class="panel-head">
      <div>
        <h3>Supplier Directory <span class="count-badge"><?= count($rows) ?></span></h3>
        <p>All registered suppliers.</p>
      </div>
    </div>
    <form class="filters" method="get" style="padding-bottom:14px">
      <input name="q" value="<?= e($q) ?>" placeholder="Search by name, email or phone...">
      <button class="btn secondary" type="submit">Search</button>
      <?php if ($q): ?><a class="btn ghost" href="suppliers.php">Clear</a><?php endif; ?>
    </form>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Supplier</th><th>Contact</th><th>Products</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <b><?= e($r['name']) ?></b>
              <small class="subline"><?= e($r['email']) ?></small>
            </td>
            <td><?= e($r['contact_person'] ?: ($r['phone'] ?: '&mdash;')) ?></td>
            <td><span class="pill info"><?= $r['product_count'] ?></span></td>
            <td>
              <div class="actions">
                <a class="action-link" href="suppliers.php?edit=<?= $r['id'] ?>">Edit</a>
                <form method="post" class="inline-form" data-confirm="Delete supplier '<?= e($r['name']) ?>'?">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="mode" value="delete">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="action-link danger-link" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="4" class="empty-cell">No suppliers found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>
</div>

<?php require 'includes/footer.php'; ?>

