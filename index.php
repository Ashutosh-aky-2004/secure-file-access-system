<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Secure SysCalls</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
  <div class="bg-gray-800 p-8 rounded-lg shadow-lg w-96">
    <h1 class="text-2xl font-bold text-green-500 mb-6 text-center">Secure SysCalls</h1>
    <form action="auth.php" method="POST" class="space-y-4">
      <input type="text" name="username" placeholder="Username" required class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded">
      <input type="password" name="password" placeholder="Password" required class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded">
      <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded font-semibold">
        Login
      </button>
      <p class="text-gray-400 text-sm text-center">
        New user? <a href="register.php" class="text-green-500 hover:underline">Register</a>
      </p>
      <?php if (isset($_GET['error'])) echo '<p class="text-red-500 text-sm">Invalid credentials!</p>'; ?>
    </form>
  </div>
</body>
</html>