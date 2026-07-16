<?php
session_start();

$koneksi = mysqli_connect("localhost", "root", "", "db_perpustakaan");
if (!$koneksi) { die("Koneksi gagal: " . mysqli_connect_error()); }

// ==========================================
// FITUR AUTO-REPAIR TRANSAKSI
// ==========================================
$cekKolomKembali = mysqli_query($koneksi, "SHOW COLUMNS FROM loans LIKE 'tanggal_kembali'");
if (mysqli_num_rows($cekKolomKembali) == 0) {
    mysqli_query($koneksi, "ALTER TABLE loans ADD COLUMN tanggal_kembali DATE NULL AFTER tanggal_pinjam");
}
// ==========================================

// Logika Peminjaman Baru
if (isset($_POST['pinjam_buku'])) {
    $member_id = intval($_POST['member_id']);
    $book_id = intval($_POST['book_id']);
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $tanggal_kembali = !empty($_POST['tanggal_kembali']) ? $_POST['tanggal_kembali'] : NULL;
    $status = (!empty($tanggal_kembali)) ? 'Dikembalikan' : 'Dipinjam';
    
    $ambilNama = mysqli_query($koneksi, "SELECT name FROM members WHERE id = $member_id");
    $dataMember = mysqli_fetch_assoc($ambilNama);
    $nama_peminjam = $dataMember ? mysqli_real_escape_string($koneksi, $dataMember['name']) : 'Umum';

    $cekStok = mysqli_query($koneksi, "SELECT stock FROM books WHERE id = $book_id");
    $dataBuku = mysqli_fetch_assoc($cekStok);
    
    if ($dataBuku && $dataBuku['stock'] > 0) {
        if ($status == 'Dipinjam') {
            mysqli_query($koneksi, "UPDATE books SET stock = stock - 1 WHERE id = $book_id");
        }
        
        $sql = "INSERT INTO loans (nama_peminjam, book_id, tanggal_pinjam, tanggal_kembali, status) VALUES ('$nama_peminjam', '$book_id', '$tanggal_pinjam', " . ($tanggal_kembali ? "'$tanggal_kembali'" : "NULL") . ", '$status')";
        $simpanTx = mysqli_query($koneksi, $sql);
        
        if ($simpanTx) {
            $_SESSION['notif'] = ['tipe' => 'success', 'judul' => 'Berhasil!', 'pesan' => 'Data sirkulasi baru berhasil disimpan.'];
        } else {
            $_SESSION['notif'] = ['tipe' => 'error', 'judul' => 'Gagal!', 'pesan' => 'Error: ' . mysqli_error($koneksi)];
        }
    } else {
        $_SESSION['notif'] = ['tipe' => 'error', 'judul' => 'Stok Habis!', 'pesan' => 'Stok buku di rak kosong.'];
    }
    header("Location: transaksi.php"); exit();
}

// Logika Edit Transaksi
if (isset($_POST['edit_buku'])) {
    $id_tx = intval($_POST['id_transaksi']);
    $nama_peminjam = mysqli_real_escape_string($koneksi, $_POST['nama_peminjam']);
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $tanggal_kembali = !empty($_POST['tanggal_kembali']) ? $_POST['tanggal_kembali'] : NULL;
    $status = $_POST['status'];
    
    if ($status == 'Dikembalikan' && empty($tanggal_kembali)) {
        $tanggal_kembali = date('Y-m-d');
    } elseif ($status == 'Dipinjam') {
        $tanggal_kembali = NULL;
    }

    $cekLama = mysqli_query($koneksi, "SELECT status, book_id FROM loans WHERE id = $id_tx");
    $dataLama = mysqli_fetch_assoc($cekLama);
    
    if ($dataLama) {
        if ($dataLama['status'] == 'Dipinjam' && $status == 'Dikembalikan') {
            mysqli_query($koneksi, "UPDATE books SET stock = stock + 1 WHERE id = " . $dataLama['book_id']);
        } elseif ($dataLama['status'] == 'Dikembalikan' && $status == 'Dipinjam') {
            mysqli_query($koneksi, "UPDATE books SET stock = stock - 1 WHERE id = " . $dataLama['book_id']);
        }
    }

    $sql_update = "UPDATE loans SET nama_peminjam = '$nama_peminjam', tanggal_pinjam = '$tanggal_pinjam', tanggal_kembali = " . ($tanggal_kembali ? "'$tanggal_kembali'" : "NULL") . ", status = '$status' WHERE id = $id_tx";
    mysqli_query($koneksi, $sql_update);
    
    $_SESSION['notif'] = ['tipe' => 'success', 'judul' => 'Data Diperbarui!', 'pesan' => 'Perubahan data sirkulasi berhasil disimpan.'];
    header("Location: transaksi.php"); exit();
}

// Logika Pengembalian Cepat lewat tombol kuning
if (isset($_GET['kembali'])) {
    $id_transaksi = intval($_GET['kembali']);
    $tanggal_kembali = date('Y-m-d'); 
    
    $ambilTx = mysqli_query($koneksi, "SELECT book_id, status FROM loans WHERE id = $id_transaksi");
    $tx = mysqli_fetch_assoc($ambilTx);
    
    if ($tx && $tx['status'] == 'Dipinjam') {
        mysqli_query($koneksi, "UPDATE books SET stock = stock + 1 WHERE id = " . $tx['book_id']);
        mysqli_query($koneksi, "UPDATE loans SET status = 'Dikembalikan', tanggal_kembali = '$tanggal_kembali' WHERE id = $id_transaksi");
        $_SESSION['notif'] = ['tipe' => 'success', 'judul' => 'Buku Dikembalikan!', 'pesan' => 'Status berhasil diselesaikan!'];
    }
    header("Location: transaksi.php"); exit();
}

$ambilSemuaPeminjam = mysqli_query($koneksi, "SELECT * FROM members ORDER BY name ASC");
$ambilBuku = mysqli_query($koneksi, "SELECT * FROM books WHERE stock > 0");
$ambilLog = mysqli_query($koneksi, "SELECT loans.*, books.title FROM loans JOIN books ON loans.book_id = books.id ORDER BY loans.id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sirkulasi Buku</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gradient-to-br from-slate-200 via-purple-100 to-pink-200 min-h-screen font-sans antialiased">

    <div class="max-w-6xl mx-auto p-4 md:p-8">
        
        <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white/40 p-6 rounded-2xl border border-white/60 backdrop-blur-xl shadow-xl shadow-slate-300/40">
            <div>
                <h1 class="text-3xl font-black tracking-tight bg-gradient-to-r from-slate-700 via-rose-500 to-fuchsia-600 bg-clip-text text-transparent">🔄 Sirkulasi Buku</h1>
                <p class="text-sm text-slate-600 mt-1 font-medium">Log Sistem Peminjaman & Pengembalian Buku</p>
            </div>
            <a href="index.php" class="text-xs font-bold bg-[#2b303c]/90 hover:bg-[#1e222b] text-slate-100 py-3 px-5 rounded-xl transition-all border border-slate-600/50 shadow-md text-center flex items-center justify-center gap-1.5">
                🏠 Kembali ke Dashboard
            </a>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <div class="lg:col-span-4 w-full">
                <div class="p-6 bg-[#2b303c] border border-slate-600/50 rounded-2xl shadow-2xl text-slate-100">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-2 h-6 bg-rose-500 rounded-full"></div>
                        <h3 class="text-sm font-black tracking-wider uppercase text-slate-200">⚡ Sirkulasi Baru</h3>
                    </div>
                    
                    <form action="" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-2">Nama Peminjam</label>
                            <select name="member_id" required class="w-full p-3 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400 transition-all cursor-pointer">
                                <option value="" disabled selected class="text-slate-600">-- Pilih Peminjam --</option>
                                <?php mysqli_data_seek($ambilSemuaPeminjam, 0); while($m = mysqli_fetch_assoc($ambilSemuaPeminjam)): ?>
                                    <option value="<?php echo $m['id']; ?>" class="text-slate-100"><?php echo $m['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-2">Pilih Koleksi Buku</label>
                            <select name="book_id" required class="w-full p-3 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400 transition-all cursor-pointer">
                                <option value="" disabled selected class="text-slate-600">-- Pilih Buku --</option>
                                <?php while($b = mysqli_fetch_assoc($ambilBuku)): ?>
                                    <option value="<?php echo $b['id']; ?>" class="text-slate-100"><?php echo $b['title']; ?> (Stok: <?php echo $b['stock']; ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-2">Tanggal Pinjam</label>
                            <input type="date" name="tanggal_pinjam" value="<?php echo date('Y-m-d'); ?>" required class="w-full p-3 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400 transition-all cursor-pointer">
                        </div>

                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-2">Tanggal Kembali (Opsional)</label>
                            <input type="date" name="tanggal_kembali" class="w-full p-3 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400 transition-all cursor-pointer">
                            <span class="text-[10px] text-slate-500 block mt-1">*Kosongkan jika buku belum dikembalikan</span>
                        </div>

                        <button type="submit" name="pinjam_buku" class="w-full bg-gradient-to-r from-rose-500 to-fuchsia-600 hover:from-rose-400 hover:to-fuchsia-500 text-white font-black py-3 rounded-xl text-sm transition-all shadow-lg shadow-rose-500/30 cursor-pointer">
                            Proses Peminjaman ✓
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-8 w-full">
                <div class="bg-[#2b303c] border border-slate-600/50 rounded-2xl p-6 shadow-2xl text-slate-100">
                    <h2 class="text-xs font-black tracking-wider text-rose-400 uppercase mb-4">Log Aktivitas Sirkulasi</h2>
                    
                    <ul class="space-y-3">
                        <?php if(mysqli_num_rows($ambilLog) > 0): ?>
                            <?php while($log = mysqli_fetch_assoc($ambilLog)): ?>
                                <li class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 transition-all bg-[#1e222b] border border-slate-700/60 hover:border-rose-500/50 rounded-xl shadow-sm">
                                    <div>
                                        <h4 class="font-bold text-slate-50 text-base tracking-wide"><?php echo $log['nama_peminjam']; ?></h4>
                                        <p class="text-xs text-slate-400 mt-0.5">📖 Meminjam: <span class="text-fuchsia-400 font-semibold"><?php echo $log['title']; ?></span></p>
                                        
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-[11px] font-mono">
                                            <span class="text-slate-400">📅 Pinjam: <b class="text-rose-400"><?php echo $log['tanggal_pinjam']; ?></b></span>
                                            <span class="text-slate-400">📅 Kembali: <b class="text-cyan-400"><?php echo (!empty($log['tanggal_kembali']) && $log['tanggal_kembali'] != '0000-00-00') ? $log['tanggal_kembali'] : '-'; ?></b></span>
                                        </div>
                                    </div>
                                    
                                    <div class="w-full sm:w-auto flex items-center gap-2 justify-between sm:justify-end">
                                        <button onclick="bukaModalEdit(<?php echo htmlspecialchars(json_encode($log)); ?>)" class="text-xs font-bold bg-slate-700 hover:bg-slate-600 text-slate-200 py-1.5 px-3 rounded-lg border border-slate-600 transition-all cursor-pointer shadow-md">
                                            Edit
                                        </button>

                                        <?php if($log['status'] == 'Dipinjam'): ?>
                                            <span class="text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/30 px-2 py-1 rounded-md font-bold">Dipinjam</span>
                                            <a href="transaksi.php?kembali=<?php echo $log['id']; ?>" class="text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 py-1.5 px-2.5 rounded-lg transition-all block text-center shadow-md">
                                                Kembalikan
                                            </a>
                                        <?php else: ?>
                                            <span class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2.5 py-1 rounded-md font-bold">Dikembalikan</span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class='text-sm text-slate-500 text-center py-12 border border-dashed border-slate-600 rounded-xl bg-[#1e222b]/50'>Belum ada aktivitas sirkulasi terdaftar.</div>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <div id="modalEdit" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-[#2b303c] border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl relative text-slate-100">
            <h3 class="text-base font-black uppercase text-rose-400 tracking-wider mb-4">✏️ Edit Data Sirkulasi</h3>
            
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="id_transaksi" id="edit_id">

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Nama Peminjam</label>
                    <input type="text" name="nama_peminjam" id="edit_nama" required class="w-full p-2.5 bg-[#1e222b] border border-slate-700 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Tanggal Pinjam</label>
                    <input type="date" name="tanggal_pinjam" id="edit_tgl_pinjam" required class="w-full p-2.5 bg-[#1e222b] border border-slate-700 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Tanggal Kembali</label>
                    <input type="date" name="tanggal_kembali" id="edit_tgl_kembali" class="w-full p-2.5 bg-[#1e222b] border border-slate-700 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Status Transaksi</label>
                    <select name="status" id="edit_status" class="w-full p-2.5 bg-[#1e222b] border border-slate-700 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400">
                        <option value="Dipinjam">Dipinjam</option>
                        <option value="Dikembalikan">Dikembalikan</option>
                    </select>
                </div>

                <div class="flex gap-2 pt-2 justify-end">
                    <button type="button" onclick="tutupModalEdit()" class="py-2.5 px-4 text-xs font-bold bg-slate-700 text-slate-300 rounded-xl hover:bg-slate-600 transition-all cursor-pointer">Batal</button>
                    <button type="submit" name="edit_buku" class="py-2.5 px-5 text-xs font-bold bg-gradient-to-r from-rose-500 to-fuchsia-600 text-white rounded-xl hover:from-rose-400 shadow-md cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalEdit(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama').value = data.nama_peminjam;
            document.getElementById('edit_tgl_pinjam').value = data.tanggal_pinjam;
            document.getElementById('edit_tgl_kembali').value = (data.tanggal_kembali && data.tanggal_kembali !== '0000-00-00') ? data.tanggal_kembali : '';
            document.getElementById('edit_status').value = data.status;
            
            document.getElementById('modalEdit').classList.remove('hidden');
        }

        function tutupModalEdit() {
            document.getElementById('modalEdit').classList.add('hidden');
        }
    </script>

    <?php if (isset($_SESSION['notif'])): ?>
    <script>
        Swal.fire({
            title: '<?php echo $_SESSION['notif']['judul']; ?>',
            text: '<?php echo $_SESSION['notif']['pesan']; ?>',
            icon: '<?php echo $_SESSION['notif']['tipe']; ?>',
            background: '#2b303c',
            color: '#f8fafc',
            confirmButtonColor: '#f43f5e'
        });
    </script>
    <?php unset($_SESSION['notif']); endif; ?>

</body>
</html>