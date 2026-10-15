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
try {
    (static function (array $value): void {})('type-test-secret');
} catch (TypeError $error) {
    $context = App\Libraries\EmailSyncDiagnostic::context($error, 1, 'consulta e importação', ['password' => 'type-test-secret']);
    verifyService($context['origin_file'] === basename(__FILE__) && $context['origin_line'] === $error->getLine(), 'Actual exception location preserved');
    verifyService(!isset($context['file'], $context['line']) && str_contains($context['type_detail'], 'array'), 'Logger reserved fields avoided and expected type included');
    verifyService(!str_contains(json_encode($context), 'type-test-secret'), 'No secret argument values in diagnostics');
}
echo "Sync diagnostics: true origin, type details and secret exclusion passed.\n";
