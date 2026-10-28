<?php
/**
 * AKUN ADMIN YANG DIIZINKAN (allowlist).
 *
 * Tambahkan hanya alamat Gmail panitia yang memang diizinkan.
 * Setiap akun harus dipetakan ke baris users dengan role='admin' melalui user_nim.
 * Jangan menyimpan password teks biasa di file ini.
 *
 * Buat hash password lewat terminal XAMPP:
 *   C:\xampp\php\php.exe -r "echo password_hash('PASSWORD_KUAT_ANDA', PASSWORD_DEFAULT), PHP_EOL;"
 * Salin hasilnya ke password_hash. Jangan gunakan password contoh di dokumentasi.
 *
 * Contoh format (hapus komentar dan ganti seluruh nilainya):
 * 'panitia.nama@gmail.com' => [
 *     'user_nim' => 'admin', // harus cocok dengan users.nim dan role admin
 *     'name' => 'Administrator', // opsional; default memakai nama dari database
 *     'password_hash' => '$2y$10$PASTE_HASH_VALID_DI_SINI',
 * ],
 */
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}


return [
    'diahayupuspitasastra@gmail.com' => [
        'user_nim' => 'admin',
        'name' => 'Administrator Satu',
        'password_hash' => '$2y$10$zVPOuJifmxjEhj/ngil1S.HH4gg6FGoN19XCroNI2IttOwJCccPoq', #diah123
    ],
    'deafeb1902@gmail.com' => [
        'user_nim' => 'admin',
        'name' => 'Administrator Dua',
        'password_hash' => '$2y$10$a4PjPqeRectv.lYJrG2mZel6hgQglOt0armNmfXlX6T9GEx5LzqHe', #dea123
    ],
    'bebek123@gmail.com' => [
        'user_nim' => 'admin',
        'name' => 'Administrator Tiga',
        'password_hash' => '$2y$10$Y0eii0TXN60S98Z7gz32Ru1dvbkAANfwIerN7Q1FudZrVBSo234yC', #bebek123
    ],
];
