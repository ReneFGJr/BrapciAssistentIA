<?php
namespace App\Controllers;

use App\Models\KanbanModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

class Kanban extends BaseController
{
    private function userId(): string
    {
        $id = (string) (session('auth_user')['id'] ?? '');
        if ($id === '') {
            throw PageNotFoundException::forPageNotFound();
        }
        return $id;
    }

    private function item(int $id): array
    {
        return (new KanbanModel())->owned($id, $this->userId())
            ?? throw PageNotFoundException::forPageNotFound('Cartão não encontrado.');
    }

    public function index(): string
    {
        $columns = array_fill_keys(array_keys(KanbanModel::STATUSES), []);
        foreach ((new KanbanModel())->forUser($this->userId()) as $item) {
            $columns[$item['status']][] = $item;
        }
        return view('main', ['content' => view('kanban/index', ['columns' => $columns])]);
    }

    public function new(): string
    {
        $source = $this->emailSource();
        $draft = $source === null ? [] : (new KanbanModel())->emailDraft($source[0], $source[1], (array) session('auth_user'));
        return view('main', ['content' => view('kanban/form', ['item' => null, 'draft' => $draft, 'emailSource' => $source])]);
    }

    private function emailSource(bool $post = false): ?array
    {
        $account = $post ? $this->request->getPost('email_account') : $this->request->getGet('email_account');
        $message = $post ? $this->request->getPost('email_message') : $this->request->getGet('email_message');
        if ($account === null && $message === null) return null;
        foreach ([$account, $message] as $value) {
            if (!is_string($value) || !ctype_digit($value) || (int) $value < 1) throw PageNotFoundException::forPageNotFound();
        }
        return [(int) $account, (int) $message];
    }

    public function edit(int $id): string
    {
        return view('main', ['content' => view('kanban/form', ['item' => $this->item($id)])]);
    }

    public function create()
    {
        return $this->save();
    }

    public function update(int $id)
    {
        $this->item($id);
        return $this->save($id);
    }

    private function save(?int $id = null)
    {
        $userId = $this->userId();
        $back = $id === null ? '/kanban/new' : '/kanban/' . $id . '/edit';
        $source = $id === null ? $this->emailSource(true) : null;
        if ($source !== null) {
            (new KanbanModel())->emailDraft($source[0], $source[1], (array) session('auth_user'));
            $back = site_url('kanban/new') . '?email_account=' . $source[0] . '&email_message=' . $source[1];
        }
        $data = [];
        foreach (['title', 'description', 'status', 'priority'] as $field) {
            $value = $this->request->getPost($field);
            if (! is_string($value)) {
                return redirect()->to($back)->with('error', 'Preencha os dados do cartão.');
            }
            $data[$field] = trim($value);
        }
        $model = new KanbanModel();
        try {
            $saved = $source !== null
                ? $model->createForEmail($source[0], $source[1], (array) session('auth_user'), $data)
                : ($id === null ? $model->createFor($userId, $data) : $model->updateFor($id, $userId, $data));
            if ($saved === false) {
                return redirect()->to($back)->withInput()->with('error', implode(' ', $model->errors()) ?: 'Não foi possível salvar o cartão.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Erro no kanban: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to($back)->withInput()->with('error', 'Não foi possível salvar o cartão.');
        }
        return redirect()->to('/kanban')->with('success', 'Cartão salvo.');
    }
}
