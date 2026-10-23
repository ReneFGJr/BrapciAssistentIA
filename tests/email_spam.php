<?php
require __DIR__ . '/email_inbox.php';
$spam = new App\Models\EmailSpamSenderModel($db);
$accountA = $emails->saveFor(null, array_replace($data, ['email_user' => 'spam-a@example.test']), $actor);
$accountB = $emails->saveFor(null, array_replace($data, ['email_user' => 'spam-b@example.test']), $actor);
$foreign = $emails->saveFor(null, array_replace($data, ['email_user' => 'spam-other@example.test']), $other);
$seed = static function (int $account, array $user, string $key, string $from) use ($rows, $message): int {
    $rows->import($account, array_replace($message, ['remote_key' => hash('sha256', $key), 'message_id' => $key, 'email_from' => $from]), $user);
    return (int) $rows->getInsertID();
};
$a = $seed($accountA, $actor, 'spam-a', 'Promo <Sender@Example.test>');
$b = $seed($accountB, $actor, 'spam-b', 'sender@example.test');
$f = $seed($foreign, $other, 'spam-other', 'sender@example.test');
try { $spam->blockMessage($accountA, $a, $other); throw new LogicException('Foreign block accepted'); }
catch (CodeIgniter\Exceptions\PageNotFoundException $e) {}
$spam->blockMessage($accountA, $a, $actor);
$spam->blockMessage($accountA, $a, $actor);
verifyService(count($spam->forUser($actor)) === 1, 'Normalized sender and idempotent block');
verifyService($spam->forUser($other) === [], 'Spam list private');
verifyService((int) $rows->findOwned($accountA, $a, $actor)['email_status'] === 4 && (int) $rows->findOwned($accountB, $b, $actor)['email_status'] === 4, 'Existing messages across accounts classified');
verifyService((int) $rows->findOwned($foreign, $f, $other)['email_status'] === 0, 'Other user messages preserved');
$future = $seed($accountB, $actor, 'spam-future', 'Different name <SENDER@example.test>');
verifyService((int) $rows->findOwned($accountB, $future, $actor)['email_status'] === 4, 'Future messages classified');
verifyService($rows->markRead($accountB, $future, $actor) === 4, 'Read timer preserves spam');
try { $rows->changeStatus($accountB, $future, '2', $actor); throw new LogicException('Spam priority override accepted'); }
catch (InvalidArgumentException $e) {}
$safe = $seed($accountA, $actor, 'safe', 'sender@example.test.evil');
verifyService((int) $rows->findOwned($accountA, $safe, $actor)['email_status'] === 0, 'Exact mailbox match');
$invalid = $seed($accountA, $actor, 'invalid', 'invalid sender');
try { $spam->blockMessage($accountA, $invalid, $actor); throw new LogicException('Invalid sender accepted'); }
catch (InvalidArgumentException $e) {}
session()->set('email_inbox_filter', 'all');
$html = view('email/account', ['account' => $emails->findOwned($accountA, $actor), 'accounts' => $emails->forUser($actor), 'messages' => $rows->unified($actor), 'spamSenders' => $spam->forUser($actor)]);
verifyService(str_contains($html, 'data-filter="4"') && str_contains($html, '/spam') && str_contains($html, 'Remetentes de SPAM'), 'Spam tab, button and sender list');
verifyService((bool) preg_match('/<button type="button" hidden class="mail-row[^>]+data-message="' . $a . '"/', $html), 'Spam hidden in initial All rendering');
echo "Email spam: sender normalization, private lists, ownership, existing and future classification, read protection and rendering passed.\n";
