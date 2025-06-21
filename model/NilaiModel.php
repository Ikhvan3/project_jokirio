<?php
// File: model/NilaiModel.php
require_once './config/db.php';

class NilaiModel
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function getAllNilai($limit = null, $offset = null, $search = null, $jurusan = null, $semester = null)
    {
        $sql = "SELECT n.*, m.nim, m.nama as nama_mahasiswa, m.jurusan,
                       mk.kode_mata_kuliah, mk.nama_mata_kuliah, mk.sks, mk.semester
                FROM nilai n
                JOIN mahasiswa m ON n.mahasiswa_id = m.id
                JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id";

        $params = [];
        $types = "";
        $conditions = [];

        if ($search) {
            $conditions[] = "(m.nim LIKE ? OR m.nama LIKE ? OR mk.nama_mata_kuliah LIKE ? OR mk.kode_mata_kuliah LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            $types .= "ssss";
        }

        if ($jurusan) {
            $conditions[] = "m.jurusan = ?";
            $params[] = $jurusan;
            $types .= "s";
        }

        if ($semester) {
            $conditions[] = "mk.semester = ?";
            $params[] = $semester;
            $types .= "i";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY m.nim ASC, mk.kode_mata_kuliah ASC";

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

    public function getNilaiById($id)
    {
        $stmt = $this->conn->prepare("
            SELECT n.*, m.nim, m.nama as nama_mahasiswa, m.jurusan,
                   mk.kode_mata_kuliah, mk.nama_mata_kuliah, mk.sks, mk.semester
            FROM nilai n
            JOIN mahasiswa m ON n.mahasiswa_id = m.id
            JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
            WHERE n.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $nilai = $result->fetch_assoc();
        $stmt->close();
        return $nilai;
    }

    public function getNilaiByMahasiswa($mahasiswaId)
    {
        $stmt = $this->conn->prepare("
            SELECT n.*, mk.kode_mata_kuliah, mk.nama_mata_kuliah, mk.sks, mk.semester
            FROM nilai n
            JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
            WHERE n.mahasiswa_id = ?
            ORDER BY mk.semester ASC, mk.kode_mata_kuliah ASC
        ");
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

    public function createNilai($data)
    {
        try {
            // Check if nilai already exists for this mahasiswa and mata kuliah
            $stmt = $this->conn->prepare("SELECT id FROM nilai WHERE mahasiswa_id = ? AND mata_kuliah_id = ?");
            $stmt->bind_param("ii", $data['mahasiswa_id'], $data['mata_kuliah_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'Nilai untuk mata kuliah ini sudah ada untuk mahasiswa tersebut'];
            }
            $stmt->close();

            // Calculate grade based on nilai
            $grade = $this->calculateGrade($data['nilai']);

            $stmt = $this->conn->prepare("
                INSERT INTO nilai (mahasiswa_id, mata_kuliah_id, nilai, grade, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iids", $data['mahasiswa_id'], $data['mata_kuliah_id'], $data['nilai'], $grade);

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
            // Check if nilai already exists for this mahasiswa and mata kuliah (exclude current record)
            $stmt = $this->conn->prepare("SELECT id FROM nilai WHERE mahasiswa_id = ? AND mata_kuliah_id = ? AND id != ?");
            $stmt->bind_param("iii", $data['mahasiswa_id'], $data['mata_kuliah_id'], $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'Nilai untuk mata kuliah ini sudah ada untuk mahasiswa tersebut'];
            }
            $stmt->close();

            // Calculate grade based on nilai
            $grade = $this->calculateGrade($data['nilai']);

            $stmt = $this->conn->prepare("
                UPDATE nilai SET mahasiswa_id = ?, mata_kuliah_id = ?, nilai = ?, grade = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("iidsi", $data['mahasiswa_id'], $data['mata_kuliah_id'], $data['nilai'], $grade, $id);

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

    public function getTotalNilai($search = null, $jurusan = null, $semester = null)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM nilai n
                JOIN mahasiswa m ON n.mahasiswa_id = m.id
                JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id";

        $params = [];
        $types = "";
        $conditions = [];

        if ($search) {
            $conditions[] = "(m.nim LIKE ? OR m.nama LIKE ? OR mk.nama_mata_kuliah LIKE ? OR mk.kode_mata_kuliah LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            $types .= "ssss";
        }

        if ($jurusan) {
            $conditions[] = "m.jurusan = ?";
            $params[] = $jurusan;
            $types .= "s";
        }

        if ($semester) {
            $conditions[] = "mk.semester = ?";
            $params[] = $semester;
            $types .= "i";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $stmt->close();

        return $total;
    }

    public function getIPKMahasiswa($mahasiswaId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                SUM(
                    CASE 
                        WHEN n.grade = 'A' THEN 4.0 * mk.sks
                        WHEN n.grade = 'B+' THEN 3.5 * mk.sks
                        WHEN n.grade = 'B' THEN 3.0 * mk.sks
                        WHEN n.grade = 'C+' THEN 2.5 * mk.sks
                        WHEN n.grade = 'C' THEN 2.0 * mk.sks
                        WHEN n.grade = 'D' THEN 1.0 * mk.sks
                        ELSE 0
                    END
                ) / NULLIF(SUM(mk.sks), 0) as ipk,
                SUM(mk.sks) as total_sks
            FROM nilai n 
            JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id 
            WHERE n.mahasiswa_id = ?
        ");
        $stmt->bind_param("i", $mahasiswaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return [
            'ipk' => round($data['ipk'] ?? 0, 2),
            'total_sks' => $data['total_sks'] ?? 0
        ];
    }

    public function getTranskrip($mahasiswaId)
    {
        $stmt = $this->conn->prepare("
            SELECT n.*, mk.kode_mata_kuliah, mk.nama_mata_kuliah, mk.sks, mk.semester,
                   m.nim, m.nama as nama_mahasiswa, m.jurusan
            FROM nilai n
            JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
            JOIN mahasiswa m ON n.mahasiswa_id = m.id
            WHERE n.mahasiswa_id = ?
            ORDER BY mk.semester ASC, mk.kode_mata_kuliah ASC
        ");
        $stmt->bind_param("i", $mahasiswaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transkrip = [];

        while ($row = $result->fetch_assoc()) {
            $transkrip[] = $row;
        }

        $stmt->close();
        return $transkrip;
    }

    private function calculateGrade($nilai)
    {
        if ($nilai >= 85) return 'A';
        elseif ($nilai >= 80) return 'B+';
        elseif ($nilai >= 75) return 'B';
        elseif ($nilai >= 70) return 'C+';
        elseif ($nilai >= 65) return 'C';
        elseif ($nilai >= 60) return 'D';
        else return 'E';
    }

    public function getStatistikNilai()
    {
        $stats = [];

        // Total nilai
        $result = $this->conn->query("SELECT COUNT(*) as total FROM nilai");
        $stats['total'] = $result->fetch_assoc()['total'];

        // Distribusi grade
        $result = $this->conn->query("
            SELECT grade, COUNT(*) as jumlah 
            FROM nilai 
            GROUP BY grade 
            ORDER BY FIELD(grade, 'A', 'B+', 'B', 'C+', 'C', 'D', 'E')
        ");
        $stats['distribusi_grade'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['distribusi_grade'][] = $row;
        }

        // Rata-rata nilai
        $result = $this->conn->query("SELECT AVG(nilai) as rata_nilai FROM nilai");
        $stats['rata_nilai'] = round($result->fetch_assoc()['rata_nilai'] ?? 0, 2);

        // Nilai tertinggi dan terendah
        $result = $this->conn->query("SELECT MAX(nilai) as max_nilai, MIN(nilai) as min_nilai FROM nilai");
        $data = $result->fetch_assoc();
        $stats['max_nilai'] = $data['max_nilai'] ?? 0;
        $stats['min_nilai'] = $data['min_nilai'] ?? 0;

        return $stats;
    }

    public function searchLive($query)
    {
        $searchParam = "%$query%";
        $stmt = $this->conn->prepare("
            SELECT DISTINCT n.id, m.nim, m.nama as nama_mahasiswa, 
                   mk.kode_mata_kuliah, mk.nama_mata_kuliah, n.nilai, n.grade
            FROM nilai n
            JOIN mahasiswa m ON n.mahasiswa_id = m.id
            JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id
            WHERE m.nim LIKE ? OR m.nama LIKE ? OR mk.nama_mata_kuliah LIKE ? OR mk.kode_mata_kuliah LIKE ?
            LIMIT 10
        ");
        $stmt->bind_param("ssss", $searchParam, $searchParam, $searchParam, $searchParam);
        $stmt->execute();
        $result = $stmt->get_result();
        $nilai = [];

        while ($row = $result->fetch_assoc()) {
            $nilai[] = $row;
        }

        $stmt->close();
        return $nilai;
    }

    public function getRankingMahasiswa($jurusan = null)
    {
        $sql = "
            SELECT m.id, m.nim, m.nama, m.jurusan,
                   AVG(
                       CASE 
                           WHEN n.grade = 'A' THEN 4.0
                           WHEN n.grade = 'B+' THEN 3.5
                           WHEN n.grade = 'B' THEN 3.0
                           WHEN n.grade = 'C+' THEN 2.5
                           WHEN n.grade = 'C' THEN 2.0
                           WHEN n.grade = 'D' THEN 1.0
                           ELSE 0
                       END
                   ) as ipk,
                   COUNT(n.id) as total_mata_kuliah
            FROM mahasiswa m
            LEFT JOIN nilai n ON m.id = n.mahasiswa_id
        ";

        if ($jurusan) {
            $sql .= " WHERE m.jurusan = ?";
        }

        $sql .= " GROUP BY m.id ORDER BY ipk DESC LIMIT 20";

        $stmt = $this->conn->prepare($sql);

        if ($jurusan) {
            $stmt->bind_param("s", $jurusan);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $ranking = [];

        while ($row = $result->fetch_assoc()) {
            $row['ipk'] = round($row['ipk'] ?? 0, 2);
            $ranking[] = $row;
        }

        $stmt->close();
        return $ranking;
    }
}
