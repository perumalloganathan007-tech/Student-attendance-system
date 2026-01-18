<?php
// Simple autoloader created by install_tcpdf_direct.php
spl_autoload_register(function ($class) {
    // Only handle TCPDF class
    if ($class === 'TCPDF') {
        require_once __DIR__ . '/tcpdf/tcpdf.php';
    }
});