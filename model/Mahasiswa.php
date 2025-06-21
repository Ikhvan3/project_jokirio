<?php
// File: model/MahasiswaModel.php

class MahasiswaModel
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function getAllMahasiswa($limit = null, $offset = null, $search = null)
    {
        $sql = "SELECT m.*, u.username, u.email, u.profile_photo 
                FROM mahasiswa m 
                LEFT JOIN users u ON m.user_id = u.id";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " WHERE (m.nim LIKE ? OR m.nama LIKE ? OR m.jurusan LIKE ? OR u.email LIKE ?)";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam];
            $types = "ssss";
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

        $stmt = $this->conn->prepare($sql);

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
        $stmt = $this->conn->prepare("SELECT m.*, u.username, u.email, u.profile_photo 
                                     FROM mahasiswa m 
                                     LEFT JOIN users u ON m.user_id = u.id 
                                     WHERE m.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $mahasiswa = $result->fetch_assoc();
        $stmt->close();

        return $mahasiswa;
    }

    public function getMahasiswaByUserId($userId)
    {
        $stmt = $this->conn->prepare("SELECT m.*, u.username, u.email, u.profile_photo 
                                     FROM mahasiswa m 
                                     JOIN users u ON m.user_id = u.id 
                                     WHERE m.user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $mahasiswa = $result->fetch_assoc();
        $stmt->close();

        return $mahasiswa;
    }

    public function createMahasiswa($data)
    {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Create user account if username and email provided
            $userId = null;
            if (isset($data['username']) && isset($data['email'])) {
                $hashedPassword = password_hash($data['password'] ?? 'password123', PASSWORD_DEFAULT);

                $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'mahasiswa')");
                $stmt->bind_param("sss", $data['username'], $data['email'], $hashedPassword);

                if (!$stmt->execute()) {
                    throw new Exception("Failed to create user account");
                }

                $userId = $this->conn->insert_id;
                $stmt->close();
            }

            // Create mahasiswa record
            $stmt = $this->conn->prepare("INSERT INTO mahasiswa (nim, nama, jurusan, angkatan, alamat, no_hp, user_id, foto) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "sssissis",
                $data['nim'],
                $data['nama'],
                $data['jurusan'],
                $data['angkatan'],
                $data['alamat'],
                $data['no_hp'],
                $userId,
                $data['foto'] ?? null
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to create mahasiswa record");
            }

            $mahasiswaId = $this->conn->insert_id;
            $stmt->close();

            // Commit transaction
            $this->conn->commit();

            return ['success' => true, 'id' => $mahasiswaId, 'user_id' => $userId];
        } catch (Exception $e) {
            // Rollback transaction
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateMahasiswa($id, $data)
    {
        try {
            $stmt = $this->conn->prepare("UPDATE mahasiswa SET nim = ?, nama = ?, jurusan = ?, angkatan = ?, alamat = ?, no_hp = ?, foto = ? WHERE id = ?");
            $stmt->bind_param(
                "sssisssi",
                $data['nim'],
                $data['nama'],
                $data['jurusan'],
                $data['angkatan'],
                $data['alamat'],
                $data['no_hp'],
                $data['foto'] ?? null,
                $id
            );

            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Data mahasiswa berhasil diupdate'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Gagal mengupdate data mahasiswa'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function deleteMahasiswa($id)
    {
        try {
            // Get mahasiswa data first
            $mahasiswa = $this->getMahasiswaById($id);
            if (!$mahasiswa) {
                return ['success' => false, 'message' => 'Data mahasiswa tidak ditemukan'];
            }

            // Start transaction
            $this->conn->begin_transaction();

            // Delete related nilai records
            $stmt = $this->conn->prepare("DELETE FROM nilai WHERE mahasiswa_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Delete mahasiswa record
            $stmt = $this->conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
            $stmt->bind_param("i", $id);

            if (!$stmt->execute()) {
                throw new Exception("Failed to delete mahasiswa record");
            }
            $stmt->close();

            // Delete user account if exists
            if ($mahasiswa['user_id']) {
                $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $mahasiswa['user_id']);
                $stmt->execute();
                $stmt->close();
            }

            // Delete photo file if exists
            if ($mahasiswa['foto'] && file_exists("uploads/mahasiswa/" . $mahasiswa['foto'])) {
                unlink("uploads/mahasiswa/" . $mahasiswa['foto']);
            }

            // Commit transaction
            $this->conn->commit();

            return ['success' => true, 'message' => 'Data mahasiswa berhasil dihapus'];
        } catch (Exception $e) {
            // Rollback transaction
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getTotalMahasiswa($search = null)
    {
        $sql = "SELECT COUNT(*) as total FROM mahasiswa m LEFT JOIN users u ON m.user_id = u.id";

        if ($search) {
            $sql .= " WHERE (m.nim LIKE ? OR m.nama LIKE ? OR m.jurusan LIKE ? OR u.email LIKE ?)";
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

    public function checkNimExists($nim, $excludeId = null)
    {
        $sql = "SELECT id FROM mahasiswa WHERE nim = ?";
        $params = [$nim];
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

    public function uploadFoto($file, $nim)
    {
        $uploadDir = "uploads/mahasiswa/";

        // Create directory if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF.'];
        }

        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 2MB.'];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $nim . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => true, 'filename' => $filename];
        } else {
            return ['success' => false, 'message' => 'Gagal mengunggah file.'];
        }
    }
}
