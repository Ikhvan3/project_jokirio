<?php
// File: model/MataKuliahModel.php

class MataKuliahModel
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function getAllMataKuliah($limit = null, $offset = null, $search = null)
    {
        $sql = "SELECT * FROM mata_kuliah";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " WHERE (kode_mata_kuliah LIKE ? OR nama_mata_kuliah LIKE ? OR jurusan LIKE ?)";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam];
            $types = "sss";
        }

        $sql .= " ORDER BY kode_mata_kuliah ASC";

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            $types .= "i";

            if ($offset !== null) {
                $sql .= " OFFSET ?";
                $params[] = $offset;
                $types .= "i";
            }
        }

        $stmt = $this->conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $mataKuliah = [];

        while ($row = $result->fetch_assoc()) {
            $mataKuliah[] = $row;
        }

        $stmt->close();
        return $mataKuliah;
    }

    public function getMataKuliahById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $mataKuliah = $result->fetch_assoc();
        $stmt->close();

        return $mataKuliah;
    }

    public function createMataKuliah($data)
    {
        try {
            // Check if kode_mata_kuliah already exists
            $stmt = $this->conn->prepare("SELECT id FROM mata_kuliah WHERE kode_mata_kuliah = ?");
            $stmt->bind_param("s", $data['kode_mata_kuliah']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'Kode mata kuliah sudah digunakan'];
            }
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO mata_kuliah (kode_mata_kuliah, nama_mata_kuliah, sks, semester, jurusan, deskripsi, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param(
                "ssiisss",
                $data['kode_mata_kuliah'],
                $data['nama_mata_kuliah'],
                $data['sks'],
                $data['semester'],
                $data['jurusan'],
                $data['deskripsi'] ?? null,
                $data['status'] ?? 'active'
            );

            if ($stmt->execute()) {
                $mataKuliahId = $this->conn->insert_id;
                $stmt->close();
                return ['success' => true, 'id' => $mataKuliahId, 'message' => 'Mata kuliah berhasil ditambahkan'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Gagal menambahkan mata kuliah'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function updateMataKuliah($id, $data)
    {
        try {
            // Check if kode_mata_kuliah already exists (exclude current record)
            $stmt = $this->conn->prepare("SELECT id FROM mata_kuliah WHERE kode_mata_kuliah = ? AND id != ?");
            $stmt->bind_param("si", $data['kode_mata_kuliah'], $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'Kode mata kuliah sudah digunakan'];
            }
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE mata_kuliah SET kode_mata_kuliah = ?, nama_mata_kuliah = ?, sks = ?, semester = ?, jurusan = ?, deskripsi = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param(
                "ssiisssi",
                $data['kode_mata_kuliah'],
                $data['nama_mata_kuliah'],
                $data['sks'],
                $data['semester'],
                $data['jurusan'],
                $data['deskripsi'] ?? null,
                $data['status'] ?? 'active',
                $id
            );

            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Mata kuliah berhasil diupdate'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Gagal mengupdate mata kuliah'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function deleteMataKuliah($id)
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM mata_kuliah WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Mata kuliah berhasil dihapus'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Gagal menghapus mata kuliah'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getTotalMataKuliah($search = null)
    {
        $sql = "SELECT COUNT(*) as total FROM mata_kuliah";

        if ($search) {
            $sql .= " WHERE (kode_mata_kuliah LIKE ? OR nama_mata_kuliah LIKE ? OR jurusan LIKE ?)";
            $searchParam = "%$search%";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
        } else {
            $stmt = $this->conn->prepare($sql);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $stmt->close();

        return $total;
    }

    public function getMataKuliahByJurusan($jurusan)
    {
        $stmt = $this->conn->prepare("SELECT * FROM mata_kuliah WHERE jurusan = ? AND status = 'active' ORDER BY semester ASC, kode_mata_kuliah ASC");
        $stmt->bind_param("s", $jurusan);
        $stmt->execute();
        $result = $stmt->get_result();
        $mataKuliah = [];

        while ($row = $result->fetch_assoc()) {
            $mataKuliah[] = $row;
        }

        $stmt->close();
        return $mataKuliah;
    }

    public function getMataKuliahBySemester($semester, $jurusan = '')
    {
        if ($jurusan) {
            $stmt = $this->conn->prepare("SELECT * FROM mata_kuliah WHERE semester = ? AND jurusan = ? AND status = 'active' ORDER BY kode_mata_kuliah ASC");
            $stmt->bind_param("is", $semester, $jurusan);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM mata_kuliah WHERE semester = ? AND status = 'active' ORDER BY kode_mata_kuliah ASC");
            $stmt->bind_param("i", $semester);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $mataKuliah = [];

        while ($row = $result->fetch_assoc()) {
            $mataKuliah[] = $row;
        }

        $stmt->close();
        return $mataKuliah;
    }

    public function checkKodeExists($kode, $excludeId = null)
    {
        $sql = "SELECT id FROM mata_kuliah WHERE kode_mata_kuliah = ?";
        $params = [$kode];
        $types = "s";

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function checkMataKuliahUsage($id)
    {
        $stmt = $this->conn->prepare("SELECT id FROM nilai WHERE mata_kuliah_id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $isUsed = $result->num_rows > 0;
        $stmt->close();

        return $isUsed;
    }

    public function getMahasiswaByMataKuliah($mataKuliahId)
    {
        $stmt = $this->conn->prepare("
            SELECT m.id, m.nim, m.nama, m.jurusan, n.nilai, n.grade, n.created_at
            FROM mahasiswa m
            JOIN nilai n ON m.id = n.mahasiswa_id
            WHERE n.mata_kuliah_id = ?
            ORDER BY m.nim ASC
        ");
        $stmt->bind_param("i", $mataKuliahId);
        $stmt->execute();
        $result = $stmt->get_result();
        $mahasiswa = [];

        while ($row = $result->fetch_assoc()) {
            $mahasiswa[] = $row;
        }

        $stmt->close();
        return $mahasiswa;
    }

    // Statistik methods untuk controller
    public function getMataKuliahPerJurusan()
    {
        $stmt = $this->conn->prepare("
            SELECT jurusan, COUNT(*) as jumlah 
            FROM mata_kuliah 
            WHERE status = 'active' 
            GROUP BY jurusan 
            ORDER BY jurusan ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = [];

        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }

        $stmt->close();
        return $stats;
    }

    public function getMataKuliahPerSemester()
    {
        $stmt = $this->conn->prepare("
            SELECT semester, COUNT(*) as jumlah 
            FROM mata_kuliah 
            WHERE status = 'active' 
            GROUP BY semester 
            ORDER BY semester ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = [];

        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }

        $stmt->close();
        return $stats;
    }

    public function getRataRataSKS()
    {
        $stmt = $this->conn->prepare("SELECT AVG(sks) as rata_sks FROM mata_kuliah WHERE status = 'active'");
        $stmt->execute();
        $result = $stmt->get_result();
        $avg = $result->fetch_assoc()['rata_sks'];
        $stmt->close();

        return round($avg ?? 0, 2);
    }

    // Import method untuk controller
    public function importFromFile($file)
    {
        try {
            $imported = 0;
            $skipped = 0;
            $errors = [];

            // Validasi file
            $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if (!in_array($file['type'], $allowedTypes)) {
                return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan CSV atau Excel.'];
            }

            $fileName = $file['tmp_name'];
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

            if (strtolower($fileExtension) === 'csv') {
                $handle = fopen($fileName, 'r');
                if ($handle !== FALSE) {
                    // Skip header row
                    fgetcsv($handle);

                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($data) >= 5) {
                            $mataKuliahData = [
                                'kode_mata_kuliah' => strtoupper(trim($data[0])),
                                'nama_mata_kuliah' => trim($data[1]),
                                'sks' => (int)$data[2],
                                'semester' => (int)$data[3],
                                'jurusan' => trim($data[4]),
                                'deskripsi' => isset($data[5]) ? trim($data[5]) : '',
                                'status' => 'active'
                            ];

                            // Validasi basic
                            if (!empty($mataKuliahData['kode_mata_kuliah']) && !empty($mataKuliahData['nama_mata_kuliah'])) {
                                $result = $this->createMataKuliah($mataKuliahData);
                                if ($result['success']) {
                                    $imported++;
                                } else {
                                    $skipped++;
                                    $errors[] = "Baris dengan kode {$mataKuliahData['kode_mata_kuliah']}: " . $result['message'];
                                }
                            } else {
                                $skipped++;
                            }
                        }
                    }
                    fclose($handle);
                }
            }

            return [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error saat import: ' . $e->getMessage()];
        }
    }
}
