<?php
require __DIR__ . '/email_inbox.php';
$structure = json_decode(json_encode(['type' => 1, 'parts' => [
    ['type' => 0, 'subtype' => 'PLAIN', 'bytes' => 12],
    ['type' => 0, 'disposition' => 'ATTACHMENT', 'encoding' => 3, 'bytes' => 1024, 'dparameters' => [['attribute' => 'filename', 'value' => 'dados.csv']]],
    ['type' => 1, 'parts' => [
        ['type' => 3, 'disposition' => 'attachment', 'encoding' => 3, 'bytes' => 2048, 'dparameters' => [['attribute' => 'filename*', 'value' => "UTF-8''relat%C3%B3rio.pdf"]]],
        ['type' => 5, 'disposition' => 'inline', 'bytes' => 300, 'parameters' => [['attribute' => 'name', 'value' => '../../logo.png']]],
    ]],
]]));
$parts = App\Libraries\EmailAttachments::parts($structure);
verifyService(count($parts) === 3, 'Text attachments and inline images listed');
verifyService($parts[0]['section'] === '2' && $parts[0]['name'] === 'dados.csv', 'MIME section and filename');
verifyService($parts[1]['section'] === '3.1' && $parts[1]['name'] === 'relatório.pdf', 'Nested MIME and RFC2231 filename');
verifyService(!str_contains($parts[2]['name'], '/') && !str_contains($parts[2]['name'], '\\'), 'Path separators removed from filename');
verifyService(App\Libraries\EmailAttachments::parts((object) ['type' => 0]) === [], 'Plain body is not attachment');
$method = new ReflectionMethod(App\Libraries\EmailInbox::class, 'hasAttachments');
verifyService($method->invoke(new App\Libraries\EmailInbox(), $structure), 'Attachment flag includes text files');
// Match production IMAP responses where MIME collections are stdClass objects.
$objectStructure = unserialize(serialize($structure));
$objectStructure->parts[1]->parameters = new stdClass();
$objectStructure->parts[1]->dparameters = (object) $objectStructure->parts[1]->dparameters;
$objectStructure->parts[2]->parts[0]->dparameters = (object) $objectStructure->parts[2]->parts[0]->dparameters;
$objectStructure->parts[2]->parts[1]->parameters = (object) $objectStructure->parts[2]->parts[1]->parameters;
verifyService(App\Libraries\EmailAttachments::parts($objectStructure) === $parts, 'stdClass parameter collections preserve attachment names and sections');
verifyService($method->invoke(new App\Libraries\EmailInbox(), $objectStructure), 'Production object collections do not interrupt import');
$singleParameter = (object) ['type' => 3, 'disposition' => 'attachment', 'parameters' => (object) ['attribute' => 'name', 'value' => 'arquivo.pdf']];
verifyService(App\Libraries\EmailAttachments::parts($singleParameter)[0]['name'] === 'arquivo.pdf', 'Single object parameter supported');
echo "Email attachments: MIME hierarchy, text files, Unicode filenames and safe names passed.\n";
verifyService(App\Libraries\EmailAttachmentPreview::mime('teste.txt', 'Texto de exemplo') === 'text/plain; charset=UTF-8', 'Text preview');
verifyService(App\Libraries\EmailAttachmentPreview::mime('teste.html', '<script>alert(1)</script>') === 'text/plain; charset=UTF-8', 'HTML rendered only as text');
verifyService(App\Libraries\EmailAttachmentPreview::mime('programa.exe', "MZ\0binary") === null, 'Executable preview blocked');
verifyService(App\Libraries\EmailAttachmentPreview::mime('arquivo.zip', "PK\0binary") === null, 'Unsupported file has download fallback');
echo "Attachment preview: text, active content isolation and unsupported formats passed.\n";
