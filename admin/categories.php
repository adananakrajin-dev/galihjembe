<?php
$page_title  = 'Kategori';
$admin_active = 'categories';
include '_head.php';

// ── Aksi: tambah / hapus kategori ──
if (isset($_POST['tambah'])) {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $type = in_array($_POST['type'] ?? '', ['product', 'service', 'all'], true) ? $_POST['type'] : 'all';

    if (strlen($name) < 2 || strlen($name) > 60) {
        set_flash('Nama kategori 2–60 karakter.', 'danger');
    } else {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        if (db_one($db, 'SELECT id FROM categories WHERE slug = ? OR name = ?', 'ss', $slug, $name)) {
            set_flash('Kategori sudah ada.', 'danger');
        } else {
            $stmt = $db->prepare('INSERT INTO categories (name, slug, type) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $slug, $type);
            $stmt->execute();
            $stmt->close();
            set_flash('Kategori "' . $name . '" ditambahkan.', 'success');
        }
    }
    header('Location: categories.php');
    exit;
}

if (isset($_POST['hapus'])) {
    csrf_check();
    $cid = (int)($_POST['category_id'] ?? 0);
    if ($cid > 0) {
        // FK listing.category_id ON DELETE SET NULL → listing tetap aman
        $stmt = $db->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->bind_param('i', $cid);
        $stmt->execute();
        $stmt->close();
        set_flash('Kategori dihapus. Listing terkait jadi tanpa kategori.', 'success');
    }
    header('Location: categories.php');
    exit;
}

$cats = [];
try {
    $cats = db_all(
        $db,
        'SELECT c.*, (SELECT COUNT(*) FROM listings l WHERE l.category_id = c.id) AS listing_count
         FROM categories c ORDER BY c.type, c.id'
    );
} catch (Throwable $e) {}
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Kategori</h1>
            <p>Atur kategori untuk jasa web &amp; produk agar mudah ditemukan.</p>
            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <div class="grid-2" style="align-items:start;">
                <div class="panel">
                    <h3 class="mb-2">Tambah Kategori</h3>
                    <form action="categories.php" method="POST" class="form">
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="name">Nama Kategori</label>
                            <input id="name" type="text" name="name" required minlength="2" maxlength="60"
                                   placeholder="Contoh: Sepeda Motor">
                        </div>
                        <div class="field">
                            <label for="type">Tipe</label>
                            <select id="type" name="type">
                                <option value="all">Semua (jasa &amp; produk)</option>
                                <option value="product">Produk saja</option>
                                <option value="service">Jasa saja</option>
                            </select>
                        </div>
                        <button type="submit" name="tambah" class="btn btn-primary btn-block">Tambah</button>
                    </form>
                </div>

                <div class="panel">
                    <h3 class="mb-2">Daftar Kategori (<?= count($cats) ?>)</h3>
                    <?php if (!$cats): ?>
                        <p class="faint">Belum ada kategori.</p>
                    <?php else: ?>
                        <div class="link-list">
                            <?php foreach ($cats as $c): ?>
                                <div class="link-item" style="cursor:default;">
                                    <span>
                                        <strong><?= e($c['name']) ?></strong>
                                        <span class="faint"> · <?= e($c['type']) ?> · <?= (int)$c['listing_count'] ?> listing</span>
                                    </span>
                                    <form method="POST" action="categories.php"
                                          data-confirm="Hapus kategori &quot;<?= e($c['name']) ?>&quot;?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>">
                                        <button type="submit" name="hapus" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include '_foot.php'; ?>
