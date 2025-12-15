<?php

/**
 * Config/Path.php
 * 
 * Helper class untuk mengelola path ke berbagai folder dalam aplikasi
 * Menghindari hardcoding path dan membuat maintenance lebih mudah
 * 
 * Gunakan method static ini untuk include/require file view dan template
 */

class Path {
    /**
     * view($view_path)
     * Menghasilkan full path ke file view
     * 
     * Contoh: Path::view('stok/index') 
     * Hasil: __DIR__/../View/stok/index.php
     * 
     * @param string $view_path Path relatif tanpa ekstensi .php
     * @return string Full path ke file view
     */
    public static function view($view_path) {
        return __DIR__ . '/../View/' . $view_path;
    }
    
    /**
     * template($template_path)
     * Menghasilkan full path ke file template (header, footer, dll)
     * 
     * Contoh: Path::template('header')
     * Hasil: __DIR__/../View/template/header.php
     * 
     * @param string $template_path Path relatif tanpa ekstensi .php
     * @return string Full path ke file template
     */
    public static function template($template_path) {
        return __DIR__ . '/../View/template/' . $template_path;
    }
    
    /**
     * API()
     * Menghasilkan base URL untuk API endpoints
     * 
     * @return string Base URL untuk API
     */
    public static function API() {
        return './';
    }
}
?>