<section class="col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <h1 class="h3">Resumo do dia</h1>
    <p><?= esc($today->format('d/m/Y')) ?> · Horário de Brasília</p>
    <p class="small">Agenda conforme a última sincronização. Atualize os compromissos na página da agenda.</p>
    <div class="row g-3">
        <div class="col-12 col-lg-4"><article class="astra-panel p-3">
            <h2 class="h5"><a class="link-info" href="<?= site_url('schedule') ?>">Compromissos de hoje (<?= count($events) ?>)</a></h2>
            <?php if (!$events): ?><p>Nenhum compromisso sincronizado para hoje.</p><?php endif; ?>
            <ul class="list-unstyled mb-0">
            <?php foreach ($events as $event): ?>
                <li class="py-2 border-bottom"><strong><?= !empty($event['all_day']) ? 'Dia inteiro' : esc((new DateTimeImmutable($event['starts_at'], new DateTimeZone('UTC')))->setTimezone($today->getTimezone())->format('H:i')) ?></strong> · <?= esc($event['title']) ?></li>
            <?php endforeach; ?>
            </ul>
        </article></div>
        <div class="col-12 col-lg-4"><article class="astra-panel p-3">
            <h2 class="h5"><a class="link-info" href="<?= site_url('notes') ?>">Reuniões de hoje (<?= count($notes) ?>)</a></h2>
            <?php if (!$notes): ?><p>Nenhuma reunião registrada para hoje.</p><?php endif; ?>
            <ul class="list-unstyled mb-0">
            <?php foreach ($notes as $note): ?>
                <li class="py-2 border-bottom"><a class="link-info" href="<?= site_url('notes/' . (int) $note['id']) ?>"><?= esc(substr($note['meeting_time'] ?? '', 0, 5)) ?> · <?= esc($note['title']) ?></a><br><small><?= esc(\App\Models\NoteModel::STATUSES[$note['status']] ?? $note['status']) ?></small></li>
            <?php endforeach; ?>
            </ul>
        </article></div>
        <div class="col-12 col-lg-4"><article class="astra-panel p-3">
            <h2 class="h5"><a class="link-info" href="<?= site_url('kanban') ?>">Kanban atualizado hoje (<?= count($tasks) ?>)</a></h2>
            <?php if (!$tasks): ?><p>Nenhum cartão atualizado hoje.</p><?php endif; ?>
            <ul class="list-unstyled mb-0">
            <?php foreach ($tasks as $task): ?>
                <li class="py-2 border-bottom"><a class="link-info" href="<?= site_url('kanban/' . (int) $task['id'] . '/edit') ?>"><?= esc($task['title']) ?></a><br><small><?= esc(\App\Models\KanbanModel::STATUSES[$task['status']] ?? $task['status']) ?></small></li>
            <?php endforeach; ?>
            </ul>
        </article></div>
    </div>
</section>
