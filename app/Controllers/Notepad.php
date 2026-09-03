<?php

namespace App\Controllers;

use App\Models\UserNoteModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

class Notepad extends BaseController
{
    public function index(): string
    {
        $model = new UserNoteModel();
        $userId = $this->userId();

        try {
            $notes = $model->getNotesByUser($userId);
            $selectedId = (int) $this->request->getGet('note');
            $selected = $selectedId > 0
                ? $model->getNote($selectedId, $userId)
                : ($notes[0] ?? null);

            if ($selectedId > 0 && $selected === null) {
                throw PageNotFoundException::forPageNotFound('Anotação não encontrada.');
            }
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            log_message('error', 'Erro ao carregar anotações: {message}', ['message' => $exception->getMessage()]);
            $notes = [];
            $selected = null;
            session()->setFlashdata('error', $exception->getMessage());
        }

        return view('main', [
            'content' => view('User/notepad', [
                'notes' => $notes,
                'selected' => $selected,
            ]),
        ]);
    }

    public function create()
    {
        if (! $this->validateNote()) {
            return redirect()->to('/notepad')->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        try {
            $id = (new UserNoteModel())->createNote(
                $this->userId(),
                trim((string) $this->request->getPost('title')),
                trim((string) $this->request->getPost('content'))
            );

            return redirect()->to('/notepad?note=' . $id)->with('success', 'Anotação criada com sucesso.');
        } catch (Throwable $exception) {
            log_message('error', 'Erro ao criar anotação: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to('/notepad')->withInput()->with('error', $exception->getMessage());
        }
    }

    public function update(int $id)
    {
        if (! $this->validateNote()) {
            return redirect()->to('/notepad?note=' . $id)->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        try {
            $updated = (new UserNoteModel())->updateNote(
                $id,
                $this->userId(),
                trim((string) $this->request->getPost('title')),
                trim((string) $this->request->getPost('content'))
            );

            if (! $updated) {
                throw PageNotFoundException::forPageNotFound('Anotação não encontrada.');
            }

            return redirect()->to('/notepad?note=' . $id)->with('success', 'Anotação atualizada com sucesso.');
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            log_message('error', 'Erro ao atualizar anotação: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to('/notepad?note=' . $id)->withInput()->with('error', $exception->getMessage());
        }
    }

    public function delete(int $id)
    {
        if (! (new UserNoteModel())->deleteNote($id, $this->userId())) {
            throw PageNotFoundException::forPageNotFound('Anotação não encontrada.');
        }

        return redirect()->to('/notepad')->with('success', 'Anotação excluída.');
    }

    private function validateNote(): bool
    {
        return $this->validate([
            'title' => 'required|max_length[150]',
            'content' => 'permit_empty|max_length[50000]',
        ]);
    }

    private function userId(): string
    {
        return (string) session('auth_user')['id'];
    }
}
