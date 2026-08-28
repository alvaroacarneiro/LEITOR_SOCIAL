<?php
require_once __DIR__ . '/../config/database.php';

// ============================================================
// FUNÇÃO PARA EXTRAIR DADOS (COM FALLBACK)
// ============================================================
function getRequestData() {
    $raw = file_get_contents('php://input');
    $data = [];

    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $decoded;
        } else {
            parse_str($raw, $parsed);
            if (!empty($parsed)) {
                $data = $parsed;
            }
        }
    }

    if (empty($data) && !empty($_POST)) {
        $data = $_POST;
    }

    return $data;
}

// ============================================================
// LOG DE DEPURAÇÃO (remova depois)
// ============================================================
file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - REQUEST\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "RAW: " . file_get_contents('php://input') . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "COOKIE: " . print_r($_COOKIE, true) . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "SESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "------------------\n", FILE_APPEND);

$method = $_SERVER['REQUEST_METHOD'];
$input = getRequestData();

// Só exige dados para POST
if ($method === 'POST' && empty($input)) {
    sendJson(['error' => 'Nenhum dado recebido.'], 400);
}

// ============================================================
// ROTEAMENTO
// ============================================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'me') {
    if (isset($_SESSION['user_id'])) {
        sendJson([
            'logged' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name']
            ]
        ]);
    }
    sendJson(['logged' => false]);
}

if ($method === 'POST') {
    $action = isset($input['action']) ? trim($input['action']) : '';

    // REGISTRO
    if ($action === 'register') {
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            sendJson(['error' => 'Todos os campos são obrigatórios.'], 400);
        }
        if (strlen($name) < 2) {
            sendJson(['error' => 'Nome deve ter pelo menos 2 caracteres.'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJson(['error' => 'Email inválido.'], 400);
        }
        if (strlen($password) < 6) {
            sendJson(['error' => 'Senha deve ter pelo menos 6 caracteres.'], 400);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hash]);
            sendJson(['success' => true, 'message' => 'Usuário criado com sucesso!']);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                sendJson(['error' => 'Este email já está cadastrado.'], 400);
            }
            sendJson(['error' => 'Erro interno ao cadastrar.'], 500);
        }
    }

    // LOGIN
    if ($action === 'login') {
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            sendJson(['error' => 'Email e senha são obrigatórios.'], 400);
        }

        $stmt = $pdo->prepare("SELECT id, name, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            setcookie(session_name(), session_id(), [
                'expires' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            sendJson([
                'success' => true,
                'user' => ['id' => $user['id'], 'name' => $user['name']]
            ]);
        } else {
            sendJson(['error' => 'Email ou senha incorretos.'], 401);
        }
    }

    // LOGOUT
    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
        sendJson(['success' => true]);
    }

    sendJson(['error' => "Ação '$action' não reconhecida."], 400);
}

sendJson(['error' => 'Método não permitido.'], 405);
?>
