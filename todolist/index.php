
<?php
session_start();

require_once __DIR__ . '/config/koneksi.php';

/*
|--------------------------------------------------------------------------
| FUNGSI BANTU
|--------------------------------------------------------------------------
*/

// Mengamankan teks saat ditampilkan ke HTML.
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Mengatur notifikasi sementara.
function setFlash($pesan, $tipe = 'success')
{
    $_SESSION['flash'] = [
        'pesan' => $pesan,
        'tipe' => $tipe
    ];
}

// Kembali ke halaman utama.
function redirectHalaman()
{
    header('Location: index.php');
    exit;
}

// Mengambil satu tugas berdasarkan ID.
function ambilTugas($koneksi, $id)
{
    $stmt = $koneksi->prepare(
        'SELECT id, title, status FROM tasks WHERE id = ?'
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $hasil = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $hasil ?: null;
}

/*
|--------------------------------------------------------------------------
| PROSES TAMBAH, EDIT, STATUS, DAN HAPUS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // Menambahkan tugas baru.
    if ($aksi === 'tambah') {
        $judul = trim($_POST['judul'] ?? '');

        if ($judul === '') {
            setFlash('Judul tugas tidak boleh kosong.', 'danger');
        } elseif (strlen($judul) > 200) {
            setFlash('Judul maksimal 200 karakter.', 'danger');
        } else {
            $stmt = $koneksi->prepare(
                'INSERT INTO tasks (title, status) VALUES (?, ?)'
            );

            $status = 'belum';
            $stmt->bind_param('ss', $judul, $status);

            if ($stmt->execute()) {
                setFlash('Tugas berhasil ditambahkan.');
            } else {
                setFlash('Tugas gagal ditambahkan.', 'danger');
            }

            $stmt->close();
        }

        redirectHalaman();
    }

    // Mengubah judul tugas.
    if ($aksi === 'edit') {
        $id = filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );

        $judul = trim($_POST['judul'] ?? '');

        if (!$id || $id < 1) {
            setFlash('ID tugas tidak valid.', 'danger');
        } elseif ($judul === '') {
            setFlash('Judul tugas tidak boleh kosong.', 'danger');
        } elseif (strlen($judul) > 200) {
            setFlash('Judul maksimal 200 karakter.', 'danger');
        } else {
            $stmt = $koneksi->prepare(
                'UPDATE tasks SET title = ? WHERE id = ?'
            );

            $stmt->bind_param('si', $judul, $id);

            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                setFlash('Judul tugas berhasil diperbarui.');
            } else {
                setFlash('Tugas gagal diperbarui.', 'danger');
            }

            $stmt->close();
        }

        redirectHalaman();
    }

    // Mengubah status selesai/belum selesai.
    if ($aksi === 'status') {
        $id = filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );

        $statusBaru = $_POST['status_baru'] ?? '';

        if (
            !$id ||
            $id < 1 ||
            !in_array($statusBaru, ['belum', 'selesai'], true)
        ) {
            setFlash('Data perubahan status tidak valid.', 'danger');
        } else {
            $stmt = $koneksi->prepare(
                'UPDATE tasks SET status = ? WHERE id = ?'
            );

            $stmt->bind_param('si', $statusBaru, $id);

            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                setFlash('Status tugas berhasil diperbarui.');
            } else {
                setFlash('Status tugas gagal diperbarui.', 'danger');
            }

            $stmt->close();
        }

        redirectHalaman();
    }

    // Menghapus tugas.
    if ($aksi === 'hapus') {
        $id = filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {
            setFlash('ID tugas tidak valid.', 'danger');
        } else {
            $stmt = $koneksi->prepare(
                'DELETE FROM tasks WHERE id = ?'
            );

            $stmt->bind_param('i', $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                setFlash('Tugas berhasil dihapus.');
            } else {
                setFlash('Tugas tidak ditemukan.', 'danger');
            }

            $stmt->close();
        }

        redirectHalaman();
    }
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA DARI DATABASE
|--------------------------------------------------------------------------
*/

// Mode edit berdasarkan parameter GET.
$idEdit = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$tugasEdit = null;

if ($idEdit && $idEdit > 0) {
    $tugasEdit = ambilTugas($koneksi, $idEdit);

    if (!$tugasEdit) {
        setFlash('Tugas yang ingin diedit tidak ditemukan.', 'danger');
        redirectHalaman();
    }
}

// Mengambil seluruh tugas, yang terbaru ditampilkan terlebih dahulu.
$hasilTugas = $koneksi->query(
    'SELECT id, title, status, created_at
     FROM tasks
     ORDER BY id DESC'
);

$tasks = $hasilTugas->fetch_all(MYSQLI_ASSOC);

// Menghitung statistik langsung dari database.
$hasilStatistik = $koneksi->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'selesai'), 0) AS selesai,
        COALESCE(SUM(status = 'belum'), 0) AS belum
     FROM tasks"
);

$statistik = $hasilStatistik->fetch_assoc();

$totalTugas = (int) $statistik['total'];
$totalSelesai = (int) $statistik['selesai'];
$totalBelum = (int) $statistik['belum'];

$persentase = $totalTugas > 0
    ? (int) round(($totalSelesai / $totalTugas) * 100)
    : 0;

// Mengambil notifikasi satu kali.
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>To-Do List | Produktif Setiap Hari</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <nav class="navbar navbar-dark app-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="brand-icon">✓</span>
                To-Do List
            </a>

            <span class="navbar-text text-white-50 small">
                Kelola tugasmu dengan mudah
            </span>
        </div>
    </nav>

    <main class="container py-5">
        <section class="hero-section mb-4">
            <p class="text-primary fw-semibold mb-2">
                
            </p>

            <h1 class="fw-bold">DASHBOARD TUGAS</h1>

            <p class="text-secondary mb-0">
            
            </p>
        </section>

        <?php if ($flash): ?>
            <div
                class="alert alert-<?= e($flash['tipe']) ?> alert-dismissible fade show"
                role="alert"
            >
                <?= e($flash['pesan']) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Tutup"
                ></button>
            </div>
        <?php endif; ?>

        <!-- Statistik tugas -->
        <section class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Total Tugas</div>
                    <div class="stat-number"><?= $totalTugas ?></div>
                    <div class="stat-description">Semua tugas tercatat</div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Belum Selesai</div>
                    <div class="stat-number text-warning"><?= $totalBelum ?></div>
                    <div class="stat-description">Tugas yang perlu dikerjakan</div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Selesai</div>
                    <div class="stat-number text-success"><?= $totalSelesai ?></div>
                    <div class="stat-description">Tugas yang telah diselesaikan</div>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <!-- Form tambah/edit -->
            <div class="col-12 col-lg-4">
                <section class="content-card">
                    <h2 class="section-title">
                        <?= $tugasEdit ? 'Edit Tugas' : 'Tambah Tugas' ?>
                    </h2>

                    <p class="text-secondary small mb-4">
                        <?= $tugasEdit
                            ? 'Perbarui judul tugas yang dipilih.'
                            : 'Tuliskan pekerjaan yang ingin kamu selesaikan.' ?>
                    </p>

                    <form method="POST" action="index.php">
                        <input
                            type="hidden"
                            name="aksi"
                            value="<?= $tugasEdit ? 'edit' : 'tambah' ?>"
                        >

                        <?php if ($tugasEdit): ?>
                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $tugasEdit['id'] ?>"
                            >
                        <?php endif; ?>

                        <label for="judul" class="form-label fw-semibold">
                            Judul Tugas
                        </label>

                        <input
                            type="text"
                            class="form-control form-control-lg mb-3"
                            id="judul"
                            name="judul"
                            maxlength="200"
                            placeholder="Contoh: Belajar PHP"
                            value="<?= $tugasEdit ? e($tugasEdit['title']) : '' ?>"
                            required
                            autofocus
                        >

                        <button type="submit" class="btn btn-primary w-100">
                            <?= $tugasEdit ? 'Simpan Perubahan' : '+ Tambah Tugas' ?>
                        </button>

                        <?php if ($tugasEdit): ?>
                            <a href="index.php" class="btn btn-light w-100 mt-2">
                                Batal Edit
                            </a>
                        <?php endif; ?>
                    </form>
                </section>

                <section class="content-card mt-4">
                    <h2 class="section-title">Progres Tugas</h2>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">Penyelesaian</span>
                        <strong><?= $persentase ?>%</strong>
                    </div>

                    <div
                        class="progress"
                        role="progressbar"
                        aria-label="Progres tugas"
                        aria-valuenow="<?= $persentase ?>"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="progress-bar"
                            style="width: <?= $persentase ?>%"
                        ></div>
                    </div>

                    <p class="small text-secondary mt-3 mb-0">
                        <?= $totalSelesai ?> dari <?= $totalTugas ?> tugas selesai.
                    </p>
                </section>
            </div>

            <!-- Daftar tugas -->
            <div class="col-12 col-lg-8">
                <section class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="section-title mb-1">Daftar Tugas</h2>
                            <p class="text-secondary small mb-0">
                                Kelola semua pekerjaanmu di sini.
                            </p>
                        </div>

                        <span class="badge text-bg-primary">
                            <?= $totalTugas ?> tugas
                        </span>
                    </div>

                    <?php if (empty($tasks)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">✓</div>
                            <h3 class="h5 fw-bold">Belum ada tugas</h3>
                            <p class="text-secondary mb-0">
                                Tambahkan tugas pertamamu melalui formulir.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="task-list">
                            <?php foreach ($tasks as $task): ?>
                                <?php $selesai = $task['status'] === 'selesai'; ?>

                                <article class="task-item">
                                    <div class="task-main">
                                        <form method="POST" action="index.php">
                                            <input
                                                type="hidden"
                                                name="aksi"
                                                value="status"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $task['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="status_baru"
                                                value="<?= $selesai ? 'belum' : 'selesai' ?>"
                                            >

                                            <input
                                                class="form-check-input task-checkbox"
                                                type="checkbox"
                                                aria-label="Ubah status tugas <?= e($task['title']) ?>"
                                                <?= $selesai ? 'checked' : '' ?>
                                                onchange="this.form.submit()"
                                            >
                                        </form>

                                        <div class="task-text">
                                            <div class="task-title <?= $selesai ? 'task-completed' : '' ?>">
                                                <?= e($task['title']) ?>
                                            </div>

                                            <span class="badge <?= $selesai ? 'text-bg-success' : 'text-bg-warning' ?>">
                                                <?= $selesai ? 'Selesai' : 'Belum Selesai' ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="task-actions">
                                        <a
                                            href="index.php?edit=<?= (int) $task['id'] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="index.php"
                                            onsubmit="return confirm('Yakin ingin menghapus tugas ini?')"
                                        >
                                            <input
                                                type="hidden"
                                                name="aksi"
                                                value="hapus"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $task['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <footer class="text-center text-secondary small mt-5">
            Marshall &copy; <?= date('Y') ?> |
            PHP, MySQL, dan Bootstrap 5
        </footer>
    </main>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>
</body>
</html>