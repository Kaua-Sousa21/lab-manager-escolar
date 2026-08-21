<?php

declare(strict_types=1);

function projectRoot(): string
{
    return realpath(__DIR__ . '/..') ?: dirname(__DIR__);
}

function basePath(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
    $projectRoot = realpath(projectRoot());

    if ($documentRoot && $projectRoot) {
        $doc = rtrim(str_replace('\\', '/', $documentRoot), '/');
        $project = rtrim(str_replace('\\', '/', $projectRoot), '/');
        if (strpos(strtolower($project), strtolower($doc)) === 0) {
            $relative = substr($project, strlen($doc));
            $base = '/' . trim($relative, '/');
            return $base === '/' ? '' : $base;
        }
    }

    $base = rtrim((string) (getenv('APP_BASE_PATH') ?: ''), '/');
    if ($base !== '' && $base[0] !== '/') {
        $base = '/' . $base;
    }
    return $base;
}

function appUrl(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return basePath() . ($path === '/' ? '/' : $path);
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    if (!preg_match('#^https?://#i', $url)) {
        $url = appUrl($url);
    }
    header('Location: ' . $url);
    exit;
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function isGet(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
}

function getPost(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

function getQuery(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

function sanitizeInput(string $data): string
{
    return trim($data);
}

function sanitizeArray(array $data): array
{
    $clean = [];
    foreach ($data as $key => $value) {
        $clean[$key] = is_string($value) ? trim($value) : $value;
    }
    return $clean;
}

function formatDate(?string $date, string $format = 'd/m/Y'): string
{
    if (!$date) return '-';
    try {
        return (new DateTime($date))->format($format);
    } catch (Throwable $e) {
        return '-';
    }
}

function formatDateTime(?string $date, string $format = 'd/m/Y H:i'): string
{
    return formatDate($date, $format);
}

function formatCurrency($value): string
{
    if ($value === null || $value === '') return '-';
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function roleLabel(string $role): string
{
    return [
        'admin' => 'Administrador',
        'coordinator' => 'Coordenação',
        'technician' => 'Técnico',
        'teacher' => 'Professor',
        'common' => 'Usuário limitado',
    ][$role] ?? ucfirst($role);
}

function statusLabel(string $status): string
{
    return [
        'available' => 'Disponível', 'reserved' => 'Reservado', 'borrowed' => 'Emprestado',
        'maintenance' => 'Em manutenção', 'inactive' => 'Inativo', 'returned' => 'Devolvido',
        'overdue' => 'Atrasado', 'cancelled' => 'Cancelado', 'pending' => 'Pendente',
        'in_progress' => 'Em andamento', 'completed' => 'Concluído', 'active' => 'Ativo',
        'admin' => 'Administrador', 'coordinator' => 'Coordenação', 'technician' => 'Técnico',
        'teacher' => 'Professor', 'common' => 'Usuário limitado',
        'preventive' => 'Preventiva', 'corrective' => 'Corretiva',
    ][$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function getStatusBadge(string $status): string
{
    $classes = [
        'available' => 'success', 'returned' => 'success', 'completed' => 'success', 'active' => 'success',
        'reserved' => 'info', 'in_progress' => 'info', 'technician' => 'info', 'coordinator' => 'primary',
        'borrowed' => 'warning', 'pending' => 'warning', 'teacher' => 'secondary', 'common' => 'secondary',
        'maintenance' => 'danger', 'overdue' => 'danger', 'corrective' => 'danger', 'admin' => 'danger',
        'inactive' => 'secondary', 'cancelled' => 'secondary', 'preventive' => 'primary',
    ];
    $class = $classes[$status] ?? 'secondary';
    $textClass = in_array($status, ['borrowed', 'pending'], true) ? ' text-dark' : '';
    return '<span class="badge text-bg-' . $class . $textClass . '">' . e(statusLabel($status)) . '</span>';
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(?string $token): bool
{
    return is_string($token) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

function requireCsrfForPost(bool $json = false): void
{
    if (!isPost()) return;
    if (!verifyCsrf($_POST['_csrf'] ?? null)) {
        if ($json) jsonResponse(['success' => false, 'message' => 'Sessão expirada. Atualize a página e tente novamente.'], 419);
        http_response_code(419);
        exit('Sessão expirada. Atualize a página e tente novamente.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][$type][] = $message;
}

function pullFlash(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

function can(string $permission): bool
{
    $role = $_SESSION['user_role'] ?? null;
    $rules = [
        'manage_users' => ['admin'],
        'manage_school_data' => ['admin', 'coordinator'],
        'manage_equipment' => ['admin', 'coordinator', 'technician'],
        'manage_loans' => ['admin', 'coordinator', 'technician'],
        'schedule_equipment' => ['admin', 'coordinator', 'technician', 'teacher'],
        'manage_maintenance' => ['admin', 'coordinator', 'technician'],
        'view_all_loans' => ['admin', 'coordinator', 'technician'],
    ];
    return $role && in_array($role, $rules[$permission] ?? [], true);
}

function activeNav(string $needle): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    return strpos($path, $needle) !== false ? ' active' : '';
}
