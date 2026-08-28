<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    sendJson(['error' => 'Não autorizado.'], 401);
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// ============================================================
// GET – Listar grupos do usuário
// ============================================================
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Buscar um grupo específico com detalhes
        $groupId = (int)$_GET['id'];
        $stmt = $pdo->prepare("
            SELECT g.*, u.name as creator_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                   (SELECT COUNT(*) FROM group_books WHERE group_id = g.id) as book_count
            FROM groups g
            JOIN users u ON g.created_by = u.id
            WHERE g.id = ?
        ");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        if (!$group) {
            sendJson(['error' => 'Grupo não encontrado.'], 404);
        }

        // Verificar se o usuário é membro
        $stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $user_id]);
        $group['is_member'] = $stmt->fetch() ? true : false;

        // Buscar membros
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, gm.role, gm.joined_at
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ?
            ORDER BY gm.role = 'admin' DESC, gm.joined_at ASC
        ");
        $stmt->execute([$groupId]);
        $group['members'] = $stmt->fetchAll();

        // Buscar livros do grupo
        $stmt = $pdo->prepare("
            SELECT b.*, gb.status, gb.added_at, u.name as added_by_name
            FROM group_books gb
            JOIN books b ON gb.book_id = b.id
            JOIN users u ON gb.added_by = u.id
            WHERE gb.group_id = ?
            ORDER BY gb.added_at DESC
        ");
        $stmt->execute([$groupId]);
        $group['books'] = $stmt->fetchAll();

        // Buscar discussões
        $stmt = $pdo->prepare("
            SELECT gd.*, u.name as user_name
            FROM group_discussions gd
            JOIN users u ON gd.user_id = u.id
            WHERE gd.group_id = ?
            ORDER BY gd.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$groupId]);
        $group['discussions'] = $stmt->fetchAll();

        sendJson($group);
    } else {
        // Listar grupos do usuário (criados ou membro)
        $stmt = $pdo->prepare("
            SELECT g.*, u.name as creator_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                   (SELECT COUNT(*) FROM group_books WHERE group_id = g.id) as book_count,
                   EXISTS (SELECT 1 FROM group_members WHERE group_id = g.id AND user_id = ?) as is_member
            FROM groups g
            JOIN users u ON g.created_by = u.id
            WHERE g.created_by = ? OR g.id IN (SELECT group_id FROM group_members WHERE user_id = ?)
            ORDER BY g.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        sendJson($stmt->fetchAll());
    }
}

// ============================================================
// POST – Criar grupo
// ============================================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'create') {
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $is_private = isset($input['is_private']) ? (bool)$input['is_private'] : false;

    if (empty($name)) {
        sendJson(['error' => 'Nome do grupo é obrigatório.'], 400);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO groups (name, description, created_by, is_private, join_code)
            VALUES (?, ?, ?, ?, ?)
        ");
        $joinCode = strtoupper(substr(md5(uniqid()), 0, 8));
        $stmt->execute([$name, $description, $user_id, $is_private, $joinCode]);
        $groupId = $pdo->lastInsertId();

        // Adicionar criador como admin
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$groupId, $user_id]);

        sendJson([
            'success' => true,
            'message' => 'Grupo criado com sucesso!',
            'group_id' => $groupId,
            'join_code' => $joinCode
        ]);
    } catch (PDOException $e) {
        sendJson(['error' => 'Erro ao criar grupo: ' . $e->getMessage()], 500);
    }
}

// ============================================================
// POST – Entrar em um grupo (por ID ou código)
// ============================================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'join') {
    $groupId = isset($input['group_id']) ? (int)$input['group_id'] : 0;
    $joinCode = trim($input['join_code'] ?? '');

    if (!$groupId && !$joinCode) {
        sendJson(['error' => 'Informe o ID do grupo ou o código de convite.'], 400);
    }

    if ($groupId) {
        $stmt = $pdo->prepare("SELECT id FROM groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT id FROM groups WHERE join_code = ?");
        $stmt->execute([$joinCode]);
        $group = $stmt->fetch();
    }

    if (!$group) {
        sendJson(['error' => 'Grupo não encontrado.'], 404);
    }

    // Verificar se já é membro
    $stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$group['id'], $user_id]);
    if ($stmt->fetch()) {
        sendJson(['error' => 'Você já é membro deste grupo.'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
    $stmt->execute([$group['id'], $user_id]);

    sendJson(['success' => true, 'message' => 'Você entrou no grupo!']);
}

// ============================================================
// POST – Sair do grupo
// ============================================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'leave') {
    $groupId = (int)($input['group_id'] ?? 0);
    if (!$groupId) {
        sendJson(['error' => 'ID do grupo é obrigatório.'], 400);
    }

    // Verificar se é o único admin
    $stmt = $pdo->prepare("SELECT COUNT(*) as admin_count FROM group_members WHERE group_id = ? AND role = 'admin'");
    $stmt->execute([$groupId]);
    $adminCount = $stmt->fetch()['admin_count'];

    $stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $user_id]);
    $member = $stmt->fetch();

    if (!$member) {
        sendJson(['error' => 'Você não é membro deste grupo.'], 400);
    }

    if ($member['role'] === 'admin' && $adminCount <= 1) {
        sendJson(['error' => 'Você é o único administrador. Transfira a administração antes de sair.'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $user_id]);

    sendJson(['success' => true, 'message' => 'Você saiu do grupo.']);
}

// ============================================================
// POST – Adicionar livro ao grupo
// ============================================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'add_book') {
    $groupId = (int)($input['group_id'] ?? 0);
    $bookId = (int)($input['book_id'] ?? 0);
    $status = $input['status'] ?? 'pending';

    if (!$groupId || !$bookId) {
        sendJson(['error' => 'ID do grupo e do livro são obrigatórios.'], 400);
    }

    // Verificar se o usuário é membro do grupo
    $stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $user_id]);
    if (!$stmt->fetch()) {
        sendJson(['error' => 'Você não é membro deste grupo.'], 403);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO group_books (group_id, book_id, added_by, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        $stmt->execute([$groupId, $bookId, $user_id, $status]);
        sendJson(['success' => true, 'message' => 'Livro adicionado ao grupo!']);
    } catch (PDOException $e) {
        sendJson(['error' => 'Erro ao adicionar livro: ' . $e->getMessage()], 500);
    }
}

// ============================================================
// POST – Remover livro do grupo
// ============================================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'remove_book') {
    $groupId = (int)($input['group_id'] ?? 0);
    $bookId = (int)($input['book_id'] ?? 0);

    if (!$groupId || !$bookId) {
        sendJson(['error' => 'ID do grupo e do livro são obrigatórios.'], 400);
    }

    // Verificar se o usuário é admin ou moderador
    $stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $user_id]);
    $member = $stmt->fetch();
    if (!$member || !in_array($member['role'], ['admin', 'moderator'])) {
        sendJson(['error' => 'Apenas administradores e moderadores podem remover livros.'], 403);
    }

    $stmt = $pdo->prepare("DELETE FROM group_books WHERE group_id = ? AND book_id = ?");
    $stmt->execute([$groupId, $bookId]);
    sendJson(['success' => true, 'message' => 'Livro removido do grupo.']);
}

// ============================================================
// POST – Adicionar discussão
// ============================================================
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'discuss') {
    $groupId = (int)($input['group_id'] ?? 0);
    $message = trim($input['message'] ?? '');
    $bookId = isset($input['book_id']) ? (int)$input['book_id'] : null;

    if (!$groupId || empty($message)) {
        sendJson(['error' => 'ID do grupo e mensagem são obrigatórios.'], 400);
    }

    // Verificar se o usuário é membro
    $stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $user_id]);
    if (!$stmt->fetch()) {
        sendJson(['error' => 'Você não é membro deste grupo.'], 403);
    }

    $stmt = $pdo->prepare("
        INSERT INTO group_discussions (group_id, user_id, book_id, message)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$groupId, $user_id, $bookId, $message]);
    sendJson(['success' => true, 'message' => 'Mensagem enviada!']);
}

// ============================================================
// Se nenhuma ação for reconhecida
// ============================================================
sendJson(['error' => 'Ação não suportada.'], 400);
?>
