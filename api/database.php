<?php

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dir = dirname(SQLITE_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $pdo = new PDO("sqlite:" . SQLITE_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA journal_mode=WAL");
    $pdo->exec("CREATE TABLE IF NOT EXISTS todos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        completed INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    return $pdo;
}

function listTodos(string $filter = 'all'): array {
    if (DB_DRIVER === 'supabase') return listTodosSupabase($filter);
    $pdo = getDB();
    $sql = "SELECT * FROM todos";
    if ($filter === 'active') $sql .= " WHERE completed = 0";
    elseif ($filter === 'completed') $sql .= " WHERE completed = 1";
    $sql .= " ORDER BY created_at DESC";
    return $pdo->query($sql)->fetchAll();
}

function getTodo(int $id): ?array {
    if (DB_DRIVER === 'supabase') return getTodoSupabase($id);
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function createTodo(string $title): array {
    if (DB_DRIVER === 'supabase') return createTodoSupabase($title);
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO todos (title) VALUES (?)");
    $stmt->execute([$title]);
    return getTodo($pdo->lastInsertId());
}

function updateTodo(int $id, ?string $title = null, ?int $completed = null): ?array {
    if (DB_DRIVER === 'supabase') return updateTodoSupabase($id, $title, $completed);
    $pdo = getDB();
    $fields = []; $params = [];
    if ($title !== null) { $fields[] = "title = ?"; $params[] = $title; }
    if ($completed !== null) { $fields[] = "completed = ?"; $params[] = $completed; }
    if (empty($fields)) return getTodo($id);
    $fields[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $id;
    $pdo->prepare("UPDATE todos SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
    return getTodo($id);
}

function deleteTodo(int $id): bool {
    if (DB_DRIVER === 'supabase') return deleteTodoSupabase($id);
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}

function getAllTodosRaw(): array {
    $pdo = getDB();
    return $pdo->query("SELECT * FROM todos ORDER BY id")->fetchAll();
}

function upsertTodos(array $todos): void {
    if (empty($todos)) return;
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO todos (id, title, completed, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
        foreach ($todos as $t) $stmt->execute([$t['id'], $t['title'], $t['completed'], $t['created_at'], $t['updated_at']]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
