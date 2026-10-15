<?php
require __DIR__ . '/email_inbox.php';

class FakeEmailDeletion extends App\Libraries\EmailMessageDeletion
{
    public array $calls = [];
    public bool $uidplus = true;
    public bool $fail = false;
    public string $uids = '8 15';
    public ?string $searchResponse = null;
    protected function command(array $account, string $command, ?string $validity = null): string
    {
        $this->calls[] = [$command, $validity];
        if ($command === 'CAPABILITY') return '* CAPABILITY IMAP4rev1' . ($this->uidplus ? ' UIDPLUS' : '');
        if ($command === 'STATUS INBOX (UIDVALIDITY UIDNEXT)') return '* STATUS INBOX (UIDVALIDITY 123 UIDNEXT 25000)';
        if ($command === 'UID SEARCH ALL') throw new LogicException('Unbounded search is forbidden');
        if (str_starts_with($command, 'UID SEARCH UID ')) {
            if ($this->searchResponse !== null) return $this->searchResponse;
            $uid = substr($command, strlen('UID SEARCH UID '));
            return '* SEARCH ' . (in_array($uid, explode(' ', $this->uids), true) ? $uid : '') . "\r\n";
        }
        if ($this->fail && str_starts_with($command, 'UID EXPUNGE')) throw new RuntimeException('Server failed');
        return 'OK';
    }
}
$account = ['protocol' => 'imap', 'mail_user' => 'imap.example.test', 'email_user' => 'mail@example.test'];
$message['remote_key'] = hash('sha256', 'imap|imap.example.test|mail@example.test|123|15');
$service = new FakeEmailDeletion();
$service->delete($account, $message);
verifyService($service->calls[3] === ['UID STORE 15 +FLAGS.SILENT (\\Deleted)', '123'], 'Only selected UID marked deleted');
verifyService($service->calls[4] === ['UID EXPUNGE 15', '123'], 'Targeted expunge and UIDVALIDITY');
verifyService($service->calls[2] === ['UID SEARCH UID 15', '123'], 'Single UID searched even with large mailbox');
$missingService = new FakeEmailDeletion();
$missingService->uids = '8';
verifyService($missingService->delete($account, $message) === false && count($missingService->calls) === 3, 'Confirmed absence permits local cleanup without server mutations');
foreach (["* SEARCH\r\n", "* SEARCH \r\n"] as $emptyResponse) {
    $service = new FakeEmailDeletion();
    $service->searchResponse = $emptyResponse;
    verifyService($service->delete($account, $message) === false, 'Empty result without trailing space supported');
}
foreach (["OK\r\n", "* SEARCH 99\r\n", "* SEARCH 15 invalid\r\n"] as $invalidResponse) {
    $service = new FakeEmailDeletion(); $service->searchResponse = $invalidResponse;
    try { $service->delete($account, $message); throw new LogicException('Invalid search response accepted'); }
    catch (RuntimeException $e) {}
    verifyService(count($service->calls) === 3, 'Invalid search never mutates server');
}
foreach (['uidplus', 'pop3', 'failure'] as $case) {
    $service = new FakeEmailDeletion();
    if ($case === 'uidplus') $service->uidplus = false;
    if ($case === 'failure') $service->fail = true;
    try { $service->delete(array_replace($account, $case === 'pop3' ? ['protocol' => 'pop3'] : []), $message); throw new LogicException('Unsafe deletion accepted: ' . $case); }
    catch (RuntimeException $e) { if ($e instanceof LogicException) throw $e; }
    if ($case !== 'failure') verifyService(count($service->calls) < 4, 'No mutation on unsafe identity/capability');
}
$id = $emails->saveFor(null, $data, $actor);
$rows->import($id, $message, $actor);
$messageId = (int) $rows->forAccount($id, $actor)->first()['id'];
try { $rows->deleteFor($id, $messageId, $other); throw new LogicException('Foreign deletion allowed'); }
catch (CodeIgniter\Exceptions\PageNotFoundException $e) {}
verifyService($rows->findOwned($id, $messageId, $actor)['remote_key'] === $message['remote_key'], 'Foreign deletion preserves row');
$rows->deleteFor($id, $messageId, $actor);
verifyService($rows->forAccount($id, $actor)->findAll() === [], 'Local removal');
echo "Email deletion: targeted UID, capability guard, failures and ownership passed.\n";
