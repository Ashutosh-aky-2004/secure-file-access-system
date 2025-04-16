<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Handle system call actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $file = $_POST['file'] ?? '';

    // Role-based permissions
    if ($_SESSION['role'] === 'user' && $action === 'delete_file') {
        die(json_encode(['error' => 'Permission denied!']));
    }

    // Log the action (skip logging save_file to avoid spam)
    if ($action !== 'refresh_logs') {
        $stmt = $db->prepare("INSERT INTO syscall_logs (user_id, action, file_path) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $action, $file]);
    }

    // Execute system calls
    $response = [];

    switch ($action) {
        case 'open_file':
            if (file_exists($file)) {
                $response['content'] = file_get_contents($file);
            } else {
                $response['error'] = 'File not found!';
            }
            break;

        case 'save_file':
            $content = $_POST['content'] ?? '';
            if (file_exists($file) && is_writable($file)) {
                if (file_put_contents($file, $content) !== false) {
                    $response['success'] = 'File saved successfully!';
                } else {
                    $response['error'] = 'Failed to save the file.';
                }
            } else {
                $response['error'] = 'File is not writable or does not exist.';
            }
            break;

        case 'delete_file':
            if (unlink($file)) {
                $response['success'] = 'File deleted!';
            } else {
                $response['error'] = 'Deletion failed!';
            }
            break;

        case 'refresh_logs':
            ob_start();
            include 'fetch_logs.php'; // We'll add this optional improvement if needed
            $response['logs'] = ob_get_clean();
            break;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fetch logs (admin sees all, user sees their own)
$query = $_SESSION['role'] === 'admin'
    ? "SELECT l.*, u.username FROM syscall_logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 50"
    : "SELECT l.*, u.username FROM syscall_logs l JOIN users u ON l.user_id = u.id WHERE l.user_id = ? ORDER BY l.created_at DESC LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute($_SESSION['role'] === 'admin' ? [] : [$_SESSION['user_id']]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Secure SysCalls</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .mostly-customized-scrollbar {
            display: block;
            border-radius: 5px;
            overflow: auto;
            height: 1em;
            padding: 1em;
            margin: 1em auto;
        }

        .mostly-customized-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 8px;
            background-color: #aaa;
        }

        .mostly-customized-scrollbar::-webkit-scrollbar-thumb {
            background: #000;
        }
    </style>
</head>

<body class="bg-gray-900 min-h-screen">
    <div class="container mx-auto p-4">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-green-500">
                Welcome, <?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['role'] ?>)
            </h1>
            <a href="logout.php" class="text-red-500 hover:text-red-400">Logout</a>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Action Panel -->
            <div class="bg-gray-800 p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold text-green-400 mb-4">System Calls</h2>
                <div class="space-y-4">
                    <input id="filePath" type="text" placeholder="File path"
                        class="w-full text-white px-4 py-2 bg-gray-700 border border-gray-600 rounded">
                    <button onclick="runSysCall('open_file')"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                        Open File
                    </button>
                    <button onclick="saveFile()"
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded">
                        Save File
                    </button>

                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <button onclick="confirmSysCall('delete_file')"
                            class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded">
                            Delete File (Admin Only)
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- File Content & Logs -->
            <div class="bg-gray-800 p-6 rounded-lg shadow md:col-span-2">
                <h2 class="text-xl font-semibold text-green-400 mb-4">File Editor</h2>
                <textarea id="fileEditor"
                    class="w-full bg-gray-900 text-white p-4 rounded h-64 font-mono text-sm resize-none mb-4"
                    placeholder="File content will appear here..."></textarea>


                <h2 class="text-xl font-semibold text-green-400 mb-4 ">Recent Activity</h2>

                <div id="recentLogs" class="bg-gray-900 p-4 rounded h-64 overflow-y-auto font-mono text-sm mostly-customized-scrollbar">
                    <?php foreach ($logs as $log): ?>
                        <div class="mb-2">
                            <span class="text-gray-400">[<?= $log['created_at'] ?>]</span>
                            <span class="text-green-400"><?= htmlspecialchars($log['username']) ?></span>
                            <span class="text-yellow-400"><?= $log['action'] ?></span>
                            <span class="text-white"><?= htmlspecialchars($log['file_path']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Auth Modal for Admin Actions -->
    <div id="authModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
        <div class="bg-gray-800 p-6 rounded-lg w-96">
            <h2 class="text-xl font-semibold text-green-400 mb-4">Confirm Action</h2>
            <p class="text-gray-300 mb-4">Enter your password to proceed:</p>
            <input type="password" id="authPassword" placeholder="Password"
                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded mb-4">
            <div class="flex justify-end space-x-2">
                <button onclick="hideModal()" class="px-4 py-2 bg-gray-700 rounded">Cancel</button>
                <button onclick="authorizeAction()" class="px-4 py-2 bg-green-600 rounded">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        let pendingAction = null;



        function runSysCall(action) {
            const filePath = document.getElementById('filePath').value;

            const body = new URLSearchParams({
                action: action,
                file: filePath
            });

            if (action === 'save_file') {
                const content = document.getElementById('fileEditor').value;
                body.append('content', content);
            }

            fetch('dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: body.toString()
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) alert(data.error);
                    if (data.success) alert(data.success);
                    if (data.content !== undefined) {
                        document.getElementById('fileEditor').value = data.content;
                    }

                    // Refresh logs if needed
                    refreshLogs();
                });
        }


        function saveFile() {
            runSysCall('save_file');

        }

        function confirmSysCall(action) {
            pendingAction = action;
            document.getElementById('authModal').classList.remove('hidden');
        }

        function authorizeAction() {
            const password = document.getElementById('authPassword').value;
            fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `username=${encodeURIComponent('<?= $_SESSION['username'] ?>')}&password=${encodeURIComponent(password)}`
                })
                .then(response => {
                    if (response.ok) runSysCall(pendingAction);
                    else alert('Wrong password!');
                    hideModal();
                });
        }

        function hideModal() {
            document.getElementById('authModal').classList.add('hidden');
        }

        // ✅ Function to refresh the logs
        function updateLogs() {
            fetch('get_logs.php')
                .then(res => res.json())
                .then(logs => {
                    const logContainer = document.querySelector('#recentLogs');
                    logContainer.innerHTML = ''; // clear old logs
                    logs.forEach(log => {
                        const div = document.createElement('div');
                        div.classList.add('mb-2');
                        div.innerHTML = `
                        <span class="text-gray-400">[${log.created_at}]</span>
                        <span class="text-green-400">${log.username}</span>
                        <span class="text-yellow-400">${log.action}</span>
                        <span class="text-white">${log.file_path}</span>
                    `;
                        logContainer.appendChild(div);
                    });
                });
        }

        // function saveFile() {
        //     const filePath = document.getElementById('filePath').value;
        //     const fileContent = document.getElementById('fileContent').value;
        //     fetch('dashboard.php', {
        //             method: 'POST',
        //             headers: {
        //                 'Content-Type': 'application/x-www-form-urlencoded'
        //             },
        //             body: `action=save_file&file=${encodeURIComponent(filePath)}&content=${encodeURIComponent(fileContent)}`
        //         })
        //         .then(response => response.json())
        //         .then(data => {
        //             if (data.success) alert(data.success);
        //             if (data.error) alert(data.error);
        //             updateLogs(); // update logs on file save
        //         });
        // }
        function saveFile() {
            runSysCall('save_file');
            updateLogs();
        }
    </script>

</body>

</html>