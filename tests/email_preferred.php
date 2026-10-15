<?php
require __DIR__ . '/email_unified.php';
$emails->setPreferred($secondId, $actor);
$visible = $rows->unified($actor);
verifyService(count($visible) === 1 && (int) $visible[0]['email_user_id'] === $secondId, 'Cross-account copies favor preferred account');
$emails->setPreferred($firstId, $actor);
$visible = $rows->unified($actor);
verifyService(count($visible) === 1 && (int) $visible[0]['email_user_id'] === $firstId, 'Changing preference immediately changes visible copy');
verifyService(count(array_filter($emails->forUser($actor), static fn($a) => (int) $a['is_preferred'] === 1)) === 1, 'Exactly one preferred account');
try { $emails->setPreferred($foreignId, $actor); throw new LogicException('Foreign preference accepted'); }
catch (CodeIgniter\Exceptions\PageNotFoundException $e) {}
verifyService((int) $emails->findOwned($firstId, $actor)['is_preferred'] === 1, 'Failed foreign preference leaves preference intact');
verifyService(count($rows->forUser($actor)->findAll()) === 2, 'Underlying mailbox copies preserved');
$fallback = array_replace($message, ['message_id' => null, 'email_title' => 'Sem identificador', 'remote_key' => hash('sha256', 'fallback-first')]);
$rows->import($firstId, $fallback, $actor);
$rows->import($secondId, array_replace($fallback, ['remote_key' => hash('sha256', 'fallback-second')]), $actor);
verifyService(count($rows->unified($actor)) === 2, 'Fallback fingerprint collapses identical contents across accounts');
$rows->import($firstId, array_replace($fallback, ['remote_key' => hash('sha256', 'distinct'), 'email_context' => 'Outro conteúdo']), $actor);
verifyService(count($rows->unified($actor)) === 3, 'Different contents without Message-ID stay distinct');
$html = view('email/index', ['accounts' => $emails->forUser($actor)]);
verifyService(str_contains($html, 'Preferencial') && str_contains($html, 'email/prefer/' . $secondId), 'Preferred badge and selection action');
$chosen = array_values(array_filter($rows->unified($actor), static fn($row) => $row['message_id'] === $message['message_id']))[0];
$rows->markServerDeleted($firstId, (int) $chosen['id'], $actor);
verifyService(count($rows->unified($actor)) === 2, 'Deleted preferred message stays hidden without resurrecting duplicate');
verifyService($rows->findOwned($firstId, (int) $chosen['id'], $actor)['email_context'] === $chosen['email_context'], 'Hidden message remains stored');
echo "Email preference: deduplication, switching, fallback and ownership passed.\n";
