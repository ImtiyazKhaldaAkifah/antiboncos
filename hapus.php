<?php
$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
if (isset($_GET['id'])) {
    $id = new MongoDB\BSON\ObjectId($_GET['id']);
    
    // Ambil Data Lama
    $query = new MongoDB\Driver\Query(['_id' => $id]);
    $cursor = $manager->executeQuery('dompetpribadi.transaksi', $query);
    $lama = current($cursor->toArray());

    if ($lama) {
        // Hapus
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->delete(['_id' => $id]);
        $manager->executeBulkWrite('dompetpribadi.transaksi', $bulk);
        
        // Log
        $bulkLog = new MongoDB\Driver\BulkWrite;
        $bulkLog->insert(['aksi' => "Hapus: {$lama->jenis} Rp {$lama->jumlah}", 'waktu' => date('Y-m-d H:i:s')]);
        $manager->executeBulkWrite('dompetpribadi.log_transaksi', $bulkLog);
    }
}
header("Location: index.php");
?>