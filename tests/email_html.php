<?php
require __DIR__ . '/email_inbox.php';

use App\Libraries\EmailHtml;

$html = '<html><head><meta http-equiv="refresh" content="0;url=https://evil.test"><base href="https://evil.test"><style>td{padding:8px}</style></head><body onload="alert(1)"><table><tr><td style="color:blue"><strong>Assinatura</strong><br>Universidade</td></tr></table><a href="https://example.test/profile">Perfil</a><a href="javascript:alert(1)">Inválido</a><img src="https://tracker.test/pixel" onerror="alert(1)"><script>alert(1)</script><iframe src="https://evil.test"></iframe><form action="https://evil.test"><input name="password"></form><svg onload="alert(1)"></svg></body></html>';
$rendered = EmailHtml::document($html);
verifyService(str_contains($rendered, '<strong>Assinatura</strong>') && str_contains($rendered, '<table>') && str_contains($rendered, 'color:blue'), 'HTML signature, tables and inline formatting preserved');
foreach (['<script', '<iframe', '<form', '<input', '<svg', '<base', 'http-equiv="refresh"', 'onload=', 'onerror=', 'javascript:', 'src="https://'] as $unsafe) {
    verifyService(!str_contains($rendered, $unsafe), 'Unsafe or remote HTML removed: ' . $unsafe);
}
verifyService(str_contains($rendered, 'href="https://example.test/profile"') && str_contains($rendered, 'noopener noreferrer'), 'Links remain usable without opener access');
verifyService(str_contains(html_entity_decode(EmailHtml::document('<p>Olá, reunião e Ciência</p>'), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'Olá, reunião e Ciência'), 'UTF-8 preserved');
verifyService(str_contains(EmailHtml::document(''), '<!doctype html>'), 'Empty HTML handled');
$legacyCharset = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"></head><body bgcolor="white" style="background:white;color:black"><p>Ação, reunião, Ciência e café</p></body></html>';
$themed = EmailHtml::document($legacyCharset);
verifyService(str_contains(html_entity_decode($themed, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'Ação, reunião, Ciência e café'), 'Stale HTML charset does not corrupt decoded UTF-8');
verifyService(str_contains($themed, 'background:#172131!important;color:#ecf1fa!important') && str_contains($themed, 'background-color:transparent!important'), 'Reader applies theme even to white email backgrounds');
$accountId = $emails->saveFor(null, $data, $actor);
$rows->import($accountId, array_replace($message, ['email_html' => $html, 'email_html_loaded' => 1]), $actor);
$stored = $rows->forAccount($accountId, $actor)->first();
verifyService($stored['email_html'] === $html && (int) $stored['email_html_loaded'] === 1, 'HTML retained separately from plain-text body');
$page = view('email/account', ['account' => $emails->findOwned($accountId, $actor), 'messages' => [$stored]]);
verifyService(!str_contains($page, 'tracker.test') && !str_contains($page, 'src="https://evil.test'), 'Untrusted HTML never inserted into parent page');
verifyService(str_contains($page, 'data-html-url=') && str_contains($page, 'sandbox="allow-popups allow-popups-to-escape-sandbox"'), 'HTML loaded lazily into sandboxed reader');
$old = array_replace($stored, ['email_html' => null, 'email_html_loaded' => 0]);
$page = view('email/account', ['account' => $emails->findOwned($accountId, $actor), 'messages' => [$old]]);
verifyService(str_contains($page, 'Carregar formatação HTML original'), 'Legacy messages offer explicit recovery without automatic mailbox queries');
echo "Email HTML: formatting, UTF-8, active content removal, tracking protection, storage and isolated lazy rendering passed.\n";
