<section class="tools-page col-12 p-3 text-light" data-code-tool="<?= esc($tool, 'attr') ?>">
    <a href="<?= site_url('tools') ?>" class="btn btn-outline-light mb-3"><i class="bi bi-arrow-left" aria-hidden="true"></i> Ferramentas</a>
    <h1 class="h3 mb-4"><?= $tool === 'barcode' ? 'Gerador de Código de Barras' : 'Gerador de QR Code' ?></h1>
    <form id="code-form" class="tools-panel p-4 rounded">
        <?php if ($tool === 'barcode'): ?>
            <label for="barcode-format" class="form-label">Formato</label>
            <select id="barcode-format" class="form-select mb-3">
                <option value="CODE128">CODE128 — letras, números e símbolos</option>
                <option value="EAN13">EAN-13 — 12 ou 13 dígitos</option>
            </select>
        <?php endif; ?>
        <label for="code-content" class="form-label"><?= $tool === 'barcode' ? 'Conteúdo do código' : 'Texto ou endereço (URL)' ?></label>
        <textarea id="code-content" class="form-control mb-2" rows="3" maxlength="<?= $tool === 'barcode' ? 80 : 1500 ?>" required aria-describedby="code-help"></textarea>
        <p id="code-help" class="small"><?= $tool === 'barcode' ? 'CODE128: até 80 caracteres sem acentos. EAN-13: com 12 dígitos, o verificador é calculado; com 13, é validado.' : 'Até 1.500 caracteres. Textos muito extensos podem ultrapassar a capacidade do QR Code.' ?></p>
        <p class="small">O conteúdo é processado no navegador e não é enviado ao servidor.</p>
        <button type="submit" class="btn btn-info"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Gerar</button>
        <p id="code-status" class="mt-3 mb-0" role="status" aria-live="polite"></p>
        <noscript><p>Ative o JavaScript para gerar o código.</p></noscript>
    </form>
    <div id="code-result" class="tools-panel p-3 mt-3 rounded" hidden>
        <div class="code-preview mb-3"><canvas id="code-canvas" role="img" aria-label="Código gerado"></canvas></div>
        <a id="code-download" class="btn btn-outline-info" download="<?= $tool === 'barcode' ? 'codigo-de-barras.png' : 'qrcode.png' ?>"><i class="bi bi-download" aria-hidden="true"></i> Baixar PNG</a>
    </div>
</section>
<script src="<?= base_url($tool === 'barcode' ? 'assets/vendor/JsBarcode.all.min.js' : 'assets/vendor/qrcode.min.js') ?>" defer></script>
<script src="<?= base_url('assets/js/tool-codes.js') ?>" defer></script>