<?php
// File: model/NilaiModel.php

class NilaiModel
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function getAllNilai($limit = null, $offset = null, $search = null)
    {
        $sql = "SELECT n.*, m.nim, m.nama as nama_mahasiswa, mk.kode_mata_kuliah, mk.nama_mata_kuliah, mk.sks
                FROM nilai n
                JOIN mahasiswa m ON n.mahasiswa_id = m.id
                JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " WHERE (m.nim LIKE ? OR m.nama LIKE ? OR mk.nama_mata_kuliah LIKE ? OR mk.kode_mata_kuliah LIKE ?)";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam];
            $types = "ssss";
        }

        $sql .= " ORDER BY n.created_at DESC";

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
        $nilai = [];

        while ($row = $result->fetch_assoc()) {
            $nilai[] = $row;
        }

        $stmt->close();
        return $nilai;
    }

    public function getNilaiByMahasiswa($mahasiswaId)
    {
        $stmt = $this->conn->prepare("SELECT n.*, mk.kode_mata_kuliah, mk.nama_mata_kuliah, mk.sks
                                     FROM nilai n
                                     JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
                                     WHERE n.mahasiswa_id = ?
                                     ORDER BY n.semester ASC, mk.nama_mata_kuliah ASC");
        $stmt->bind_param("i", $mahasiswaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $nilai = [];

        while ($row = $result->fetch_assoc()) {
            $nilai[] = $row;
        }

        $stmt->close();
        return $nilai;
    }

    public function getNilaiById($id)
    {
        $stmt = $this->conn->prepare("SELECT n.*, m.nim, m.nama as nama_mahasiswa, mk.kode_mata_kuliah, mk.nama_mata_kuliah, mk.sks
                                     FROM nilai n
                                     JOIN mahasiswa m ON n.mahasiswa_id = m.id
                                     JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
                                     WHERE n.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $nilai = $result->fetch_assoc();
        $stmt->close();

        return $nilai;
    }

    public function createNilai($data)
    {
        try {
            // Check if nilai already exists for this mahasiswa and mata kuliah
            $stmt = $this->conn->prepare("SELECT id FROM nilai WHERE mahasiswa_id = ? AND mata_kuliah_id = ? AND semester = ?");
            $stmt->bind_param("iii", $data['mahasiswa_id'], $data['mata_kuliah_id'], $data['semester']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'Nilai untuk mata kuliah ini pada semester yang sama sudah ada'];
            }
            $stmt->close();

            // Calculate grade based on nilai
            $grade = $this->calculateGrade($data['nilai']);

            $stmt = $this->conn->prepare("INSERT INTO nilai (mahasiswa_id, mata_kuliah_id, semester, nilai, grade, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "iiidss",
                $data['mahasiswa_id'],
                $data['mata_kuliah_id'],
                $data['semester'],
                $data['nilai'],
                $grade,
                $data['keterangan'] ?? null
            );

            if ($stmt->execute()) {
                $nilaiId = $this->conn->insert_id;
                $stmt->close();
                return ['success' => true, 'id' => $nilaiId, 'message' => 'Nilai berhasil ditambahkan'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Gagal menambahkan nilai'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function updateNilai($id, $data)
    {
        try {
            // Calculate grade based on nilai
            $grade = $this->calculateGrade($data['nilai']);

            $stmt = $this->conn->prepare("UPDATE nilai SET mahasiswa_id = ?, mata_kuliah_id = ?, semester = ?, nilai = ?, grade = ?, keterangan = ? WHERE id = ?");
            $stmt->bind_param(
                "iiidssi",
                $data['mahasiswa_id'],
                $data['mata_kuliah_id'],
                $data['semester'],
                $data['nilai'],
                $grade,
                $data['keterangan'] ?? null,
                $id
            );

            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Nilai berhasil diupdate'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Gagal mengupdate nilai'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function deleteNilai($id)
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM nilai WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Nilai berhasil dihapus'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Gagal menghapus nilai'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function calculateGrade($nilai)
    {
        if ($nilai >= 85) return 'A';
        if ($nilai >= 75) return 'B';
        if ($nilai >= 65) return 'C';
        if ($nilai >= 55) return 'D';
        return 'E';
    }

    public function calculateIPK($mahasiswaId)
    {
        $stmt = $this->conn->prepare("SELECT n.grade, mk.sks 
                                     FROM nilai n 
                                     JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id 
                                     WHERE n.mahasiswa_id = ?");
        $stmt->bind_param("i", $mahasiswaId);
        $stmt->execute();
        $result = $stmt->get_result();

        $totalNilai = 0;
        $totalSKS = 0;

        while ($row = $result->fetch_assoc()) {
            $nilaiGrade = 0;
            switch ($row['grade']) {
                case 'A':
                    $nilaiGrade = 4.0;
                    break;
                case 'B':
                    $nilaiGrade = 3.0;
                    break;
                case 'C':
                    $nilaiGrade = 2.0;
                    break;
                case 'D':
                    $nilaiGrade = 1.0;
                    break;
                case 'E':
                    $nilaiGrade = 0.0;
                    break;
            }

            $totalNilai += $nilaiGrade * $row['sks'];
            $totalSKS += $row['sks'];
        }

        $stmt->close();

        return $totalSKS > 0 ? round($totalNilai / $totalSKS, 2) : 0;
    }

    public function getTranskrip($mahasiswaId)
    {
        $stmt = $this->conn->prepare("SELECT n.semester, n.nilai, n.grade, mk.kode_mata_kuliah, mk.nama_mata_kuliah, mk.sks
                                     FROM nilai n
                                     JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
                                     WHERE n.mahasiswa_id = ?
                                     ORDER BY n.semester ASC, mk.nama_mata_kuliah ASC");
        $stmt->bind_param("i", $mahasiswaId);
        $stmt->execute();
        $result = $stmt->get_result();

        $transkrip = [];
        while ($row = $result->fetch_assoc()) {
            $transkrip[$row['semester']][] = $row;
        }

        $stmt->close();
        return $transkrip;
    }

    public function getTotalNilai($search = null)
    {
        $sql = "SELECT COUNT(*) as total FROM nilai n
                JOIN mahasiswa m ON n.mahasiswa_id = m.id
                JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id";

        if ($search) {
            $sql .= " WHERE (m.nim LIKE ? OR m.nama LIKE ? OR mk.nama_mata_kuliah LIKE ? OR mk.kode_mata_kuliah LIKE ?)";
            $searchParam = "%$search%";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssss", $searchParam, $searchParam, $searchParam, $searchParam);
        } else {
            $stmt = $this->conn->prepare($sql);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $stmt->close();

        return $total;
    }

    public function getNilaiStatistik()
    {
        $stats = [];

        // Grade distribution
        $stmt = $this->conn->prepare("SELECT grade, COUNT(*) as jumlah FROM nilai GROUP BY grade ORDER BY grade");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['grade_distribution'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['grade_distribution'][$row['grade']] = $row['jumlah'];
        }
        $stmt->close();

        // Average nilai per mata kuliah
        $stmt = $this->conn->prepare("SELECT mk.nama_mata_kuliah, AVG(n.nilai) as rata_rata 
                                     FROM nilai n 
                                     JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id 
                                     GROUP BY mk.id 
                                     ORDER BY rata_rata DESC 
                                     LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['avg_per_matkul'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['avg_per_matkul'][] = $row;
        }
        $stmt->close();

        return $stats;
    }
}
