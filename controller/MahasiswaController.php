<?php
require_once './model/Mahasiswa.php';
require_once './config/db.php';

class MahasiswaController
{

    private function handleUploadWithThumbnail($fileInputName, &$errorMessage)
    {
        if (!isset($_FILES[$fileInputName]) || empty($_FILES[$fileInputName]['tmp_name'])) {
            $errorMessage = "Tidak ada file yang dipilih.";
            return null;
        }

        if ($_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
            if ($_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
                return null;
            }
            $errorMessage = "Terjadi error saat upload: " . $_FILES[$fileInputName]['error'];
            return false;
        }

        $target_dir = "uploads/";
        $thumb_dir = "thumbs/";

        if (!is_dir($target_dir)) {
            @mkdir($target_dir, 0755, true);
        }
        if (!is_dir($thumb_dir)) {
            @mkdir($thumb_dir, 0755, true);
        }

        if (!is_writable($target_dir) || !is_writable($thumb_dir)) {
            $errorMessage = "Folder 'uploads' atau 'thumbs' tidak ada atau tidak writable.";
            return false;
        }

        $original_file_name = basename($_FILES[$fileInputName]["name"]);
        $imageFileType = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
        $unique_file_name = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9_.]/", "", $original_file_name);
        $target_file = $target_dir . $unique_file_name;

        $check = @getimagesize($_FILES[$fileInputName]["tmp_name"]);
        if ($check === false) {
            $errorMessage = "File yang diupload bukan gambar.";
            return false;
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowed)) {
            $errorMessage = "Hanya file JPG, JPEG, PNG dan GIF yang diperbolehkan.";
            return false;
        }

        if ($_FILES[$fileInputName]["size"] > 2 * 1024 * 1024) {
            $errorMessage = "Ukuran file terlalu besar (maks 2MB).";
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES[$fileInputName]["tmp_name"]);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
            $errorMessage = "Tipe Mime file tidak sesuai.";
            return false;
        }

        if (!move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $target_file)) {
            $errorMessage = "Gagal memindahkan file yang diupload.";
            return false;
        }

        list($width, $height) = getimagesize($target_file);
        $new_width = 200;
        $new_height = floor($height * ($new_width / $width));
        $thumbpath = $thumb_dir . "thumb_" . $unique_file_name;

        switch ($imageFileType) {
            case 'jpg':
            case 'jpeg':
                $src = @imagecreatefromjpeg($target_file);
                break;
            case 'png':
                $src = @imagecreatefrompng($target_file);
                break;
            case 'gif':
                $src = @imagecreatefromgif($target_file);
                break;
            default:
                $src = false;
        }

        if (!$src) {
            @unlink($target_file);
            $errorMessage = "Gagal membaca source gambar untuk thumbnail.";
            return false;
        }

        $thumb = imagecreatetruecolor($new_width, $new_height);

        if ($imageFileType == 'png' || $imageFileType == 'gif') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, $new_width, $new_height, $transparent);
            imagecolortransparent($thumb, $transparent);
        }

        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        $thumb_saved = false;
        switch ($imageFileType) {
            case 'jpg':
            case 'jpeg':
                $thumb_saved = imagejpeg($thumb, $thumbpath, 80);
                break;
            case 'png':
                $thumb_saved = imagepng($thumb, $thumbpath);
                break;
            case 'gif':
                $thumb_saved = imagegif($thumb, $thumbpath);
                break;
        }

        imagedestroy($src);
        imagedestroy($thumb);

        if (!$thumb_saved) {
            @unlink($target_file);
            $errorMessage = "Gagal menyimpan file thumbnail.";
            return false;
        }

        return [
            'filename'  => $unique_file_name,
            'filepath'  => $target_file,
            'thumbpath' => $thumbpath,
            'width'     => $width,
            'height'    => $height
        ];
    }


    public function index()
    {
        include './view/mahasiswa/welcome.php';
    }

    public function create()
    {
        include './view/mahasiswa/tambah.php';
    }

    public function store()
    {
        global $conn;

        $nim = trim($_POST['nim']);
        $nama = trim($_POST['nama']);
        $jurusan = trim($_POST['jurusan']);
        $errorMessage = '';

        $foto_db = '';
        $filename_db = '';
        $filepath_db = '';
        $thumbpath_db = '';
        $width_db = 0;
        $height_db = 0;

        if (!preg_match('/^[A-Z]\d{2}\.\d{4}\.\d{5}$/', $nim)) {
            echo "<script>
                    alert('Format NIM tidak sesuai! Gunakan format: A11.2023.12345');
                    window.location.href='index.php?act=create';
                  </script>";
            exit();
        }

        $sql_check = "SELECT nim FROM mahasiswa WHERE nim = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $nim);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $stmt_check->close();
            echo "<script>
                    alert('Maaf, NIM " . htmlspecialchars($nim) . " sudah ada dalam database.');
                    window.location.href='index.php?act=create';
                  </script>";
            exit();
        }
        $stmt_check->close();

        $imageData = $this->handleUploadWithThumbnail('foto', $errorMessage);

        if ($imageData === false) {
            echo "<script>alert('Upload Gagal: " . addslashes($errorMessage) . "'); window.location.href='index.php?act=create';</script>";
            exit();
        } elseif ($imageData !== null) {
            $foto_db = $imageData['filename'];
            $filename_db = $imageData['filename'];
            $filepath_db = $imageData['filepath'];
            $thumbpath_db = $imageData['thumbpath'];
            $width_db = $imageData['width'];
            $height_db = $imageData['height'];
        }

        $success = Mahasiswa::insert($nim, $nama, $jurusan, $foto_db, $filename_db, $filepath_db, $thumbpath_db, $width_db, $height_db);

        if ($success) {
            header('Location: index.php?status=success_add');
        } else {
            if (!empty($foto_db)) {
                @unlink('uploads/' . $foto_db);
                @unlink('thumbs/thumb_' . $foto_db);
            }
            echo "<script>
                    alert('Gagal menyimpan data mahasiswa.');
                    window.location.href='index.php?act=create';
                  </script>";
        }
        exit();
    }

    public function edit($id)
    {
        $mahasiswa = Mahasiswa::find($id);
        if (!$mahasiswa) {
            echo "<script>alert('Data mahasiswa tidak ditemukan.'); window.location.href='index.php';</script>";
            exit();
        }
        include './view/mahasiswa/edit.php';
    }

    public function update($id)
    {
        global $conn;

        $nim_baru = trim($_POST['nim']);
        $nama = trim($_POST['nama']);
        $jurusan = trim($_POST['jurusan']);
        $errorMessage = '';

        $mahasiswa_lama = Mahasiswa::find($id);
        if (!$mahasiswa_lama) {
            echo "<script>alert('Data mahasiswa tidak ditemukan untuk diupdate.'); window.location.href='index.php';</script>";
            exit();
        }

        $nim_lama = $mahasiswa_lama['nim'];
        $foto_db = $mahasiswa_lama['foto'];
        $filename_db = $mahasiswa_lama['filename'];
        $filepath_db = $mahasiswa_lama['filepath'];
        $thumbpath_db = $mahasiswa_lama['thumbpath'];
        $width_db = $mahasiswa_lama['width'];
        $height_db = $mahasiswa_lama['height'];


        if (!preg_match('/^[A-Z]\d{2}\.\d{4}\.\d{5}$/', $nim_baru)) {
            echo "<script>
                    alert('Format NIM tidak sesuai! Gunakan format: A11.2023.12345');
                    window.history.back();
                  </script>";
            exit();
        }

        if ($nim_baru !== $nim_lama) {
            $sql_check_update = "SELECT nim FROM mahasiswa WHERE nim = ? AND id != ?";
            $stmt_check_update = $conn->prepare($sql_check_update);
            $stmt_check_update->bind_param("si", $nim_baru, $id);
            $stmt_check_update->execute();
            $result_check_update = $stmt_check_update->get_result();

            if ($result_check_update->num_rows > 0) {
                $stmt_check_update->close();
                echo "<script>
                        alert('Maaf, NIM " . htmlspecialchars($nim_baru) . " sudah digunakan oleh mahasiswa lain.');
                        window.history.back();
                      </script>";
                exit();
            }
            $stmt_check_update->close();
        }

        $imageData = $this->handleUploadWithThumbnail('foto', $errorMessage);

        if ($imageData === false) {
            echo "<script>alert('Upload Gagal: " . addslashes($errorMessage) . "'); window.history.back();</script>";
            exit();
        } elseif ($imageData !== null) {
            if (!empty($mahasiswa_lama['foto'])) {
                @unlink('uploads/' . $mahasiswa_lama['foto']);
                @unlink('thumbs/thumb_' . $mahasiswa_lama['foto']);
            }
            $foto_db = $imageData['filename'];
            $filename_db = $imageData['filename'];
            $filepath_db = $imageData['filepath'];
            $thumbpath_db = $imageData['thumbpath'];
            $width_db = $imageData['width'];
            $height_db = $imageData['height'];
        }

        $success = Mahasiswa::update($id, $nim_baru, $nama, $jurusan, $foto_db, $filename_db, $filepath_db, $thumbpath_db, $width_db, $height_db);

        if ($success) {
            header('Location: index.php?status=success_update');
        } else {
            if ($imageData !== null && $foto_db !== $mahasiswa_lama['foto']) {
                @unlink('uploads/' . $foto_db);
                @unlink('thumbs/thumb_' . $foto_db);
            }
            echo "<script>
                    alert('Gagal mengupdate data mahasiswa.');
                    window.history.back();
                  </script>";
        }
        exit();
    }

    public function delete($id)
    {
        $mahasiswa = Mahasiswa::find($id);
        if ($mahasiswa && !empty($mahasiswa['foto'])) {
            @unlink('uploads/' . $mahasiswa['foto']);
            @unlink('thumbs/thumb_' . $mahasiswa['foto']);
        }
        Mahasiswa::delete($id);
        header('Location: index.php?status=success_delete');
        exit();
    }
}
