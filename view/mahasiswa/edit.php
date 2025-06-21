<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="form-content-card">
        <h2><i class="bi bi-pencil-square me-2"></i>Edit Mahasiswa</h2>

        <?php if (isset($mahasiswa) && $mahasiswa): ?>
            <form action="index.php?act=update&id=<?= htmlspecialchars($mahasiswa['id']) ?>" method="POST" enctype="multipart/form-data">

                <div class="mb-3">
                    <label for="nim" class="form-label">NIM:</label>
                    <input type="text" class="form-control" name="nim" id="nim" value="<?= htmlspecialchars($mahasiswa['nim']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap:</label>
                    <input type="text" class="form-control" name="nama" id="nama" value="<?= htmlspecialchars($mahasiswa['nama']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="jurusan" class="form-label">Jurusan:</label>
                    <select class="form-select" id="jurusan" name="jurusan" required>
                        <option value="">-- Pilih Jurusan --</option>
                        <option value="Teknik Informatika" <?= ($mahasiswa['jurusan'] == 'Teknik Informatika') ? 'selected' : ''; ?>>A11 - Teknik Informatika</option>
                        <option value="Sistem Informasi" <?= ($mahasiswa['jurusan'] == 'Sistem Informasi') ? 'selected' : ''; ?>>A12 - Sistem Informasi</option>
                        <option value="Desain Komunikasi Visual" <?= ($mahasiswa['jurusan'] == 'Desain Komunikasi Visual') ? 'selected' : ''; ?>>D3 - Desain Komunikasi Visual</option>
                        <option value="Teknik Elektro" <?= ($mahasiswa['jurusan'] == 'Teknik Elektro') ? 'selected' : ''; ?>>A12 - Teknik Elektro</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="foto" class="form-label">Ubah Foto (Opsional):</label>
                    <input type="file" class="form-control" name="foto" id="foto" accept="image/*">

                    <?php if (!empty($mahasiswa['foto'])): ?>
                        <div class="mt-2">
                            <label class="form-label d-block">Foto Saat Ini:</label>
                            <img src="uploads/<?= htmlspecialchars($mahasiswa['foto']) ?>" alt="Foto Mahasiswa" class="image-preview">
                        </div>
                    <?php else: ?>
                        <p class="text-muted mt-2">Tidak ada foto saat ini.</p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i> Update Data
                </button>
                <a href="index.php" class="back-link"><i class="bi bi-arrow-left"></i> Kembali ke Data Mahasiswa</a>
            </form>
        <?php else: ?>
            <div class="alert alert-danger text-center">Data mahasiswa tidak ditemukan.</div>
            <a href="index.php" class="back-link"><i class="bi bi-arrow-left"></i> Kembali ke Data Mahasiswa</a>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>

</html>