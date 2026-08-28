<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    sendJson(['error' => 'Não autorizado.'], 401);
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// ============================================================
// GET – Listar livros do usuário (com dados completos)
// ============================================================
if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT b.*, ub.status, ub.rating, ub.review, ub.id as user_book_id,
               ub.started_at, ub.finished_at
        FROM user_books ub
        JOIN books b ON ub.book_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.updated_at DESC
    ");
    $stmt->execute([$user_id]);
    sendJson($stmt->fetchAll());
}

// ============================================================
// POST – Adicionar livro à estante
// ============================================================
if ($method === 'POST') {
    $google_id = $input['google_book_id'] ?? null;
    $status = $input['status'] ?? 'want_to_read';

    if (!$google_id) {
        sendJson(['error' => 'ID do Google Books é obrigatório.'], 400);
    }

    // Busca ou cria o livro
    $stmt = $pdo->prepare("SELECT id FROM books WHERE google_book_id = ?");
    $stmt->execute([$google_id]);
    $book = $stmt->fetch();

    if (!$book) {
        $tags = $input['tags'] ?? null; // tags vindas da busca
        $stmt = $pdo->prepare("
            INSERT INTO books (google_book_id, title, authors, description, thumbnail, published_date, tags)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $google_id,
            $input['title'] ?? 'Sem título',
            $input['authors'] ?? 'Autor desconhecido',
            $input['description'] ?? '',
            $input['thumbnail'] ?? '',
            $input['published_date'] ?? null,
            $tags
        ]);
        $book_id = $pdo->lastInsertId();
    } else {
        $book_id = $book['id'];
    }

    // Insere/atualiza a relação
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_books (user_id, book_id, status)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$user_id, $book_id, $status]);
        sendJson(['success' => true, 'message' => 'Livro adicionado à estante!']);
    } catch (PDOException $e) {
        sendJson(['error' => 'Erro ao salvar: ' . $e->getMessage()], 500);
    }
}

// ============================================================
// PUT – Atualizar status, avaliação, resenha, datas e tags
// ============================================================
if ($method === 'PUT') {
    $user_book_id = $input['user_book_id'] ?? null;
    if (!$user_book_id) {
        sendJson(['error' => 'ID do livro na estante é obrigatório.'], 400);
    }

    $fields = [];
    $params = [];

    // Campos da tabela user_books
    if (isset($input['status'])) {
        $fields[] = 'status = ?';
        $params[] = $input['status'];
    }
    if (isset($input['rating'])) {
        $fields[] = 'rating = ?';
        $params[] = (int)$input['rating'];
    }
    if (isset($input['review'])) {
        $fields[] = 'review = ?';
        $params[] = trim($input['review']);
    }
    if (isset($input['started_at'])) {
        $fields[] = 'started_at = ?';
        $params[] = $input['started_at'] ?: null;
    }
    if (isset($input['finished_at'])) {
        $fields[] = 'finished_at = ?';
        $params[] = $input['finished_at'] ?: null;
    }

    // Atualiza tags na tabela books (precisa do book_id)
    if (isset($input['tags'])) {
        // Primeiro, obtém o book_id
        $stmt = $pdo->prepare("SELECT book_id FROM user_books WHERE id = ? AND user_id = ?");
        $stmt->execute([$user_book_id, $user_id]);
        $book = $stmt->fetch();
        if ($book) {
            $stmt = $pdo->prepare("UPDATE books SET tags = ? WHERE id = ?");
            $stmt->execute([trim($input['tags']), $book['book_id']]);
        }
    }

    if (empty($fields)) {
        sendJson(['error' => 'Nenhum campo para atualizar.'], 400);
    }

    $sql = "UPDATE user_books SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
    $params[] = $user_book_id;
    $params[] = $user_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    sendJson(['success' => true, 'message' => 'Atualizado com sucesso!']);
}

// ============================================================
// DELETE – Remover livro da estante
// ============================================================
if ($method === 'DELETE') {
    $user_book_id = $input['user_book_id'] ?? null;
    if (!$user_book_id) {
        sendJson(['error' => 'ID do livro na estante é obrigatório.'], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM user_books WHERE id = ? AND user_id = ?");
    $stmt->execute([$user_book_id, $user_id]);
    sendJson(['success' => true, 'message' => 'Livro removido da estante.']);
}

sendJson(['error' => 'Método não permitido.'], 405);
?>
