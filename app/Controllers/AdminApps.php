<?php

namespace App\Controllers;

use App\Models\ApplicationModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class AdminApps extends BaseController
{
    public function index(): string
    {
        return view('main', [
            'content' => view('dashboard/admin/apps', [
                'apps' => (new ApplicationModel())->getAllApps(),
            ]),
        ]);
    }

    public function create()
    {
        if (! $this->validateApp()) {
            return redirect()->to('/dashboard/admin')->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        (new ApplicationModel())->createApp($this->appData());

        return redirect()->to('/dashboard/admin')->with('success', 'Aplicativo criado com sucesso.');
    }

    public function update(int $id)
    {
        $model = new ApplicationModel();
        if ($model->getApp($id) === null) {
            throw PageNotFoundException::forPageNotFound('Aplicativo não encontrado.');
        }

        if (! $this->validateApp()) {
            return redirect()->to('/dashboard/admin')->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $model->updateApp($id, $this->appData());

        return redirect()->to('/dashboard/admin')->with('success', 'Aplicativo atualizado com sucesso.');
    }

    public function delete(int $id)
    {
        $model = new ApplicationModel();
        if ($model->getApp($id) === null) {
            throw PageNotFoundException::forPageNotFound('Aplicativo não encontrado.');
        }

        $model->deleteApp($id);

        return redirect()->to('/dashboard/admin')->with('success', 'Aplicativo excluído.');
    }

    private function validateApp(): bool
    {
        return $this->validate([
            'name' => 'required|max_length[120]',
            'icon' => 'required|max_length[100]|regex_match[/^bi-[a-z0-9-]+$/]',
            'url' => 'required|max_length[500]',
            'access_level' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[255]',
        ]);
    }

    private function appData(): array
    {
        return [
            'name' => trim((string) $this->request->getPost('name')),
            'icon' => trim((string) $this->request->getPost('icon')),
            'url' => trim((string) $this->request->getPost('url')),
            'access_level' => (int) $this->request->getPost('access_level'),
        ];
    }
}
