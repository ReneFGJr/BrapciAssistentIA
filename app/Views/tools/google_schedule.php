<section class="tools-page col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <a class="btn btn-outline-light mb-3" href="<?= site_url('tools') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Ferramentas</a>
    <h1 class="h3 mb-4"><i class="bi bi-calendar3" aria-hidden="true"></i> Google Agenda</h1>
    <section class="card bg-dark text-light border-secondary p-3 mb-4" aria-labelledby="registered-services-title">
        <h2 id="registered-services-title" class="h5">Serviços cadastrados</h2>
        <div class="table-responsive">
            <table class="table table-dark align-middle mb-0">
                <thead><tr><th>Serviço</th><th>Data de criação</th><th>Última atualização</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                    <?php if (empty($registeredServices)): ?><tr><td colspan="4">Nenhum serviço cadastrado para seu usuário.</td></tr><?php endif; ?>
                    <?php foreach ($registeredServices ?? [] as $registered): ?><tr>
                        <td><?= esc($registered['service'] === 'googleSchedule' ? 'Google Agenda (googleSchedule)' : $registered['service']) ?></td>
                        <td><?= esc(date('d/m/Y H:i', strtotime($registered['created_at']))) ?></td>
                        <td><?= esc(date('d/m/Y H:i', strtotime($registered['updated_at']))) ?></td>
                        <td class="text-end">
                            <form method="post" action="<?= site_url('tools/googleSchedule/services/' . $registered['id'] . '/delete') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir serviço" aria-label="<?= esc('Excluir serviço ' . $registered['service'], 'attr') ?>"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </form>
                        </td>
                    </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php if (!empty($configuration['has_key'])): ?><a class="btn btn-info mb-3" href="<?= site_url('schedule') ?>">Abrir agenda</a><?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-5">
            <form method="post" action="<?= site_url('tools/googleSchedule') ?>" class="card bg-dark text-light border-secondary p-3">
                <?= csrf_field() ?>
                <h2 class="h5">Configurar integração</h2>
                <label for="google-email" class="form-label">E-mail da conta Google</label>
                <input id="google-email" name="email" type="email" class="form-control mb-3" maxlength="254" autocomplete="email" required value="<?= esc(session()->getFlashdata('google_schedule_email') ?? $configuration['email'] ?? '', 'attr') ?>">
                <label for="google-api-key" class="form-label">API key</label>
                <input id="google-api-key" name="api_key" type="password" class="form-control mb-2" maxlength="512" autocomplete="new-password" spellcheck="false" <?= empty($configuration['has_key']) ? 'required' : '' ?> aria-describedby="google-key-help">
                <p id="google-key-help" class="small"><?= !empty($configuration['has_key']) ? 'Há uma chave salva. Deixe em branco para mantê-la ou informe outra para substituir.' : 'Cole a chave criada no Google Cloud Console.' ?></p>
                <p class="small">O e-mail e a chave são armazenados criptografados para seu usuário.</p>
                <button type="submit" class="btn btn-info">Salvar configuração</button>
            </form>
        </div>
        <div class="col-lg-7">
            <section class="card bg-dark text-light border-secondary p-3" aria-labelledby="google-tutorial">
                <h2 id="google-tutorial" class="h5">Tutorial: como obter sua API key</h2>
                <ol class="ps-4">
                    <li class="mb-2">Acesse o <a class="link-info" href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a> com sua conta Google e crie ou selecione um projeto.</li>
                    <li class="mb-2">Em <strong>APIs e serviços → Biblioteca</strong>, procure <strong>Google Calendar API</strong> e clique em <strong>Ativar</strong>.</li>
                    <li class="mb-2">Abra <strong>APIs e serviços → Credenciais → Criar credenciais → Chave de API</strong>.</li>
                    <li class="mb-2">Edite a chave e restrinja seu uso à <strong>Google Calendar API</strong>. Configure também a restrição de aplicativo conforme o ambiente que fará as chamadas.</li>
                    <li>Copie a chave, informe-a neste formulário junto com seu e-mail e salve.</li>
                </ol>
                <h3 class="h6">API key e autorização da agenda</h3>
                <p>API keys permitem acesso a dados públicos. Para consultar uma agenda privada ou criar eventos, é necessário autorizar a conta com OAuth 2.0. A tela Agenda consulta os eventos públicos usando o e-mail informado como ID da agenda. Para acesso privado, configure a ferramenta Google Agenda particular (usergoogleSchedule).</p>
                <p class="mb-0">Documentação: <a class="link-info" href="https://developers.google.com/workspace/guides/create-credentials" target="_blank" rel="noopener noreferrer">criar credenciais</a> e <a class="link-info" href="https://developers.google.com/workspace/calendar/api/quickstart/js" target="_blank" rel="noopener noreferrer">início rápido do Google Calendar</a>.</p>
            </section>
        </div>
    </div>
</section>
