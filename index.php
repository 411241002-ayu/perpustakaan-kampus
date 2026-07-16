<?php
session_start();

$koneksi = mysqli_connect("localhost", "root", "", "db_perpustakaan");
if (!$koneksi) { die("Koneksi gagal: " . mysqli_connect_error()); }

// Tambah Buku Baru
if (isset($_POST['tambah_buku'])) {
    $title = $_POST['title']; $author = $_POST['author']; $year = $_POST['year']; $stock = $_POST['stock'];
    mysqli_query($koneksi, "INSERT INTO books (title, author, year, stock) VALUES ('$title', '$author', '$year', '$stock')");
    
    $_SESSION['notif'] = ['tipe' => 'success', 'judul' => 'Berhasil!', 'pesan' => 'Koleksi buku baru sukses didaftarkan!'];
    header("Location: index.php"); exit();
}

$id_edit = ""; $title_edit = ""; $author_edit = ""; $year_edit = ""; $stock_edit = ""; $is_edit = false;
if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit']; $is_edit = true;
    $queryAmbilSatu = mysqli_query($koneksi, "SELECT * FROM books WHERE id = $id_edit");
    if ($buku_edit = mysqli_fetch_assoc($queryAmbilSatu)) {
        $title_edit = $buku_edit['title']; $author_edit = $buku_edit['author']; 
        $year_edit = $buku_edit['year']; $stock_edit = $buku_edit['stock'];
    }
}

// Update Buku
if (isset($_POST['update_buku'])) {
    $id = $_POST['id']; $title = $_POST['title']; $author = $_POST['author']; $year = $_POST['year']; $stock = $_POST['stock'];
    mysqli_query($koneksi, "UPDATE books SET title='$title', author='$author', year='$year', stock='$stock' WHERE id=$id");
    
    $_SESSION['notif'] = ['tipe' => 'success', 'judul' => 'Diperbarui!', 'pesan' => 'Data informasi buku berhasil disimpan!'];
    header("Location: index.php"); exit();
}

if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM books WHERE id = $id_hapus");
    
    $_SESSION['notif'] = ['tipe' => 'warning', 'judul' => 'Dihapus!', 'pesan' => 'Koleksi buku telah dihapus dari rak database.'];
    header("Location: index.php"); exit();
}

$ambilData = mysqli_query($koneksi, "SELECT * FROM books");

// Menghitung total stok buku fisik
$hitungTotalStok = mysqli_query($koneksi, "SELECT SUM(stock) as total_stok FROM books");
$dataStok = mysqli_fetch_assoc($hitungTotalStok);
$totalBukuFisik = $dataStok['total_stok'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utama - EduLib</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<!-- BACKGROUND TETAP PASTEL CERAH MERONA (TIDAK BERUBAH) -->
<body class="bg-gradient-to-br from-slate-200 via-purple-100 to-pink-200 min-h-screen font-sans selection:bg-rose-400 selection:text-white antialiased">

    <div class="max-w-6xl mx-auto p-4 md:p-8">
        
        <!-- HEADER: Transparan dengan paduan gradasi Rose Magenta -->
        <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white/40 p-6 rounded-2xl border border-white/60 backdrop-blur-xl shadow-xl shadow-slate-300/40">
            <div>
                <h1 class="text-3xl font-black tracking-tight bg-gradient-to-r from-slate-700 via-rose-500 to-fuchsia-600 bg-clip-text text-transparent">
                     📚 Dashboard
                </h1>
                <p class="text-sm text-slate-600 mt-1 font-medium">Sistem Data Koleksi Perpustakaan Kampus</p>
            </div>
            <!-- TOMBOL KELOLA PEMINJAM DAN TRANSAKSI KINI SUDAH BERUBAH SEIRING TEMA RAMBUT -->
            <div class="flex gap-3 w-full sm:w-auto">
                <a href="peminjam.php" class="text-xs font-bold bg-[#2b303c]/90 hover:bg-[#1e222b] text-slate-100 py-3 px-5 rounded-xl transition-all border border-slate-600/50 shadow-md text-center flex-1 sm:flex-initial flex items-center justify-center gap-1.5">
                    👥 Kelola Peminjam
                </a>
                <a href="transaksi.php" class="text-xs font-bold bg-gradient-to-r from-rose-500 via-pink-500 to-fuchsia-600 hover:opacity-95 text-white py-3 px-6 rounded-xl transition-all shadow-md shadow-rose-500/20 text-center flex-1 sm:flex-initial flex items-center justify-center gap-1.5 font-black">
                    Transaksi Baru 🔄
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- FORM INPUT (KIRI): Warna Smokey Slate Charcoal -->
            <div class="lg:col-span-5 sticky top-8">
                <div class="p-6 <?php echo $is_edit ? 'bg-amber-950/90 border-amber-500/40' : 'bg-[#2b303c] border-slate-600/50'; ?> rounded-2xl border border-slate-600/50 shadow-2xl text-slate-100">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-2 h-6 <?php echo $is_edit ? 'bg-amber-400' : 'bg-rose-500'; ?> rounded-full"></div>
                        <h3 class="text-sm font-black tracking-wider uppercase text-slate-200">
                            <?php echo $is_edit ? '📝 Mode Edit Buku' : '⚡ Input Buku Baru'; ?>
                        </h3>
                    </div>
                    
                    <form action="" method="POST" class="space-y-4">
                        <?php if($is_edit): ?><input type="hidden" name="id" value="<?php echo $id_edit; ?>"><?php endif; ?>

                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-2">Judul Koleksi Buku</label>
                            <input type="text" name="title" placeholder="Contoh: Pemrograman Web" value="<?php echo $title_edit; ?>" required class="w-full p-3 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-rose-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-2">Nama Penulis / Author</label>
                            <input type="text" name="author" placeholder="Contoh: Ayu Lestari" value="<?php echo $author_edit; ?>" required class="w-full p-3 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-rose-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-2">Tahun Terbit</label>
                            <input type="number" name="year" placeholder="Contoh: 2026" min="1900" max="2100" value="<?php echo $year_edit; ?>" required class="w-full p-3 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-rose-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold tracking-wider text-slate-400 uppercase mb-2">Ketersediaan Stok (Jumlah Fisik)</label>
                            <input type="number" name="stock" placeholder="Masukkan angka stok" min="1" value="<?php echo $stock_edit; ?>" required class="w-full p-3 bg-[#1e222b] border border-slate-600 rounded-xl text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-rose-400 transition-all">
                        </div>

                        <div class="flex gap-3 pt-2">
                            <?php if($is_edit): ?>
                                <button type="submit" name="update_buku" class="flex-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black py-3 rounded-xl text-sm transition-all shadow-md cursor-pointer">Simpan Perubahan</button>
                                <a href="index.php" class="w-1/3 bg-slate-700 hover:bg-slate-600 text-slate-200 font-semibold py-3 rounded-xl text-sm text-center transition-all flex items-center justify-center border border-slate-600">Batal</a>
                            <?php else: ?>
                                <button type="submit" name="tambah_buku" class="w-full bg-gradient-to-r from-rose-500 to-fuchsia-600 hover:from-rose-400 hover:to-fuchsia-500 text-white font-black py-3 rounded-xl text-sm transition-all shadow-lg shadow-rose-500/30 cursor-pointer">+ Daftarkan Buku</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABEL VIEW DI KANAN -->
            <div class="lg:col-span-7 space-y-4">
                
                <!-- KOTAK CARI: Smokey Charcoal -->
                <div class="p-4 bg-[#2b303c] border border-slate-600/50 rounded-2xl shadow-lg flex items-center gap-3 text-slate-100">
                    <span class="text-xl pl-2 text-rose-400">🔍</span>
                    <input type="text" id="inputCari" placeholder="Cari judul buku atau penulis dengan instan..." class="w-full p-2 bg-transparent text-sm text-slate-50 focus:outline-none placeholder-slate-500">
                </div>

                <!-- DAFTAR RAK: Kontainer utama Smokey Charcoal -->
                <div class="bg-[#2b303c] border border-slate-600/50 rounded-2xl p-6 shadow-2xl text-slate-100">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-4">
                        <h2 class="text-xs font-black tracking-wider text-rose-400 uppercase">Koleksi Buku di Rak</h2>
                        <!-- BADGE TOTAL -->
                        <div class="flex gap-2">
                            <span class="text-[10px] bg-rose-500/20 text-rose-300 px-2.5 py-1 rounded-full font-black border border-rose-500/30">
                                <?php echo mysqli_num_rows($ambilData); ?> Judul
                            </span>
                            <span class="text-[10px] bg-cyan-500/20 text-cyan-300 px-2.5 py-1 rounded-full font-black border border-cyan-500/30">
                                Total Buku: <?php echo $totalBukuFisik; ?> Ekspl
                            </span>
                        </div>
                    </div>
                    
                    <ul id="daftarBuku" class="space-y-3">
                        <?php while($buku = mysqli_fetch_assoc($ambilData)) { ?>
                            <!-- LIST ITEM BUKU: Charcoal gelap di bagian dalam dengan hover border Rose Magenta -->
                            <li class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 transition-all bg-[#1e222b] hover:bg-[#232833] border border-slate-700/60 hover:border-rose-500/50 rounded-xl shadow-sm">
                                <div>
                                    <h4 class="font-bold text-slate-50 text-base tracking-wide">
                                        <?php echo $buku['title']; ?> 
                                        <?php if(!empty($buku['year'])): ?>
                                            <span class="text-xs font-normal text-rose-400 font-bold">(<?php echo $buku['year']; ?>)</span>
                                        <?php endif; ?>
                                    </h4>
                                    <p class="text-xs text-slate-400 font-medium mt-0.5">✍️ <span class="text-fuchsia-400 font-semibold"><?php echo $buku['author']; ?></span></p>
                                </div>
                                
                                <div class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-3 border-t sm:border-t-0 border-slate-700/40 pt-2 sm:pt-0">
                                    <!-- BADGE STOK -->
                                    <span class="text-rose-300 bg-rose-950/30 border border-rose-900/40 px-3 py-1.5 rounded-xl text-xs font-extrabold font-mono shadow-inner">
                                        Stok: <?php echo $buku['stock']; ?>
                                    </span>
                                    <div class="flex gap-2">
                                        <a href="index.php?edit=<?php echo $buku['id']; ?>" class="text-xs font-bold bg-amber-500/10 text-amber-400 hover:bg-amber-50 hover:text-slate-950 border border-amber-500/20 py-1.5 px-3 rounded-lg transition-all">Edit</a>
                                        <a href="index.php?hapus=<?php echo $buku['id']; ?>" onclick="return confirm('Hapus koleksi buku ini?')" class="text-xs font-bold bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white border border-rose-500/20 py-1.5 px-3 rounded-lg transition-all">Hapus</a>
                                    </div>
                                </div>
                            </li>
                        <?php } ?>
                        <?php if(mysqli_num_rows($ambilData) == 0) {
                            echo "<div class='text-sm text-slate-500 text-center py-12 border border-dashed border-slate-600 rounded-xl bg-[#1e222b]/50'>Belum ada koleksi buku terdaftar.</div>";
                        } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fitur Cari Cepat JavaScript
        const inputCari = document.getElementById('inputCari');
        const daftarBuku = document.querySelectorAll('#daftarBuku li');
        inputCari.addEventListener('keyup', function() {
            const kataKunci = inputCari.value.toLowerCase();
            daftarBuku.forEach(function(buku) {
                const judulBuku = buku.querySelector('h4').textContent.toLowerCase();
                const penulisBuku = buku.querySelector('p').textContent.toLowerCase();
                if (judulBuku.includes(kataKunci) || penulisBuku.includes(kataKunci)) {
                    buku.style.display = 'flex';
                } else {
                    buku.style.display = 'none';
                }
            });
        });
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