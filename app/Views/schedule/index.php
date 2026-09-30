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
                <?php $currentDay = null; ?>
                <?php $weekdays = [1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 7 => 'Domingo']; ?>
                <?php foreach ($events as $event): ?>
                    <?php $start = (new \DateTimeImmutable($event['starts_at'], new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone($event['timezone'])); ?>
                    <?php $location = trim((string) $event['location']); ?>
                    <?php $locationUrl = filter_var($location, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($location, PHP_URL_SCHEME)), ['http', 'https'], true) ? $location : null; ?>
                    <?php $eventDay = $start->format('Y-m-d'); ?>
                    <?php if ($eventDay !== $currentDay): $currentDay = $eventDay; ?>
                        <tr class="table-secondary"><th colspan="5" scope="rowgroup" class="text-dark"><?= esc($start->format('d/m/Y') . ' (' . $weekdays[(int) $start->format('N')] . ')') ?></th></tr>
                    <?php endif; ?>
                    <tr>
                        <td><?= $event['all_day'] ? 'Dia inteiro' : esc($start->format('H:i')) ?><?php if (!$event['all_day']): ?><br><small><?= esc($event['timezone']) ?></small><?php endif; ?></td>
                        <td style="min-width: 220px; overflow-wrap: anywhere;">
                            <?php $eventTitle = trim((string) $event['title']) === '' || $event['title'] === 'Sem título' ? 'Título não fornecido pelo Google' : $event['title']; ?>
                            <strong class="d-block text-light"><?php if ($locationUrl !== null): ?><a class="link-info" href="<?= esc($locationUrl, 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc($eventTitle) ?></a><?php else: ?><?= esc($eventTitle) ?><?php endif; ?></strong>
                        </td>
                        <td class="text-light" style="min-width: 160px; white-space: pre-wrap; overflow-wrap: anywhere;"><?php if ($locationUrl !== null): ?><a class="link-info" href="<?= esc($locationUrl, 'attr') ?>" target="_blank" rel="noopener noreferrer" title="Abrir link" aria-label="Abrir link"><i class="bi bi-link-45deg" aria-hidden="true"></i></a><?php else: ?><?= esc($location) ?><?php endif; ?></td>
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
