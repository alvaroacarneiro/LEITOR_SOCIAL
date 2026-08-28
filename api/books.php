<?php
require_once __DIR__ . '/../config/database.php';

// ============================================================
// VALIDAÇÃO DA REQUISIÇÃO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || empty($_GET['q'])) {
    sendJson(['error' => 'Parâmetro de busca (q) é obrigatório.'], 400);
}

$query = urlencode(trim($_GET['q']));
$apiKey = 'AIzaSyDDY_17Mmo9vaoyzLrA-6pqeY9wKxnRytk'; // 🔑 Substitua pela sua chave

// ============================================================
// CONSULTAR API DO GOOGLE BOOKS
// ============================================================
$url = "https://www.googleapis.com/books/v1/volumes?q={$query}&maxResults=20&langRestrict=pt&key={$apiKey}&projection=full";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false, // Apenas em desenvolvimento
    CURLOPT_FOLLOWLOCATION => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// ============================================================
// TRATAMENTO DE ERROS
// ============================================================
if ($curlError) {
    sendJson(['error' => 'Erro de conexão: ' . $curlError], 500);
}

if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    $msg = $errorData['error']['message'] ?? 'Erro desconhecido na API do Google.';
    
    // Mensagens amigáveis para erros comuns
    $userMessage = match ($httpCode) {
        403 => 'Chave da API inválida ou com permissões insuficientes. Verifique sua chave e ative a API Books no Google Cloud Console.',
        404 => 'Nenhum livro encontrado para esta busca.',
        429 => 'Limite de requisições excedido. Tente novamente mais tarde.',
        default => "Erro {$httpCode}: {$msg}"
    };
    
    sendJson(['error' => $userMessage], $httpCode);
}

// ============================================================
// PROCESSAR RESULTADOS
// ============================================================
$data = json_decode($response, true);
$items = [];

foreach ($data['items'] ?? [] as $item) {
    $volume = $item['volumeInfo'] ?? [];
    $saleInfo = $item['saleInfo'] ?? null;

    $buyLink = $saleInfo['buyLink'] ?? null;
    $price = $saleInfo['retailPrice']['amount'] ?? null;
    $currency = $saleInfo['retailPrice']['currencyCode'] ?? 'BRL';

    $items[] = [
        'google_book_id' => $item['id'] ?? 'unknown',
        'title'          => $volume['title'] ?? 'Sem título',
        'authors'        => isset($volume['authors']) ? implode(', ', $volume['authors']) : 'Autor desconhecido',
        'description'    => $volume['description'] ?? 'Sem descrição disponível.',
        'thumbnail'      => $volume['imageLinks']['thumbnail'] ?? 'https://via.placeholder.com/128x200?text=Sem+Capa',
        'published_date' => $volume['publishedDate'] ?? 'Data não informada',
        'publisher'      => $volume['publisher'] ?? '',
        'pageCount'      => $volume['pageCount'] ?? 0,
        'buyLink'        => $buyLink,
        'price'          => $price !== null ? number_format($price, 2, ',', '.') : null,
        'currency'       => $currency,
    ];
}

sendJson($items);
?>
