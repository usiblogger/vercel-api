<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/supabase.php';

function posSupabaseRequest(string $method, string $table, array $params = [], array $data = []): ?array {
    if (!SUPABASE_URL || !SUPABASE_KEY) return null;
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($table, '/');
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation',
    ];
    $ch = curl_init();
    $query = ($method === 'GET' && $params) ? '?' . http_build_query($params) : '';
    curl_setopt_array($ch, [
        CURLOPT_URL => $url . $query,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);
    if (in_array($method, ['POST', 'PATCH', 'DELETE']) && $data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http >= 200 && $http < 300) {
        if ($method === 'DELETE' && empty($res)) return ['ok' => true];
        $decoded = json_decode($res, true);
        return is_array($decoded) ? $decoded : null;
    }
    return null;
}

function listProducts(): array {
    return posSupabaseRequest('GET', 'products', ['select' => '*', 'order' => 'created_at.desc']) ?? [];
}

function createProduct(array $data): ?array {
    $res = posSupabaseRequest('POST', 'products', [], [$data]);
    return $res[0] ?? null;
}

function updateProduct(int $id, array $data): ?array {
    $res = posSupabaseRequest('PATCH', "products?id=eq.$id", [], $data);
    return $res[0] ?? null;
}

function deleteProduct(int $id): bool {
    return posSupabaseRequest('DELETE', "products?id=eq.$id") !== null;
}

function listOrders(): array {
    return posSupabaseRequest('GET', 'orders', ['select' => '*', 'order' => 'created_at.desc']) ?? [];
}

function createOrder(array $data): ?array {
    $res = posSupabaseRequest('POST', 'orders', [], [$data]);
    return $res[0] ?? null;
}

function deleteOrder(int $id): bool {
    return posSupabaseRequest('DELETE', "orders?id=eq.$id") !== null;
}

function dashboardStats(): array {
    $orders = listOrders();
    $totalSales = 0;
    $orderCount = count($orders);
    foreach ($orders as $o) $totalSales += (float)($o['total'] ?? 0);
    return [
        'total_sales' => round($totalSales, 2),
        'order_count' => $orderCount,
        'orders' => $orders,
    ];
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    if (preg_match('#^/api/pos/products(?:/(\d+))?#', $path, $m)) {
        $id = isset($m[1]) ? (int)$m[1] : null;
        switch ($method) {
            case 'GET':
                echo json_encode(['data' => listProducts()]);
                break;
            case 'POST':
                $body = json_decode(file_get_contents('php://input'), true);
                if (empty($body['name'])) { http_response_code(400); echo json_encode(['error' => 'Name required']); exit; }
                $p = createProduct([
                    'name' => $body['name'],
                    'price' => (float)($body['price'] ?? 0),
                    'category' => $body['category'] ?? '',
                    'image_url' => $body['image_url'] ?? '',
                ]);
                http_response_code(201);
                echo json_encode(['data' => $p]);
                break;
            case 'PUT':
                if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID required']); exit; }
                $body = json_decode(file_get_contents('php://input'), true);
                $data = [];
                if (isset($body['name'])) $data['name'] = $body['name'];
                if (isset($body['price'])) $data['price'] = (float)$body['price'];
                if (isset($body['category'])) $data['category'] = $body['category'];
                if (isset($body['image_url'])) $data['image_url'] = $body['image_url'];
                $p = updateProduct($id, $data);
                if (!$p) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
                echo json_encode(['data' => $p]);
                break;
            case 'DELETE':
                if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID required']); exit; }
                if (!deleteProduct($id)) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
                echo json_encode(['ok' => true]);
                break;
            default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
        }
    } elseif (preg_match('#^/api/pos/orders(?:/(\d+))?#', $path, $m)) {
        $id = isset($m[1]) ? (int)$m[1] : null;
        switch ($method) {
            case 'GET':
                echo json_encode(['data' => listOrders()]);
                break;
            case 'POST':
                $body = json_decode(file_get_contents('php://input'), true);
                if (empty($body['items'])) { http_response_code(400); echo json_encode(['error' => 'Items required']); exit; }
                $items = $body['items'];
                $total = 0;
                foreach ($items as $it) $total += (float)($it['price'] ?? 0) * (int)($it['quantity'] ?? 1);
                $o = createOrder([
                    'total' => round($total, 2),
                    'payment_method' => $body['payment_method'] ?? 'cash',
                    'items' => json_encode($items),
                ]);
                http_response_code(201);
                echo json_encode(['data' => $o]);
                break;
            case 'DELETE':
                if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID required']); exit; }
                if (!deleteOrder($id)) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
                echo json_encode(['ok' => true]);
                break;
            default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
        }
    } elseif ($path === '/api/pos/dashboard') {
        if ($method !== 'GET') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }
        echo json_encode(['data' => dashboardStats()]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
