<?php
// File: model/Mahasiswa.php
require_once './config/db.php';

class Mahasiswa
{
    private static $conn;

    private static function getConnection()
    {
        global $conn;
        self::$conn = $conn;
        return self::$conn;
    }

    public static function getAllMahasiswa($limit = null, $offset = null, $search = null)
    {
        $conn = self::getConnection();
        $sql = "SELECT m.*, 
                       (SELECT COUNT(*) FROM nilai n WHERE n.mahasiswa_id = m.id) as total_nilai
                FROM mahasiswa m";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " WHERE (m.nim LIKE ? OR m.nama LIKE ? OR m.jurusan LIKE ?)";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam];
            $types = "sss";
        }

        $sql .= " ORDER BY m.created_at DESC";

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

        $stmt = $conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $mahasiswa = [];

        while ($row = $result->fetch_assoc()) {
            $mahasiswa[] = $row;
        }

        $stmt->close();
        return $mahasiswa;
    }

    public function getMahasiswaById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM mahasiswa WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $mahasiswa = $result->fetch_assoc();
        $stmt->close();

        return $mahasiswa;
    }

    public static function find($id)
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $mahasiswa = $result->fetch_assoc();
        $stmt->close();
        return $mahasiswa;
    }

    public static function findByNim($nim)
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE nim = ?");
        $stmt->bind_param("s", $nim);
        $stmt->execute();
        $result = $stmt->get_result();
        $mahasiswa = $result->fetch_assoc();
        $stmt->close();
        return $mahasiswa;
    }

    public static function insert($nim, $nama, $jurusan, $foto = '', $filename = '', $filepath = '', $thumbpath = '', $width = 0, $height = 0)
    {
        $conn = self::getConnection();

        try {
            $stmt = $conn->prepare("INSERT INTO mahasiswa (nim, nama, jurusan, foto, filename, filepath, thumbpath, width, height, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssssssii", $nim, $nama, $jurusan, $foto, $filename, $filepath, $thumbpath, $width, $height);

            $result = $stmt->execute();
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            error_log("Error inserting mahasiswa: " . $e->getMessage());
            return false;
        }
    }

    public static function update($id, $nim, $nama, $jurusan, $foto = '', $filename = '', $filepath = '', $thumbpath = '', $width = 0, $height = 0)
    {
        $conn = self::getConnection();

        try {
            $stmt = $conn->prepare("UPDATE mahasiswa SET nim = ?, nama = ?, jurusan = ?, foto = ?, filename = ?, filepath = ?, thumbpath = ?, width = ?, height = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssssssiis", $nim, $nama, $jurusan, $foto, $filename, $filepath, $thumbpath, $width, $height, $id);

            $result = $stmt->execute();
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            error_log("Error updating mahasiswa: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id)
    {
        $conn = self::getConnection();

        try {
            // Delete related records first
            $stmt = $conn->prepare("DELETE FROM nilai WHERE mahasiswa_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Delete mahasiswa record
            $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            error_log("Error deleting mahasiswa: " . $e->getMessage());
            return false;
        }
    }

    public static function count($search = null)
    {
        $conn = self::getConnection();
        $sql = "SELECT COUNT(*) as total FROM mahasiswa";

        if ($search) {
            $sql .= " WHERE (nim LIKE ? OR nama LIKE ? OR jurusan LIKE ?)";
            $searchParam = "%$search%";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
        } else {
            $stmt = $conn->prepare($sql);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $stmt->close();

        return $total;
    }

    public static function getNilaiMahasiswa($mahasiswaId)
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("
            SELECT n.*, mk.nama_mata_kuliah, mk.kode_mata_kuliah, mk.sks 
            FROM nilai n 
            JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id 
            WHERE n.mahasiswa_id = ? 
            ORDER BY mk.kode_mata_kuliah
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

    public static function getIPK($mahasiswaId)
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("
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
                ) / SUM(mk.sks) as ipk
            FROM nilai n 
            JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id 
            WHERE n.mahasiswa_id = ?
        ");
        $stmt->bind_param("i", $mahasiswaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ipk = $result->fetch_assoc()['ipk'] ?? 0;
        $stmt->close();

        return round($ipk, 2);
    }

    public static function searchLive($query)
    {
        $conn = self::getConnection();
        $searchParam = "%$query%";
        $stmt = $conn->prepare("
            SELECT id, nim, nama, jurusan 
            FROM mahasiswa 
            WHERE nim LIKE ? OR nama LIKE ? OR jurusan LIKE ? 
            LIMIT 10
        ");
        $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
        $stmt->execute();
        $result = $stmt->get_result();
        $mahasiswa = [];

        while ($row = $result->fetch_assoc()) {
            $mahasiswa[] = $row;
        }

        $stmt->close();
        return $mahasiswa;
    }

    public static function getStatistics()
    {
        $conn = self::getConnection();
        $stats = [];

        // Total mahasiswa
        $result = $conn->query("SELECT COUNT(*) as total FROM mahasiswa");
        $stats['total'] = $result->fetch_assoc()['total'];

        // Mahasiswa per jurusan
        $result = $conn->query("SELECT jurusan, COUNT(*) as jumlah FROM mahasiswa GROUP BY jurusan ORDER BY jumlah DESC");
        $stats['per_jurusan'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['per_jurusan'][] = $row;
        }

        // Rata-rata IPK
        $result = $conn->query("
            SELECT AVG(
                (SELECT 
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
                    ) / NULLIF(SUM(mk.sks), 0)
                    FROM nilai n 
                    JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id 
                    WHERE n.mahasiswa_id = m.id
                )
            ) as rata_ipk
            FROM mahasiswa m
        ");
        $stats['rata_ipk'] = round($result->fetch_assoc()['rata_ipk'] ?? 0, 2);

        return $stats;
    }

    public function getMahasiswaByJurusan($jurusan)
    {
        $stmt = $this->conn->prepare("SELECT * FROM mahasiswa WHERE jurusan = ? AND status = 'active' ORDER BY nim ASC");
        $stmt->bind_param("s", $jurusan);
        $stmt->execute();
        $result = $stmt->get_result();
        $mahasiswa = [];

        while ($row = $result->fetch_assoc()) {
            $mahasiswa[] = $row;
        }

        $stmt->close();
        return $mahasiswa;
    }

    public function getMahasiswaByAngkatan($angkatan)
    {
        $stmt = $this->conn->prepare("SELECT * FROM mahasiswa WHERE angkatan = ? AND status = 'active' ORDER BY nim ASC");
        $stmt->bind_param("i", $angkatan);
        $stmt->execute();
        $result = $stmt->get_result();
        $mahasiswa = [];

        while ($row = $result->fetch_assoc()) {
            $mahasiswa[] = $row;
        }

        $stmt->close();
        return $mahasiswa;
    }
}
