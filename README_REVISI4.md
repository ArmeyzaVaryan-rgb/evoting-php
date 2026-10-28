# E-Voting Kampus — Revisi 4

## Perubahan utama

- Navbar dan footer publik menggunakan container lebih lebar dan konsisten; hero section `index.php` tidak diubah.
- Panel kiri login memiliki judul vertikal lebih seimbang, kata **suara** dan **mahasiswa** diberi highlight emas, serta panel tetap tampil pada perangkat kecil.
- Admin dapat menambah dan mengedit pemilih, mengaktifkan/menonaktifkan akun, dan mengarsipkan akun dengan soft delete. Suara dan audit log tidak dihapus ketika pemilih diarsipkan.
- Filter pemilih menggunakan query SQL berdasarkan catatan nyata pada tabel `votes`: semua, sudah memilih, belum memilih, dan diarsipkan. Pencarian nama/NIM diproses oleh SQL.
- Admin dapat menambah/mengedit kandidat dan mengarsipkan atau memulihkannya. Pengarsipan kandidat tidak menghapus suara historis.
- Program kerja mempunyai kolom dan status publikasi tersendiri. Program kerja yang disetujui muncul setelah bagian Misi pada dialog detail kandidat di dashboard pemilih.
- Operasi yang mengubah data memakai POST, CSRF token, validasi server, prepared statements, password hashing untuk akun pemilih baru, dan pencatatan audit yang tersedia.

## 1. Salin proyek ke XAMPP

1. Ekstrak isi folder proyek ke `C:\xampp\htdocs\evoting-php`.
2. Jalankan Apache dan MySQL dari XAMPP.
3. Impor database aplikasi yang sudah ada ke phpMyAdmin. ZIP sumber tidak menyertakan dump SQL, jadi migrasi ini tidak membuat database/tabel dasar `users`, `candidates`, `votes`, atau `audit_logs` dari nol.
4. Pastikan konfigurasi koneksi di `config.php` sesuai dengan database lokal.

## 2. Jalankan migrasi Revisi 4

1. Buka phpMyAdmin dan pilih database `evoting`.
2. Buka tab **SQL**.
3. Buka file `database/migration_revisi4.sql`, salin seluruh isinya, tempel ke tab SQL, lalu jalankan.
4. Migrasi akan menambahkan kolom yang belum tersedia (`misi`, `misi_status`, `program_kerja`, `program_kerja_status`, `is_active` pada kandidat, serta `deleted_at` pada pemilih). Kolom yang sudah ada dilewati; baris data, suara, dan audit log tidak dihapus.
5. Jika akun database tidak punya izin membuat stored procedure, jalankan migrasi menggunakan akun database yang memiliki hak `ALTER` dan `CREATE ROUTINE`, atau minta pengelola database menjalankannya.

## 3. Konfigurasi akun admin secara lokal

Paket ini sengaja **tidak menyertakan `admin_accounts.php` yang berisi daftar akun/hash dari ZIP sumber**. Ini untuk menghindari distribusi kredensial atau daftar email admin.

1. Salin `admin_accounts.example.php` menjadi `admin_accounts.php` di folder utama.
2. Edit file lokal itu dan isi allowlist Gmail panitia. Setiap entri memerlukan `user_nim`, `name`, dan `password_hash` yang cocok dengan satu baris admin dalam tabel `users`.
3. Buat hash untuk password baru menggunakan PHP CLI, misalnya:

   ```powershell
   C:\xampp\php\php.exe -r "echo password_hash('GANTI_DENGAN_PASSWORD_BARU', PASSWORD_DEFAULT), PHP_EOL;"
   ```

4. Tempel hasil hash ke `password_hash`. Jangan tulis password teks biasa di file.
5. Jangan commit atau membagikan `admin_accounts.php`; file tersebut sudah tercantum di `.gitignore`.

## 4. Jalankan dan uji

Buka `http://localhost/evoting-php/`. Lakukan pengujian memakai database lokal atau database pengujian:

- Tambah pemilih dengan NIM baru; pastikan data masuk ke `users`.
- Coba NIM duplikat dan pastikan ditolak.
- Periksa filter sudah/belum memilih setelah data suara tercatat pada `votes`.
- Arsipkan pemilih dan pastikan ia tidak dapat login, sedangkan baris suara dan audit tetap tersedia.
- Tambah kandidat di kategori yang benar, isi visi, misi, dan program kerja, lalu setujui publikasi misi/program kerja.
- Pastikan kandidat aktif terlihat di dashboard pemilih, program kerja muncul sesudah misi, dan kandidat arsip tidak dapat dipilih lagi.
- Uji tampilan pada desktop, tablet, dan mobile.

## Catatan pengujian

Pemeriksaan sintaks PHP tidak membuktikan bahwa seluruh alur berfungsi di lingkungan Anda. Paket sumber tidak menyertakan dump skema/database SQL, sehingga koneksi database, migrasi, query aktual, autentikasi, dan operasi CRUD perlu dites pada database `evoting` lokal yang sebenarnya. Jangan gunakan data pemilih atau suara produksi untuk pengujian destruktif.
