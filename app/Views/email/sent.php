<section class="col-12 p-3 text-light">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
        <h1 class="h4 mb-0"><i class="bi bi-send" aria-hidden="true"></i> E-mails enviados</h1>
        <a class="btn btn-outline-light" href="<?= site_url('email/account') ?>"><i class="bi bi-inbox" aria-hidden="true"></i> Caixa de entrada</a>
    </div>
    <p class="small">Mensagens enviadas por este sistema e confirmadas pelo servidor SMTP. Horários de Brasília.</p>
    <?php if ($messages === []): ?><p>Nenhum e-mail enviado registrado.</p><?php endif; ?>
    <?php foreach ($messages as $message): ?>
    <details class="border rounded p-3 mb-3">
        <summary>
            <strong><?= esc($message['subject']) ?></strong>
            <span class="small"> ? <?= esc((new \DateTimeImmutable($message['sent_at'], new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i')) ?> ? <?= esc($message['recipients']) ?></span>
        </summary>
        <div class="small mt-3">
            <div><strong>De:</strong> <?= esc($message['sender']) ?></div>
            <div><strong>Para:</strong> <?= esc($message['recipients']) ?></div>
            <?php if ($message['cc'] !== ''): ?><div><strong>Cc:</strong> <?= esc($message['cc']) ?></div><?php endif; ?>
            <div><strong>Tipo:</strong> <?= ['reply' => 'Resposta', 'reply_all' => 'Resposta a todos', 'forward' => 'Encaminhamento'][$message['action']] ?? 'Envio' ?></div>
            <div class="text-success">Envio confirmado pelo servidor.</div>
        </div>
        <div class="mt-3" style="white-space:pre-wrap;overflow-wrap:anywhere"><?= esc($message['body']) ?></div>
    </details>
    <?php endforeach; ?>
</section>
