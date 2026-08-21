<?php
require_once __DIR__ . '/config/Database.php';

use Config\Database;

header('Content-Type: text/html; charset=UTF-8');

$ok = false;
$message = '';
$details = '';

try {
    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('A extensão PDO MySQL não está habilitada no PHP da hospedagem.');
    }

    $pdo = Database::getInstance()->getConnection();
    $pdo->query('SELECT 1');
    $ok = true;
    $message = 'Conexão com o banco realizada com sucesso!';

    $required = ['users', 'equipment', 'loans'];
    $missing = [];
    foreach ($required as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        if (!$stmt->fetchColumn()) {
            $missing[] = $table;
        }
    }
    if ($missing) {
        $details = 'A conexão funcionou, mas faltam tabelas. Importe database/IMPORTAR_NO_PHPMYADMIN.sql no phpMyAdmin.';
    } else {
        $details = 'As tabelas principais também foram encontradas. Você já pode abrir o sistema.';
    }
} catch (Throwable $e) {
    $message = 'Não foi possível conectar ao banco.';
    $details = $e->getMessage();
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Teste do banco - LabManager</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;padding:30px;color:#172033}.box{max-width:680px;margin:40px auto;background:#fff;border-radius:18px;padding:28px;box-shadow:0 12px 35px rgba(17,32,51,.09)}.status{padding:16px;border-radius:12px;margin:18px 0;background:<?= $ok ? '#eaf8ef' : '#fff0f0' ?>;border:1px solid <?= $ok ? '#a9dfba' : '#efb0b0' ?>}.ok{color:#16713a}.bad{color:#a52626}code{background:#eef1f5;padding:2px 5px;border-radius:5px}.btn{display:inline-block;text-decoration:none;background:#145da0;color:#fff;padding:11px 16px;border-radius:9px;margin-top:8px}.steps{line-height:1.7}</style>
</head>
<body><div class="box">
<h1>Teste do banco de dados</h1>
<div class="status"><strong class="<?= $ok ? 'ok' : 'bad' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></strong><p><?= htmlspecialchars($details, ENT_QUOTES, 'UTF-8') ?></p></div>
<?php if (!$ok): ?>
<div class="steps"><strong>Faça somente isto:</strong><ol><li>Abra <code>config/banco.php</code>.</li><li>Cole os dados do MySQL da Hostinger.</li><li>Importe <code>database/IMPORTAR_NO_PHPMYADMIN.sql</code> no phpMyAdmin.</li><li>Volte aqui e atualize a página.</li></ol></div>
<?php endif; ?>
<a class="btn" href="<?= htmlspecialchars(dirname($_SERVER['SCRIPT_NAME']) === '/' ? '/views/auth/login.php' : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/views/auth/login.php', ENT_QUOTES, 'UTF-8') ?>">Abrir o LabManager</a>
</div></body></html>
