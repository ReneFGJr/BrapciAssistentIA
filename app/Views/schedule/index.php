<section class="col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <h1 class="h3"><?= ($schedulePath ?? 'schedule') === 'userSchedule' ? 'Agenda particular — próximas reuniões' : 'Agenda — próximas reuniões' ?></h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-light" href="<?= site_url($configurationPath ?? 'tools/googleSchedule') ?>">Configurar</a>
            <form method="post" action="<?= site_url(($schedulePath ?? 'schedule') . '/sync') ?>"><?= csrf_field() ?><button class="btn btn-info" type="submit">Atualizar agenda</button></form>
        </div>
    </div>
    <p>Eventos dos próximos 90 dias. Horários exibidos no fuso de cada agenda.</p>
    <?php if ($lastSync): ?><p class="small">Última consulta: <?= esc($lastSync) ?> UTC</p><?php endif; ?>
    <div class="table-responsive">
        <table class="table table-dark align-middle">
            <thead><tr><th>Data e hora</th><th>Título da reunião</th><th>Local</th><th>Situação</th><th>Assunto</th></tr></thead>
            <tbody>
                <?php if ($events === []): ?><tr><td colspan="5">Nenhuma reunião disponível nesta consulta.</td></tr><?php endif; ?>
                <?php foreach ($events as $event): ?>
                    <?php $start = (new \DateTimeImmutable($event['starts_at'], new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone($event['timezone'])); ?>
                    <tr>
                        <td><?= esc($start->format($event['all_day'] ? 'd/m/Y' : 'd/m/Y H:i')) ?><br><small><?= $event['all_day'] ? 'Dia inteiro' : esc($event['timezone']) ?></small></td>
                        <td style="min-width: 220px; overflow-wrap: anywhere;">
                            <strong class="d-block text-light"><?= esc(trim((string) $event['title']) === '' || $event['title'] === 'Sem título' ? 'Título não fornecido pelo Google' : $event['title']) ?></strong>
                            <?php if (trim((string) $event['description']) !== ''): ?><div class="text-light" style="white-space: pre-wrap; overflow-wrap: anywhere;"><?= esc($event['description']) ?></div><?php endif; ?>
                        </td>
                        <td class="text-light" style="min-width: 160px; white-space: pre-wrap; overflow-wrap: anywhere;"><?= esc(trim((string) $event['location']) !== '' ? $event['location'] : 'Não informado pelo Google') ?></td>
                        <td><?= $event['status'] === 'tentative' ? 'Provisória' : 'Confirmada' ?></td>
                        <td>
                            <form method="post" action="<?= site_url(($schedulePath ?? 'schedule') . '/' . $event['id'] . '/subject') ?>" class="d-flex gap-2">
                                <?= csrf_field() ?>
                                <select name="subject_id" class="form-select" aria-label="<?= esc('Assunto de ' . $event['title'], 'attr') ?>">
                                    <option value="">Sem assunto</option>
                                    <?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id'] ?>" <?= (string) $event['subject_id'] === (string) $subject['id'] ? 'selected' : '' ?>><?= esc($subject['name']) ?></option><?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-info" type="submit">Salvar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $pager->links() ?>
</section>
