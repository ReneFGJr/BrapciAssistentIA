<section class="tools-page col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <a class="btn btn-outline-light mb-3" href="<?= site_url('tools') ?>">Ferramentas</a>
    <h1 class="h3 mb-4"><i class="bi bi-calendar-lock" aria-hidden="true"></i> Google Agenda particular</h1>
    <?php if ($configuration !== null): ?>
        <div class="table-responsive mb-4">
            <table class="table table-dark align-middle">
                <thead><tr><th>Serviço</th><th>E-mail informado</th><th>Conexão</th><th>Atualização</th><th>Ações</th></tr></thead>
                <tbody><tr>
                    <td>usergoogleSchedule</td><td><?= esc($configuration['email']) ?></td>
                    <td><?= $configuration['connected'] ? 'Autorização cadastrada' : 'Aguardando autorização' ?></td>
                    <td><?= esc($configuration['updated_at']) ?> UTC</td>
                    <td><form method="post" action="<?= site_url('tools/usergoogleSchedule/delete') ?>" onsubmit="return confirm('Excluir o serviço e os eventos armazenados localmente?');">
                        <?= csrf_field() ?><button class="btn btn-outline-danger btn-sm" title="Excluir serviço" aria-label="Excluir serviço"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </form></td>
                </tr></tbody>
            </table>
        </div>
        <div class="d-flex gap-2 mb-4">
            <form method="post" action="<?= site_url('tools/usergoogleSchedule/connect') ?>">
                <?= csrf_field() ?><button class="btn btn-info">Conectar com Google</button>
            </form>
            <?php if ($configuration['connected']): ?><a class="btn btn-outline-light" href="<?= site_url('schedule') ?>">Abrir agenda particular</a><?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-5">
            <form class="card bg-dark text-light border-secondary p-3" method="post" action="<?= site_url('tools/usergoogleSchedule') ?>">
                <?= csrf_field() ?>
                <h2 class="h5">Configurar serviço particular</h2>
                <label for="private-email" class="form-label">E-mail da conta Google</label>
                <input id="private-email" name="email" type="email" required maxlength="254" autocomplete="email" class="form-control mb-3" value="<?= esc($configuration['email'] ?? '', 'attr') ?>">
                <label for="private-client" class="form-label">Client ID OAuth</label>
                <input id="private-client" name="client_id" required maxlength="255" class="form-control mb-3" value="<?= esc($configuration['client_id'] ?? '', 'attr') ?>">
                <label for="private-secret" class="form-label">Client Secret</label>
                <input id="private-secret" name="client_secret" type="password" maxlength="512" autocomplete="new-password" class="form-control mb-2" <?= empty($configuration['has_secret']) ? 'required' : '' ?>>
                <p class="small">Deixe o segredo em branco para manter o atual. Credenciais e tokens são armazenados criptografados com a chave do sistema.</p>
                <p class="small">O e-mail sugere a conta no Google. A agenda exibida será a principal da conta que você autorizar. Alterar as credenciais exige nova conexão.</p>
                <button class="btn btn-info">Salvar configuração</button>
            </form>
        </div>
        <div class="col-lg-7">
            <section class="card bg-dark text-light border-secondary p-3">
                <h2 class="h5">Tutorial: conectar a agenda particular</h2>
                <ol>
                    <li>Acesse o <a class="link-info" href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a>, selecione seu projeto e ative a <strong>Google Calendar API</strong>.</li>
                    <li>Configure a tela de consentimento no <strong>Google Auth Platform</strong>. Se o aplicativo estiver em testes, inclua seu e-mail entre os usuários de teste.</li>
                    <li>Em <strong>Clientes / Credenciais</strong>, crie um <strong>ID do cliente OAuth 2.0</strong> do tipo <strong>Aplicativo da Web</strong>.</li>
                    <li>Adicione exatamente este URI aos <strong>URIs de redirecionamento autorizados</strong>:<br><code class="text-info" style="overflow-wrap:anywhere"><?= esc($redirectUri) ?></code></li>
                    <li>Copie o Client ID e o Client Secret para o formulário e salve.</li>
                    <li>Clique em <strong>Conectar com Google</strong>, selecione a conta e permita a leitura dos eventos. Depois, abra a agenda particular.</li>
                </ol>
                <?php if (!$validRedirect): ?><p><strong>Endereço local:</strong> o Google não aceita <code class="text-info">http://assistentia</code> como retorno OAuth. Disponibilize o sistema em <code class="text-info">http://localhost</code> (com a porta/caminho corretos) ou em um domínio HTTPS válido. Configure <code class="text-info">app.baseURL</code> no .env, acesse o sistema por esse endereço, entre novamente e cadastre no Google o URI atualizado mostrado acima.</p><?php endif; ?>
                <h3 class="h6">Produção: cip.brapci.inf.br/sudo</h3>
                <p>Ative HTTPS nesse domínio e configure no .env de produção <code class="text-info">app.baseURL = 'https://cip.brapci.inf.br/sudo/'</code>. Com <code class="text-info">app.indexPage = 'index.php'</code>, cadastre no Google o retorno <code class="text-info" style="overflow-wrap:anywhere">https://cip.brapci.inf.br/sudo/index.php/tools/usergoogleSchedule/callback</code>. O Google não aceita HTTP para esse domínio. O URI mostrado acima acompanha a configuração do ambiente atual.</p>
                <p>A permissão solicitada é somente de leitura dos eventos. A agenda exibe título, descrição, data, horário e local quando fornecidos pelo Google, e permite associar seus assuntos.</p>
                <p>Em aplicativos externos em modo de teste, a autorização pode expirar após sete dias; nesse caso, conecte novamente.</p>
                <p>Excluir o serviço remove as credenciais e os eventos locais, sem excluir reuniões no Google. Para revogar também a permissão na conta, use <a class="link-info" href="https://myaccount.google.com/connections" target="_blank" rel="noopener noreferrer">Conexões da conta Google</a>.</p>
                <a class="link-info" href="https://developers.google.com/identity/protocols/oauth2/web-server" target="_blank" rel="noopener noreferrer">Documentação OAuth do Google</a>
            </section>
        </div>
    </div>
</section>
