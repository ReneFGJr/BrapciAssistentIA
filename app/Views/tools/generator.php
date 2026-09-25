<section class="tools-page col-12 p-3 text-light" data-generator="<?= esc($tool, 'attr') ?>">
    <a href="<?= site_url('tools') ?>" class="btn btn-outline-light mb-3"><i class="bi bi-arrow-left" aria-hidden="true"></i> Ferramentas</a>
    <h1 class="h3 mb-4"><?= $tool === 'senha' ? 'Gerador de Senha' : 'Gerador de CPF (Fake)' ?></h1>
    <form class="tools-panel p-4 rounded" id="generator-form">
        <?php if ($tool === 'senha'): ?>
            <label class="form-label" for="password-length">Quantidade de caracteres</label>
            <input class="form-control mb-3" id="password-length" type="number" min="8" max="128" value="20" required>
            <fieldset class="mb-3">
                <legend class="h6">Incluir caracteres</legend>
                <?php foreach (['upper' => 'Letras maiúsculas', 'lower' => 'Letras minúsculas', 'digits' => 'Números', 'symbols' => 'Símbolos'] as $key => $label): ?>
                    <div class="form-check">
                        <input class="form-check-input" id="option-<?= $key ?>" type="checkbox" data-character-group="<?= $key ?>" checked>
                        <label class="form-check-label" for="option-<?= $key ?>"><?= esc($label) ?></label>
                    </div>
                <?php endforeach; ?>
            </fieldset>
            <p class="small">A senha é gerada no navegador e não é enviada ao servidor.</p>
        <?php else: ?>
            <p>CPF fictício para testes, com dígitos verificadores válidos. Não representa uma identidade e pode coincidir com um CPF existente.</p>
            <div class="form-check mb-3">
                <input class="form-check-input" id="cpf-formatted" type="checkbox" checked>
                <label class="form-check-label" for="cpf-formatted">Formatar como 000.000.000-00</label>
            </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-info mb-3"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Gerar</button>
        <label for="generator-result" class="form-label d-block">Resultado</label>
        <div class="d-flex gap-2">
            <input class="form-control" id="generator-result" type="text" readonly autocomplete="off" spellcheck="false">
            <button type="button" id="generator-copy" class="btn btn-outline-info" title="Copiar resultado" aria-label="Copiar resultado" disabled><i class="bi bi-clipboard" aria-hidden="true"></i></button>
        </div>
        <p class="mt-3 mb-0" id="generator-status" role="status" aria-live="polite"></p>
        <noscript><p class="mt-3">Ative o JavaScript para usar esta ferramenta.</p></noscript>
    </form>
</section>
<script src="<?= base_url('assets/js/tools.js') ?>" defer></script>