<?php
// --- 1. KONEKSI & LOGIKA DATA (JANGAN DIUBAH) ---
$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");

// A. AMBIL DATA LAMA (GET)
if (isset($_GET['id'])) {
    $id = new MongoDB\BSON\ObjectId($_GET['id']);
    $query = new MongoDB\Driver\Query(['_id' => $id]);
    $cursor = $manager->executeQuery('dompetpribadi.transaksi', $query);
    $data = current($cursor->toArray());
}

// B. SIMPAN DATA BARU (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = new MongoDB\BSON\ObjectId($_POST['id']);
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update(['_id' => $id], ['$set' => [
        'jenis' => $_POST['jenis'], 
        'jumlah' => (int)$_POST['jumlah'], 
        'deskripsi' => $_POST['deskripsi'], 
        'tanggal' => $_POST['tanggal']
    ]]);
    $manager->executeBulkWrite('dompetpribadi.transaksi', $bulk);
    
    // Balik ke dashboard dengan pesan sukses
    header("Location: index.php?pesan=sukses");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Data ✨ AntiBoncos</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { 
            font-family: 'Outfit', sans-serif; 
            background: #f8f9fe; 
            min-height: 100vh; 
            overflow: hidden; /* Biar bola-bolanya gak bikin scroll */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* --- ANIMASI BACKGROUND (Sama kayak Dashboard) --- */
        .bg-animation-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: -1; }
        .orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.6; animation: float 20s infinite ease-in-out; }
        .orb-1 { width: 400px; height: 400px; background: #6c5ce7; top: -100px; left: -100px; }
        .orb-2 { width: 300px; height: 300px; background: #74b9ff; bottom: 10%; right: -50px; animation-delay: -5s; }
        @keyframes float { 0% { transform: translate(0, 0); } 50% { transform: translate(20px, -20px); } 100% { transform: translate(0, 0); } }

        /* --- CARD STYLE --- */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(108, 92, 231, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.5);
            width: 100%;
            max-width: 500px; /* Lebar kartu dibatasi biar rapi */
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .form-control, .form-select {
            border: none;
            background: #f1f2f6;
            padding: 12px 15px;
            border-radius: 12px;
            font-weight: 500;
        }
        .form-control:focus, .form-select:focus {
            background: #fff;
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.2);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            border: none;
            color: white;
            padding: 12px;
            font-weight: bold;
            border-radius: 50px;
            width: 100%;
            transition: 0.3s;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.4);
        }
    </style>
</head>
<body>

    <div class="bg-animation-container">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="container d-flex justify-content-center">
        <div class="glass-card">
            
            <div class="text-center mb-4">
                <h3 class="fw-bold" style="color: #6c5ce7;">Edit Transaksi ✏️</h3>
                <p class="text-secondary small">Ada yang salah input ya? Yuk benerin.</p>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="id" value="<?= $data->_id ?>">

                <div class="mb-3">
                    <label class="small text-muted fw-bold ms-2 mb-1">Tipe Transaksi</label>
                    <select name="jenis" class="form-select">
                        <option value="pemasukan" <?= $data->jenis == 'pemasukan' ? 'selected' : '' ?>>🟢 Pemasukan (Income)</option>
                        <option value="pengeluaran" <?= $data->jenis == 'pengeluaran' ? 'selected' : '' ?>>🔴 Pengeluaran (Expense)</option>
                        <option value="sedekah" <?= $data->jenis == 'sedekah' ? 'selected' : '' ?>>🤲 Sedekah (Charity)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="small text-muted fw-bold ms-2 mb-1">Nominal (Rp)</label>
                    <input type="number" name="jumlah" class="form-control" value="<?= $data->jumlah ?>" required>
                </div>

                <div class="mb-3">
                    <label class="small text-muted fw-bold ms-2 mb-1">Keterangan</label>
                    <input type="text" name="deskripsi" class="form-control" value="<?= $data->deskripsi ?>" required>
                </div>

                <div class="mb-4">
                    <label class="small text-muted fw-bold ms-2 mb-1">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= $data->tanggal instanceof MongoDB\BSON\UTCDateTime ? $data->tanggal->toDateTime()->format('Y-m-d') : $data->tanggal ?>">
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <a href="index.php" class="btn btn-light w-100 rounded-pill py-2 fw-bold text-secondary">Batal</a>
                    </div>
                    <div class="col-6">
                        <button type="submit" class="btn-primary-custom">Simpan Perubahan</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</body>
</html>