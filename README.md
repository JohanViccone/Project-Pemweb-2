# Hotel The Peak - PHP CRUD (sesuai desain)

Project ini adalah template **PHP + MySQL (PDO)** untuk aplikasi admin hotel sesuai mockup yang kamu upload:
- Login Admin
- Dashboard
- Manajemen Kamar (CRUD)
- Manajemen Tamu (CRUD untuk data menginap/stay)
- Transaksi & Boking (Create transaksi + otomatis buat tamu & stay)
- Laporan Transaksi (Read + Edit status checkout + Delete)
- Pengaturan Toko/Hotel (Update)
- Profil Pengguna (Update profil, ganti password, ganti foto)

## 1) Cara install (XAMPP/Laragon)

1. Copy folder project ini ke:
   - `htdocs/hotel_the_peak_crud` (XAMPP) **atau**
   - folder web root kamu.

2. Buat database + tabel:
   - Buka phpMyAdmin → Import → pilih file `database.sql`

3. Atur koneksi DB:
   - Edit `config/config.php`
   - Sesuaikan:
     - host, name, user, pass
   - Sesuaikan `base_url` jika perlu.

4. Jalankan di browser:
   - `http://localhost/hotel_the_peak_crud`

Login default:
- **admin / admin123**

> Catatan: file `assets/img/hero.jpg` hanya placeholder. Kamu boleh ganti dengan gambar banner sesuai desain.

---

## 2) Flow CRUD

### A) Login
- **login.php**: cek username + password (bcrypt) → simpan session → redirect dashboard
- **logout.php**: hapus session

### B) Kamar (Manajemen Kamar)
- **rooms.php**
  - Read (list) + search + filter status
- **room_form.php**
  - Create (Tambah Kamar)
  - Update (Edit Kamar)
- **room_delete.php**
  - Delete kamar (gagal bila sudah dipakai stay/transaksi karena foreign key)

Status kamar:
- `available` = Tersedia
- `occupied` = Terisi
- `maintenance` = Maintence

### C) Tamu / Data Menginap (Manajemen Tamu)
Halaman **guests.php** menampilkan **stays** (data menginap), bukan hanya master “nama tamu”.

- **guests.php**
  - Read list stay + search/filter
- **stay_form.php**
  - Update data stay (nama, kamar, tanggal, status)
  - Jika status diubah ke **Selesai**:
    - Stay → `selesai`
    - Transaksi (jika ada) → `checkout`
    - Kamar → `available` (kecuali maintenance)
- **stay_delete.php**
  - Delete stay (transaksi ikut terhapus karena FK cascade)
  - Kamar otomatis jadi available bila tidak ada stay aktif lain

### D) Transaksi & Boking
- **transaction_create.php**
  - Create transaksi:
    - Insert `guests`
    - Insert `stays` (status `menginap`)
    - Insert `transactions` (status `menginap`)
    - Update `rooms.status` → `occupied`

- **report.php**
  - Read list transaksi + search/filter
- **transaction_edit.php**
  - Update transaksi (dp, bayar, status)
  - Jika status transaksi diubah ke **checkout**:
    - stay → selesai
    - room → available (kecuali maintenance)
- **transaction_delete.php**
  - Delete transaksi (dengan menghapus stay → tx cascade)

### E) Pengaturan Toko
- **settings.php**
  - Update table `settings` (1 row)

### F) Profil Pengguna
- **profile.php**
  - Update foto (upload ke `assets/uploads`)
  - Ganti password
  - Update profil (nama/email/telp/alamat)

---

## 3) Struktur Database

- `users`
- `rooms`
- `guests`
- `stays`
- `transactions`
- `settings`

Lihat detailnya di `database.sql`.

---

## 4) Catatan penting
- Semua query pakai **PDO prepared statements**.
- Semua form POST pakai token CSRF sederhana (`csrf_token()`).
- Jika kamu mau tambah fitur:
  - role user (admin/staff)
  - multi hotel / multi cabang
  - cetak invoice / export laporan (PDF/Excel)
  - validasi overlap booking untuk kamar

Silakan edit sesuai kebutuhan tugas kamu.
