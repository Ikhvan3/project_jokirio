<?php
include 'db.php';
$id = $_GET['id'];
$data = $conn->query("SELECT * FROM mahasiswa WHERE id=$id")->fetch_assoc();
if ($data['foto'] != '' && file_exists('uploads/' . $data['foto'])) {
    unlink('uploads/' . $data['foto']);
}
$conn->query("DELETE FROM mahasiswa WHERE id=$id");
header('Location: index.php');
