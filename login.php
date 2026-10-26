<?php 
require 'config.php'; 
if(isset($_SESSION['user_id'])) header("Location: index.php");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE nim = ?");
    $stmt->execute([$nim]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (!$user['is_active'] && $user['role'] === 'voter') {
            $error = "Akun Anda belum diaktifkan oleh panitia.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nim'] = $user['nim'];
            
            log_activity($pdo, $user['id'], 'LOGIN', 'User berhasil login');

            if ($user['role'] === 'admin') {
                header("Location: Admin/dashboard.php");
            } else {
                header("Location: User/dashboard.php");
            }
            exit;
        }
    } else {
        $error = "NIM atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - E-Voting</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Masuk Pemilihan</h2>
        <?php if($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm border border-red-200"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-700">NIM / Username</label>
                <input type="text" name="nim" required class="w-full border p-2.5 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Masukkan NIM">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1 text-gray-700">Password</label>
                <input type="password" name="password" required class="w-full border p-2.5 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Masukkan Password">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-lg hover:bg-indigo-700 font-bold transition">Masuk</button>
        </form>
        <div class="mt-4 text-xs text-gray-500 text-center bg-gray-50 p-2 rounded">
            <p>Admin: <b>admin</b> | Voter: <b>2201</b></p>
            <p>Password: <b>password123</b></p>
        </div>
    </div>
</body>
</html>