<?php
session_start();

// Kalau user iseng balik ke login padahal sudah login
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("location:index.php");
    exit();
}

$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
$pesan = "";

// Cek pesan dari URL
if(isset($_GET['pesan'])){
    if($_GET['pesan'] == 'registered') $pesan = "Akun berhasil dibuat! Silakan login.";
    if($_GET['pesan'] == 'logout') $pesan = "Berhasil logout. Sampai jumpa!";
    if($_GET['pesan'] == 'belum_login') $pesan = "Eits, login dulu dong!";
}

// LOGIKA LOGIN
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Cari user di database
        $filter = ['username' => $username];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $manager->executeQuery('dompetpribadi.users', $query);
        $user = current($cursor->toArray());

        // Cek Password (PAKAI LOGIKA SIMPEL: ==)
        // Kita ubah jadi == biar cocok sama register.php yang simpel
        if ($user && $user->password == $password) {
            // Login Sukses
            $_SESSION['status'] = "login";
            $_SESSION['user_id'] = (string)$user->_id; 
            $_SESSION['username'] = $user->username;
            header("location:index.php");
            exit();
        } else {
            $pesan = "Username atau Password salah bestie! 😭";
        }
    } catch (Exception $e) {
        $pesan = "Error sistem: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login ✨ AntiBoncos</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { font-family: 'Outfit', sans-serif; background: #f8f9fe; height: 100vh; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative; }
    .bg-animation-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: -1; }
    .orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.6; animation: float 20s infinite ease-in-out; }
    .orb-1 { width: 400px; height: 400px; background: #6c5ce7; top: -100px; left: -100px; }
    .orb-2 { width: 300px; height: 300px; background: #74b9ff; bottom: -50px; right: -50px; animation-delay: -5s; }
    @keyframes float { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(30px, -30px); } }

    .login-card { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.6); border-radius: 25px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
    .brand-gradient { background: -webkit-linear-gradient(45deg, #6c5ce7, #a29bfe); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }
    .form-control { background: #f8f9fa; border: none; padding: 12px; border-radius: 10px; }
    .form-control:focus { box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.2); background: white; }
    .btn-login { background: linear-gradient(135deg, #6c5ce7, #a29bfe); border: none; color: white; padding: 12px; border-radius: 50px; font-weight: bold; width: 100%; transition: 0.3s; }
    .btn-login:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(108, 92, 231, 0.3); }
</style>
</head>
<body>

<div class="bg-animation-container">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
</div>

<div class="login-card text-center">
    <div class="mb-4">
        <span style="font-size: 3rem;">🔐</span>
        <h3 class="brand-gradient mt-2">AntiBoncos.</h3>
        <p class="text-secondary small">Masuk dulu bestie!</p>
    </div>

    <?php if($pesan): ?>
        <div class="alert alert-info py-2 small rounded-pill border-0 shadow-sm mb-4">
            <?= $pesan ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3 text-start">
            <label class="small fw-bold text-secondary ms-2">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Isi username..." required>
        </div>
        <div class="mb-4 text-start">
            <label class="small fw-bold text-secondary ms-2">Password</label>
            <input type="password" name="password" class="form-control" placeholder="******" required>
        </div>
        <button type="submit" name="login" class="btn btn-login">GAS LOGIN 🚀</button>
    </form>

    <div class="mt-4">
        <small class="text-muted">Belum punya akun? <a href="register.php" class="text-decoration-none fw-bold" style="color: #6c5ce7;">Daftar sini</a></small>
    </div>
</div>

</body>
</html>