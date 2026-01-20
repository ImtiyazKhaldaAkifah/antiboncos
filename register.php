<?php
session_start();
$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");

// Kalau user iseng buka halaman ini padahal udah login
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("location:index.php");
    exit();
}

$pesan_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cek dulu, username ini udah ada yang pake belum?
    $filter = ['username' => $username];
    $query = new MongoDB\Driver\Query($filter);
    $cursor = $manager->executeQuery('dompetpribadi.users', $query);
    
    if (count($cursor->toArray()) > 0) {
        $pesan_error = "Username udah kepake orang lain! Ganti yang unik ya.";
    } else {
        // Simpan User Baru
        $bulk = new MongoDB\Driver\BulkWrite;
        $user_data = [
            '_id' => new MongoDB\BSON\ObjectId,
            'username' => $username,
            'password' => $password // Password disimpan polos (sesuai request)
        ];
        $bulk->insert($user_data);
        $manager->executeBulkWrite('dompetpribadi.users', $bulk);
        
        // Lempar ke login biar dia masuk
        header("location:login.php?pesan=registered");
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun ✨ AntiBoncos</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Desain sama persis kayak Login biar rapi */
        body { font-family: 'Outfit', sans-serif; background: #f8f9fe; display: flex; align-items: center; justify-content: center; min-height: 100vh; overflow: hidden; position: relative; }
        .bg-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.6; z-index: -1; }
        .orb-1 { width: 400px; height: 400px; background: #a29bfe; top: -100px; left: -100px; }
        .orb-2 { width: 300px; height: 300px; background: #74b9ff; bottom: -50px; right: -50px; }
        .login-card { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border-radius: 25px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.6); }
        .brand-gradient { background: -webkit-linear-gradient(45deg, #6c5ce7, #a29bfe); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }
        .btn-primary { background: linear-gradient(135deg, #6c5ce7, #a29bfe); border: none; padding: 12px; border-radius: 50px; font-weight: 700; transition: 0.3s; }
        .btn-primary:hover { background: #5649c0; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3); }
        .form-control { border-radius: 12px; padding: 12px; border: 1px solid #e0e0e0; background: #fdfdfd; }
        .form-control:focus { box-shadow: none; border-color: #6c5ce7; }
    </style>
</head>
<body>
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>

    <div class="login-card text-center">
        <div style="font-size: 3rem;">📝</div>
        <h3 class="brand-gradient mt-2 mb-1">Daftar Akun</h3>
        <p class="text-muted small mb-4">Yuk gabung AntiBoncos!</p>

        <?php if($pesan_error): ?>
            <div class="alert alert-danger py-2 small rounded-3"><?= $pesan_error ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3 text-start">
                <label class="small fw-bold text-secondary ms-1">Username Baru</label>
                <input type="text" name="username" class="form-control" placeholder="Mau dipanggil siapa?" required>
            </div>
            <div class="mb-4 text-start">
                <label class="small fw-bold text-secondary ms-1">Password</label>
                <input type="password" name="password" class="form-control" placeholder="******" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">GAS DAFTAR 🚀</button>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">Udah punya akun? 
                <a href="login.php" class="fw-bold text-primary text-decoration-none">Login aja</a>
            </small>
        </div>
    </div>
</body>
</html>