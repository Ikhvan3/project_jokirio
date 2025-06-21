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
            $sql .= " WHERE (kode_mata_kuliah LIKE ? OR nama_mata_kuliah LIKE ? OR dosen_pengampu LIKE ?)";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam];
            $types = "sss";
        }

        $sql .= " ORDER BY kode_mata_kuliah ASC";

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            $types .= "i";

            if ($offset) {
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

            $stmt = $this->conn->prepare("INSERT INTO mata_kuliah (kode_mata_kuliah, nama_mata_kuliah, sks, semester, dosen_pengampu, deskripsi) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssiiss",
                $data['kode_mata_kuliah'],
                $data['nama_mata_kuliah'],
                $data['sks'],
                $data['semester'],
                $data['dosen_pengampu'],
                $data['deskripsi'] ?? null
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

            $stmt = $this->conn->prepare("UPDATE mata_kuliah SET kode_mata_kuliah = ?, nama_mata_kuliah = ?, sks = ?, semester = ?, dosen_pengampu = ?, deskripsi = ? WHERE id = ?");
            $stmt->bind_param(
                "ssiissi",
                $data['kode_mata_kuliah'],
                $data['nama_mata_kuliah'],
                $data['sks'],
                $data['semester'],
                $data['dosen_pengampu'],
                $data['deskripsi'] ?? null,
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
            // Check if mata kuliah is used in nilai
            $stmt = $this->conn->prepare("SELECT id FROM nilai WHERE mata_kuliah_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'Mata kuliah tidak dapat dihapus karena masih digunakan dalam data nilai'];
            }
            $stmt->close();

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
            $sql .= " WHERE (kode_mata_kuliah LIKE ? OR nama_mata_kuliah LIKE ? OR dosen_pengampu LIKE ?)";
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

    public function getMataKuliahByDosen($dosenName)
    {
        $stmt = $this->conn->prepare("SELECT * FROM mata_kuliah WHERE dosen_pengampu = ? ORDER BY kode_mata_kuliah ASC");
        $stmt->bind_param("s", $dosenName);
        $stmt->execute();
        $result = $stmt->get_result();
        $mataKuliah = [];

        while ($row = $result->fetch_assoc()) {
            $mataKuliah[] = $row;
        }

        $stmt->close();
        return $mataKuliah;
    }

    public function getMataKuliahBySemester($semester)
    {
        $stmt = $this->conn->prepare("SELECT * FROM mata_kuliah WHERE semester = ? ORDER BY kode_mata_kuliah ASC");
        $stmt->bind_param("i", $semester);
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

    public function getStatistics()
    {
        $stats = [];

        // Total mata kuliah
        $result = $this->conn->query("SELECT COUNT(*) as total FROM mata_kuliah");
        $stats['total'] = $result->fetch_assoc()['total'];

        // Total SKS
        $result = $this->conn->query("SELECT SUM(sks) as total_sks FROM mata_kuliah");
        $stats['total_sks'] = $result->fetch_assoc()['total_sks'] ?? 0;

        // Mata kuliah per semester
        $result = $this->conn->query("SELECT semester, COUNT(*) as jumlah FROM mata_kuliah GROUP BY semester ORDER BY semester");
        $stats['per_semester'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['per_semester'][$row['semester']] = $row['jumlah'];
        }

        // Dosen pengampu
        $result = $this->conn->query("SELECT COUNT(DISTINCT dosen_pengampu) as total_dosen FROM mata_kuliah WHERE dosen_pengampu IS NOT NULL");
        $stats['total_dosen'] = $result->fetch_assoc()['total_dosen'];

        return $stats;
    }
}
