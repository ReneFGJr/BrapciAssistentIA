<?php
require __DIR__ . '/email_inbox.php';
foreach ([
    ['Certificate failure: self-signed certificate', 'certificado'],
    ['AUTHENTICATIONFAILED invalid credentials password=secret', 'autenticação'],
    ['Connection timed out', 'Tempo limite'],
    ['Host not found', 'DNS'],
    ['Connection refused', 'firewall'],
    ['Unknown failure secret', 'caixa de entrada'],
] as [$raw, $expected]) {
    $error = App\Exceptions\EmailInboxException::connectionFailure([$raw])->getMessage();
    verifyService(str_contains($error, $expected), 'Actionable production error: ' . $expected);
    verifyService(!str_contains($error, 'secret'), 'Raw credentials never disclosed');
}
echo "Production email errors: safe connection diagnostics passed.\n";
