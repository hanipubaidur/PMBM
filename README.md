<div align="center">
  <img src="https://cdn.postimage.me/2026/04/11/logo-kemenag.png" alt="Logo MAN 1 MURA" width="120">
  <h1>🎓 PMBM MAN 1 MUSI RAWAS</h1>
  <p>Sistem Penerimaan Murid Baru Madrasah Tahun Ajaran 2026/2027</p>
</div>

---

## 🌟 Selamat Datang Calon Siswa/i MAN 1 Musi Rawas!

Selamat datang di portal PMBM Online MAN 1 Musi Rawas! Kami sangat antusias menyambut para calon siswa/i baru yang akan bergabung dengan keluarga besar MAN 1 Musi Rawas. Di sini kalian akan memulai perjalanan menuju masa depan yang cerah dengan pendidikan berkualitas berbasis Islam.

## 📚 Panduan untuk Calon Siswa

### 🔑 1. Pendaftaran Akun
1. Klik tombol "Daftar" di pojok kanan atas
2. Isi formulir dengan data:
   - Nama Lengkap
   - NISN (10 digit)
   - No. WhatsApp
   - Pilih Jalur Pendaftaran
   - Password
   - Konfirmasi Password
   - CAPTCHA
3. Klik "Daftar" untuk membuat akun
4. Cek WhatsApp Anda untuk menerima detail kredensial login (terintegrasi API).

### 🔐 2. Login ke Sistem
1. Klik tombol "Login" di pojok kanan atas
2. Masukkan NISN dan Password
3. Isi CAPTCHA
4. Centang "Ingat Saya" (opsional)
5. Klik "Login"

### 📝 3. Pengisian Data
1. **Data Pribadi**
   - Lengkapi biodata diri
   - Isi data orangtua/wali
   - Pilih program keahlian
   - Simpan perubahan

2. **Upload Dokumen** 📎
   - Kartu Keluarga (PDF, max 5MB)
   - Akte Kelahiran (PDF, max 5MB)
   - Pas Foto (JPG/PNG, max 5MB - dengan fitur auto-compress)
   - Raport semester 1-5 (PDF, max 5MB)

3. **Input Prestasi** 🏆
   - Bidang prestasi
   - Peringkat/hasil
   - Tingkat (Kecamatan - Internasional)

### ⏳ 4. Proses Verifikasi
1. Tunggu admin memverifikasi berkas
2. Cek status verifikasi di dashboard
3. Jika ditolak, lengkapi kekurangan data sesuai catatan Admin.

### 📢 5. Pengumuman Terjadwal
1. Buka menu "Pengumuman"
2. Tunggu Live Countdown Timer mencapai angka nol (00:00:00).
3. Halaman otomatis memuat hasil kelulusan.
4. Jika diterima, perhatikan instruksi kehadiran Pertemuan Wali sekaligus Daftar Ulang.

---

## 👨‍💼 Panduan untuk Admin

### 🔍 1. Manajemen Data Siswa
1. Login sebagai admin
2. Akses melalui menu:
   - "Verifikasi" - untuk verifikasi data baru/pending
   - "Peserta" - untuk melihat semua data peserta
   - "Pengumuman" - untuk manajemen pengumuman
3. Fitur pencarian terintegrasi (Cari berdasarkan Nama atau NISN).
4. Pengurutan data otomatis berdasarkan prioritas (Verified > Pending > Rejected) untuk mempercepat kerja admin.

### 📏 2. Verifikasi & Catatan
1. Klik tombol "Verifikasi" atau "Edit"
2. Input/edit data verifikasi:
   - Status verifikasi (Verified/Pending/Rejected)
   - Status penerimaan (muncul dinamis khusus untuk status Verified)
3. Sistem manajemen catatan:
   - Generate otomatis catatan jika ada berkas dokumen siswa yang kurang.
   - Admin dapat menambahkan catatan kustom yang langsung terlihat di dashboard siswa.

### 📊 3. Dashboard Admin
1. Statistik Pendaftaran (Total, Terverifikasi, Pending, Ditolak).
2. Statistik persebaran jumlah peserta per Jalur Pendaftaran.
3. Tabel *Quick View* 10 pendaftar terbaru beserta tombol aksi cepat.

---

## 🎨 Fitur Menarik

### Untuk Siswa
- 🎯 UI Dashboard bergaya modern (Rounded & Clean)
- 📱 Fully Responsive Design (Mobile-first)
- ⏳ Live Countdown Timer Pengumuman
- 📲 Notifikasi WhatsApp Real-time (Fonnte API)
- 🖼️ Auto-compress ukuran gambar sebelum diunggah

### Untuk Admin
- 📊 Statistik real-time visual
- 🛡️ Pencegahan duplikasi/clone data pada proses verifikasi
- 🔔 SweetAlert2 untuk notifikasi & konfirmasi yang elegan
- 📂 Manajemen file terpusat (Preview & Download Dokumen)

---

## 🆕 Update Terbaru (15 April 2026)

### 🛠 Perbaikan (Bug Fixes)
- **Fixed:** Mencegah terjadinya duplikasi (*cloning*) data di tabel pengumuman saat admin melakukan perubahan status berulang kali.
- **Fixed:** Perbaikan logika transaksi database (`PDO transaction`) pada proses pendaftaran agar aman dari bentrok data.
- **Fixed:** Pemanggilan nama jalur pendaftaran secara dinamis langsung dari database, tidak lagi dibatasi oleh *hardcode* array.
- **Fixed:** Perbaikan layout (margin & padding) yang bergeser (*annoying shift*) pada *sidebar* dan navigasi di panel admin.

### 💡 Peningkatan (Enhancements)
1. **Modernisasi UI/UX**
   - Merombak seluruh tampilan admin dan siswa dari tabel kaku menjadi *card layout* bergaya *modern rounded-4* dengan *soft shadow*.
   - Integrasi SweetAlert2 secara menyeluruh untuk *alert* sukses/gagal dan konfirmasi hapus data.
2. **Pengumuman Interaktif**
   - Implementasi *Live Countdown Timer* berbasis JavaScript dan sinkronisasi zona waktu (Asia/Jakarta) pada halaman pengumuman siswa.
   - Penggabungan informasi jadwal Pertemuan Wali dan Daftar Ulang menjadi satu tahapan praktis.
3. **Integrasi & Media**
   - Penambahan *preview* visual langsung untuk *upload* Pas Foto di halaman profil siswa dan verifikasi admin.
   - Peningkatan *template* pengiriman notifikasi pendaftaran via WhatsApp (Fonnte API) menjadi lebih detail dan personal (memanggil nama pendaftar).

<div align="center">
  <br>
  <p>💫 <b>Teruslah belajar, teruslah berkarya!</b> 💫</p>
  
  ---
  ### 👨‍💻 Development Team
  
  **Hanif Ubaidur Rohman Syah** (23106050081) <br>
  Mahasiswa Semester 6 - Informatika <br>
  Universitas Islam Negeri (UIN) Sunan Kalijaga Yogyakarta
  
</div>