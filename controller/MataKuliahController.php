<?php
require_once './model/MataKuliahModel.php';
require_once './config/db.php';

class MataKuliahController
{
    private $mataKuliahModel;

    public function __construct()
    {
        global $conn;
        $this->mataKuliahModel = new MataKuliahModel($conn);
    }

    public function index()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $mataKuliah = $this->mataKuliahModel->getAllMataKuliah($limit, $offset, $search);
        $total = $this->mataKuliahModel->getTotalMataKuliah($search);
        $totalPages = ceil($total / $limit);

        include './view/mata-kuliah/index.php';
    }

    public function create()
    {
        include './view/mata-kuliah/create.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=mata-kuliah&action=index');
            return;
        }

        $data = [
            'kode_mata_kuliah' => strtoupper(trim($_POST['kode_mata_kuliah'] ?? '')),
            'nama_mata_kuliah' => trim($_POST['nama_mata_kuliah'] ?? ''),
            'sks' => $_POST['sks'] ?? '',
            'semester' => $_POST['semester'] ?? '',
            'jurusan' => trim($_POST['jurusan'] ?? ''),
            'deskripsi' => trim($_POST['deskripsi'] ?? ''),
            'status' => $_POST['status'] ?? 'active'
        ];

        // Validasi
        $errors = $this->validateMataKuliah($data);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect('index.php?controller=mata-kuliah&action=create');
            return;
        }

        $result = $this->mataKuliahModel->createMataKuliah($data);

        if ($result['success']) {
            $_SESSION['success'] = 'Mata kuliah berhasil ditambahkan';
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('index.php?controller=mata-kuliah&action=index');
    }

    public function show($id)
    {
        $mataKuliah = $this->mataKuliahModel->getMataKuliahById($id);
        if (!$mataKuliah) {
            $_SESSION['error'] = 'Data mata kuliah tidak ditemukan';
            $this->redirect('index.php?controller=mata-kuliah&action=index');
            return;
        }

        // Get mahasiswa yang mengambil mata kuliah ini
        $mahasiswa = $this->mataKuliahModel->getMahasiswaByMataKuliah($id);

        include './view/mata-kuliah/show.php';
    }

    public function edit($id)
    {
        $mataKuliah = $this->mataKuliahModel->getMataKuliahById($id);
        if (!$mataKuliah) {
            $_SESSION['error'] = 'Data mata kuliah tidak ditemukan';
            $this->redirect('index.php?controller=mata-kuliah&action=index');
            return;
        }

        include './view/mata-kuliah/edit.php';
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=mata-kuliah&action=index');
            return;
        }

        $data = [
            'kode_mata_kuliah' => strtoupper(trim($_POST['kode_mata_kuliah'] ?? '')),
            'nama_mata_kuliah' => trim($_POST['nama_mata_kuliah'] ?? ''),
            'sks' => $_POST['sks'] ?? '',
            'semester' => $_POST['semester'] ?? '',
            'jurusan' => trim($_POST['jurusan'] ?? ''),
            'deskripsi' => trim($_POST['deskripsi'] ?? ''),
            'status' => $_POST['status'] ?? 'active'
        ];

        // Validasi
        $errors = $this->validateMataKuliah($data, $id);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect('index.php?controller=mata-kuliah&action=edit&id=' . $id);
            return;
        }

        $result = $this->mataKuliahModel->updateMataKuliah($id, $data);

        if ($result['success']) {
            $_SESSION['success'] = 'Mata kuliah berhasil diupdate';
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('index.php?controller=mata-kuliah&action=index');
    }

    public function delete($id)
    {
        // Cek apakah mata kuliah sudah digunakan di tabel nilai
        $isUsed = $this->mataKuliahModel->checkMataKuliahUsage($id);

        if ($isUsed) {
            $_SESSION['error'] = 'Mata kuliah tidak dapat dihapus karena sudah digunakan dalam data nilai';
            $this->redirect('index.php?controller=mata-kuliah&action=index');
            return;
        }

        $result = $this->mataKuliahModel->deleteMataKuliah($id);

        if ($result['success']) {
            $_SESSION['success'] = 'Mata kuliah berhasil dihapus';
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('index.php?controller=mata-kuliah&action=index');
    }

    // AJAX Methods
    public function searchAjax()
    {
        if (!isset($_GET['search'])) {
            echo json_encode(['error' => 'Parameter search tidak ditemukan']);
            return;
        }

        $search = trim($_GET['search']);
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $mataKuliah = $this->mataKuliahModel->getAllMataKuliah($limit, $offset, $search);
        $total = $this->mataKuliahModel->getTotalMataKuliah($search);

        $response = [
            'data' => $mataKuliah,
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit)
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    public function getMataKuliahByJurusan()
    {
        if (!isset($_GET['jurusan'])) {
            echo json_encode(['error' => 'Parameter jurusan tidak ditemukan']);
            return;
        }

        $jurusan = $_GET['jurusan'];
        $mataKuliah = $this->mataKuliahModel->getMataKuliahByJurusan($jurusan);

        header('Content-Type: application/json');
        echo json_encode($mataKuliah);
    }

    public function getMataKuliahBySemester()
    {
        if (!isset($_GET['semester'])) {
            echo json_encode(['error' => 'Parameter semester tidak ditemukan']);
            return;
        }

        $semester = $_GET['semester'];
        $jurusan = $_GET['jurusan'] ?? '';

        $mataKuliah = $this->mataKuliahModel->getMataKuliahBySemester($semester, $jurusan);

        header('Content-Type: application/json');
        echo json_encode($mataKuliah);
    }

    public function checkKodeExists()
    {
        if (!isset($_POST['kode']) || !isset($_POST['id'])) {
            echo json_encode(['error' => 'Parameter tidak lengkap']);
            return;
        }

        $kode = strtoupper(trim($_POST['kode']));
        $id = $_POST['id'] ? (int)$_POST['id'] : null;

        $exists = $this->mataKuliahModel->checkKodeExists($kode, $id);

        header('Content-Type: application/json');
        echo json_encode(['exists' => $exists]);
    }

    // Helper Methods
    private function validateMataKuliah($data, $excludeId = null)
    {
        $errors = [];

        // Validasi kode mata kuliah
        if (empty($data['kode_mata_kuliah'])) {
            $errors[] = 'Kode mata kuliah harus diisi';
        } elseif (!preg_match('/^[A-Z]{2,4}\d{3,4}$/', $data['kode_mata_kuliah'])) {
            $errors[] = 'Format kode mata kuliah tidak valid (contoh: TIF101, MATH2101)';
        } elseif ($this->mataKuliahModel->checkKodeExists($data['kode_mata_kuliah'], $excludeId)) {
            $errors[] = 'Kode mata kuliah sudah digunakan';
        }

        // Validasi nama mata kuliah
        if (empty($data['nama_mata_kuliah'])) {
            $errors[] = 'Nama mata kuliah harus diisi';
        } elseif (strlen($data['nama_mata_kuliah']) < 3) {
            $errors[] = 'Nama mata kuliah minimal 3 karakter';
        }

        // Validasi SKS
        if (empty($data['sks'])) {
            $errors[] = 'SKS harus diisi';
        } elseif (!is_numeric($data['sks']) || $data['sks'] < 1 || $data['sks'] > 6) {
            $errors[] = 'SKS harus berupa angka antara 1-6';
        }

        // Validasi semester
        if (empty($data['semester'])) {
            $errors[] = 'Semester harus dipilih';
        } elseif (!in_array($data['semester'], [1, 2, 3, 4, 5, 6, 7, 8])) {
            $errors[] = 'Semester tidak valid';
        }

        // Validasi jurusan
        if (empty($data['jurusan'])) {
            $errors[] = 'Jurusan harus diisi';
        }

        return $errors;
    }

    private function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    // Statistik mata kuliah
    public function statistik()
    {
        $stats = [
            'total_mata_kuliah' => $this->mataKuliahModel->getTotalMataKuliah(),
            'mata_kuliah_per_jurusan' => $this->mataKuliahModel->getMataKuliahPerJurusan(),
            'mata_kuliah_per_semester' => $this->mataKuliahModel->getMataKuliahPerSemester(),
            'rata_rata_sks' => $this->mataKuliahModel->getRataRataSKS()
        ];

        include './view/mata-kuliah/statistik.php';
    }

    // Import mata kuliah dari Excel/CSV
    public function import()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'File tidak valid atau tidak dipilih';
                $this->redirect('index.php?controller=mata-kuliah&action=import');
                return;
            }

            $result = $this->mataKuliahModel->importFromFile($_FILES['file']);

            if ($result['success']) {
                $_SESSION['success'] = "Import berhasil! {$result['imported']} mata kuliah ditambahkan, {$result['skipped']} dilewati";
            } else {
                $_SESSION['error'] = $result['message'];
            }

            $this->redirect('index.php?controller=mata-kuliah&action=index');
            return;
        }

        include './view/mata-kuliah/import.php';
    }
}
