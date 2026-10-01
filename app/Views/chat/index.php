<section class="col-12 chat-page" aria-labelledby="chat-title">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <div class="chat-layout">
        <aside class="chat-conversations" aria-label="Conversas">
            <form method="post" action="<?= site_url('chat') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-info w-100" type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Novo chat</button>
            </form>
            <nav class="chat-conversation-list mt-3">
                <?php foreach ($conversations as $conversation): ?>
                    <div class="chat-conversation-item">
                        <a class="chat-conversation-link <?= (int) ($selected['id'] ?? 0) === (int) $conversation['id'] ? 'is-active' : '' ?>" href="<?= site_url('chat') . '?conversation=' . (int) $conversation['id'] ?>"><?= esc($conversation['title']) ?></a>
                        <form method="post" action="<?= site_url('chat/' . $conversation['id'] . '/delete') ?>" onsubmit="return confirm('Excluir esta conversa?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Excluir conversa" aria-label="<?= esc('Excluir conversa ' . $conversation['title'], 'attr') ?>"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if ($conversations === []): ?><p class="small text-secondary">Nenhuma conversa salva.</p><?php endif; ?>
            </nav>
        </aside>
        <div class="chat-main">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h1 id="chat-title" class="h4 mb-0"><?= esc($selected['title'] ?? 'new chat') ?></h1>
                <?php if ($selected !== null): ?>
                    <form class="chat-title-form d-flex gap-2" method="post" action="<?= site_url('chat/' . $selected['id'] . '/rename') ?>">
                        <?= csrf_field() ?>
                        <label class="visually-hidden" for="chat-conversation-title">Nome da conversa</label>
                        <input id="chat-conversation-title" class="form-control" name="title" maxlength="150" required value="<?= esc($selected['title'], 'attr') ?>">
                        <button class="btn btn-outline-info" type="submit" title="Salvar conversa" aria-label="Salvar conversa"><i class="bi bi-floppy" aria-hidden="true"></i></button>
                    </form>
                <?php endif; ?>
            </div>
            <div id="chat-history" class="chat-history" aria-live="polite">
                <?php foreach (($selected['messages'] ?? []) as $message): ?>
                    <article class="chat-message chat-message-<?= esc($message['role'], 'attr') ?>">
                        <strong><?= $message['role'] === 'user' ? 'Você' : 'Assistente' ?></strong>
                        <div><?= esc($message['content']) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div id="chat-feedback" class="chat-feedback" role="status" aria-live="polite"></div>
        </div>
    </div>
</section>

<?= view('chat/prompt', ['selected' => $selected]) ?>
