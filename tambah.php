<?php
session_start(); // <--- WAJIB ADA BIAR TAU SIAPA YANG LOGIN

// Cek dulu user udah login belum, kalau belum tendang ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis = $_POST['jenis'];
    $jumlah = (int)$_POST['jumlah'];
    $deskripsi = $_POST['deskripsi'];
    $tanggal = $_POST['tanggal'];

    // Simpan Data
    $bulk = new MongoDB\Driver\BulkWrite;
    $id_baru = new MongoDB\BSON\ObjectId;
    
    $bulk->insert([
        '_id' => $id_baru,
        'user_id' => $_SESSION['user_id'], // <--- INI KUNCINYA (Label Pemilik)
        'jenis' => $jenis,
        'jumlah' => $jumlah,
        'deskripsi' => $deskripsi,
        'tanggal' => $tanggal // Format string yyyy-mm-dd
    ]);
    
    $manager->executeBulkWrite('dompetpribadi.transaksi', $bulk);

    // Simpan Log (Opsional: Tambahin user_id juga biar rapi)
    $bulkLog = new MongoDB\Driver\BulkWrite;
    $bulkLog->insert([
        'user_id' => $_SESSION['user_id'], // Log juga dikasih label
        'id_transaksi' => $id_baru,
        'aksi' => "Tambah: $jenis Rp $jumlah",
        'waktu' => date('Y-m-d H:i:s')
    ]);
    $manager->executeBulkWrite('dompetpribadi.log_transaksi', $bulkLog);

    // Redirect
    if ($jenis == 'sedekah') {
        header("Location: index.php?pesan=sukses_sedekah");
    } else {
        header("Location: index.php?pesan=sukses");
    }
}
?>