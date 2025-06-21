<?php
require_once 'config/auth/auth.php';
require_once 'config/db.php';

// Require login
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

// Get statistics for dashboard
function getStatistics($conn) {
    $stats = [];
    
    // Total mahasiswa
    $result = $conn->query("SELECT COUNT(*) as total FROM mahasiswa");
    $stats['total_mahasiswa'] = $result->fetch_assoc()['total'];
    
    // Total users
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $stats['total_users'] = $result->fetch_assoc()['total'];
    
    // Total dosen
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'dosen' AND status = 'active'");
    $stats['total_dosen'] = $result->fetch_assoc()['total'];
    
    // Recent mahasiswa (last 7 days)
    $result = $conn->query("SELECT COUNT(*) as total FROM mahasiswa WHERE DATE(uploaded_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stats['recent_mahasiswa'] = $result->fetch_assoc()['total'];
    
    return $stats;
}

$stats = getStatistics($conn);

// Get recent activities
function getRecentActivities($conn, $limit = 5) {
    $activities = [];
    
    // Recent mahasiswa data
    $sql = "SELECT nim, nama, jurusan, uploaded_at FROM mahasiswa ORDER BY uploaded_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            'type' => 'mahasiswa_added',
            'description' => "Data mahasiswa {$row['nama']} ({$row['nim']}) - {$row['jurusan']} ditambahkan",
            'time' => $row['uploaded_at']
        ];
    }
    
    $stmt->close();
    return $activities;
}

$recent_activities = getRecentActivities($conn);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Informasi Nilai Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
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
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .sidebar {
            background: white;
            min-height: calc(100vh - 76px);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .stats-card.primary::before { background: var(--primary-gradient); }
        .stats-card.secondary::before { background: var(--secondary-gradient); }
        .stats-card.success::before { background: var(--success-gradient); }
        .stats-card.warning::before { background: var(--warning-gradient); }

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

        .stats-icon.primary { background: var(--primary-gradient); }
        .stats-icon.secondary { background: var(--secondary-gradient); }
        .stats-icon.success { background: var(--success-gradient); }
        .stats-icon.warning { background: var(--warning-gradient); }

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
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(180deg); }
        }

        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-gradient);
            transition: all 0.3s ease;
        }

        .activity-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
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
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            color: #495057;
        }

        .chart-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: 30px;
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
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
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
                        <li><hr class="dropdown-divider"></li>
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
                            <i class="bi bi