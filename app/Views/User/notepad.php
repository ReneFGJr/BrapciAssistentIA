<?php
$notes = is_array($notes ?? null) ? $notes : [];
$selected = is_array($selected ?? null) ? $selected : null;
$error = session()->getFlashdata('error');
$success = session()->getFlashdata('success');
$footerMessage = $error ?: $success;
$footerStatus = $error ? 'error' : ($success ? 'success' : '');
$updatedToday = count(array_filter($notes, static fn (array $note): bool => substr((string) $note['updated_at'], 0, 10) === date('Y-m-d')));
?>

<div class="col-12 notepad-page" data-footer-message="<?= esc((string) ($footerMessage ?? ''), 'attr') ?>" data-footer-status="<?= esc($footerStatus, 'attr') ?>">
    <header class="notepad-titlebar">
        <div><span>BLOCO DE NOTAS CRIPTOGRAFADO</span><h1>Minhas anotações</h1></div>
        <button class="new-note-button" type="button" data-bs-toggle="modal" data-bs-target="#new-note-modal">
            <i class="bi bi-plus-lg"></i> Nova anotação
        </button>
    </header>

    <div class="notepad-metrics">
        <article><span>TOTAL DE NOTAS</span><strong><?= count($notes) ?></strong></article>
        <article><span>ATUALIZADAS HOJE</span><strong><?= $updatedToday ?></strong></article>
        <article><span>PROTEÇÃO</span><strong class="metric-secure"><i class="bi bi-shield-lock-fill"></i> ATIVA</strong></article>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger border-danger bg-dark text-danger" role="alert"><?= esc($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success border-success bg-dark text-success" role="status"><?= esc($success) ?></div>
    <?php endif; ?>

    <div class="notepad-layout">
        <aside class="notepad-list" aria-labelledby="notes-list-title">
            <div class="notepad-panel-header"><h2 id="notes-list-title"><i class="bi bi-journal-text"></i> Notas recentes</h2></div>
            <div class="note-filter">
                <label class="visually-hidden" for="note-title-filter">Localizar pelo título</label>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input id="note-title-filter" type="search" placeholder="Localizar pelo título..." autocomplete="off">
            </div>
            <nav class="notepad-items" aria-label="Lista de anotações">
                <?php if ($notes === []): ?>
                    <div class="notepad-empty"><i class="bi bi-journal-plus"></i><p>Nenhuma anotação criada.</p></div>
                <?php endif; ?>

                <?php foreach ($notes as $note): ?>
                    <a data-note-item data-note-title="<?= esc($note['title'], 'attr') ?>" class="note-list-item<?= (string) ($selected['id'] ?? '') === (string) $note['id'] ? ' active' : '' ?>"
                        href="<?= site_url('notepad?note=' . $note['id']) ?>">
                        <strong><?= esc($note['title']) ?></strong>
                        <span><?= esc(date('d/m/Y H:i', strtotime($note['updated_at']))) ?></span>
                    </a>
                <?php endforeach; ?>
                <div class="note-filter-empty" data-note-filter-empty hidden>
                    Nenhum título encontrado.
                </div>
            </nav>
        </aside>

        <section class="notepad-viewer" aria-labelledby="note-viewer-title">
            <?php if ($selected): ?>
                <div class="notepad-panel-header viewer-header">
                    <div>
                        <span>ANOTAÇÃO SELECIONADA</span>
                        <h2 id="note-viewer-title"><?= esc($selected['title']) ?></h2>
                    </div>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#edit-note-modal">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                </div>
                <div class="viewer-body">
                    <textarea class="note-content-screen" readonly aria-label="Conteúdo da anotação"><?= esc($selected['content']) ?></textarea>
                    <small>Atualizada em <?= esc(date('d/m/Y H:i', strtotime($selected['updated_at']))) ?></small>
                </div>
            <?php else: ?>
                <div class="viewer-empty"><i class="bi bi-journal-text"></i><h2 id="note-viewer-title">Selecione uma anotação</h2><p>Escolha uma nota na lista ou crie uma nova.</p></div>
            <?php endif; ?>
        </section>
    </div>
</div>

<div class="modal fade" id="new-note-modal" tabindex="-1" aria-labelledby="new-note-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content notepad-modal">
        <form action="<?= site_url('notepad') ?>" method="post"><?= csrf_field() ?>
            <div class="modal-header"><h2 class="modal-title fs-5" id="new-note-title">Nova anotação</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
            <div class="modal-body"><label for="new-title">Título</label><input id="new-title" name="title" type="text" maxlength="150" value="<?= esc(old('title')) ?>" required><label for="new-content">Anotação</label><textarea id="new-content" name="content" maxlength="50000" placeholder="Escreva sua anotação..."><?= esc(old('content')) ?></textarea></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-info">Criar anotação</button></div>
        </form>
    </div></div>
</div>

<?php if ($selected): ?>
<div class="modal fade" id="edit-note-modal" tabindex="-1" aria-labelledby="edit-note-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content notepad-modal">
        <form action="<?= site_url('notepad/' . $selected['id'] . '/update') ?>" method="post"><?= csrf_field() ?>
            <div class="modal-header"><h2 class="modal-title fs-5" id="edit-note-title">Editar anotação</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
            <div class="modal-body"><label for="edit-title">Título</label><input id="edit-title" name="title" type="text" maxlength="150" value="<?= esc($selected['title']) ?>" required><label for="edit-content">Anotação</label><textarea id="edit-content" name="content" maxlength="50000"><?= esc($selected['content']) ?></textarea></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-info">Salvar alterações</button></div>
        </form>
    </div></div>
</div>
<?php endif; ?>

<style>
.notepad-page{padding:8px 12px 24px}.notepad-titlebar{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}.notepad-titlebar span{color:var(--cyan);font-size:10px;letter-spacing:2px}.notepad-titlebar h1{margin:4px 0 0;color:#fff;font-size:25px}.new-note-button{min-height:44px;padding:9px 17px;border:1px solid var(--cyan);border-radius:8px;color:#02101a;background:var(--cyan);font-weight:700}.notepad-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}.notepad-metrics article{padding:12px 16px;border:1px solid #12334a;border-radius:10px;background:linear-gradient(145deg,#061624fa,#020a12fa)}.notepad-metrics span{display:block;color:#7193ac;font-size:9px;letter-spacing:1px}.notepad-metrics strong{display:block;margin-top:4px;color:#fff;font-size:20px}.notepad-metrics .metric-secure{color:var(--green);font-size:15px}.notepad-layout{display:grid;grid-template-columns:300px minmax(0,1fr);gap:16px}.notepad-list,.notepad-viewer{border:1px solid #12334a;border-radius:12px;background:linear-gradient(145deg,#061624fa,#020a12fa);overflow:hidden}.notepad-panel-header{padding:13px 16px;border-bottom:1px solid #12334a}.notepad-panel-header h2{margin:0;color:var(--cyan);font-size:20px}.notepad-items{max-height:calc(100vh - 340px);overflow:auto}.note-list-item{display:flex;flex-direction:column;gap:6px;padding:15px 16px;border-bottom:1px solid #102b3e;color:#d7ebf8;text-decoration:none}.note-list-item:hover,.note-list-item.active{color:#fff;background:#06304a}.note-list-item.active{border-left:3px solid var(--cyan)}.note-list-item strong{font-family:"Courier New",Courier,monospace;font-size:16px}.note-list-item span{color:#7193ac;font-size:10px}.viewer-header{display:flex;align-items:center;justify-content:space-between}.viewer-header span{color:#7193ac;font-size:9px;letter-spacing:1px}.viewer-header h2{margin-top:4px;color:#fff}.viewer-header button{border:1px solid #164c70;border-radius:7px;padding:7px 12px;color:var(--cyan);background:#05243a}.viewer-body{padding:14px}.note-content-screen{display:block;width:100%;height:calc(100vh - 375px);min-height:360px;padding:16px;border:1px solid #164c70;border-radius:8px;resize:none;outline:0;color:#d7ebf8;background:#020a12;font-family:"Courier New",Courier,monospace;font-size:18px;line-height:1.6}.viewer-body small{display:block;margin-top:8px;color:#57738e;text-align:right}.viewer-empty{height:calc(100vh - 305px);min-height:430px;display:grid;place-content:center;text-align:center;color:#57738e}.viewer-empty i{font-size:42px}.viewer-empty h2{margin:10px 0 0;color:#8db5d1}.notepad-empty{padding:45px 15px;text-align:center;color:#57738e}.notepad-empty i{font-size:34px}.notepad-modal{border:1px solid #164c70;color:#d7ebf8;background:#04101c}.notepad-modal .modal-header,.notepad-modal .modal-footer{border-color:#12334a}.notepad-modal .modal-body{display:grid;gap:9px}.notepad-modal label{color:#8db5d1;font-size:12px}.notepad-modal input,.notepad-modal textarea{width:100%;padding:11px 13px;border:1px solid #164c70;border-radius:8px;outline:0;color:#fff;background:#020a12}.notepad-modal textarea{min-height:320px;font-family:"Courier New",Courier,monospace}.notepad-modal input:focus,.notepad-modal textarea:focus{border-color:var(--cyan);box-shadow:0 0 0 3px #00d9ff20}@media(max-width:900px){.notepad-layout{grid-template-columns:1fr}.notepad-items{max-height:260px}.note-content-screen{height:60vh}.notepad-metrics{grid-template-columns:1fr}.notepad-titlebar{align-items:flex-start;gap:12px;flex-direction:column}}
.note-filter{position:relative;padding:12px;border-bottom:1px solid #12334a}.note-filter i{position:absolute;left:25px;top:50%;transform:translateY(-50%);color:#7193ac}.note-filter input{width:100%;height:40px;padding:8px 12px 8px 38px;border:1px solid #164c70;border-radius:8px;outline:0;color:#fff;background:#020a12;font-size:13px}.note-filter input:focus{border-color:var(--cyan);box-shadow:0 0 0 3px #00d9ff20}.note-filter input::placeholder{color:#57738e}.note-filter-empty{padding:28px 14px;text-align:center;color:#7193ac;font-size:13px}</style>
