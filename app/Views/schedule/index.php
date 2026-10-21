<section class="col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <h1 class="h3"><?= ($schedulePath ?? 'schedule') === 'userSchedule' ? 'Agenda particular — próximas reuniões' : 'Agenda — próximas reuniões' ?></h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-light" href="<?= site_url($configurationPath ?? 'tools/googleSchedule') ?>">Configurar</a>
            <form id="schedule-sync-form" method="post" action="<?= site_url(($schedulePath ?? 'schedule') . '/sync') ?>"><?= csrf_field() ?><button id="schedule-sync-button" class="btn btn-info" type="submit">Atualizar agenda</button></form>
        </div>
    </div>
    <p>Eventos dos próximos 90 dias. Horários exibidos no fuso de cada agenda.</p>
    <script>
    document.getElementById('schedule-sync-form')?.addEventListener('submit', () => {
        const button = document.getElementById('schedule-sync-button');
        button.disabled = true;
        button.textContent = 'Consultando o Google…';
    });
    </script>
    <?php if ($lastSync): ?><p class="small">Última consulta: <?= esc($lastSync) ?> UTC</p><?php endif; ?>
    <?php $renderDescription = static function (string $description): string {
        $parts = preg_split('~(https?://[^\s<>"\']+)~iu', $description, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) return nl2br(esc($description), false);
        $html = '';
        foreach ($parts as $part) {
            if (preg_match('~^https?://~i', $part) && filter_var($part, FILTER_VALIDATE_URL)) {
                $html .= '<a class="link-info" href="' . esc($part, 'attr') . '" target="_blank" rel="noopener noreferrer">' . esc($part) . '</a>';
            } else {
                $html .= esc($part);
            }
        }
        return nl2br($html, false);
    }; ?>
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
                            <button class="btn btn-link link-info p-0 text-start fw-bold text-decoration-none" type="button" data-bs-toggle="modal" data-bs-target="#schedule-event-<?= (int) $event['id'] ?>" aria-label="<?= esc('Ver detalhes: ' . $eventTitle, 'attr') ?>"><?= esc($eventTitle) ?></button>
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
    <?php foreach ($events as $event): ?>
        <?php
            $eventStart = (new \DateTimeImmutable($event['starts_at'], new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone($event['timezone']));
            $eventEnd = (new \DateTimeImmutable($event['ends_at'], new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone($event['timezone']));
            $eventTitle = trim((string) $event['title']) === '' || $event['title'] === 'Sem título' ? 'Título não fornecido pelo Google' : $event['title'];
            $eventLocation = trim((string) $event['location']);
            $eventLocationUrl = filter_var($eventLocation, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($eventLocation, PHP_URL_SCHEME)), ['http', 'https'], true) ? $eventLocation : null;
            $eventSubject = '';
            foreach ($subjects as $subject) {
                if ((string) ($event['subject_id'] ?? '') === (string) $subject['id']) {
                    $eventSubject = $subject['name'];
                    break;
                }
            }
        ?>
        <div class="modal fade" id="schedule-event-<?= (int) $event['id'] ?>" tabindex="-1" aria-labelledby="schedule-event-title-<?= (int) $event['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content bg-dark text-light border-secondary">
                    <div class="modal-header border-secondary">
                        <h2 class="modal-title h5" id="schedule-event-title-<?= (int) $event['id'] ?>"><?= esc($eventTitle) ?></h2>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Início</dt>
                            <dd class="col-sm-9"><?= $event['all_day'] ? esc($eventStart->format('d/m/Y') . ' (dia inteiro)') : esc($eventStart->format('d/m/Y H:i') . ' ' . $event['timezone']) ?></dd>
                            <dt class="col-sm-3">Término</dt>
                            <dd class="col-sm-9"><?= $event['all_day'] ? esc($eventEnd->modify('-1 day')->format('d/m/Y') . ' (dia inteiro)') : esc($eventEnd->format('d/m/Y H:i') . ' ' . $event['timezone']) ?></dd>
                            <dt class="col-sm-3">Local ou link</dt>
                            <dd class="col-sm-9 text-break"><?php if ($eventLocationUrl !== null): ?><a class="link-info" href="<?= esc($eventLocationUrl, 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc($eventLocation) ?></a><?php else: ?><?= $eventLocation !== '' ? nl2br(esc($eventLocation), false) : 'Não informado' ?><?php endif; ?></dd>
                            <dt class="col-sm-3">Situação</dt>
                            <dd class="col-sm-9"><?= $event['status'] === 'tentative' ? 'Provisória' : 'Confirmada' ?></dd>
                            <dt class="col-sm-3">Assunto</dt>
                            <dd class="col-sm-9"><?= $eventSubject !== '' ? esc($eventSubject) : 'Sem assunto' ?></dd>
                            <dt class="col-sm-3">Descrição</dt>
                            <dd class="col-sm-9 text-break"><?= trim((string) ($event['description'] ?? '')) !== '' ? $renderDescription((string) $event['description']) : 'Sem descrição' ?></dd>
                        </dl>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?= $pager->links() ?>
</section>
