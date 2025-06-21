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
            $stmt->bind_param("ssiiss", 
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
            $stmt->bind_param("ssiissi", 
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
                return ['success' =>