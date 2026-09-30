<section class="card bg-dark text-light border-secondary p-3 mt-4" aria-labelledby="participants-title">
    <h2 id="participants-title" class="h5">Participantes da reunião</h2>
    <?php if ($participants === []): ?><p>Nenhum participante incluído.</p><?php endif; ?>
    <ul class="list-unstyled">
    <?php foreach ($participants as $participant): ?>
        <li class="d-flex justify-content-between align-items-center gap-2 mb-2">
            <span><?= esc($participant['full_name']) ?></span>
            <form method="post" action="<?= site_url('notes/' . $note['id'] . '/participants/' . $participant['person_id'] . '/remove') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="<?= esc('Remover ' . $participant['full_name'], 'attr') ?>">Remover</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
    <form id="participant-search-form" method="post" action="<?= site_url('notes/' . $note['id'] . '/participants') ?>" data-search-url="<?= esc(site_url('notes/' . $note['id'] . '/participants/search'), 'attr') ?>">
        <?= csrf_field() ?>
        <label for="participant-name" class="form-label">Buscar Person e adicionar à reunião</label>
        <div class="d-flex flex-wrap gap-2">
            <input id="participant-name" type="search" class="form-control flex-grow-1" style="flex-basis: 240px" maxlength="255" required autocomplete="off" placeholder="Digite palavras do nome" aria-describedby="participant-help" aria-controls="participant-results">
            <input id="participant-id" type="hidden" name="participant_id">
            <button id="participant-add" class="btn btn-info" type="submit" disabled>Adicionar participante</button>
        </div>
        <small id="participant-help" class="text-light">Digite pelo menos 2 caracteres e selecione uma pessoa nos resultados.</small>
        <div id="participant-results" class="list-group mt-2" aria-label="Pessoas encontradas" hidden></div>
    </form>
    <script src="<?= base_url('assets/js/notes-participants.js') ?>?v=<?= (int) filemtime(FCPATH . 'assets/js/notes-participants.js') ?>" defer></script>
</section>
