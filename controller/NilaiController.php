<?php
require_once './model/NilaiModel.php';
require_once './model/MahasiswaModel.php';
require_once './model/MataKuliahModel.php';
require_once './config/db.php';

class NilaiController
{
    private $nilaiModel;
    private $mahasiswaModel;
    private $mataKuliahModel;

    public function __construct()
    {
        global $conn;
        $this->nilaiModel = new NilaiModel($conn);
        $this->mahasiswaModel = new MahasiswaModel($conn);
        $this->mataKuliahModel = new MataKuliahModel($conn);
    }

    public function index()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $nilai = $this->nilaiModel->getAllNilai($limit, $offset, $search);
        $total = $this->nilaiModel->getTotalNilai($search);
        $totalPages = ceil($total / $limit);

        // Get data untuk dropdown
        $mahasiswa = $this->mahasiswaModel->getAllMahasiswa();
        $mataKuliah = $this->mataKuliahModel->getAllMataKuliah();

        include './view/nilai/index.php';
    }

    public function create()
    {
        $mahasiswa = $this->mahasiswaModel->getAllMahasiswa();
        $mataKuliah = $this->mataKuliahModel->getAllMataKuliah();
        include './view/nilai/create.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=nilai&action=index');
            return;
        }

        $data = [
            'mahasiswa_id' => $_POST['mahasiswa_id'] ?? '',
            'mata_kuliah_id' => $_POST['mata_kuliah_id'] ?? '',
            'nilai' => $_POST['nilai'] ?? '',
            'semester' => $_POST['semester'] ?? '',
            'tahun_akademik' => $_POST['tahun_akademik'] ?? ''
        ];

        // Validasi
        $errors = $this->validateNilai($data);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect('index.php?controller=nilai&action=create');
            return;
        }

        // Hitung grade berdasarkan nilai
        $data['grade'] = $this->calculateGrade($data['nilai']);

        $result = $this->nilaiModel->createNilai($data);

        if ($result['success']) {
            $_SESSION['success'] = 'Nilai berhasil ditambahkan';
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('index.php?controller=nilai&action=index');
    }

    public function edit($id)
    {
        $nilai = $this->nilaiModel->getNilaiById($id);
        if (!$nilai) {
            $_SESSION['error'] = 'Data nilai tidak ditemukan';
            $this->redirect('index.php?controller=nilai&action=index');
            return;
        }

        $mahasiswa = $this->mahasiswaModel->getAllMahasiswa();
        $mataKuliah = $this->mataKuliahModel->getAllMataKuliah();

        include './view/nilai/edit.php';
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=nilai&action=index');
            return;
        }

        $data = [
            'mahasiswa_id' => $_POST['mahasiswa_id'] ?? '',
            'mata_kuliah_id' => $_POST['mata_kuliah_id'] ?? '',
            'nilai' => $_POST['nilai'] ?? '',
            'semester' => $_POST['semester'] ?? '',
            'tahun_akademik' => $_POST['tahun_akademik'] ?? ''
        ];

        // Validasi
        $errors = $this->validateNilai($data);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect('index.php?controller=nilai&action=edit&id=' . $id);
            return;
        }

        // Hitung grade berdasarkan nilai
        $data['grade'] = $this->calculateGrade($data['nilai']);

        $result = $this->nilaiModel->updateNilai($id, $data);

        if ($result['success']) {
            $_SESSION['success'] = 'Nilai berhasil diupdate';
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('index.php?controller=nilai&action=index');
    }

    public function delete($id)
    {
        $result = $this->nilaiModel->deleteNilai($id);

        if ($result['success']) {
            $_SESSION['success'] = 'Nilai berhasil dihapus';
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('index.php?controller=nilai&action=index');
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

        $nilai = $this->nilaiModel->getAllNilai($limit, $offset, $search);
        $total = $this->nilaiModel->getTotalNilai($search);

        $response = [
            'data' => $nilai,
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit)
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    public function getMahasiswaByJurusan()
    {
        if (!isset($_GET['jurusan'])) {
            echo json_encode(['error' => 'Parameter jurusan tidak ditemukan']);
            return;
        }

        $jurusan = $_GET['jurusan'];
        $mahasiswa = $this->mahasiswaModel->getMahasiswaByJurusan($jurusan);

        header('Content-Type: application/json');
        echo json_encode($mahasiswa);
    }

    public function getNilaiByMahasiswa($mahasiswaId)
    {
        $nilai = $this->nilaiModel->getNilaiByMahasiswaId($mahasiswaId);

        header('Content-Type: application/json');
        echo json_encode($nilai);
    }

    // Helper Methods
    private function validateNilai($data)
    {
        $errors = [];

        if (empty($data['mahasiswa_id'])) {
            $errors[] = 'Mahasiswa harus dipilih';
        }

        if (empty($data['mata_kuliah_id'])) {
            $errors[] = 'Mata kuliah harus dipilih';
        }

        if (empty($data['nilai'])) {
            $errors[] = 'Nilai harus diisi';
        } elseif (!is_numeric($data['nilai']) || $data['nilai'] < 0 || $data['nilai'] > 100) {
            $errors[] = 'Nilai harus berupa angka antara 0-100';
        }

        if (empty($data['semester'])) {
            $errors[] = 'Semester harus dipilih';
        }

        if (empty($data['tahun_akademik'])) {
            $errors[] = 'Tahun akademik harus diisi';
        }

        // Cek duplikasi nilai
        if (!empty($data['mahasiswa_id']) && !empty($data['mata_kuliah_id'])) {
            $existing = $this->nilaiModel->checkDuplicateNilai(
                $data['mahasiswa_id'],
                $data['mata_kuliah_id'],
                $data['semester'],
                $data['tahun_akademik']
            );

            if ($existing) {
                $errors[] = 'Nilai untuk mahasiswa dan mata kuliah ini sudah ada di semester dan tahun akademik yang sama';
            }
        }

        return $errors;
    }

    private function calculateGrade($nilai)
    {
        if ($nilai >= 80) return 'A';
        if ($nilai >= 70) return 'B';
        if ($nilai >= 60) return 'C';
        if ($nilai >= 50) return 'D';
        return 'E';
    }

    private function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    // Transkrip Nilai per Mahasiswa
    public function transkrip($mahasiswaId)
    {
        $mahasiswa = $this->mahasiswaModel->getMahasiswaById($mahasiswaId);
        if (!$mahasiswa) {
            $_SESSION['error'] = 'Data mahasiswa tidak ditemukan';
            $this->redirect('index.php?controller=nilai&action=index');
            return;
        }

        $nilai = $this->nilaiModel->getNilaiByMahasiswaId($mahasiswaId);
        $ipk = $this->nilaiModel->calculateIPK($mahasiswaId);

        include './view/nilai/transkrip.php';
    }

    // Export ke PDF
    public function exportPDF($mahasiswaId)
    {
        // Implementasi export PDF akan ditambahkan nanti
        $_SESSION['info'] = 'Fitur export PDF akan segera tersedia';
        $this->redirect('index.php?controller=nilai&action=transkrip&id=' . $mahasiswaId);
    }
}
