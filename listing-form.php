<?php
include 'database.php';
include 'includes/functions.php';
require_role('seller', 'admin');

$seller_id = (int)$_SESSION['user_id'];
$id        = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$is_edit   = $id > 0;

// ── Listing lama (mode edit) ──
$old  = null;
$pkgs = [];
$imgs = [];
if ($is_edit) {
    try {
        $old = db_one($db, 'SELECT * FROM listings WHERE id = ? AND seller_id = ?', 'ii', $id, $seller_id);
    } catch (Throwable $e) {}
    if (!$old) {
        set_flash('Listing tidak ditemukan.', 'danger');
        header('Location: my-listings.php');
        exit;
    }
    try { $pkgs = db_all($db, 'SELECT * FROM listing_packages WHERE listing_id = ? ORDER BY sort_order, id', 'i', $id); }
    catch (Throwable $e) {}
    try { $imgs = db_all($db, 'SELECT * FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, id', 'i', $id); }
    catch (Throwable $e) {}
}

// ── Kategori ──
$categories = [];
try { $categories = db_all($db, 'SELECT id, name, type FROM categories ORDER BY id'); }
catch (Throwable $e) {}

// ── Aksi: hapus gambar ──
if (isset($_POST['hapus_gambar'])) {
    csrf_check();
    $img_id = (int)($_POST['image_id'] ?? 0);
    $img = db_one(
        $db,
        'SELECT i.* FROM listing_images i JOIN listings l ON l.id = i.listing_id
         WHERE i.id = ? AND l.seller_id = ?',
        'ii', $img_id, $seller_id
    );
    if ($img) {
        delete_upload($img['image_url']);
        $stmt = $db->prepare('DELETE FROM listing_images WHERE id = ?');
        $stmt->bind_param('i', $img_id);
        $stmt->execute();
        $stmt->close();
        set_flash('Gambar dihapus.', 'success');
    }
    header('Location: listing-form.php?id=' . $id);
    exit;
}

// ── Simpan listing ──
$error = '';
if (isset($_POST['simpan'])) {
    csrf_check();

    $type        = ($_POST['type'] ?? '') === 'service' ? 'service' : 'product';
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = max(0, (float)str_replace([',', '.'], '', $_POST['price'] ?? '0'));
    $cat_id      = (int)($_POST['category_id'] ?? 0);
    $condition   = in_array($_POST['condition'] ?? '', ['baru', 'seperti-baru', 'bekas-baik', 'bekas-cukup'], true)
                 ? $_POST['condition'] : null;
    $location    = trim($_POST['location'] ?? '');
    $website     = trim($_POST['website'] ?? '');
    $stack       = trim($_POST['stack'] ?? '');

    // ── Validasi ──
    if (strlen($title) < 5 || strlen($title) > 150) {
        $error = 'Judul harus 5–150 karakter.';
    } elseif (strlen($description) < 10) {
        $error = 'Deskripsi minimal 10 karakter.';
    } elseif ($price <= 0) {
        $error = 'Harga harus lebih dari 0.';
    } elseif ($type === 'product' && !$condition) {
        $error = 'Pilih kondisi produk.';
    } elseif ($cat_id > 0 && !db_one($db, 'SELECT id FROM categories WHERE id = ?', 'i', $cat_id)) {
        $error = 'Kategori tidak valid.';
    }
    // FK: kategori kosong harus NULL, bukan 0
    $cat_bind = $cat_id > 0 ? $cat_id : null;

    // ── Upload foto baru (maks 3 per simpan, total maks 5) ──
    $new_files = [];
    if ($error === '' && !empty($_FILES['images']['name'][0])) {
        $current_count = count($imgs);
        foreach ($_FILES['images']['name'] as $i => $n) {
            if ($n === '') { continue; }
            if ($current_count + count($new_files) >= 5) { break; } // batas total
            $one = [
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i],
            ];
            $up = handle_upload($one, 'image');
            if (!$up['ok']) { $error = $up['error']; break; }
            $new_files[] = $up['file'];
        }
        if ($error === '' && count($new_files) > 3) {
            $error = 'Maksimal 3 foto baru per simpan.';
        }
    }

    if ($error === '' && $type === 'product' && count($imgs) + count($new_files) === 0) {
        // Produk wajib punya minimal 1 foto (cek juga saat edit yang belum ada foto)
        $error = 'Produk wajib memiliki minimal 1 foto.';
    }

    // ── Paket jasa (opsional, maks 3) ──
    $pkg_rows = [];
    if ($error === '' && $type === 'service') {
        $names = $_POST['pkg_name'] ?? [];
        $prices = $_POST['pkg_price'] ?? [];
        $days = $_POST['pkg_days'] ?? [];
        $revs = $_POST['pkg_revs'] ?? [];
        $feats = $_POST['pkg_features'] ?? [];
        foreach ($names as $i => $pn) {
            $pn = trim((string)$pn);
            $pp = max(0, (float)str_replace([',', '.'], '', $prices[$i] ?? '0'));
            if ($pn === '') { continue; }
            if ($pp <= 0) { $error = 'Setiap paket wajib punya harga > 0.'; break; }
            $pkg_rows[] = [
                'name' => $pn, 'price' => $pp,
                'days' => (int)($days[$i] ?? 0) ?: null,
                'revs' => $revs[$i] !== '' ? (int)$revs[$i] : null,
                'feats' => trim((string)($feats[$i] ?? '')),
            ];
        }
    }

    // ── Eksekusi ──
    if ($error === '') {
        $moderation_note = null;
        if ($is_edit) {
            // Listing aktif yang berubah isinya wajib moderasi ulang
            $re_moderate = $old['moderation'] === 'approved';
            $moderation = $re_moderate ? 'pending' : $old['moderation'];
            $stmt = $db->prepare(
                'UPDATE listings SET type=?, title=?, description=?, price=?, category_id=?,
                        `condition`=?, location=?, website=?, stack=?, moderation=?
                 WHERE id=? AND seller_id=?'
            );
            $stmt->bind_param(
                'sssdississii',
                $type, $title, $description, $price, $cat_bind,
                $condition, $location, $website, $stack, $moderation, $id, $seller_id
            );
            $stmt->execute();
            $stmt->close();
            $listing_id = $id;
            if ($re_moderate) { $moderation_note = ' Listing dikirim ulang ke moderasi admin.'; }
        } else {
            $stmt = $db->prepare(
                'INSERT INTO listings (seller_id, category_id, type, title, description, price,
                                       `condition`, location, website, stack, moderation)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")'
            );
            $stmt->bind_param(
                'iissdsssss',
                $seller_id, $cat_bind, $type, $title, $description, $price,
                $condition, $location, $website, $stack
            );
            $stmt->execute();
            $listing_id = $stmt->insert_id;
            $stmt->close();
        }

        // Simpan gambar baru
        foreach ($new_files as $k => $f) {
            $stmt = $db->prepare('INSERT INTO listing_images (listing_id, image_url, is_primary) VALUES (?, ?, ?)');
            $prim = count($imgs) === 0 && $k === 0 ? 1 : 0;
            $stmt->bind_param('isi', $listing_id, $f, $prim);
            $stmt->execute();
            $stmt->close();
        }

        // Simpan paket (ganti semua untuk konsistensi)
        if ($type === 'service') {
            $stmt = $db->prepare('DELETE FROM listing_packages WHERE listing_id = ?');
            $stmt->bind_param('i', $listing_id);
            $stmt->execute();
            $stmt->close();

            foreach ($pkg_rows as $sort => $p) {
                $stmt = $db->prepare(
                    'INSERT INTO listing_packages (listing_id, name, price, duration_days, revisions, features, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('isdiisi', $listing_id, $p['name'], $p['price'], $p['days'], $p['revs'], $p['feats'], $sort);
                $stmt->execute();
                $stmt->close();
            }
        }

        set_flash(
            ($is_edit ? 'Listing diperbarui.' : 'Listing dibuat.') . $moderation_note
            . ' Menunggu persetujuan admin sebelum tayang.',
            'success'
        );
        header('Location: my-listings.php');
        exit;
    }

    // Simpan input lama agar form tidak kosong saat error
    $_POST['keep'] = 1;
}

// ── Nilai form ──
$v = function (string $key, $default = '') use ($is_edit, $old) {
    if (isset($_POST[$key])) { return $_POST[$key]; }
    return $is_edit && $old !== null && $old[$key] !== null ? $old[$key] : $default;
};
$type_val = isset($_POST['type']) ? $_POST['type'] : ($is_edit ? $old['type'] : 'product');

// Harga ditampilkan tanpa format ribuan agar mudah diedit
$price_val = isset($_POST['price'])
    ? $_POST['price']
    : ($is_edit ? rtrim(rtrim(number_format((float)$old['price'], 0, ',', ''), '0'), '.') : '');

$page_title = $is_edit ? 'Edit Listing' : 'Tambah Listing';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <a class="back-link" href="my-listings.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg>
                Kembali ke listing saya
            </a>
            <h1><?= $is_edit ? 'Edit Listing' : 'Tambah Listing Baru' ?></h1>
            <p><?= $is_edit ? 'Perbarui info listingmu. Perubahan akan dimoderasi ulang.' : 'Isi detail jasa/produkmu. Listing tayang setelah disetujui admin.' ?></p>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger mb-3" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="listing-form.php<?= $is_edit ? '?id=' . $id : '' ?>" method="POST" enctype="multipart/form-data" class="form">
                <?= csrf_field() ?>
                <?php if ($is_edit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

                <div class="grid-2" style="align-items:start;">
                    <!-- Info utama -->
                    <div class="panel">
                        <h3 class="mb-2">Informasi Utama</h3>

                        <div class="field mb-2">
                            <label>Tipe Listing</label>
                            <div style="display:flex;gap:10px;">
                                <label class="pay-method" style="flex:1;<?= $type_val === 'product' ? '' : '' ?>">
                                    <input type="radio" name="type" value="product" <?= $type_val === 'product' ? 'checked' : '' ?>>
                                    Produk
                                </label>
                                <label class="pay-method" style="flex:1;">
                                    <input type="radio" name="type" value="service" <?= $type_val === 'service' ? 'checked' : '' ?>>
                                    Jasa Web
                                </label>
                            </div>
                        </div>

                        <div class="field mb-2">
                            <label for="title">Judul</label>
                            <input id="title" type="text" name="title" required minlength="5" maxlength="150"
                                   placeholder="<?= $type_val === 'service' ? 'Contoh: Bikin Landing Page Profesional' : 'Contoh: Laptop Bekas Ringan' ?>"
                                   value="<?= e($v('title')) ?>">
                        </div>

                        <div class="field mb-2">
                            <label for="category_id">Kategori</label>
                            <select id="category_id" name="category_id">
                                <option value="0">— Tanpa kategori —</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= (int)$v('category_id') === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row mb-2">
                            <div class="field">
                                <label for="price">Harga (Rp)</label>
                                <input id="price" type="text" name="price" inputmode="numeric" required
                                       placeholder="1500000" value="<?= e((string)$price_val) ?>">
                                <span class="hint">Angka saja, tanpa titik/koma.</span>
                            </div>
                            <div class="field" id="conditionField" style="<?= $type_val === 'product' ? '' : 'display:none;' ?>">
                                <label for="condition">Kondisi Produk</label>
                                <select id="condition" name="condition">
                                    <?php foreach (['baru' => 'Baru', 'seperti-baru' => 'Seperti Baru', 'bekas-baik' => 'Bekas — Baik', 'bekas-cukup' => 'Bekas — Cukup'] as $k => $lbl): ?>
                                        <option value="<?= $k ?>" <?= $v('condition') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="field mb-2" id="stackField" style="<?= $type_val === 'service' ? '' : 'display:none;' ?>">
                            <label for="stack">Teknologi / Stack <span class="faint">(opsional)</span></label>
                            <input id="stack" type="text" name="stack" placeholder="PHP, Laravel, Tailwind..."
                                   value="<?= e($v('stack')) ?>">
                        </div>

                        <div class="field mb-2" id="websiteField" style="<?= $type_val === 'service' ? '' : 'display:none;' ?>">
                            <label for="website">Link Demo / Portofolio <span class="faint">(opsional)</span></label>
                            <input id="website" type="url" name="website" placeholder="https://..."
                                   value="<?= e($v('website')) ?>">
                        </div>

                        <div class="field mb-2">
                            <label for="location">Lokasi <span class="faint">(opsional)</span></label>
                            <input id="location" type="text" name="location" placeholder="Jakarta Selatan"
                                   value="<?= e($v('location')) ?>">
                        </div>

                        <div class="field">
                            <label for="description">Deskripsi</label>
                            <textarea id="description" name="description" required minlength="10"
                                      placeholder="Jelaskan detail, spek, fitur, dan apa yang pembeli dapat..."><?= e($v('description')) ?></textarea>
                        </div>
                    </div>

                    <!-- Foto + paket -->
                    <div>
                        <div class="panel">
                            <h3 class="mb-2">Foto</h3>
                            <div class="field mb-2">
                                <label for="images">Upload Foto (maks 5 total, 2MB/foto, JPG/PNG/WebP)</label>
                                <input id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                                <span class="hint">Produk wajib minimal 1 foto. Jasa boleh kosong (opsional).</span>
                            </div>

                            <?php if ($imgs): ?>
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(72px,1fr));gap:10px;">
                                    <?php foreach ($imgs as $im): ?>
                                        <div>
                                            <div class="card-media" style="aspect-ratio:1;border-radius:8px;">
                                                <img src="uploads/<?= e($im['image_url']) ?>" alt="" loading="lazy">
                                            </div>
                                            <form method="POST" action="listing-form.php?id=<?= $id ?>" class="mt-1">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="image_id" value="<?= (int)$im['id'] ?>">
                                                <button type="submit" name="hapus_gambar" class="btn btn-danger btn-sm" style="width:100%;padding:0 6px;font-size:11px;">Hapus</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="panel mt-3" id="packageBox" style="<?= $type_val === 'service' ? '' : 'display:none;' ?>">
                            <h3 class="mb-1">Paket Harga <span class="faint" style="text-transform:none;letter-spacing:0;">(opsional — Basic/Pro/Enterprise)</span></h3>
                            <p class="faint mb-2">Isi nama &amp; harga untuk membuat paket. Kosongkan untuk melewati.</p>

                            <?php
                            // Gabungkan data paket lama + input POST
                            $rows = [];
                            if (isset($_POST['pkg_name'])) {
                                foreach ($_POST['pkg_name'] as $i => $n) {
                                    $rows[] = [
                                        'name' => $n,
                                        'price' => $_POST['pkg_price'][$i] ?? '',
                                        'days' => $_POST['pkg_days'][$i] ?? '',
                                        'revs' => $_POST['pkg_revs'][$i] ?? '',
                                        'feats' => $_POST['pkg_features'][$i] ?? '',
                                    ];
                                }
                            } else {
                                for ($i = 0; $i < 3; $i++) {
                                    $p = $pkgs[$i] ?? null;
                                    $rows[] = $p ? [
                                        'name' => $p['name'],
                                        'price' => rtrim(rtrim(number_format((float)$p['price'], 0, ',', ''), '0'), '.'),
                                        'days' => $p['duration_days'],
                                        'revs' => $p['revisions'],
                                        'feats' => $p['features'],
                                    ] : ['name' => '', 'price' => '', 'days' => '', 'revs' => '', 'feats' => ''];
                                }
                            }
                            $labels = ['Basic', 'Pro', 'Enterprise'];
                            foreach ($rows as $i => $r):
                            ?>
                                <div class="order-summary mb-2" style="background:var(--surface);">
                                    <div class="faint mb-1"><?= e($labels[$i] ?? ('Paket ' . ($i + 1))) ?></div>
                                    <div class="form-row mb-1">
                                        <div class="field">
                                            <label>Nama Paket</label>
                                            <input type="text" name="pkg_name[]" maxlength="60" placeholder="Basic" value="<?= e($r['name']) ?>">
                                        </div>
                                        <div class="field">
                                            <label>Harga (Rp)</label>
                                            <input type="text" name="pkg_price[]" inputmode="numeric" placeholder="1500000" value="<?= e((string)$r['price']) ?>">
                                        </div>
                                    </div>
                                    <div class="form-row mb-1">
                                        <div class="field">
                                            <label>Estimasi (hari)</label>
                                            <input type="number" name="pkg_days[]" min="0" max="365" placeholder="3" value="<?= e((string)$r['days']) ?>">
                                        </div>
                                        <div class="field">
                                            <label>Jumlah Revisi</label>
                                            <input type="number" name="pkg_revs[]" min="0" max="50" placeholder="2" value="<?= e((string)$r['revs']) ?>">
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label>Fitur (satu per baris)</label>
                                        <textarea name="pkg_features[]" style="min-height:70px;" placeholder="Responsive&#10;Form kontak&#10;SEO dasar"><?= e($r['feats']) ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="panel mt-3" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:flex-end;">
                    <a href="my-listings.php" class="btn btn-ghost">Batal</a>
                    <button type="submit" name="simpan" class="btn btn-primary">
                        <?= $is_edit ? 'Simpan Perubahan' : 'Buat Listing' ?>
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
(function () {
    // Tampilkan/sembunyikan field sesuai tipe listing
    var radios = document.querySelectorAll('input[name="type"]');
    function sync() {
        var t = (document.querySelector('input[name="type"]:checked') || {}).value || 'product';
        var isProduct = t === 'product';
        document.getElementById('conditionField').style.display = isProduct ? '' : 'none';
        document.getElementById('stackField').style.display = isProduct ? 'none' : '';
        document.getElementById('websiteField').style.display = isProduct ? 'none' : '';
        document.getElementById('packageBox').style.display = isProduct ? 'none' : '';
    }
    radios.forEach(function (r) { r.addEventListener('change', sync); });
    sync();
})();
</script>

<?php include 'includes/footer.php'; ?>
