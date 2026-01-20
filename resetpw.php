<?php
// File: reset_password.php
try {
    // 1. Koneksi ke Database
    $manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
    
    // 2. Siapkan Password Baru (Dienkripsi)
    // Password aslinya "admin123", tapi diubah jadi kode acak
    $password_aman = password_hash("admin123", PASSWORD_DEFAULT);
    
    // 3. Update User 'admin' di Database
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['username' => 'admin'],           // Cari user yang namanya admin
        ['$set' => ['password' => $password_aman]] // Ubah passwordnya
    );
    
    $result = $manager->executeBulkWrite('dompetpribadi.users', $bulk);
    
    // 4. Cek Hasilnya
    if ($result->getModifiedCount() > 0) {
        echo "<h1>✅ SUKSES! Password Berhasil Di-Reset.</h1>";
        echo "<p>Sekarang database sudah kenal password versi enkripsi.</p>";
        echo "<hr>";
        echo "<h3>Silakan Login dengan:</h3>";
        echo "Username: <b>admin</b><br>";
        echo "Password: <b>admin123</b><br>";
        echo "<br><a href='login.php' style='padding:10px; background:blue; color:white; text-decoration:none; border-radius:5px;'>Ke Halaman Login >></a>";
    } else {
        echo "<h1>⚠️ Tidak ada yang berubah?</h1>";
        echo "<p>Mungkin user <b>'admin'</b> belum ada di database?</p>";
        echo "Coba jalanin file <b>buat_user.php</b> dulu ya.";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>