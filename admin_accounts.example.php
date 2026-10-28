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
        'password_hash' => '$2y$10$OlOpN82Sor/t/T6faNMfyuVQ5os.GtLzwSBanH6q3PaJKlCA.6mdG', #GantiPasswordKuat123!
    ],
    'deafeb1902@gmail.com' => [
        'user_nim' => 'admin2',
        'name' => 'Administrator Dua',
        'password_hash' => '$2y$10$JPQaKVTScrVkzdq0lXdyeuojr4NeLPy.QV8eZBN7p49CcuoVIU4fK', #Deafeb123
    ],
];
