<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

    <div class="content-card">
        <h2><i class="bi bi-people-fill me-2"></i>Data Mahasiswa</h2>
        <p class="subtitle">NIM: A12.2023.06995 | Nama: VAlentino Arizona Putra</p>

        <div class="controls">
            <div class="input-group">
                <input type="text" id="search" placeholder="Cari Nama, NIM, Jurusan..." class="form-control">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
            </div>
            <div class="input-group">
                <span class="input-group-text">Dari Tgl:</span>
                <input type="date" id="search_date_start" class="form-control">
            </div>
            <div class="input-group">
                <span class="input-group-text">Sampai Tgl:</span>
                <input type="date" id="search_date_end" class="form-control">
            </div>
            <button class="btn btn-primary" onclick="window.location.href='index.php?act=create'">
                <i class="bi bi-plus-circle me-1"></i> Tambah Data
            </button>
        </div>

        <div id="dataMahasiswa" class="table-container table-responsive">
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk memuat data via AJAX, kini dengan parameter tanggal
        function load_data(page, query = '', date_start = '', date_end = '') {
            $.ajax({
                url: "view/mahasiswa/ajaxMahasiswa.php", // Path ke handler AJAX
                method: "POST",
                data: {
                    page: page,
                    query: query,
                    date_start: date_start, // Kirim tanggal mulai
                    date_end: date_end // Kirim tanggal akhir
                },
                beforeSend: function() {
                    // Tampilkan loading spinner
                    $('#dataMahasiswa').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                },
                success: function(data) {
                    // Tampilkan hasil dari AJAX
                    $('#dataMahasiswa').html(data);
                },
                error: function() {
                    // Tampilkan pesan error jika AJAX gagal
                    $('#dataMahasiswa').html('<div class="alert alert-danger text-center">Gagal memuat data.</div>');
                }
            });
        }

        // Fungsi untuk mengambil semua nilai filter dan memanggil load_data
        function perform_search() {
            var query = $('#search').val();
            var date_start = $('#search_date_start').val();
            var date_end = $('#search_date_end').val();
            load_data(1, query, date_start, date_end); // Mulai dari halaman 1 saat filter baru
        }

        // Jalankan saat dokumen siap
        $(document).ready(function() {
            load_data(1); // Muat data awal saat halaman dibuka

            let searchTimeout;
            // Event listener untuk input teks (dengan delay)
            $('#search').keyup(function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(perform_search, 500); // Tunggu 0.5 detik sebelum cari
            });

            // Event listener untuk input tanggal (langsung cari saat berubah)
            $('#search_date_start, #search_date_end').change(function() {
                perform_search();
            });

            // Event listener untuk tombol paginasi (event delegation)
            $(document).on('click', '.pagination .page-link', function(e) {
                e.preventDefault(); // Hentikan aksi default link
                var page = $(this).data('page_number');
                // Pastikan page valid dan tombol tidak disabled
                if (typeof page !== 'undefined' && !$(this).parent().hasClass('disabled')) {
                    var query = $('#search').val();
                    var date_start = $('#search_date_start').val();
                    var date_end = $('#search_date_end').val();
                    load_data(page, query, date_start, date_end); // Muat halaman baru dengan filter saat ini
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>