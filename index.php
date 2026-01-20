<?php
session_start();

// --- 1. CEK LOGIN ---
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php");
    exit();
}

// Pastikan User ID ada (jaga-jaga error)
if (!isset($_SESSION['user_id'])) {
    header("location:login.php");
    exit();
}

$nama_user = isset($_SESSION['username']) ? $_SESSION['username'] : "User";
$inisial = strtoupper(substr($nama_user, 0, 1));
$user_id_login = $_SESSION['user_id']; // Ambil ID User dari Session

try {
    // --- 2. KONEKSI & AMBIL DATA KHUSUS USER INI ---
    $manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
    
    // BAGIAN YANG DIPERBAIKI:
    // Filter data berdasarkan 'user_id' milik user yang sedang login
    $filter = ['user_id' => $user_id_login]; 
    $options = ['sort' => ['tanggal' => 1]];
    
    $query = new MongoDB\Driver\Query($filter, $options);
    $cursor = $manager->executeQuery('dompetpribadi.transaksi', $query);
    $transaksi = $cursor->toArray();

    $masuk = 0; $keluar = 0; $sedekah = 0;
    $chart_data = [];
    $today_date = date('Y-m-d');
    
    // STATUS SEDEKAH HARI INI
    $sudah_sedekah_hari_ini = false; 

    foreach ($transaksi as $t) {
        $tgl = ($t->tanggal instanceof MongoDB\BSON\UTCDateTime) ? $t->tanggal->toDateTime()->format('Y-m-d') : $t->tanggal;

        if ($t->jenis === 'sedekah' && $tgl === $today_date) {
            $sudah_sedekah_hari_ini = true;
        }

        if ($t->jenis === 'pemasukan') $masuk += $t->jumlah;
        elseif ($t->jenis === 'sedekah') $sedekah += $t->jumlah;
        else $keluar += $t->jumlah;

        // Data Grafik
        if (!isset($chart_data[$tgl])) $chart_data[$tgl] = ['masuk' => 0, 'keluar' => 0];
        if ($t->jenis === 'pemasukan') $chart_data[$tgl]['masuk'] += $t->jumlah;
        else $chart_data[$tgl]['keluar'] += $t->jumlah;
    }

    $saldo = $masuk - ($keluar + $sedekah);
    ksort($chart_data);
    $list_transaksi = array_reverse($transaksi); 

    // Format Data ChartJS
    $lbl = []; $d_in = []; $d_out = [];
    foreach ($chart_data as $k => $v) {
        $lbl[] = date('d M', strtotime($k));
        $d_in[] = $v['masuk'];
        $d_out[] = $v['keluar'];
    }

} catch (Exception $e) { echo "Error: " . $e->getMessage(); exit; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard ✨ AntiBoncos</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { font-family: 'Outfit', sans-serif; background: #f8f9fe; min-height: 100vh; position: relative; display: flex; flex-direction: column;}
    .main-content { flex: 1; }

    /* --- ANIMASI BACKGROUND --- */
    .bg-animation-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: -1; }
    .orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.6; animation: float 20s infinite ease-in-out; }
    .orb-1 { width: 400px; height: 400px; background: #6c5ce7; top: -100px; left: -100px; }
    .orb-2 { width: 300px; height: 300px; background: #74b9ff; bottom: 10%; right: -50px; animation-delay: -5s; }
    @keyframes float { 0% { transform: translate(0, 0); } 50% { transform: translate(20px, -20px); } 100% { transform: translate(0, 0); } }

    /* --- NAVBAR --- */
    .navbar-glass { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.5); padding: 15px 0; margin-bottom: 25px; position: sticky; top: 0; z-index: 1000; }
    .brand-gradient { background: -webkit-linear-gradient(45deg, #6c5ce7, #a29bfe); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }
    .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #6c5ce7, #a29bfe); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    
    /* --- HOLO CARD --- */
    .holo-card { background: linear-gradient(135deg, #6c5ce7, #a29bfe); border-radius: 25px; padding: 30px; color: white; box-shadow: 0 20px 40px rgba(108, 92, 231, 0.35); position: relative; overflow: hidden; margin-bottom: 25px; }
    .holo-circle { position: absolute; background: rgba(255,255,255,0.1); border-radius: 50%; }

    /* --- STATUS BADGE (POJOK KANAN ATAS) --- */
    .card-status-pill {
        position: absolute; top: 25px; right: 25px;
        background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(5px);
        padding: 5px 15px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;
        display: flex; align-items: center; gap: 5px; border: 1px solid rgba(255,255,255,0.3);
    }
    .pill-warning { color: #ffeaa7; animation: bounce 2s infinite; }
    .pill-success { color: #55efc4; }
    @keyframes bounce { 0%, 20%, 50%, 80%, 100% {transform: translateY(0);} 40% {transform: translateY(-5px);} 60% {transform: translateY(-3px);} }

    /* --- COMPONENTS --- */
    .quote-box { background: #fff; border-left: 5px solid #6c5ce7; border-radius: 15px; padding: 15px 20px; display: flex; align-items: center; gap: 15px; margin-bottom: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
    .glass-panel { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 8px 24px rgba(149, 157, 165, 0.05); border: 1px solid #f0f0f0; }
    
    /* Tombol Aksi Tabel (Edit/Hapus) */
    .btn-action { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; transition: 0.2s; background: #f8f9fa; }
    .btn-action:hover { background: #e9ecef; transform: scale(1.1); }

    .badge-soft-green { background: #d1fae5; color: #065f46; padding: 6px 15px; border-radius: 30px; font-weight: 600; font-size: 0.8rem; }
    .badge-soft-red { background: #fee2e2; color: #991b1b; padding: 6px 15px; border-radius: 30px; font-weight: 600; font-size: 0.8rem; }
    .badge-soft-gold { background: #fef9c3; color: #854d0e; padding: 6px 15px; border-radius: 30px; font-weight: 600; font-size: 0.8rem; border: 1px solid #facc15; }
    .footer-glass { text-align: center; padding: 30px 0; margin-top: 50px; color: #8898aa; font-size: 0.85rem; }
</style>
</head>
<body>

<div class="bg-animation-container">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
</div>

<nav class="navbar-glass">
    <div class="container d-flex justify-content-between align-items-center" style="max-width: 900px;">
        <div class="d-flex align-items-center gap-2">
            <span style="font-size: 2rem;">💸</span> 
            <div>
                <h4 class="m-0 brand-gradient">AntiBoncos.</h4>
                <small class="text-secondary fw-bold" style="font-size: 0.7rem;">FINANCIAL TRACKER</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block">
                <small class="d-block text-secondary" style="font-size: 0.7rem;">Halo, Kak!</small>
                <span class="fw-bold text-dark"><?= $nama_user ?></span>
            </div>
            
            <div class="user-avatar shadow-sm"><?= $inisial ?></div>
            <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill">Logout</a>
        </div>
    </div>
</nav>

<div class="main-content container" style="max-width: 900px;">

    <div class="holo-card">
        <div class="holo-circle" style="width: 150px; height: 150px; top: -50px; right: -20px;"></div>
        <div class="holo-circle" style="width: 80px; height: 80px; bottom: 20px; left: 20px;"></div>
        
        <?php if($sudah_sedekah_hari_ini): ?>
            <div class="card-status-pill pill-success">
                <i class="bi bi-patch-check-fill"></i> Sedekah Done
            </div>
        <?php else: ?>
            <div class="card-status-pill pill-warning">
                <i class="bi bi-exclamation-triangle-fill"></i> Belum Sedekah
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between position-relative z-1 mt-2">
            <div>
                <span class="opacity-75 fw-light">Total Balance</span>
                <h1 class="fw-bold mb-0 mt-1">Rp <?= number_format($saldo,0,',','.') ?></h1>
            </div>
            <i class="bi bi-wallet2 fs-1 opacity-25" style="margin-top: 40px;"></i>
        </div>

        <div class="row mt-4 position-relative z-1 text-center">
            <div class="col-4 border-end border-white border-opacity-25">
                <small class="opacity-75 d-block">Masuk</small>
                <span class="fw-bold fs-5">Rp <?= number_format($masuk,0,',','.') ?></span>
            </div>
            <div class="col-4 border-end border-white border-opacity-25">
                <small class="opacity-75 d-block">Keluar</small>
                <span class="fw-bold fs-5">Rp <?= number_format($keluar,0,',','.') ?></span>
            </div>
            <div class="col-4">
                <small class="text-warning fw-bold d-block">Sedekah ✨</small>
                <span class="fw-bold fs-5 text-warning">Rp <?= number_format($sedekah,0,',','.') ?></span>
            </div>
        </div>
    </div>

    <div class="quote-box">
        <div class="quote-icon" style="font-size: 1.5rem;">💡</div>
        <div>
            <small class="text-uppercase fw-bold text-primary opacity-75" style="font-size: 0.7rem;">REMINDER</small>
            <p class="m-0 fw-bold text-dark fst-italic" id="quote-text">"..."</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="glass-panel h-100" id="panelForm">
                <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-plus-circle-fill text-primary"></i> Transaksi Baru</h6>
                <form action="tambah.php" method="POST">
                    <div class="mb-3">
                        <label class="small text-muted fw-bold ms-1">Tipe</label>
                        <select name="jenis" id="jenisInput" class="form-select border-0 bg-light rounded-3 py-2">
                            <option value="pemasukan">🟢 Pemasukan</option>
                            <option value="pengeluaran">🔴 Pengeluaran</option>
                            <option value="sedekah">🤲 Infaq / Sedekah</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-bold ms-1">Nominal</label>
                        <input type="number" name="jumlah" class="form-control border-0 bg-light rounded-3 py-2" placeholder="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-bold ms-1">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control border-0 bg-light rounded-3 py-2" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-bold ms-1">Keterangan</label>
                        <input type="text" name="deskripsi" class="form-control border-0 bg-light rounded-3 py-2" placeholder="Ket..." required>
                    </div>
                    <button class="btn btn-primary w-100 rounded-pill py-2 fw-bold" style="background: #6c5ce7; border:none;">Simpan Transaksi</button>
                </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="glass-panel h-100">
                <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-activity text-primary"></i> Grafik Keuangan</h6>
                <canvas id="chartKu" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <div class="glass-panel mt-4">
        <h6 class="fw-bold mb-3">⏳ Riwayat Transaksi</h6>
        <div class="table-responsive">
            <table class="table table-borderless align-middle">
                <tbody>
                    <?php if(empty($list_transaksi)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada data bestie. Yuk isi dulu!</td></tr>
                    <?php else: ?>
                        <?php foreach($list_transaksi as $r): ?>
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td class="small text-muted"><?= date('d/m/y', strtotime($r->tanggal instanceof MongoDB\BSON\UTCDateTime ? $r->tanggal->toDateTime()->format('Y-m-d') : $r->tanggal)) ?></td>
                            <td class="fw-bold text-dark"><?= $r->deskripsi ?></td>
                            <td>
                                <?php if($r->jenis == 'pemasukan'): ?><span class="badge-soft-green">Income</span>
                                <?php elseif($r->jenis == 'sedekah'): ?><span class="badge-soft-gold">Sedekah</span>
                                <?php else: ?><span class="badge-soft-red">Expense</span><?php endif; ?>
                            </td>
                            <td class="text-end fw-bold <?= $r->jenis == 'pemasukan' ? 'text-success' : ($r->jenis == 'sedekah' ? 'text-warning' : 'text-danger') ?>">
                                <?= number_format($r->jumlah,0,',','.') ?>
                            </td>
                            <td class="text-end">
                                <a href="edit.php?id=<?= $r->_id ?>" class="btn-action text-primary me-1"><i class="bi bi-pencil-square"></i></a>
                                <a href="hapus.php?id=<?= $r->_id ?>" class="btn-action text-danger" onclick="return confirm('Hapus nih?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer class="footer-glass">
    <div class="container"><small>&copy; 2026 <b>AntiBoncos</b>.</small></div>
</footer>

<script>
    // --- POPUP LOGIC (Sama kayak sebelumnya) ---
    const statusSedekah = <?= $sudah_sedekah_hari_ini ? 'true' : 'false' ?>;
    const urlParams = new URLSearchParams(window.location.search);
    const pesan = urlParams.get('pesan');

    if (pesan === 'sukses_sedekah') {
        Swal.fire({ title: 'Alhamdulillah! ✨', text: 'Semoga berkah ya!', icon: 'success' }).then(() => cleanUrl());
    } 
    else if (pesan === 'sukses') {
        Swal.fire({ title: 'Berhasil!', icon: 'success', timer: 1500, showConfirmButton: false }).then(() => cleanUrl());
    }
    else if (statusSedekah === false) {
        Swal.fire({
            title: 'Assalamualaikum! 🙏',
            html: `<p>Kamu belum ada catatan <b>Sedekah</b> hari ini.</p><h4 style="color:#6c5ce7;">Mau sedekah hari ini?</h4>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6c5ce7',
            cancelButtonColor: '#d63031',
            confirmButtonText: 'Iya, Input Sekarang! 🚀',
            cancelButtonText: 'Nanti Aja deh 😅',
            background: '#fff',
            backdrop: `rgba(0,0,123,0.4)`
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('jenisInput').value = 'sedekah';
                document.querySelector('input[name="jumlah"]').focus();
                document.getElementById('panelForm').scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    function cleanUrl() { window.history.replaceState(null, null, window.location.pathname); }

    // --- AUTO QUOTES (Sama kayak sebelumnya) ---
    const quotesList = [
        "Gaya elit, ekonomi sulit. Inget cicilan bestie! 😭",
        "Janji gak checkout Shopee hari ini? 👀",
        "Kopi mahal cuma bikin deg-degan, mending nabung. ☕",
        "Dompet tipis jangan dipaksa meringis. Hemat pangkal kaya! 💰",
        "Definisi dewasa: Tau mana keinginan, mana kebutuhan. 💅",
        "Uangmu tidak unlimited, sadar diri ya cantik/ganteng. ✨",
        "Self reward boleh, tapi jangan sampai makan promag. 💊",
        "Ingat! Diskon itu jebakan batman. Waspadalah! 🦇",
        "Dikit-dikit lama-lama jadi bukit. Nabung yuk! ⛰️",
        "Jangan gengsi, makan di warteg juga kenyang kok. 🍛",
        "Gaji numpang lewat kayak mantan, cuma nyapa doang. 💸",
        "Healing mulu, hilang duit iya. Stress ilang, miskin datang. ✈️",
        "Inget, bapakmu bukan Sultan Andara. Kerja keras bestie! 🔨",
        "Dompetmu berteriak: TOLONG AKU SEKARAT! 🚑",
        "Saldo nipis, jangan so-soan mau traktir bestie. 🤫",
        "Barang 90rb + Ongkir 10rb = MAHAL. Barang 100rb Free Ongkir = GAS! 📉",
        "Keranjang Shopee penuh? Hapus dulu, nangis kemudian. 🛒",
        "Jangan tergocek 'Flash Sale', itu cuma trik marketing, Sayang. 🚫",
        "Beli kopi 50rb lancar, beli nasi 15rb mikir keras. Dasar aku. ☕",
        "Mau nikah modal cinta doang? KUA bayar woy! 💍",
        "Tabungan masa depan lebih cakep daripada outfit hari ini. 👔",
        "Investasi leher ke atas, kurangi jajan ke bawah. 🧠",
        "Stop scroll marketplace! Liat tuh token listrik udah bunyi! ⚡",
        "Uang receh jangan dibuang, dikumpulin bisa buat beli seblak. 🍜",
        "Hidup itu murah, yang mahal itu gengsimu, kawan. 🥀",
        "Pura-pura kaya itu capek, mending pura-pura lupa pas ditagih utang. Eh canda. 🤣",
        "Jangan gali lubang tutup lubang, nanti keperosok sendiri. 🕳️",
        "Masak sendiri: Sehat & Hemat. GoFood: Enak & Kere. Pilih mana? 🍳",
        "Cita-cita jadi rich auntie/uncle, tapi nabung aja males. Hadeh. 😮‍💨",
        "Inget kata mama: Jangan boros-boros nak! 👩‍🦳"
    ];
    const quoteElement = document.getElementById('quote-text');
    setInterval(() => {
        quoteElement.style.opacity = 0;
        setTimeout(() => {
            quoteElement.innerText = '"' + quotesList[Math.floor(Math.random() * quotesList.length)] + '"';
            quoteElement.style.opacity = 1;
        }, 500);
    }, 5000);

    // --- CHART JS (BAGIAN INI YANG DIPERBAIKI) ---
    new Chart(document.getElementById('chartKu'), {
        type: 'line',
        data: {
            labels: <?= json_encode($lbl) ?>,
            datasets: [
                { 
                    label: 'Income', 
                    data: <?= json_encode($d_in) ?>, 
                    borderColor: '#00b894', 
                    backgroundColor: 'rgba(0, 184, 148, 0.1)', 
                    tension: 0.4, 
                    fill: true,
                    pointRadius: 4, // Titik lebih jelas
                    pointHoverRadius: 7 // Pas dihover membesar
                },
                { 
                    label: 'Expense', 
                    data: <?= json_encode($d_out) ?>, 
                    borderColor: '#d63031', 
                    backgroundColor: 'rgba(214, 48, 49, 0.1)', 
                    tension: 0.4, 
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 7
                }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',     // Hover di area tanggal, bukan harus pas garis
                intersect: false,  // Munculin popup walau gak kena garis
            },
            plugins: { 
                legend: { position: 'top' },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)', // Tooltip putih kaca
                    titleColor: '#2d3436',
                    bodyColor: '#2d3436',
                    borderColor: 'rgba(0,0,0,0.1)',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                // Format jadi Rupiah
                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }, 
            scales: { 
                x: { grid: { display: false } }, 
                y: { display: false } 
            } 
        }
    });
</script>
</body>
</html>