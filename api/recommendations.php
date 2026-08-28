<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    sendJson(['error' => 'Não autorizado.'], 401);
}

$user_id = $_SESSION['user_id'];
$apiKey = 'AIzaSyDDY_17Mmo9vaoyzLrA-6pqeY9wKxnRytk';

// ============================================================
// 1. BUSCAR LIVROS DO USUÁRIO
// ============================================================
$stmt = $pdo->prepare("
    SELECT b.*, ub.status, ub.rating, ub.review, ub.id as user_book_id
    FROM user_books ub
    JOIN books b ON ub.book_id = b.id
    WHERE ub.user_id = ?
");
$stmt->execute([$user_id]);
$userBooks = $stmt->fetchAll();

if (empty($userBooks)) {
    sendJson(['error' => 'Adicione alguns livros à sua estante para receber recomendações.'], 400);
}

// ============================================================
// 2. EXTRAIR PALAVRAS-CHAVE
// ============================================================
$keywords = [];
$tags = [];

foreach ($userBooks as $book) {
    if (!empty($book['tags'])) {
        $bookTags = array_map('trim', explode(',', $book['tags']));
        foreach ($bookTags as $tag) {
            if (strlen($tag) > 2) $tags[] = strtolower($tag);
        }
    }
    $titleWords = explode(' ', $book['title']);
    foreach ($titleWords as $word) {
        $word = preg_replace('/[^a-zA-ZÀ-ú0-9]/', '', $word);
        if (strlen($word) > 3) $keywords[] = strtolower($word);
    }
    if (!empty($book['authors'])) {
        $authors = explode(',', $book['authors']);
        foreach ($authors as $author) {
            $author = trim($author);
            if (strlen($author) > 3) $keywords[] = strtolower($author);
        }
    }
}

$tagCounts = array_count_values($tags);
$keywordCounts = array_count_values($keywords);
arsort($tagCounts);
arsort($keywordCounts);

$topTags = array_slice(array_keys($tagCounts), 0, 3);
$topKeywords = array_slice(array_keys($keywordCounts), 0, 5);
$searchTerms = array_merge($topTags, $topKeywords);
$searchQuery = implode(' ', array_slice($searchTerms, 0, 8));

if (empty($searchQuery)) {
    sendJson(['error' => 'Não foi possível gerar recomendações com base no seu histórico.'], 400);
}

// ============================================================
// 3. VERIFICAR CACHE (recomendações salvas nas últimas 6 horas)
// ============================================================
$cacheStmt = $pdo->prepare("
    SELECT recommendations FROM recommendations_cache 
    WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)
");
$cacheStmt->execute([$user_id]);
$cached = $cacheStmt->fetch();

if ($cached) {
    sendJson([
        'success' => true,
        'recommendations' => json_decode($cached['recommendations'], true),
        'based_on' => $searchQuery,
        'tags' => $topTags,
        'keywords' => $topKeywords,
        'cached' => true
    ]);
}

// ============================================================
// 4. CONSULTAR API DO GOOGLE BOOKS
// ============================================================
$encodedQuery = urlencode($searchQuery);
$url = "https://www.googleapis.com/books/v1/volumes?q={$encodedQuery}&maxResults=20&langRestrict=pt&key={$apiKey}&projection=full";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// ============================================================
// 5. TRATAMENTO DE ERRO COM FALLBACK
// ============================================================
$items = [];

if ($curlError || $httpCode !== 200) {
    // LOG do erro
    file_put_contents(__DIR__ . '/recommendations_error.log', 
        date('Y-m-d H:i:s') . " - HTTP $httpCode: " . ($curlError ?: 'Erro na API') . "\n", 
        FILE_APPEND
    );
    
    // ============================================================
    // 5a. FALLBACK: gerar recomendações baseadas em tags (mock)
    // ============================================================
    $fallbackBooks = [
        ['title' => 'O Senhor dos Anéis', 'authors' => 'J.R.R. Tolkien', 'tags' => 'Fantasia, Aventura'],
        ['title' => '1984', 'authors' => 'George Orwell', 'tags' => 'Distopia, Ficção Científica'],
        ['title' => 'Dom Casmurro', 'authors' => 'Machado de Assis', 'tags' => 'Literatura Brasileira, Romance'],
        ['title' => 'O Pequeno Príncipe', 'authors' => 'Antoine de Saint-Exupéry', 'tags' => 'Infantil, Filosofia'],
        ['title' => 'A Revolução dos Bichos', 'authors' => 'George Orwell', 'tags' => 'Sátira, Política'],
    ];
    
    // Filtra por tags que o usuário tem
    $matched = [];
    foreach ($fallbackBooks as $fb) {
        $fbTags = array_map('trim', explode(',', $fb['tags']));
        foreach ($topTags as $tag) {
            if (in_array($tag, array_map('strtolower', $fbTags))) {
                $matched[] = $fb;
                break;
            }
        }
    }
    
    // Se não encontrou nenhum match, usa os primeiros 3
    if (empty($matched)) {
        $matched = array_slice($fallbackBooks, 0, 3);
    }
    
    // Converte para o formato esperado
    foreach ($matched as $fb) {
        $items[] = [
            'google_book_id' => 'fallback_' . uniqid(),
            'title'          => $fb['title'],
            'authors'        => $fb['authors'],
            'description'    => 'Recomendação baseada em suas tags.',
            'thumbnail'      => 'https://via.placeholder.com/128x200?text=Recomendado',
            'published_date' => '',
            'publisher'      => '',
            'pageCount'      => 0,
            'buyLink'        => null,
            'price'          => null,
            'currency'       => 'BRL',
        ];
    }
    
    // Salva no cache (mesmo sendo fallback, para não chamar a API novamente)
    $saveStmt = $pdo->prepare("
        INSERT INTO recommendations_cache (user_id, recommendations, created_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE recommendations = ?, created_at = NOW()
    ");
    $jsonItems = json_encode($items);
    $saveStmt->execute([$user_id, $jsonItems, $jsonItems]);
    
    sendJson([
        'success' => true,
        'recommendations' => $items,
        'based_on' => $searchQuery,
        'tags' => $topTags,
        'keywords' => $topKeywords,
        'fallback' => true,
        'message' => 'Recomendações geradas localmente (API indisponível)'
    ]);
}

// ============================================================
// 6. PROCESSAR RESULTADOS DA API
// ============================================================
$data = json_decode($response, true);
$existingIds = array_column($userBooks, 'google_book_id');

foreach ($data['items'] ?? [] as $item) {
    $googleId = $item['id'] ?? '';
    if (in_array($googleId, $existingIds)) continue;
    $volume = $item['volumeInfo'] ?? [];
    $saleInfo = $item['saleInfo'] ?? null;
    $items[] = [
        'google_book_id' => $googleId,
        'title'          => $volume['title'] ?? 'Sem título',
        'authors'        => isset($volume['authors']) ? implode(', ', $volume['authors']) : 'Autor desconhecido',
        'description'    => $volume['description'] ?? 'Sem descrição.',
        'thumbnail'      => $volume['imageLinks']['thumbnail'] ?? 'https://via.placeholder.com/128x200?text=Sem+Capa',
        'published_date' => $volume['publishedDate'] ?? 'Data não informada',
        'publisher'      => $volume['publisher'] ?? '',
        'pageCount'      => $volume['pageCount'] ?? 0,
        'buyLink'        => $saleInfo['buyLink'] ?? null,
        'price'          => isset($saleInfo['retailPrice']['amount']) ? number_format($saleInfo['retailPrice']['amount'], 2, ',', '.') : null,
        'currency'       => $saleInfo['retailPrice']['currencyCode'] ?? 'BRL',
    ];
}

// ============================================================
// 7. SALVAR NO CACHE E RETORNAR
// ============================================================
$recommendations = array_slice($items, 0, 10);

$saveStmt = $pdo->prepare("
    INSERT INTO recommendations_cache (user_id, recommendations, created_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE recommendations = ?, created_at = NOW()
");
$jsonItems = json_encode($recommendations);
$saveStmt->execute([$user_id, $jsonItems, $jsonItems]);

sendJson([
    'success' => true,
    'recommendations' => $recommendations,
    'based_on' => $searchQuery,
    'tags' => $topTags,
    'keywords' => $topKeywords,
    'cached' => false
]);
?>
