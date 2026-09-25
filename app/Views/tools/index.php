<section class="tools-page col-12 p-3 text-light">
    <h1 class="h3 mb-4">Ferramentas</h1>
    <div class="row g-3">
        <?php foreach ([
            'senha' => ['Gerador de Senha', 'bi-key', 'Crie uma senha aleatória com tamanho e caracteres personalizados.'],
            'cpf' => ['Gerador de CPF (Fake)', 'bi-person-vcard', 'Gere um CPF fictício com dígitos verificadores para testes de formulários.'],
            'barcode' => ['Gerador de Código de Barras', 'bi-upc', 'Gere códigos CODE128 ou EAN-13 e baixe a imagem.'],
            'qrcode' => ['Gerador de QR Code', 'bi-qr-code', 'Transforme um texto ou endereço em QR Code.'],
        ] as $slug => [$title, $icon, $description]): ?>
            <div class="col-md-6">
                <a href="<?= site_url('tools/' . $slug) ?>" class="tools-tile d-block h-100 p-4 rounded">
                    <h2 class="h5"><i class="bi <?= esc($icon, 'attr') ?>" aria-hidden="true"></i> <?= esc($title) ?></h2>
                    <p class="mb-0"><?= esc($description) ?></p>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>