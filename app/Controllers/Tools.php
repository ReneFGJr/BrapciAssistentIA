<?php
namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Tools extends BaseController
{
    public function index(): string
    {
        return view('main', ['content' => view('tools/index')]);
    }

    public function show(string $tool): string
    {
        if (! in_array($tool, ['senha', 'cpf', 'barcode', 'qrcode'], true)) {
            throw PageNotFoundException::forPageNotFound('Ferramenta não encontrada.');
        }
        return view('main', ['content' => view(in_array($tool, ['barcode', 'qrcode'], true) ? 'tools/codes' : 'tools/generator', ['tool' => $tool])]);
    }
}