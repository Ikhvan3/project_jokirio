<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Mahasiswa (Valentino Arizona Putra - A12.2023.06995)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="form-content-card">
        <h2><i class="bi bi-person-plus-fill me-2"></i>Tambah Mahasiswa</h2>
        <form action="index.php?act=store" method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label for="nim" class="form-label">NIM:</label>
                <input type="text" class="form-control" name="nim" id="nim" required placeholder="Contoh: A12.2023.07108">
            </div>

            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap:</label>
                <input type="text" class="form-control" name="nama" id="nama" required placeholder="Masukkan Nama Anda">
            </div>

            <div class="mb-3">
                <label for="jurusan" class="form-label">Jurusan:</label>
                <select class="form-select" id="jurusan" name="jurusan" required>
                    <option value="">-- Pilih Jurusan --</option>
                    <option value="Teknik Informatika">A11 - Teknik Informatika</option>
                    <option value="Sistem Informasi">A12 - Sistem Informasi</option>
                    <option value="Desain Komunikasi Visual">D3 - Desain Komunikasi Visual</option>
                    <option value="Teknik Elektro">A12 - Teknik Elektro</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="foto" class="form-label">Foto (Opsional):</label>
                <input type="file" class="form-control" name="foto" id="foto" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan Data
            </button>
            <a href="index.php" class="back-link"><i class="bi bi-arrow-left"></i> Kembali ke Data Mahasiswa</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>

</html>