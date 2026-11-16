<?php
require_once 'config.php';
require_login();
$pdo = db();
$error = '';
$editId = (int)($_GET['edit'] ?? 0);
$edit = ['name'=>'','description'=>''];

if ($editId) {
    $s = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $s->execute([$editId]);
    $edit = $s->fetch() ?: $edit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mode = $_POST['mode'] ?? 'save';
    $id   = (int)($_POST['id'] ?? 0);
    $name        = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

    if ($mode === 'delete') {
        try {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('Category deleted.');
        } catch (PDOException $x) {
            flash('Category is assigned to products. Reassign products first.','danger');
        }
        redirect('categories.php');
    }

    if ($name === '') {
        $error = 'Category name is required.';
    } else {
        try {
            if ($mode === 'update') {
                $pdo->prepare('UPDATE categories SET name=?,description=? WHERE id=?')->execute([$name,$description,$id]);
                flash('Category updated.');
            } else {
                $pdo->prepare('INSERT INTO categories(name,description) VALUES(?,?)')->execute([$name,$description]);
                flash('Category created.');
            }
            redirect('categories.php');
        } catch (PDOException $x) {
            $error = 'Could not save. The category name may already exist.';
            $edit = ['name'=>$name,'description'=>$description];
        }
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$s = $pdo->prepare('SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id WHERE c.name LIKE ? GROUP BY c.id ORDER BY c.name');
$s->execute(['%'.$q.'%']);
$rows = $s->fetchAll();

$pageTitle = 'Categories';
require 'includes/header.php';
?>

<div class="page-actions">
  <p>Organize products into groups. Category name must be unique.</p>
</div>

<?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>

<div class="two-col">
  <!-- Add / Edit form -->
  <article class="panel">
    <div class="panel-head">
      <div>
        <h3><?= $editId ? 'Edit Category' : 'Add Category' ?></h3>
        <p>Keep names clear and descriptive.</p>
      </div>
    </div>
    <form method="post" class="form-stack">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="mode" value="<?= $editId ? 'update' : 'save' ?>">
      <input type="hidden" name="id" value="<?= $editId ?>">
      <label>Category Name *
        <input name="name" required maxlength="100" value="<?= e($edit['name']) ?>" placeholder="e.g. Electronics">
      </label>
      <label>Description
        <textarea name="description" rows="4" maxlength="500"><?= e($edit['description']) ?></textarea>
      </label>
      <div class="form-actions">
        <button class="btn primary" type="submit"><?= $editId ? 'Save Changes' : 'Create Category' ?></button>
        <?php if ($editId): ?><a class="btn secondary" href="categories.php">Cancel</a><?php endif; ?>
      </div>
    </form>
  </article>

  <!-- Category list -->
  <article class="panel">
    <div class="panel-head">
      <div>
        <h3>Category List <span class="count-badge"><?= count($rows) ?></span></h3>
        <p>All product categories.</p>
      </div>
    </div>
    <form class="filters" method="get" style="padding-bottom:14px">
      <input name="q" value="<?= e($q) ?>" placeholder="Search categories...">
      <button class="btn secondary" type="submit">Search</button>
      <?php if ($q): ?><a class="btn ghost" href="categories.php">Clear</a><?php endif; ?>
    </form>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Name / Description</th><th>Products</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><b><?= e($r['name']) ?></b><small class="subline"><?= e($r['description']) ?></small></td>
            <td><span class="pill info"><?= $r['product_count'] ?></span></td>
            <td>
              <div class="actions">
                <a class="action-link" href="categories.php?edit=<?= $r['id'] ?>">Edit</a>
                <form method="post" class="inline-form" data-confirm="Delete category '<?= e($r['name']) ?>'?">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="mode" value="delete">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="action-link danger-link" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="3" class="empty-cell">No categories found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>
</div>

<?php require 'includes/footer.php'; ?>

