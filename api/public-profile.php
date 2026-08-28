<?php
require_once __DIR__ . '/../config/database.php';

// ============================================================
// VALIDAÇÃO: recebe user_id ou username (slug)
// ============================================================
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if (!$user_id && !$username) {
    sendJson(['error' => 'Parâmetro user_id ou username é obrigatório.'], 400);
}

// ============================================================
// BUSCAR DADOS DO USUÁRIO (apenas dados públicos)
// ============================================================
if ($user_id) {
    $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE name = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
}

if (!$user) {
    sendJson(['error' => 'Usuário não encontrado.'], 404);
}

// ============================================================
// BUSCAR LIVROS DO USUÁRIO (apenas os que têm resenha ou status concluído)
// ============================================================
$stmt = $pdo->prepare("
    SELECT b.*, ub.status, ub.rating, ub.review, ub.id as user_book_id,
           ub.started_at, ub.finished_at
    FROM user_books ub
    JOIN books b ON ub.book_id = b.id
    WHERE ub.user_id = ?
    AND (ub.status = 'finished' OR ub.review IS NOT NULL)
    ORDER BY ub.updated_at DESC
");
$stmt->execute([$user['id']]);
$books = $stmt->fetchAll();

// ============================================================
// MONTAR RESPOSTA
// ============================================================
$response = [
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'member_since' => date('d/m/Y', strtotime($user['created_at'])),
    ],
    'books' => array_map(function($book) {
        return [
            'google_book_id' => $book['google_book_id'],
            'title'          => $book['title'],
            'authors'        => $book['authors'],
            'thumbnail'      => $book['thumbnail'],
            'status'         => $book['status'],
            'rating'         => $book['rating'],
            'review'         => $book['review'],
            'started_at'     => $book['started_at'] ? date('d/m/Y', strtotime($book['started_at'])) : null,
            'finished_at'    => $book['finished_at'] ? date('d/m/Y', strtotime($book['finished_at'])) : null,
            'tags'           => $book['tags'],
        ];
    }, $books),
    'stats' => [
        'total_books' => count($books),
        'total_reviews' => count(array_filter($books, fn($b) => !empty($b['review']))),
        'avg_rating' => count($books) > 0 ? round(array_sum(array_column($books, 'rating')) / count($books), 1) : 0,
    ]
];

sendJson($response);
?>
