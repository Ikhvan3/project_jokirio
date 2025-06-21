<?php
require_once 'config/auth/auth.php';
require_once 'config/db.php';

// Require login
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

// Get statistics for dashboard
function getStatistics($conn)
{
    $stats = [];

    // Total mahasiswa
    $result = $conn->query("SELECT COUNT(*) as total FROM mahasiswa");
    $stats['total_mahasiswa'] = $result->fetch_assoc()['total'];

    // Total mata kuliah
    $result = $conn->query("SELECT COUNT(*) as total FROM mata_kuliah");
    $stats['total_mata_kuliah'] = $result->fetch_assoc()['total'];

    // Total nilai
    $result = $conn->query("SELECT COUNT(*) as total FROM nilai");
    $stats['total_nilai'] = $result->fetch_assoc()['total'];

    // Total dosen
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'dosen' AND status = 'active'");
    $stats['total_dosen'] = $result->fetch_assoc()['total'];

    return $stats;
}

$stats = getStatistics($conn);

// Get recent activities based on user role
function getRecentActivities($conn, $userRole, $userId, $limit = 5)
{
    $activities = [];

    if ($userRole == 'mahasiswa') {
        // For mahasiswa, show their recent grades
        $sql = "SELECT n.*, mk.nama_mata_kuliah, mk.kode_mata_kuliah 
                FROM nilai n 
                JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id 
                JOIN mahasiswa m ON n.mahasiswa_id = m.id 
                JOIN users u ON m.user_id = u.id 
                WHERE u.id = ? 
                ORDER BY n.created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $activities[] = [
                'type' => 'nilai_received',
                'description' => "Nilai {$row['nama_mata_kuliah']} ({$row['kode_mata_kuliah']}) - {$row['nilai']} ({$row['grade']})",
                'time' => $row['created_at']
            ];
        }
    } else {
        // For admin/dosen, show recent system activities
        $sql = "SELECT m.nim, m.nama, m.jurusan, m.created_at 
                FROM mahasiswa m 
                ORDER BY m.created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $activities[] = [
                'type' => 'mahasiswa_added',
                'description' => "Data mahasiswa {$row['nama']} ({$row['nim']}) - {$row['jurusan']} ditambahkan",
                'time' => $row['created_at']
            ];
        }
    }

    $stmt->close();
    return $activities;
}

$recent_activities = getRecentActivities($conn, $currentUser['role'], $currentUser['id']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Informasi Nilai Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .navbar {
            background: var(--primary-gradient) !important;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .sidebar {
            background: white;
            min-height: calc(100vh - 76px);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 0 20px 20px 0;
        }

        .sidebar .nav-link {
            color: #6c757d;
            padding: 12px 20px;
            border-radius: 10px;
            margin: 5px 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            transform: translateX(5px);
        }

        .main-content {
            padding: 30px;
        }

        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            position: relative;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .stats-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stats-card.primary::before {
            background: var(--primary-gradient);
        }

        .stats-card.secondary::before {
            background: var(--secondary-gradient);
        }

        .stats-card.success::before {
            background: var(--success-gradient);
        }

        .stats-card.warning::before {
            background: var(--warning-gradient);
        }

        .stats-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 20px;
        }

        .stats-icon.primary {
            background: var(--primary-gradient);
        }

        .stats-icon.secondary {
            background: var(--secondary-gradient);
        }

        .stats-icon.success {
            background: var(--success-gradient);
        }

        .stats-icon.warning {
            background: var(--warning-gradient);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stats-label {
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .welcome-card {
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-10px) rotate(180deg);
            }
        }

        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary-gradient);
            transition: all 0.3s ease;
        }

        .activity-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .quick-action-btn {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #6c757d;
        }

        .quick-action-btn:hover {
            border-color: var(--primary-gradient);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            color: #495057;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .role-badge {
            background: var(--primary-gradient);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .time-display {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                border-radius: 0;
            }

            .main-content {
                padding: 15px;
            }

            .stats-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-mortarboard-fill me-2"></i>
                SISFO Nilai Akademik
            </a>

            <div class="navbar-nav ms-auto">
                <div class="time-display me-3">
                    <i class="bi bi-clock me-1"></i>
                    <span id="currentTime"></span>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar me-2">
                            <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($currentUser['username']) ?></div>
                            <small class="opacity-75"><?= ucfirst($currentUser['role']) ?></small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-fill me-2"></i>Profil</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear-fill me-2"></i>Pengaturan</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <div class="text-center mb-4">
                            <div class="user-avatar mx-auto mb-2" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                            </div>
                            <h6 class="mb-1"><?= htmlspecialchars($currentUser['username']) ?></h6>
                            <span class="role-badge"><?= ucfirst($currentUser['role']) ?></span>
                        </div>
                    </div>

                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>

                        <?php if (in_array($currentUser['role'], ['admin', 'dosen'])): ?>
                            <a class="nav-link" href="mahasiswa.php">
                                <i class="bi bi-people-fill me-2"></i>Data Mahasiswa
                            </a>
                            <a class="nav-link" href="nilai.php">
                                <i class="bi bi-journal-text me-2"></i>Kelola Nilai
                            </a>
                            <a class="nav-link" href="mata-kuliah.php">
                                <i class="bi bi-book-fill me-2"></i>Mata Kuliah
                            </a>
                        <?php endif; ?>

                        <?php if ($currentUser['role'] == 'mahasiswa'): ?>
                            <a class="nav-link" href="nilai-saya.php">
                                <i class="bi bi-journal-text me-2"></i>Nilai Saya
                            </a>
                            <a class="nav-link" href="jadwal.php">
                                <i class="bi bi-calendar-event me-2"></i>Jadwal Kuliah
                            </a>
                        <?php endif; ?>

                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person-fill me-2"></i>Profil
                        </a>

                        <?php if ($currentUser['role'] == 'admin'): ?>
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people me-2"></i>Manajemen User
                            </a>
                            <a class="nav-link" href="reports.php">
                                <i class="bi bi-file-earmark-text me-2"></i>Laporan
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Welcome Card -->
                    <div class="welcome-card">
                        <h2>Selamat Datang, <?= htmlspecialchars($currentUser['username']) ?>!</h2>
                        <p class="mb-0">Sistem Informasi Nilai Akademik - Dashboard <?= ucfirst($currentUser['role']) ?></p>
                        <div class="time-display mt-3 d-inline-block">
                            <i class="bi bi-calendar-event me-1"></i>
                            <?= date('l, d F Y') ?>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row">
                        <?php if (in_array($currentUser['role'], ['admin', 'dosen'])): ?>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="stats-card primary">
                                    <div class="stats-icon primary">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div class="stats-number"><?= $stats['total_mahasiswa'] ?></div>
                                    <div class="stats-label">Total Mahasiswa</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="stats-card secondary">
                                    <div class="stats-icon secondary">
                                        <i class="bi bi-book-fill"></i>
                                    </div>
                                    <div class="stats-number"><?= $stats['total_mata_kuliah'] ?></div>
                                    <div class="stats-label">Mata Kuliah</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="stats-card success">
                                    <div class="stats-icon success">
                                        <i class="bi bi-journal-text"></i>
                                    </div>
                                    <div class="stats-number"><?= $stats['total_nilai'] ?></div>
                                    <div class="stats-label">Total Nilai</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="stats-card warning">
                                    <div class="stats-icon warning">
                                        <i class="bi bi-person-badge-fill"></i>
                                    </div>
                                    <div class="stats-number"><?= $stats['total_dosen'] ?></div>
                                    <div class="stats-label">Total Dosen</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Stats for Mahasiswa -->
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="stats-card primary">
                                    <div class="stats-icon primary">
                                        <i class="bi bi-journal-text"></i>
                                    </div>
                                    <div class="stats-number">
                                        <?php
                                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM nilai n 
                                                          JOIN mahasiswa m ON n.mahasiswa_id = m.id 
                                                          JOIN users u ON m.user_id = u.id 
                                                          WHERE u.id = ?");
                                        $stmt->bind_param("i", $currentUser['id']);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        echo $result->fetch_assoc()['total'];
                                        $stmt->close();
                                        ?>
                                    </div>
                                    <div class="stats-label">Nilai Saya</div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="stats-card success">
                                    <div class="stats-icon success">
                                        <i class="bi bi-trophy-fill"></i>
                                    </div>
                                    <div class="stats-number">
                                        <?php
                                        $stmt = $conn->prepare("SELECT AVG(CASE 
                                                          WHEN grade = 'A' THEN 4.0 
                                                          WHEN grade = 'B' THEN 3.0 
                                                          WHEN grade = 'C' THEN 2.0 
                                                          WHEN grade = 'D' THEN 1.0 
                                                          ELSE 0.0 END) as ipk 
                                                          FROM nilai n 
                                                          JOIN mahasiswa m ON n.mahasiswa_id = m.id 
                                                          JOIN users u ON m.user_id = u.id 
                                                          WHERE u.id = ?");
                                        $stmt->bind_param("i", $currentUser['id']);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $ipk = $result->fetch_assoc()['ipk'];
                                        echo number_format($ipk ?: 0, 2);
                                        $stmt->close();
                                        ?>
                                    </div>
                                    <div class="stats-label">IPK</div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="stats-card warning">
                                    <div class="stats-icon warning">
                                        <i class="bi bi-book-half"></i>
                                    </div>
                                    <div class="stats-number">
                                        <?php
                                        $stmt = $conn->prepare("SELECT COUNT(DISTINCT mk.id) as total 
                                                          FROM mata_kuliah mk 
                                                          JOIN nilai n ON mk.id = n.mata_kuliah_id 
                                                          JOIN mahasiswa m ON n.mahasiswa_id = m.id 
                                                          JOIN users u ON m.user_id = u.id 
                                                          WHERE u.id = ?");
                                        $stmt->bind_param("i", $currentUser['id']);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        echo $result->fetch_assoc()['total'];
                                        $stmt->close();
                                        ?>
                                    </div>
                                    <div class="stats-label">Mata Kuliah</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Activities -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 py-3">
                                    <h5 class="mb-0">
                                        <i class="bi bi-clock-history me-2"></i>
                                        Aktivitas Terkini
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_activities)): ?>
                                        <div class="text-center py-4">
                                            <i class="bi bi-inbox display-4 text-muted"></i>
                                            <p class="text-muted mt-2">Belum ada aktivitas terkini</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <div class="activity-card">
                                                <div class="d-flex align-items-start">
                                                    <div class="me-3">
                                                        <i class="bi bi-<?= $activity['type'] == 'nilai_received' ? 'journal-plus' : 'person-plus' ?> text-primary"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <p class="mb-1"><?= htmlspecialchars($activity['description']) ?></p>
                                                        <small class="text-muted">
                                                            <i class="bi bi-clock me-1"></i>
                                                            <?= date('d M Y H:i', strtotime($activity['time'])) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 py-3">
                                    <h5 class="mb-0">
                                        <i class="bi bi-lightning-fill me-2"></i>
                                        Aksi Cepat
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (in_array($currentUser['role'], ['admin', 'dosen'])): ?>
                                        <a href="mahasiswa.php" class="quick-action-btn text-decoration-none d-block mb-3">
                                            <i class="bi bi-person-plus-fill fs-2 mb-2 d-block"></i>
                                            <strong>Tambah Mahasiswa</strong>
                                            <br><small>Kelola data mahasiswa</small>
                                        </a>
                                        <a href="nilai.php" class="quick-action-btn text-decoration-none d-block mb-3">
                                            <i class="bi bi-journal-plus fs-2 mb-2 d-block"></i>
                                            <strong>Input Nilai</strong>
                                            <br><small>Tambah nilai mahasiswa</small>
                                        </a>
                                        <a href="mata-kuliah.php" class="quick-action-btn text-decoration-none d-block">
                                            <i class="bi bi-book-fill fs-2 mb-2 d-block"></i>
                                            <strong>Mata Kuliah</strong>
                                            <br><small>Kelola mata kuliah</small>
                                        </a>
                                    <?php else: ?>
                                        <a href="nilai-saya.php" class="quick-action-btn text-decoration-none d-block mb-3">
                                            <i class="bi bi-journal-text fs-2 mb-2 d-block"></i>
                                            <strong>Lihat Nilai</strong>
                                            <br><small>Cek nilai terbaru</small>
                                        </a>
                                        <a href="profile.php" class="quick-action-btn text-decoration-none d-block mb-3">
                                            <i class="bi bi-person-gear fs-2 mb-2 d-block"></i>
                                            <strong>Update Profil</strong>
                                            <br><small>Kelola profil Anda</small>
                                        </a>
                                        <a href="jadwal.php" class="quick-action-btn text-decoration-none d-block">
                                            <i class="bi bi-calendar-event fs-2 mb-2 d-block"></i>
                                            <strong>Jadwal Kuliah</strong>
                                            <br><small>Lihat jadwal hari ini</small>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Real-time clock
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeString;
        }

        updateTime();
        setInterval(updateTime, 1000);

        // Add some interactivity to stats cards
        document.querySelectorAll('.stats-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.02)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Smooth scroll for navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Remove active class from all links
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                this.classList.add('active');
            });
        });
    </script>
</body>

</html>