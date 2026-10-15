<?php
$statuses = [0 => 'Não lido', 1 => 'Lido', 2 => 'Importante', 3 => 'Urgente'];
$savedFilter = session('email_inbox_filter') ?? session('email_filter_' . $account['id']) ?? 'all';
$accounts = $accounts ?? [$account];
$accountsById = array_column($accounts, null, 'id');
$activeAccounts = array_values(array_filter($accounts, static fn($item) => (int) $item['status'] === 1));
if (!in_array($savedFilter, ['all', '0', '2', '3'], true)) $savedFilter = 'all';
$replyDraft = (array) session()->getFlashdata('email_reply_draft');
?>
<style>
.mail-shell{background:#172131;border:1px solid #354258;border-radius:14px;overflow:hidden;color:#ecf1fa}.mail-toolbar{padding:20px;display:flex;justify-content:space-between;align-items:center;gap:16px;border-bottom:1px solid #354258;flex-wrap:wrap}.mail-layout{display:grid;grid-template-columns:minmax(260px,350px) minmax(0,1fr);min-height:65vh}.mail-list{border-right:1px solid #354258;min-width:0}.mail-search{padding:16px;background:#121c2c}.mail-search input{background:#202e43;border-color:#43516a;color:white}.mail-search input::placeholder{color:#aab8cd}.mail-rows{max-height:65vh;overflow:auto}.mail-row{display:block;width:100%;background:transparent;color:#bec9db;border:0;border-bottom:1px solid #354258;text-align:left;padding:18px;cursor:pointer}.mail-row:hover,.mail-row.selected{background:#2a3b5b}.mail-row.selected{box-shadow:inset 3px 0 #9aaaff}.mail-row.unread{color:#fff}.mail-row.unread .mail-subject,.mail-row.unread .mail-from{font-weight:700}.mail-row-top{display:flex;gap:12px;font-size:12px;margin-bottom:8px}.mail-from{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.mail-subject,.mail-preview{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.mail-subject{font-size:14px;margin-bottom:7px}.mail-preview{font-size:12px;color:#aab8cd;white-space:normal;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.6;overflow-wrap:anywhere}.mail-reader{min-width:0;background:#1b273a}.mail-placeholder{display:flex;align-items:center;justify-content:center;flex-direction:column;min-height:60vh;color:#acbbd4;text-align:center;padding:25px}.mail-placeholder i{font-size:48px;margin-bottom:20px}.mail-message{padding:28px;max-height:85vh;overflow:auto}.mail-message h2{font-size:24px;overflow-wrap:anywhere;margin:26px 0}.mail-envelope{border-bottom:1px solid #3c4960;padding-bottom:24px;color:#b8c5d9;font-size:13px;line-height:1.9;overflow-wrap:anywhere}.mail-body{white-space:pre-wrap;overflow-wrap:break-word;word-break:normal;min-width:0;width:100%;max-width:none;font-family:var(--font-interface,"Segoe UI",Arial,sans-serif);font-size:18px;line-height:1.65;padding-top:28px}.mail-status-form{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.mail-status-form select{max-width:170px;background:#26364f;color:#fff;border-color:#4a5972}.mail-bottom{padding:14px 20px;border-top:1px solid #354258;font-size:11px;color:#acbbd4}.mail-shell [hidden]{display:none!important}.mail-page-nav{padding:12px}.mail-page-nav .pagination{margin:0;flex-wrap:wrap}.mail-back-button{display:none}.mail-row:focus-visible{outline:2px solid #b2bdff;outline-offset:-3px}.mail-tabs{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}.mail-tabs button{font-size:11px}.mail-attachment{padding:12px;background:#293b57;border-radius:8px;margin-top:15px;font-size:12px}
@media(max-width:750px){.mail-layout{display:block}.mail-list{border:0}.mail-reader{display:none}.mail-shell.reading .mail-list{display:none}.mail-shell.reading .mail-reader{display:block}.mail-back-button{display:inline-block}.mail-message{padding:20px;max-height:none}.mail-rows{max-height:none}}
.mail-actions{position:sticky;top:0;z-index:10;display:flex;align-items:center;gap:8px;flex-wrap:nowrap;overflow-x:auto;margin:-28px -28px 0;padding:16px 28px;background:#1b273a;border-bottom:1px solid #354258;box-shadow:0 6px 12px #10182733}.mail-actions form{margin:0;flex-shrink:0}.mail-actions .mail-status-form{flex-wrap:nowrap;flex-shrink:1;min-width:0}.mail-actions .mail-status-form select{width:150px;min-width:100px;max-width:150px}.mail-actions button{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;padding:0}.mail-actions .form-select{height:38px}.mail-reply-compose{padding:18px;border:1px solid #43516a;border-radius:10px}.mail-reply-compose textarea{background:#152236;color:#fff;border-color:#43516a}.mail-content-layout{display:grid;grid-template-columns:minmax(0,1fr);gap:24px}.mail-content-layout.has-files{grid-template-columns:minmax(0,1fr) 230px}.mail-files{align-self:start;margin-top:28px;padding:16px;background:#152235;border:1px solid #3c4960;border-radius:10px;min-width:0}.mail-files-list{font-size:12px;color:#b8c5d9;overflow-wrap:anywhere}.mail-file-link{display:grid;grid-template-columns:20px minmax(0,1fr);gap:6px;color:#c4d8ff;text-decoration:none;padding:12px 0;border-bottom:1px solid #354258}.mail-file-link:last-child{border-bottom:0}.mail-file-link small{grid-column:2;color:#aab8cd}.mail-file-link:hover span{text-decoration:underline}@media(max-width:1200px){.mail-content-layout.has-files{grid-template-columns:minmax(0,1fr)}.mail-files{margin-top:0}}@media(max-width:750px){.mail-message{max-height:85vh;max-height:85dvh;overflow:auto}.mail-actions{margin:-20px -20px 0;padding:12px 20px}}</style>
<section class="col-12 p-3 text-light">
<div class="mail-shell" id="mail-shell">
    <header class="mail-toolbar">
        <div><h1 class="h4 mb-1"><i class="bi bi-inbox me-2" aria-hidden="true"></i>Caixa de entrada unificada</h1><span class="small text-light">Todas as suas contas · <?= count($accounts) ?> conta(s)</span></div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-light" href="<?= site_url('email/config') ?>" title="Gerenciar contas" aria-label="Gerenciar contas"><i class="bi bi-gear" aria-hidden="true"></i></a>
            <form id="mail-sync-all" method="post" action="<?= site_url('email/account/' . $account['id'] . '/sync') ?>">
                <?= csrf_field() ?><button class="btn btn-primary" title="Atualizar todas as contas ativas" <?= $activeAccounts === [] ? 'disabled' : '' ?>><i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Atualizar</button>
            </form>
        </div>
    </header>
    <?php if ($activeAccounts === []): ?><div class="alert alert-warning m-3">Nenhuma conta ativa. Ative uma conta na configuração para consultar o servidor.</div><?php endif; ?>
    <p id="mail-save-notice" class="px-3 mb-0 small text-warning" role="status" aria-live="polite"></p>
    <div class="mail-layout">
        <div class="mail-list">
            <div class="mail-search">
                <label for="mail-query" class="visually-hidden">Buscar nesta página</label>
                <input id="mail-query" class="form-control" type="search" placeholder="Buscar nesta página">
                <div class="mail-tabs" aria-label="Filtrar mensagens desta página">
                    <?php foreach (['all' => 'Todas', '0' => 'Não lidas', '2' => 'Importantes', '3' => 'Urgentes'] as $filter => $label): ?><button type="button" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-light' ?>" data-filter="<?= esc($filter, 'attr') ?>" aria-pressed="<?= $filter === 'all' ? 'true' : 'false' ?>"><?= esc($label) ?></button><?php endforeach; ?>
                </div>
            </div>
            <div class="mail-rows">
            <?php foreach ($messages as $message): ?>
                <button type="button" class="mail-row <?= (int) $message['email_status'] === 0 ? 'unread' : '' ?>" data-account="<?= (int) $message['email_user_id'] ?>" data-message="<?= (int) $message['id'] ?>" data-status="<?= (int) $message['email_status'] ?>" aria-controls="message-<?= (int) $message['id'] ?>" aria-pressed="false">
                    <span class="mail-row-top"><span class="mail-from"><?= esc($message['email_from']) ?></span></span>
                    <span class="mail-subject">
                        <?php if (in_array((int) $message['email_status'], [2, 3], true)): ?>
                            <i class="bi bi-flag-fill <?= (int) $message['email_status'] === 3 ? 'text-danger' : 'text-warning' ?> me-1" title="<?= (int) $message['email_status'] === 3 ? 'Urgente' : 'Importante' ?>" aria-hidden="true"></i><span class="visually-hidden"><?= (int) $message['email_status'] === 3 ? 'Urgente: ' : 'Importante: ' ?></span>
                        <?php endif; ?>
                        <?= esc($message['email_title'] ?: '(Sem assunto)') ?>
                    </span>
                    <?php if ($message['email_data']): ?>
                        <?php $receivedAt = new \DateTimeImmutable($message['email_data'], new \DateTimeZone('UTC')); ?>
                        <time class="mail-preview" datetime="<?= esc($receivedAt->format(DATE_ATOM), 'attr') ?>" title="Horário de Brasília"><?= esc($receivedAt->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i')) ?></time>
                    <?php else: ?>
                        <span class="mail-preview">Data indisponível</span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
                <p id="mail-empty-list" class="p-4 text-center" <?= $messages !== [] ? 'hidden' : '' ?>><?= $messages === [] ? 'Nenhuma mensagem salva. Clique em Atualizar para consultar o servidor.' : 'Nenhuma mensagem encontrada nesta página.' ?></p>
            </div>
            <div class="mail-page-nav"><?= $pager->links('default', 'default_full') ?></div>
        </div>
        <div class="mail-reader" aria-label="Leitura da mensagem">
            <div id="mail-placeholder" class="mail-placeholder"><i class="bi bi-envelope-open" aria-hidden="true"></i><h2 class="h5">Selecione uma mensagem</h2><p>O conteúdo do e-mail aparecerá aqui.</p></div>
            <?php foreach ($messages as $message): ?>
                <?php $messageAccount = $accountsById[$message['email_user_id']] ?? $account; ?>
                <article class="mail-message" id="message-<?= (int) $message['id'] ?>" hidden>
                    <button type="button" class="btn btn-sm btn-outline-light mail-back-button mb-3"><i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar às mensagens</button>
                    <div class="mail-actions">
                    <?php foreach ([2 => ['Importante', 'warning'], 3 => ['Urgente', 'danger']] as $priority => [$priorityLabel, $priorityColor]): ?>
                        <form class="mail-priority-form" method="post" action="<?= site_url('email/account/' . $messageAccount['id'] . '/messages/' . $message['id'] . '/status') ?>">
                            <?= csrf_field() ?><input type="hidden" name="email_status" value="<?= $priority ?>">
                            <button class="btn btn-sm btn-<?= (int) $message['email_status'] === $priority ? '' : 'outline-' ?><?= $priorityColor ?>" title="Marcar como <?= esc($priorityLabel, 'attr') ?>" aria-label="Marcar como <?= esc($priorityLabel, 'attr') ?>" aria-pressed="<?= (int) $message['email_status'] === $priority ? 'true' : 'false' ?>"><i class="bi <?= $priority === 3 ? 'bi-exclamation-triangle-fill' : 'bi-flag-fill' ?>" aria-hidden="true"></i></button>
                        </form>
                    <?php endforeach; ?>
                    <?php if ($messageAccount['protocol'] === 'imap'): ?>
                    <form method="post" action="<?= site_url('email/account/' . $messageAccount['id'] . '/messages/' . $message['id'] . '/delete') ?>" onsubmit="if (!confirm('Excluir permanentemente esta mensagem do servidor IMAP e do sistema? Esta ação não pode ser desfeita.')) return false; this.querySelector('button').disabled = true;">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Excluir do sistema e do servidor IMAP" aria-label="Excluir do sistema e do servidor IMAP" <?= (int) $messageAccount['status'] !== 1 ? 'disabled' : '' ?>><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </form>
                    <?php endif; ?>
                    <form method="post" action="<?= site_url('email/account/' . $messageAccount['id'] . '/messages/' . $message['id'] . '/send') ?>" onsubmit="if (!confirm('Encaminhar esta mensagem, sem anexos, para o e-mail do seu perfil?')) return false; this.querySelector('button').disabled = true;">
                        <?= csrf_field() ?><input type="hidden" name="action" value="forward"><button class="btn btn-sm btn-outline-light" title="Encaminhar para meu e-mail" aria-label="Encaminhar para meu e-mail"><i class="bi bi-forward" aria-hidden="true"></i></button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-light mail-reply-toggle" data-reply="reply-<?= (int) $message['id'] ?>" aria-controls="reply-<?= (int) $message['id'] ?>" aria-expanded="false" title="Responder por esta conta" aria-label="Responder por esta conta"><i class="bi bi-reply" aria-hidden="true"></i></button>
                    </div>
                    <form id="reply-<?= (int) $message['id'] ?>" class="mail-reply-compose mt-3" method="post" action="<?= site_url('email/account/' . $messageAccount['id'] . '/messages/' . $message['id'] . '/send') ?>" hidden onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                        <?= csrf_field() ?><input type="hidden" name="action" value="reply">
                        <label for="reply-text-<?= (int) $message['id'] ?>" class="form-label">Resposta para <?= esc($message['email_from']) ?></label>
                        <textarea id="reply-text-<?= (int) $message['id'] ?>" name="reply" class="form-control mb-2" rows="6" maxlength="50000" required><?= (int) ($replyDraft['id'] ?? 0) === (int) $message['id'] ? esc($replyDraft['text'] ?? '') : '' ?></textarea>
                        <p class="small">Envio por <?= esc($messageAccount['email_user']) ?>. Anexos originais não serão incluídos.</p>
                        <button type="submit" class="btn btn-primary" title="Enviar resposta por esta conta" aria-label="Enviar resposta por esta conta"><i class="bi bi-send" aria-hidden="true"></i></button>
                    </form>
                    <h2><?= esc($message['email_title'] ?: '(Sem assunto)') ?></h2>
                    <p class="small text-info mb-2"><i class="bi bi-inbox" aria-hidden="true"></i> Conta: <?= esc($messageAccount['email_user']) ?></p>
                    <div class="mail-envelope"><div><strong>De:</strong> <?= esc($message['email_from']) ?></div><div><strong>Para:</strong> <?= esc($message['email_to']) ?></div><time><?= esc($message['email_data'] ?? 'Data indisponível') ?><?= $message['email_data'] ? ' UTC' : '' ?></time></div>
                    <div class="mail-content-layout">
                    <?php
                    // Reflow hard-wrapped prose for display, without changing stored content.
                    $bodyParagraphs = preg_split('/\n[\t ]*\n+/', str_replace(["\r\n", "\r"], "\n", $message['email_context']));
                    $displayBody = implode("\n\n", array_map(static function (string $paragraph): string {
                        if (preg_match('/^[\t ]*(?:[-*•>]|\d+[.)])[\t ]/mu', $paragraph) || preg_match('/^[\t ]*[*•-][\t ]*$/mu', $paragraph)) return $paragraph;
                        return preg_replace('/[\t ]*\n[\t ]*/', ' ', $paragraph);
                    }, $bodyParagraphs));
                    ?>
                    <div class="mail-body"><?= esc($displayBody) ?></div>
                    <div class="mail-files" data-files-message="<?= (int) $message['id'] ?>" aria-label="Anexos da mensagem">
                        <h3 class="h6"><i class="bi bi-paperclip" aria-hidden="true"></i> Anexos</h3>
                        <div class="mail-files-list" aria-live="polite">Selecione a mensagem para consultar os arquivos.</div>
                    </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <footer class="mail-bottom">Busca e filtros nesta página · Status locais ao sistema · Atualização: até 50 mensagens por consulta, com limite de 10 MB por mensagem. Anexos disponíveis para download ao abrir a mensagem.</footer>
</div>
</section>
<dialog id="mail-preview-dialog" aria-labelledby="mail-preview-title">
    <div class="mail-preview-toolbar">
        <h2 id="mail-preview-title" class="h6 mb-0"></h2>
        <div class="d-flex gap-2">
            <a id="mail-preview-download" class="btn btn-sm btn-primary" title="Baixar anexo" aria-label="Baixar anexo"><i class="bi bi-download" aria-hidden="true"></i></a>
            <button type="button" id="mail-preview-close" class="btn btn-sm btn-outline-light" title="Fechar prévia" aria-label="Fechar prévia"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
    </div>
    <div id="mail-preview-content" aria-live="polite"></div>
</dialog>
<style>
#mail-preview-dialog{width:min(1200px,95vw);max-width:95vw;height:88vh;max-height:95vh;padding:0;border:1px solid #536683;border-radius:12px;background:#172131;color:#ecf1fa;overflow:hidden}#mail-preview-dialog::backdrop{background:#020811c9}.mail-preview-toolbar{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:15px 20px;border-bottom:1px solid #354258}.mail-preview-toolbar h2{overflow-wrap:anywhere;min-width:0}.mail-preview-toolbar>div{flex-shrink:0}#mail-preview-content{height:calc(100% - 70px);padding:16px;overflow:auto}#mail-preview-content iframe{width:100%;height:100%;border:0;background:white}#mail-preview-content img{display:block;max-width:100%;max-height:100%;object-fit:contain;margin:auto}#mail-preview-content pre{white-space:pre-wrap;overflow-wrap:anywhere;color:#ecf1fa;font-size:14px}
@media(max-width:750px){.mail-message{max-height:85vh;max-height:85dvh;overflow:auto}.mail-actions{margin:-20px -20px 0;padding:12px 20px}}</style>
<script>
(() => {
    const shell = document.getElementById('mail-shell');
    const rows = [...shell.querySelectorAll('.mail-row')];
    const panels = [...shell.querySelectorAll('.mail-message')];
    const placeholder = document.getElementById('mail-placeholder');
    const query = document.getElementById('mail-query');
    const previewDialog = document.getElementById('mail-preview-dialog');
    const previewContent = document.getElementById('mail-preview-content');
    let previewRequest = null, previewObjectUrl = null;
    function clearPreview() {
        previewRequest?.abort();
        previewRequest = null;
        previewContent.replaceChildren();
        if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = null;
    }
    previewDialog.addEventListener('close', clearPreview);
    document.getElementById('mail-preview-close').addEventListener('click', () => previewDialog.close());
    async function showPreview(file, downloadUrl) {
        clearPreview();
        document.getElementById('mail-preview-title').textContent = file.name;
        document.getElementById('mail-preview-download').href = downloadUrl;
        previewContent.textContent = 'Carregando anexo…';
        if (!previewDialog.open) previewDialog.showModal();
        const request = new AbortController(); previewRequest = request;
        try {
            const response = await fetch(downloadUrl + '/preview', {credentials: 'same-origin', signal: request.signal, headers: {'X-Requested-With': 'XMLHttpRequest'}});
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Não foi possível visualizar o anexo.');
            }
            const type = response.headers.get('Content-Type') || '';
            if (type.startsWith('text/plain')) {
                const text = await response.text();
                if (request.signal.aborted) return;
                const pre = document.createElement('pre'); pre.textContent = text;
                previewContent.replaceChildren(pre);
            } else if (type.startsWith('image/') || type.startsWith('application/pdf')) {
                const blob = await response.blob();
                if (request.signal.aborted) return;
                previewObjectUrl = URL.createObjectURL(blob);
                const element = document.createElement(type.startsWith('image/') ? 'img' : 'iframe');
                if (element.tagName === 'IFRAME') { element.title = file.name; element.setAttribute('sandbox', 'allow-same-origin allow-downloads'); }
                else element.alt = file.name;
                element.src = previewObjectUrl; previewContent.replaceChildren(element);
            } else throw new Error('Prévia indisponível para este formato. Use o download.');
        } catch (error) { if (!request.signal.aborted) previewContent.textContent = error.message; }
    }
    shell.querySelectorAll('.mail-reply-toggle').forEach(button => button.addEventListener('click', () => {
        const composer = document.getElementById(button.dataset.reply);
        composer.hidden = !composer.hidden;
        button.setAttribute('aria-expanded', String(!composer.hidden));
        if (!composer.hidden) composer.querySelector('textarea').focus();
    }));
    let filter = <?= json_encode($savedFilter) ?>, selected = null;
    const endpoint = <?= json_encode(site_url('email/account' . ($account['id'] ? '/' . $account['id'] : '')), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const accountBase = <?= json_encode(rtrim(site_url('email/account'), '/'), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const activeAccounts = <?= json_encode(array_map(static fn($item) => ['id' => (int) $item['id'], 'email' => $item['email_user']], $activeAccounts), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    async function loadAttachments(row) {
        const box = document.querySelector('[data-files-message="' + row.dataset.message + '"]');
        if (!box || box.dataset.loaded || box.dataset.loading) return;
        box.dataset.loading = 'true';
        const list = box.querySelector('.mail-files-list');
        list.textContent = 'Consultando anexos…';
        try {
            const base = accountBase + '/' + row.dataset.account + '/messages/' + row.dataset.message + '/attachments';
            const response = await fetch(base, {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Não foi possível consultar os anexos.');
            list.replaceChildren();
            box.dataset.loaded = 'true';
            box.hidden = data.files.length === 0;
            document.getElementById('message-' + row.dataset.message).querySelector('.mail-content-layout').classList.toggle('has-files', data.files.length > 0);
            data.files.forEach(file => {
                const link = document.createElement('a');
                link.className = 'mail-file-link';
                link.href = base + '/' + encodeURIComponent(file.section);
                link.title = 'Visualizar ' + file.name;
                link.addEventListener('click', event => { event.preventDefault(); showPreview(file, link.href); });
                const icon = document.createElement('i');
                icon.className = 'bi bi-eye'; icon.setAttribute('aria-hidden', 'true');
                const name = document.createElement('span'); name.textContent = file.name;
                const size = document.createElement('small'); size.textContent = Math.ceil(file.size / 1024) + ' KB';
                link.append(icon, name, size); list.append(link);
            });
        } catch (error) {
            list.textContent = error.message;
            const retry = document.createElement('button');
            retry.type = 'button'; retry.className = 'btn btn-sm btn-outline-light mt-2'; retry.textContent = 'Tentar novamente';
            retry.addEventListener('click', () => loadAttachments(row));
            list.append(document.createElement('br'), retry);
        } finally { delete box.dataset.loading; }
    }
    const tokenName = <?= json_encode(csrf_token()) ?>;
    let token = <?= json_encode(csrf_hash()) ?>;
    let pending = Promise.resolve();
    let readTimer = null;
    function post(path, values, guard = () => true, targetEndpoint = endpoint) {
        const operation = pending.then(async () => {
            if (!guard()) return null;
            const response = await fetch(targetEndpoint + path, {
                method: 'POST', credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: new URLSearchParams({...values, [tokenName]: token})
            });
            const data = await response.json();
            if (data.csrf) {
                token = data.csrf;
                document.querySelectorAll('input[type="hidden"]').forEach(input => { if (input.name === tokenName) input.value = token; });
            }
            if (!response.ok) throw new Error(data.error || 'Falha ao salvar.');
            return data;
        });
        pending = operation.catch(() => {});
        return operation;
    }
    function cancelRead() { clearTimeout(readTimer); readTimer = null; }
    function scheduleRead() {
        cancelRead();
        const row = selected;
        if (!row || row.dataset.status !== '0' || document.hidden) return;
        readTimer = setTimeout(() => {
            post('/messages/' + row.dataset.message + '/read', {}, () => selected === row && !document.hidden, accountBase + '/' + row.dataset.account)
                .then(data => {
                    if (!data) return;
                    row.dataset.status = String(data.status);
                    row.classList.toggle('unread', data.status === 0);
                    // Keep the open message readable, even in the unread filter.
                    apply(true);
                }).catch(() => { document.getElementById('mail-save-notice').textContent = 'Não foi possível marcar como lido. Abra a mensagem novamente para tentar outra vez.'; });
        }, 10000);
    }
    document.addEventListener('visibilitychange', scheduleRead);
    window.addEventListener('pagehide', cancelRead);
    document.getElementById('mail-sync-all').addEventListener('submit', async event => {
        event.preventDefault();
        const button = event.currentTarget.querySelector('button');
        if (button.disabled) return;
        button.disabled = true;
        cancelRead();
        const notice = document.getElementById('mail-save-notice');
        const failures = [];
        let total = 0;
        for (const item of activeAccounts) {
            notice.textContent = 'Consultando ' + item.email + '…';
            try { const data = await post('/sync', {}, () => true, accountBase + '/' + item.id); total += data.count; }
            catch (error) { failures.push(item.email + ': ' + error.message); }
        }
        if (failures.length === 0) window.location.reload();
        else {
            notice.textContent = total + ' mensagem(ns) importada(s). ' + failures.join(' ') + ' Recarregue a página para ver as mensagens importadas.';
            button.disabled = false;
            scheduleRead();
        }
    });
    shell.querySelectorAll('form').forEach(form => form.addEventListener('submit', event => {
        if (event.defaultPrevented) return;
        event.preventDefault();
        cancelRead();
        pending.then(() => form.submit());
    }));
    function close() {
        cancelRead();
        panels.forEach(panel => panel.hidden = true);
        rows.forEach(row => { row.classList.remove('selected'); row.setAttribute('aria-pressed', 'false'); });
        placeholder.hidden = false;
        shell.classList.remove('reading');
        selected = null;
    }
    rows.forEach(row => row.addEventListener('click', () => {
        selected = row;
        panels.forEach(panel => panel.hidden = panel.id !== 'message-' + row.dataset.message);
        rows.forEach(item => { item.classList.toggle('selected', item === row); item.setAttribute('aria-pressed', String(item === row)); });
        placeholder.hidden = true;
        shell.classList.add('reading');
        loadAttachments(row);
        apply(true);
        scheduleRead();
    }));
    function apply(keepSelected = false) {
        const text = query.value.trim().toLocaleLowerCase('pt-BR');
        rows.forEach(row => row.hidden = !(keepSelected && row === selected) && ((filter !== 'all' && row.dataset.status !== filter) || !row.textContent.toLocaleLowerCase('pt-BR').includes(text)));
        document.getElementById('mail-empty-list').hidden = rows.some(row => !row.hidden);
        if (selected?.hidden) close();
    }
    shell.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
        filter = button.dataset.filter;
        shell.querySelectorAll('[data-filter]').forEach(item => { item.classList.toggle('btn-primary', item === button); item.classList.toggle('btn-outline-light', item !== button); item.setAttribute('aria-pressed', String(item === button)); });
        apply();
        post('/filter', {filter}).catch(() => { document.getElementById('mail-save-notice').textContent = 'Não foi possível salvar o filtro na sessão. Tente novamente.'; });
    }));
    shell.querySelectorAll('[data-filter]').forEach(button => {
        const active = button.dataset.filter === filter;
        button.classList.toggle('btn-primary', active);
        button.classList.toggle('btn-outline-light', !active);
        button.setAttribute('aria-pressed', String(active));
    });
    apply();
    query.addEventListener('input', () => apply());
    shell.querySelectorAll('.mail-back-button').forEach(button => button.addEventListener('click', () => { const previous = selected; close(); previous?.focus(); }));
})();
</script>
