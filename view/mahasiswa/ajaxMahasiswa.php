<?php
// Pastikan path ini benar
require_once '../../model/Mahasiswa.php';
require_once '../../config/db.php';

// Ambil dan validasi input POST
$page = isset($_POST['page']) ? filter_var($_POST['page'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) : 1;
if ($page === false) $page = 1;

$query = isset($_POST['query']) ? trim(htmlspecialchars($_POST['query'])) : '';
$date_start = isset($_POST['date_start']) ? $_POST['date_start'] : '';
$date_end = isset($_POST['date_end']) ? $_POST['date_end'] : '';

$limit = 5; // Batas data per halaman
$start = ($page - 1) * $limit;

/**
 * Membangun klausa WHERE SQL secara dinamis berdasarkan filter.
 * * @param string $query Teks pencarian.
 * @param string $date_start Tanggal mulai.
 * @param string $date_end Tanggal akhir.
 * @param array &$params Array untuk menyimpan parameter bind.
 * @param string &$types String untuk menyimpan tipe parameter bind.
 * @return string Klausa WHERE SQL.
 */
function buildWhereClause($query, $date_start, $date_end, &$params, &$types)
{
    $sql_where = " WHERE (nim LIKE ? OR nama LIKE ? OR jurusan LIKE ?)";
    $query_like = '%' . $query . '%';
    $params = [$query_like, $query_like, $query_like];
    $types = 'sss';

    // Tambahkan filter tanggal mulai jika ada
    if (!empty($date_start)) {
        $sql_where .= " AND DATE(uploaded_at) >= ?";
        $params[] = $date_start;
        $types .= 's';
    }

    // Tambahkan filter tanggal akhir jika ada
    if (!empty($date_end)) {
        $sql_where .= " AND DATE(uploaded_at) <= ?";
        $params[] = $date_end;
        $types .= 's';
    }
    return $sql_where;
}

/**
 * Mengambil data mahasiswa terfilter dengan paginasi.
 */
function getMahasiswaFiltered($query, $date_start, $date_end, $start, $limit)
{
    global $conn;
    $params = [];
    $types = '';

    $sql_where = buildWhereClause($query, $date_start, $date_end, $params, $types);

    // Kueri utama dengan WHERE dinamis dan LIMIT
    $sql = "SELECT * FROM mahasiswa " . $sql_where . " ORDER BY id ASC LIMIT ?, ?";
    $params[] = $start;
    $params[] = $limit;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params); // Gunakan splat operator (...)
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

/**
 * Menghitung total data mahasiswa terfilter.
 */
function countMahasiswaFiltered($query, $date_start, $date_end)
{
    global $conn;
    $params = [];
    $types = '';

    $sql_where = buildWhereClause($query, $date_start, $date_end, $params, $types);

    // Kueri hitung dengan WHERE dinamis
    $sql = "SELECT COUNT(*) as total FROM mahasiswa " . $sql_where;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params); // Gunakan splat operator (...)
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total'] ?? 0;
}

// Ambil data dan hitung total halaman
$mahasiswaList = getMahasiswaFiltered($query, $date_start, $date_end, $start, $limit);
$total_data = countMahasiswaFiltered($query, $date_start, $date_end);
$total_pages = ceil($total_data / $limit);

// === Mulai Output HTML ===

if (count($mahasiswaList) > 0) {
    // Buat tabel
    echo '<table class="table table-hover align-middle">';
    echo '<thead>
            <tr>
                <th>No</th>
                <th>NIM</th>
                <th>Nama</th>
                <th>Jurusan</th>
                <th>Foto</th>
                <th>Aksi</th>
            </tr>
          </thead><tbody>';

    $no = $start + 1;
    foreach ($mahasiswaList as $mhs) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($mhs['nim']) . '</td>';
        echo '<td>' . htmlspecialchars($mhs['nama']) . '</td>';
        echo '<td>' . htmlspecialchars($mhs['jurusan']) . '</td>';

        // Logika tampilkan foto/thumbnail
        $fotoName = htmlspecialchars($mhs['foto']);
        if (!empty($fotoName)) {
            $thumbClientPath = 'thumbs/thumb_' . $fotoName;
            $originalClientPath = 'uploads/' . $fotoName;
            $thumbServerPath = __DIR__ . '/../../' . $thumbClientPath;
            $originalServerPath = __DIR__ . '/../../' . $originalClientPath;

            if (file_exists($thumbServerPath)) {
                echo '<td><a href="' . $originalClientPath . '" target="_blank"><img src="' . $thumbClientPath . '" alt="Foto ' . htmlspecialchars($mhs['nama']) . '" class="foto-thumb"></a></td>';
            } else if (file_exists($originalServerPath)) {
                echo '<td><img src="' . $originalClientPath . '" alt="Foto ' . htmlspecialchars($mhs['nama']) . '" class="foto-thumb"></td>';
            } else {
                echo '<td><span class="no-photo">Rusak</span></td>';
            }
        } else {
            echo '<td><span class="no-photo">N/A</span></td>';
        }

        // Tombol Aksi
        echo '<td>
                <button class="btn btn-sm btn-warning" onclick="window.location.href=\'index.php?act=edit&id=' . $mhs['id'] . '\'"><i class="bi bi-pencil-square"></i> Edit</button>
                <button class="btn btn-sm btn-danger" onclick="if(confirm(\'Yakin ingin hapus data ini?\')) window.location.href=\'index.php?act=delete&id=' . $mhs['id'] . '\'"><i class="bi bi-trash3"></i> Hapus</button>
              </td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // Buat Paginasi (Hanya tampilkan jika ada data)
    echo '<div class="pagination-container">';
    echo '<nav aria-label="Page navigation">';
    echo '<ul class="pagination">';

    $prevClass = ($page <= 1) ? 'disabled' : '';
    echo '<li class="page-item ' . $prevClass . '">
            <a class="page-link" href="#" data-page_number="' . ($page - 1) . '" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
          </li>';

    for ($i = 1; $i <= $total_pages; $i++) {
        $activeClass = ($i == $page) ? 'active' : '';
        echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="#" data-page_number="' . $i . '">' . $i . '</a></li>';
    }

    $nextClass = ($page >= $total_pages) ? 'disabled' : '';
    echo '<li class="page-item ' . $nextClass . '">
            <a class="page-link" href="#" data-page_number="' . ($page + 1) . '" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
          </li>';

    echo '</ul>';
    echo '</nav>';
    echo '</div>';
} else {
    // Tampilkan pesan jika tidak ada data
    echo '<div class="alert alert-info text-center mt-4" role="alert"><i class="bi bi-info-circle me-2"></i>Data tidak ditemukan sesuai dengan filter Anda.</div>';
}
