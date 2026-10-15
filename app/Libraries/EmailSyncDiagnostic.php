<?php
namespace App\Libraries;

use Throwable;
use TypeError;

class EmailSyncDiagnostic
{
    public static function context(Throwable $error, int $accountId, string $stage, array $account = []): array
    {
        // Log programming type errors only; database/protocol errors may embed mail data.
        $reason = $error instanceof TypeError ? $error->getMessage() : 'See exception type and origin';
        foreach (['password', 'pass_user'] as $key) {
            if (is_string($account[$key] ?? null) && $account[$key] !== '') $reason = str_replace($account[$key], '[REDACTED]', $reason);
        }
        $frames = [];
        foreach (array_slice($error->getTrace(), 0, 4) as $frame) {
            $frames[] = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '')
                . '@' . basename($frame['file'] ?? '') . ':' . ($frame['line'] ?? 0);
        }
        // Avoid reserved logger context keys such as "file" and "line".
        return ['account_id' => $accountId, 'sync_stage' => $stage, 'exception_type' => get_class($error),
            'exception_code' => $error->getCode(), 'origin_file' => basename($error->getFile()),
            'origin_line' => $error->getLine(), 'type_detail' => mb_substr(preg_replace('/[\r\n]+/', ' ', $reason), 0, 1500),
            'call_path' => implode(' <- ', $frames)];
    }
}
