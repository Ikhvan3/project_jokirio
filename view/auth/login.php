<?php

require_once 'config/auth.php';


if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Username dan Password harus diisi';
    } else {
        $result = $auth->login($username, $password);
        if ($result['success']) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi Nilai Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 2rem;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
        }

        .form-control {
            border-radius: 15px;
            border: 1px solid #e0e0e0;
            padding: 12px 20px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="bi bi-mortarboard-fill me-2"></i>SISFO Nilai Akademik</h3>
                        <p class="mb-0 mt-2">Sistem Informasi Nilai Akademik</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username atau Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-0">Belum punya akun? <a href="register.php" class="text-decoration-none">Daftar disini</a></p>
                        </div>

                        <hr class="my-4">
                        <div class="text-center">
                            <small class="text-muted">
                                <strong>Demo Accounts:</strong><br>
                                Admin: admin / password<br>
                                Dosen: dosen001 / password<br>
                                Mahasiswa: A12202306911 / password
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>