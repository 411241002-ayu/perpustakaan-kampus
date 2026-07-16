<?php
session_start();

$koneksi = mysqli_connect("localhost", "root", "", "db_perpustakaan");
if (!$koneksi) { die("Koneksi gagal: " . mysqli_connect_error()); }

// ==========================================
// FITUR AUTO-REPAIR: Otomatis tambah kolom prodi & telepon jika belum ada di database
// ==========================================
$cekKolomProdi = mysqli_query($koneksi, "SHOW COLUMNS FROM members LIKE 'prodi'");
if (mysqli_num_rows($cekKolomProdi) == 0) {
    mysqli_query($koneksi, "ALTER TABLE members ADD COLUMN prodi VARCHAR(100) NULL AFTER nim");
}
$cekKolomTelp = mysqli_query($koneksi, "SHOW COLUMNS FROM members LIKE 'telepon'");
if (mysqli_num_rows($cekKolomTelp) == 0) {
    mysqli_query($koneksi, "ALTER TABLE members ADD COLUMN telepon VARCHAR(20) NULL AFTER prodi");
}
// ==========================================

// Logika Tambah Peminjam
if (isset($_POST['tambah_peminjam'])) {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']); 
    $nim = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $prodi = mysqli_real_escape_string($koneksi, $_POST['prodi']);
    $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);
    
    $simpan = mysqli_query($koneksi, "INSERT INTO members (name, nim, prodi, telepon) VALUES ('$name', '$nim', '$prodi', '$telepon')");
    
    if ($simpan) {
        $_SESSION['notif'] = ['tipe' => 'success', 'judul' => 'Berhasil!', 'pesan' => 'Anggota baru berhasil terdaftar!'];
    } else {
        $_SESSION['notif'] = ['tipe' => 'error', 'judul' => 'Gagal Simpan!', 'pesan' => 'Terjadi kesalahan database: ' . mysqli_error($koneksi)];
    }
    header("Location: peminjam.php"); exit();
}

// Logika Edit Anggota (FITUR BARU)
if (isset($_POST['edit_peminjam'])) {
    $id_member = intval($_POST['id_member']);
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $nim = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $prodi = mysqli_real_escape_string($koneksi, $_POST['prodi']);
    $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);
    
    $update = mysqli_query($koneksi, "UPDATE members SET name='$name', nim='$nim', prodi='$prodi', telepon='$telepon' WHERE id=$id_member");
    
    if ($update) {
        $_SESSION['notif'] = ['tipe' => 'success', 'judul' => 'Diperbarui!', 'pesan' => 'Data anggota berhasil diperbarui.'];
    } else {
        $_SESSION['notif'] = ['tipe' => 'error', 'judul' => 'Gagal Update!', 'pesan' => 'Error: ' . mysqli_error($koneksi)];
    }
    header("Location: peminjam.php"); exit();
}

// Logika Hapus Peminjam
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM members WHERE id = $id_hapus");
    $_SESSION['notif'] = ['tipe' => 'warning', 'judul' => 'Dihapus!', 'pesan' => 'Data anggota telah dihapus dari sistem.'];
    header("Location: peminjam.php"); exit();
}

$ambilMember = mysqli_query($koneksi, "SELECT * FROM members ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peminjam</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gradient-to-br from-slate-200 via-purple-100 to-pink-200 min-h-screen font-sans antialiased">

    <div class="max-w-6xl mx-auto p-4 md:p-8">
        
        <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white/40 p-6 rounded-2xl border border-white/60 backdrop-blur-xl shadow-xl shadow-slate-300/40">
            <div>
                <h1 class="text-3xl font-black tracking-tight bg-gradient-to-r from-slate-700 via-rose-500 to-fuchsia-600 bg-clip-text text-transparent">
                     👥 Data Anggota
                </h1>
                <p class="text-sm text-slate-600 mt-1 font-medium">Manajemen Keanggotaan & Data Kontak Mahasiswa</p>
            </div>
            <div class="flex gap-3 w-full sm:w-auto">
                <a href="index.php" class="text-xs font-bold bg-[#2b303c]/90 hover:bg-[#1e222b] text-slate-100 py-3 px-5 rounded-xl transition-all border border-slate-600/50 shadow-md text-center flex-1 sm:flex-initial flex items-center justify-center gap-1.5">
                    🏠 Kembali ke Dashboard
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <div class="lg:col-span-5 w-full">
                <div class="p-6 bg-[#2b303c] border border-slate-600/50 rounded-2xl shadow-2xl text-slate-100">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-2 h-6 bg-rose-500 rounded-full"></div>
                        <h3 class="text-sm font-black tracking-wider uppercase text-slate-200">⚡ Input Anggota Baru</h3>
                    </div>
                    
                    <form action="" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-1">Nama Lengkap</label>
                            <input type="text" name="name" placeholder="Contoh: Akila Nasuwa" required class="w-full p-2.5 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-1">NIM</label>
                            <input type="text" name="nim" placeholder="Contoh: 411241012" required class="w-full p-2.5 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-1">Program Studi (Prodi)</label>
                            <input type="text" name="prodi" placeholder="Contoh: Teknik Informatika" required class="w-full p-2.5 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-1">No. Telepon</label>
                            <input type="text" name="telepon" placeholder="Contoh: 0812345678" required class="w-full p-2.5 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400 transition-all">
                        </div>

                        <button type="submit" name="tambah_peminjam" class="w-full bg-gradient-to-r from-rose-500 to-fuchsia-600 hover:from-rose-400 hover:to-fuchsia-500 text-white font-black py-3 rounded-xl text-sm transition-all shadow-lg shadow-rose-500/30 cursor-pointer">
                            + Daftarkan Anggota
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-7 w-full">
                <div class="bg-[#2b303c] border border-slate-600/50 rounded-2xl p-6 shadow-2xl text-slate-100 min-h-[200px]">
                    <h2 class="text-xs font-black tracking-wider text-rose-400 uppercase mb-4">Daftar Anggota Terdaftar</h2>
                    
                    <ul class="space-y-3">
                        <?php if(mysqli_num_rows($ambilMember) > 0): ?>
                            <?php while($member = mysqli_fetch_assoc($ambilMember)): ?>
                                <li class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 transition-all bg-[#1e222b] hover:bg-[#232833] border border-slate-700/60 hover:border-rose-500/50 rounded-xl shadow-sm">
                                    <div>
                                        <h4 class="font-bold text-slate-50 text-base tracking-wide"><?php echo $member['name']; ?> <span class="text-xs font-normal text-slate-400">(<?php echo $member['nim']; ?>)</span></h4>
                                        <p class="text-xs text-rose-400 font-medium mt-0.5">🎓 Prodi: <span class="text-fuchsia-400"><?php echo isset($member['prodi']) ? $member['prodi'] : '-'; ?></span></p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">📞 Telp: <span class="text-cyan-400 font-mono"><?php echo isset($member['telepon']) ? $member['telepon'] : '-'; ?></span></p>
                                    </div>
                                    <div class="flex gap-2 w-full sm:w-auto justify-end">
                                        <button onclick="bukaModalEdit(<?php echo htmlspecialchars(json_encode($member)); ?>)" class="text-xs font-bold bg-slate-700 hover:bg-slate-600 text-slate-200 py-1.5 px-3 rounded-lg border border-slate-600 transition-all cursor-pointer shadow-md">
                                            Edit
                                        </button>
                                        <a href="peminjam.php?hapus=<?php echo $member['id']; ?>" onclick="return confirm('Hapus anggota ini?')" class="text-xs font-bold bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white border border-rose-500/20 py-1.5 px-3 rounded-lg transition-all text-center">
                                            Hapus
                                        </a>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class='text-sm text-slate-500 text-center py-12 border border-dashed border-slate-600 rounded-xl bg-[#1e222b]/50'>Belum ada anggota terdaftar.</div>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <div id="modalEdit" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <div class="bg-[#2b303c] border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl relative text-slate-100">
            <h3 class="text-base font-black uppercase text-rose-400 tracking-wider mb-4">✏️ Edit Data Anggota</h3>
            
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="id_member" id="edit_id">

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="edit_name" required class="w-full p-2.5 bg-[#1e222b] border border-slate-700 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">NIM</label>
                    <input type="text" name="nim" id="edit_nim" required class="w-full p-2.5 bg-[#1e222b] border border-slate-700 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Program Studi</label>
                    <input type="text" name="prodi" id="edit_prodi" required class="w-full p-2.5 bg-[#1e222b] border border-slate-700 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">No. Telepon</label>
                    <input type="text" name="telepon" id="edit_telepon" required class="w-full p-2.5 bg-[#1e222b] border border-slate-700 rounded-xl text-sm text-slate-100 focus:outline-none focus:border-rose-400">
                </div>

                <div class="flex gap-2 pt-2 justify-end">
                    <button type="button" onclick="tutupModalEdit()" class="py-2.5 px-4 text-xs font-bold bg-slate-700 text-slate-300 rounded-xl hover:bg-slate-600 transition-all cursor-pointer">Batal</button>
                    <button type="submit" name="edit_peminjam" class="py-2.5 px-5 text-xs font-bold bg-gradient-to-r from-rose-500 to-fuchsia-600 text-white rounded-xl hover:from-rose-400 shadow-md cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalEdit(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_nim').value = data.nim;
            document.getElementById('edit_prodi').value = data.prodi ? data.prodi : '';
            document.getElementById('edit_telepon').value = data.telepon ? data.telepon : '';
            
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