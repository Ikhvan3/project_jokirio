<?php
require_once './model/NilaiModel.php';
require_once './model/MahasiswaModel.php';
require_once './model/MataKuliahModel.php';
require_once './config/auth/auth.php';

class NilaiController
{
    private $nilaiModel;
    private $mahasiswaModel;
    private $mataKuliahModel;
    private $auth;

    public function __construct()
    {
        global $conn, $auth;
        $this->nilaiModel = new NilaiModel($conn);
        $this->mahasiswaModel = new Mahasiswa($conn);
        $this->mataKuliahModel = new MataKuliahModel($conn);
        $this->auth = $auth;

        // Pastikan user sudah login
        $this->auth->requireLogin();
    }

    public function index()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $jurusan = isset($_GET['jurusan']) ? trim($_GET['jurusan']) : '';
        $semester = isset($_GET['semester']) ? (int)$_GET['semester'] : null;

        $nilai = $this->nilaiModel->getAllNilai($limit, $offset, $search, $jurusan, $semester);
        $total = $this->nilaiModel->getTotalNilai($search, $jurusan, $semester);
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
            'nilai' => $_POST['nilai'] ?? ''
        ];

        // Validasi
        $errors = $this->validateNilai($data);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect('index.php?controller=nilai&action=create');
            return;
        }

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
            'nilai' => $_POST['nilai'] ?? ''
        ];

        // Validasi
        $errors = $this->validateNilai($data, $id);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect('index.php?controller=nilai&action=edit&id=' . $id);
            return;
        }

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
        header('Content-Type: application/json');

        if (!isset($_GET['search'])) {
            echo json_encode(['error' => 'Parameter search tidak ditemukan']);
            return;
        }

        $search = trim($_GET['search']);
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $jurusan = isset($_GET['jurusan']) ? trim($_GET['jurusan']) : '';
        $semester = isset($_GET['semester']) ? (int)$_GET['semester'] : null;

        $nilai = $this->nilaiModel->getAllNilai($limit, $offset, $search, $jurusan, $semester);
        $total = $this->nilaiModel->getTotalNilai($search, $jurusan, $semester);

        $response = [
            'data' => $nilai,
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $limit)
        ];

        echo json_encode($response);
    }

    public function liveSearch()
    {
        header('Content-Type: application/json');

        if (!isset($_GET['q'])) {
            echo json_encode(['error' => 'Parameter q tidak ditemukan']);
            return;
        }

        $query = trim($_GET['q']);
        $hasil = $this->nilaiModel->searchLive($query);

        echo json_encode($hasil);
    }

    public function getMahasiswaByJurusan()
    {
        header('Content-Type: application/json');

        if (!isset($_GET['jurusan'])) {
            echo json_encode(['error' => 'Parameter jurusan tidak ditemukan']);
            return;
        }

        $jurusan = $_GET['jurusan'];
        $mahasiswa = $this->mahasiswaModel->getMahasiswaByJurusan($jurusan);

        echo json_encode($mahasiswa);
    }

    public function getNilaiByMahasiswa($mahasiswaId)
    {
        header('Content-Type: application/json');
        $nilai = $this->nilaiModel->getNilaiByMahasiswa($mahasiswaId);
        echo json_encode($nilai);
    }

    public function getStatistik()
    {
        header('Content-Type: application/json');
        $stats = $this->nilaiModel->getStatistikNilai();
        echo json_encode($stats);
    }

    public function getRanking()
    {
        header('Content-Type: application/json');
        $jurusan = isset($_GET['jurusan']) ? $_GET['jurusan'] : null;
        $ranking = $this->nilaiModel->getRankingMahasiswa($jurusan);
        echo json_encode($ranking);
    }

    // Helper Methods
    private function validateNilai($data, $excludeId = null)
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

        // Cek duplikasi nilai (sudah ada di model)
        // Model akan handle duplikasi check

        return $errors;
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

        $transkrip = $this->nilaiModel->getTranskrip($mahasiswaId);
        $ipkData = $this->nilaiModel->getIPKMahasiswa($mahasiswaId);

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
