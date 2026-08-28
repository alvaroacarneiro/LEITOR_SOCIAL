<?php
// ============================================================
// CONFIGURAÇÃO DO BANCO DE DADOS
// ============================================================
$host = 'localhost';
$dbname = 'kidionon_leitor_social';
$username = 'kidionon_leitor_social';
$password = 'A74859610c*';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro interno no servidor.']);
    exit;
}

function sendJson($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ============================================================
// CONFIGURAÇÃO DE SESSÃO (SIMPLES E FUNCIONAL)
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    // Configurações básicas de segurança
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_path', '/');
    
    // NÃO define cookie_domain - deixa o PHP usar o domínio atual
    // Isso funciona tanto em kidion.online quanto em www.kidion.online
    
    // Define o nome da sessão (opcional, mas ajuda a evitar conflitos)
    session_name('LEITOR_SOCIAL');
    
    // Inicia a sessão
    session_start();
}
?>
