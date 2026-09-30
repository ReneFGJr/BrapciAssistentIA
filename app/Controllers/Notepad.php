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

        $persons = (new \App\Models\PersonModel())->whereIn('id',
            (new \App\Libraries\PersonAccessService())->accessibleIds((array) session('auth_user')))
            ->searchByName('')->findAll();
        $selectedPerson = null;
        foreach ($persons as $person) {
            if ((string) $person['id'] === (string) ($selected['person_id'] ?? '')) {
                $selectedPerson = $person;
                break;
            }
        }
        return view('main', [
            'content' => view('User/notepad', [
                'notes' => $notes,
                'selected' => $selected,
                'persons' => $persons,
                'selectedPerson' => $selectedPerson,
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
                trim((string) $this->request->getPost('content')),
                ...$this->meetingData()
            );

            return redirect()->to('/notepad?note=' . $id)->with('success', 'Anotação criada com sucesso.');
        } catch (Throwable $exception) {
            log_message('error', 'Erro ao criar anotação: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to('/notepad')->withInput()->with('error', $exception->getMessage());
        }
    }

    public function update(int $id)
    {
        if ((new UserNoteModel())->getNote($id, $this->userId()) === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        if (! $this->validateNote()) {
            return redirect()->to('/notepad?note=' . $id)->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        try {
            $updated = (new UserNoteModel())->updateNote(
                $id,
                $this->userId(),
                trim((string) $this->request->getPost('title')),
                trim((string) $this->request->getPost('content')),
                ...$this->meetingData()
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
            'person_id' => 'permit_empty|is_natural_no_zero',
            'meeting_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
        ]);
    }

    private function meetingData(): array
    {
        $personId = $this->request->getPost('person_id');
        $personId = empty($personId) ? null : (int) $personId;
        if ($personId !== null) {
            (new \App\Libraries\PersonAccessService())->access($personId, (array) session('auth_user'));
        }
        $meeting = (string) $this->request->getPost('meeting_at');
        return [$personId, $meeting === '' ? null : str_replace('T', ' ', $meeting) . ':00'];
    }

    private function userId(): string
    {
        return (string) session('auth_user')['id'];
    }
}
