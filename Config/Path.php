<?php
class Path {
    public static function view($view_path) {
        return __DIR__ . '/../View/' . $view_path;
    }
    
    public static function template($template_path) {
        return __DIR__ . '/../View/template/' . $template_path;
    }
    
    public static function API() {
        return './';
    }
}
?>