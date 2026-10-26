<?php
$canWrite = !empty($calendar['connected']) && !empty($calendar['can_write']);
$start = (new \DateTimeImmutable('tomorrow 09:00', new \DateTimeZone('America/Sao_Paulo')));
?>
<section class="col-12 p-3 text-light">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <h1 class="h4">Agendar reunião a partir do e-mail</h1>
        <a class="btn btn-outline-light" href="<?= esc(site_url('email/account') . '?message=' . (int) $message['id'], 'attr') ?>">Voltar ao e-mail</a>
    </div>
    <p><strong>Referência:</strong> <?= esc($message['email_title'] ?: '(Sem assunto)') ?><br><strong>De:</strong> <?= esc($message['email_from']) ?></p>
    <?php if (!$canWrite): ?>
    <div class="alert alert-warning">Para criar reuniões, conecte o Google Agenda particular e autorize a criação de eventos. Conexões antigas com acesso somente de leitura precisam ser autorizadas novamente. <a href="<?= site_url('tools/usergoogleSchedule') ?>">Configurar e conectar Google Agenda</a></div>
    <?php endif; ?>
    <div id="meeting-result" hidden role="status" aria-live="polite"></div>
    <form id="email-meeting-form" method="post" action="<?= site_url('email/account/' . (int) $message['email_user_id'] . '/messages/' . (int) $message['id'] . '/meeting') ?>" class="border rounded p-3">
        <?= csrf_field() ?><input type="hidden" name="request_key" value="<?= esc($requestKey, 'attr') ?>">
        <fieldset <?= !$canWrite ? 'disabled' : '' ?>>
            <div class="mb-3"><label class="form-label" for="meeting-title">Título da reunião</label><input id="meeting-title" class="form-control" name="title" maxlength="255" required value="<?= esc(mb_substr($message['email_title'] ?: 'Reunião', 0, 255), 'attr') ?>"></div>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label class="form-label" for="meeting-start">Início (horário de Brasília)</label><input id="meeting-start" type="datetime-local" name="start" class="form-control" required value="<?= $start->format('Y-m-d\TH:i') ?>"></div>
                <div class="col-md-6"><label class="form-label" for="meeting-end">Término (horário de Brasília)</label><input id="meeting-end" type="datetime-local" name="end" class="form-control" required value="<?= $start->modify('+1 hour')->format('Y-m-d\TH:i') ?>"></div>
            </div>
            <div class="mb-3"><label class="form-label" for="meeting-location">Local (opcional)</label><input id="meeting-location" name="location" class="form-control" maxlength="1000"></div>
            <div class="form-check mb-3"><input id="meeting-meet" class="form-check-input" type="checkbox" name="meet" value="1"><label for="meeting-meet" class="form-check-label">Criar link do Google Meet</label></div>
            <div class="mb-3"><label class="form-label" for="meeting-guests">Convidados (opcional)</label><input id="meeting-guests" name="guests" class="form-control" maxlength="5000" placeholder="pessoa@exemplo.com, outra@exemplo.com"><p class="small mt-2">O Google enviará convites aos endereços informados. Sem convidados, o evento será criado somente na sua agenda. A descrição incluirá a referência e um trecho deste e-mail.</p></div>
            <p class="small">Em caso de falha de conexão, tente novamente neste mesmo formulário, com os mesmos dados, para consultar a solicitação sem duplicar a reunião.</p>
            <button type="submit" class="btn btn-primary"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Agendar no Google Agenda</button>
        </fieldset>
    </form>
    <div id="meeting-links" class="d-flex gap-2 mt-3 flex-wrap"></div>
</section>
<div id="meeting-busy" hidden role="status" aria-live="polite"><div class="text-center border rounded p-4"><span class="spinner-border" aria-hidden="true"></span><p class="mt-3 mb-0">Agendando reunião no Google…</p></div></div>
<style>#meeting-busy{position:fixed;inset:0;z-index:2000;display:flex;align-items:center;justify-content:center;background:#07101ddd;color:white}#meeting-busy[hidden]{display:none}#meeting-busy>div{background:#1b273a}</style>
<script>
(() => {
    const form = document.getElementById('email-meeting-form');
    const result = document.getElementById('meeting-result');
    const busy = document.getElementById('meeting-busy');
    let sending = false;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (sending) return;
        sending = true;
        const data = new FormData(form);
        const button = form.querySelector('button');
        button.disabled = true; form.inert = true; form.setAttribute('aria-busy', 'true'); busy.hidden = false;
        window.setOperationStatus?.('Agendando reunião no Google…', 'info');
        let created = false;
        const status = (message, error = false) => {
            result.hidden = false; result.textContent = message; result.className = 'alert alert-' + (error ? 'danger' : 'success');
            window.setOperationStatus?.(message, error ? 'error' : 'success');
        };
        try {
            const response = await fetch(form.getAttribute('action'), {method: 'POST', credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}, body: data});
            let body;
            try { body = await response.json(); }
            catch (_) { throw new Error('Não foi possível consultar o resultado. Confira sua sessão e tente novamente neste formulário.'); }
            if (body.csrf) form.querySelector('input[name=<?= esc(csrf_token(), 'attr') ?>]').value = body.csrf;
            if (!response.ok) throw new Error(body.error || 'O Google não confirmou o agendamento.');
            created = true; status(body.message);
            for (const [key, label] of [['event_url', 'Abrir no Google Agenda'], ['email_url', 'Ver reunião no e-mail']]) {
                if (!body[key]) continue;
                const link = document.createElement('a'); link.className = 'btn btn-outline-light'; link.href = body[key]; link.textContent = label;
                document.getElementById('meeting-links').append(link);
            }
        } catch (error) { status(error.message, true); }
        finally { busy.hidden = true; form.inert = false; form.setAttribute('aria-busy', 'false'); button.disabled = created; sending = created; }
    });
})();
</script>
