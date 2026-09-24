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
        return view('main', ['content' => view('kanban/form', ['item' => null])]);
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
            $saved = $id === null ? $model->createFor($userId, $data) : $model->updateFor($id, $userId, $data);
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