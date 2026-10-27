<?php
require __DIR__ . '/email_preferred.php';

// Existing fixtures exercise account preference and duplicate selection.
$expected = $rows->unified($actor);
$page = $rows->unifiedPage($actor);
verifyService(array_column($page['messages'], 'id') === array_column($expected, 'id'), 'Pagination preserves preference and deduplication');
$newAccount = $emails->saveFor(null, array_replace($data, ['email_user' => 'pagination@example.test']), $actor);
for ($index = 0; $index < 61; $index++) {
    $rows->import($newAccount, array_replace($message, ['remote_key' => hash('sha256', 'page-' . $index), 'message_id' => '<page-' . $index . '@example.test>',
        'email_html' => '<b>Original</b>', 'email_html_loaded' => 1, 'email_data' => '2026-11-01 10:00:00']), $actor);
}
$expected = $rows->unified($actor);
$first = $rows->unifiedPage($actor, 1);
$second = $rows->unifiedPage($actor, 2);
verifyService(count($first['messages']) === 50 && $first['pages'] === 2 && $first['total'] === count($expected), 'Large inbox bounded to 50 messages per page');
verifyService(array_column(array_merge($first['messages'], $second['messages']), 'id') === array_column($expected, 'id'), 'Every saved message remains reachable in stable order');
verifyService(!array_key_exists('email_html', $first['messages'][0]) && (int) $first['messages'][0]['has_html'] === 1, 'Original HTML not loaded until a message is opened');
verifyService($rows->unifiedPage($actor, 999)['page'] === 2 && $rows->unifiedPage($other)['total'] === count($rows->unified($other)), 'Page bounds and user isolation');
$html = view('email/account', ['account' => $emails->findOwned($newAccount, $actor), 'messages' => $first['messages'], 'inboxPage' => $first]);
verifyService(str_contains($html, 'Página 1 de 2') && str_contains(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'), '?page=2') && substr_count($html, 'class="mail-row ') === 50, 'Pagination navigation rendered with bounded message panels');
echo "Inbox pagination: preferred accounts, stable ordering, bounded HTML, navigation and user isolation passed.\n";
