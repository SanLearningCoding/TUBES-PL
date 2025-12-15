<?php

/**
 * Config/Database.php
 * 
 * Kelas untuk mengelola koneksi PDO ke database MySQL
 * Digunakan oleh semua Model untuk mengakses data
 * 
 * CARA MENGUBAH KONEKSI DATABASE:
 * - Ubah $host, $db_name, $username, $password di method __construct
 * - Atau set environment variable jika production
 */

class Database {
    // Konfigurasi koneksi database
    // CATATAN: Jika ingin menggunakan database berbeda, ubah $db_name di sini
    private $host = "localhost";
    private $db_name = "pmi_darah";  // Nama database - ganti jika menggunakan database lain
    private $username = "root";       // Username database - sesuaikan dengan config local
    private $password = "";           // Password database - kosong untuk localhost default
    public $conn;

    /**
     * getConnection()
     * Membuat koneksi PDO ke database
     * Menangani error exception jika koneksi gagal
     * 
     * @return PDO|null Koneksi database atau null jika gagal
     */
    public function getConnection() {
        $this->conn = null;
        try {
            // Buat koneksi baru dengan DSN format MySQL
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            // Set charset UTF-8 untuk mendukung karakter Indonesia
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // Log error ke console (development mode)
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>