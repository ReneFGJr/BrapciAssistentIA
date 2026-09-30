<?php
namespace App\Controllers;

use App\Models\SubjectModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use Throwable;

class Subjects extends BaseController
{
    public function index(): string
    {
        return view('main', ['content' => view('tools/subjects/index', [
            'subjects' => (new SubjectModel())->forUser((array) session('auth_user')),
        ])]);
    }

    public function add(): string
    {
        return $this->form(null);
    }

    public function edit(int $id): string
    {
        return $this->form((new SubjectModel())->findOwned($id, (array) session('auth_user')));
    }

    private function form(?array $subject): string
    {
        return view('main', ['content' => view('tools/subjects/form', ['subject' => $subject])]);
    }

    public function create()
    {
        return $this->persist(null);
    }

    public function update(int $id)
    {
        return $this->persist($id);
    }

    private function persist(?int $id)
    {
        $name = $this->request->getPost('name');
        try {
            (new SubjectModel())->saveFor($id, is_string($name) ? $name : '', (array) session('auth_user'));
            return redirect()->to(site_url('tools/subjects'))->with('success', 'Assunto salvo com sucesso.');
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            $error = 'Não foi possível salvar o assunto.';
        }
        return redirect()->to(site_url('tools/subjects/' . ($id === null ? 'add' : $id . '/edit')))
            ->withInput()->with('error', $error);
    }

    public function delete(int $id)
    {
        try {
            (new SubjectModel())->deleteFor($id, (array) session('auth_user'));
            return redirect()->to(site_url('tools/subjects'))->with('success', 'Assunto excluído com sucesso.');
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return redirect()->to(site_url('tools/subjects'))->with('error', 'Não foi possível excluir o assunto.');
        }
    }
}
