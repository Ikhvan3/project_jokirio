<?php
require_once __DIR__ . '/../config/db.php';

class Mahasiswa
{
    public static function all()
    {
        global $conn;
        $data = [];
        $stmt = $conn->prepare("SELECT * FROM mahasiswa ORDER BY id DESC");
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            return $data;
        } else {
            $stmt->close();
            return false;
        }
    }

    public static function find($id)
    {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data;
        } else {
            $stmt->close();
            return false;
        }
    }

    public static function insert($nim, $nama, $jurusan, $foto, $filename, $filepath, $thumbpath, $width, $height)
    {
        global $conn;
        $sql = "INSERT INTO mahasiswa (nim, nama, jurusan, foto, filename, filepath, thumbpath, width, height, uploaded_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssii", $nim, $nama, $jurusan, $foto, $filename, $filepath, $thumbpath, $width, $height);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public static function update($id, $nim, $nama, $jurusan, $foto, $filename, $filepath, $thumbpath, $width, $height)
    {
        global $conn;
        $sql = "UPDATE mahasiswa SET nim = ?, nama = ?, jurusan = ?, foto = ?, 
                filename = ?, filepath = ?, thumbpath = ?, width = ?, height = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssiii", $nim, $nama, $jurusan, $foto, $filename, $filepath, $thumbpath, $width, $height, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public static function delete($id)
    {
        global $conn;
        $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
