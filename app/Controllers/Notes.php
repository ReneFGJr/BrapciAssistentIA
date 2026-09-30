<?php
namespace App\Controllers;
use App\Libraries\PersonAccessService;
use App\Models\NoteModel;
use App\Models\PersonModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;
class Notes extends BaseController
{
    private function actor(): array
    {
        return (array) session('auth_user');
    }
    public function index(): string
    {
        $model = new NoteModel();
        $notes = $model->visibleTo($this->actor())->paginate(25);
        $editableIds = (new PersonModel())->whereIn('id',
            (new PersonAccessService())->accessibleIds($this->actor())->where('access_level', 'edit'))->findColumn('id') ?? [];
        return view('main', ['content' => view('notes/index', [
            'notes' => $notes, 'participantsByNote' => $model->participantsFor(array_column($notes, 'id'), $this->actor()), 'pager' => $model->pager, 'editableIds' => $editableIds, 'actorId' => (string) $this->actor()['id'],
        ])]);
    }
    public function show(int $id): string
    {
        $model = new NoteModel();
        $note = $model->visibleTo($this->actor())->where('notes.id', $id)->first();
        if ($note === null) {
            throw PageNotFoundException::forPageNotFound('Nota não encontrada.');
        }
        $grant = empty($note['person_id']) ? ['access_level' => 'edit']
            : (new PersonAccessService())->access((int) $note['person_id'], $this->actor());
        return view('main', ['content' => view('notes/show', [
            'note' => $note,
            'canEdit' => $grant['access_level'] === 'edit',
            'participants' => $model->participantsFor([$id], $this->actor())[$id] ?? [],
            'categories' => (new \App\Models\SubjectModel())->forNote($id, $this->actor()),
            'tasks' => (new \App\Models\KanbanModel())->forNote($id, $this->actor()),
        ])]);
    }
    public function related(int $id, int $subjectId): string
    {
        $subjects = new \App\Models\SubjectModel();
        $model = $subjects->relatedNotes($subjectId, $id, $this->actor());
        $notes = $model->paginate(25);
        $category = array_values(array_filter($subjects->forNote($id, $this->actor()),
            static fn (array $subject): bool => (int) $subject['id'] === $subjectId))[0];
        return view('main', ['content' => view('notes/related', [
            'notes' => $notes, 'category' => $category, 'sourceId' => $id, 'pager' => $model->pager,
        ])]);
    }

    public function add(): string
    {
        return $this->form(null);
    }
    public function edit(int $id): string
    {
        return $this->form((new NoteModel())->editable($id, $this->actor()));
    }
    private function form(?array $note): string
    {
        $persons = (new PersonModel())->whereIn('id',
            (new PersonAccessService())->accessibleIds($this->actor())->where('access_level', 'edit'))
            ->searchByName('')->findAll();
        $participants = $note === null ? [] : ((new NoteModel())->participantsFor([$note['id']], $this->actor())[$note['id']] ?? []);
        $participantIds = array_column($participants, 'person_id');
        $availableParticipants = $note === null ? [] : (new PersonModel())->whereIn('id',
            (new PersonAccessService())->accessibleIds($this->actor()))->searchByName('')->findAll();
        $availableParticipants = array_values(array_filter($availableParticipants,
            static fn (array $person): bool => !in_array($person['id'], $participantIds)));
        $subjects = new \App\Models\SubjectModel();
        $categories = $note === null ? [] : $subjects->forNote((int) $note['id'], $this->actor());
        $selectedIds = array_column($categories, 'id');
        $availableCategories = $note === null ? [] : array_values(array_filter($subjects->forUser($this->actor()),
            static fn (array $subject): bool => !in_array($subject['id'], $selectedIds)));
        return view('main', ['content' => view('notes/form', [
            'note' => $note, 'persons' => $persons, 'participants' => $participants,
            'availableParticipants' => $availableParticipants,
            'categories' => $categories, 'availableCategories' => $availableCategories,
            'tasks' => $note === null ? [] : (new \App\Models\KanbanModel())->forNote((int) $note['id'], $this->actor()),
        ])]);
    }
    public function create()
    {
        return $this->persist();
    }
    public function update(int $id)
    {
        return $this->persist($id);
    }

    public function addParticipant(int $id)
    {
        $model = new NoteModel();
        $model->editable($id, $this->actor());
        $personId = $this->request->getPost('participant_id');
        if (!is_string($personId) || !ctype_digit($personId) || (int) $personId < 1) {
            return redirect()->to('/notes/' . $id . '/edit')->with('error', 'Selecione uma Person válida.');
        }
        return $this->participantResult($id, fn () => $model->addParticipant($id, (int) $personId, $this->actor()));
    }
    public function searchParticipants(int $id)
    {
        (new NoteModel())->editable($id, $this->actor());
        $query = $this->request->getGet('q');
        if (!is_string($query) || mb_strlen(trim($query)) < 2 || mb_strlen($query) > 255) {
            return $this->response->setJSON([]);
        }
        $model = new PersonModel();
        $model->select('id, full_name, nickname')->whereIn('id',
            (new PersonAccessService())->accessibleIds($this->actor()));
        $existing = db_connect()->table('note_participants')->select('person_id')->where('note_id', $id);
        $model->whereNotIn('id', $existing)->searchByWords($query);
        return $this->response->setJSON($model->orderBy('full_name')->orderBy('id')->findAll(20));
    }
    public function removeParticipant(int $id, int $personId)
    {
        return $this->participantResult($id, fn () => (new NoteModel())->removeParticipant($id, $personId, $this->actor()));
    }
    private function participantResult(int $id, callable $operation)
    {
        try {
            if ($operation()) {
                return redirect()->to('/notes/' . $id . '/edit')->with('success', 'Participantes atualizados.');
            }
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            log_message('error', 'Erro ao atualizar participantes: {message}', ['message' => $exception->getMessage()]);
        }
        return redirect()->to('/notes/' . $id . '/edit')->with('error', 'Não foi possível atualizar os participantes.');
    }

    public function addCategory(int $id)
    {
        return $this->categoryResult($id, function () use ($id) {
            $subjectId = $this->request->getPost('subject_id');
            if (!is_string($subjectId) || !ctype_digit($subjectId) || (int) $subjectId < 1) {
                throw new \InvalidArgumentException('Selecione uma categoria.');
            }
            return (new \App\Models\SubjectModel())->attach($id, (int) $subjectId, $this->actor());
        });
    }
    public function createCategory(int $id)
    {
        return $this->categoryResult($id, function () use ($id) {
            $name = $this->request->getPost('category_name');
            if (!is_string($name)) {
                throw new \InvalidArgumentException('Informe o nome da categoria.');
            }
            return (new \App\Models\SubjectModel())->createAndAttach($id, $name, $this->actor());
        });
    }
    public function removeCategory(int $id, int $subjectId)
    {
        return $this->categoryResult($id,
            fn () => (new \App\Models\SubjectModel())->detach($id, $subjectId, $this->actor()));
    }
    private function categoryResult(int $id, callable $operation)
    {
        (new NoteModel())->editable($id, $this->actor());
        try {
            if ($operation()) {
                return redirect()->to('/notes/' . $id . '/edit')->with('success', 'Categorias atualizadas.');
            }
            $error = 'Não foi possível atualizar as categorias.';
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (\InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            log_message('error', 'Erro ao atualizar categorias: {message}', ['message' => $exception->getMessage()]);
            $error = 'Não foi possível atualizar as categorias.';
        }
        return redirect()->to('/notes/' . $id . '/edit')->withInput()->with('error', $error);
    }

    public function createTask(int $id)
    {
        (new NoteModel())->editable($id, $this->actor());
        $data = [];
        foreach (['title', 'description', 'status', 'priority'] as $field) {
            $value = $this->request->getPost('task_' . $field);
            $data[$field] = is_string($value) ? trim($value) : '';
        }
        $model = new \App\Models\KanbanModel();
        try {
            if ($model->createForNote($id, $this->actor(), $data) !== false) {
                return redirect()->to('/notes/' . $id . '/edit')->with('success', 'Tarefa adicionada ao Kanban.');
            }
            $error = implode(' ', $model->errors()) ?: 'Não foi possível criar a tarefa.';
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            log_message('error', 'Erro ao criar tarefa da nota: {message}', ['message' => $exception->getMessage()]);
            $error = 'Não foi possível criar a tarefa.';
        }
        return redirect()->to('/notes/' . $id . '/edit')->withInput()
            ->with('note_task_form', true)->with('error', $error);
    }

    private function persist(?int $id = null)
    {
        $model = new NoteModel();
        $data = [];
        foreach (['meeting_date', 'meeting_time', 'title', 'description', 'status'] as $field) {
            $value = $this->request->getPost($field);
            $data[$field] = is_string($value) ? trim($value) : '';
        }
        try {
            if ($model->saveFor($this->actor(), $data, $id)) {
                return redirect()->to('/notes')->with('success', 'Notas salvas com sucesso.');
            }
            $error = implode(' ', $model->errors()) ?: 'Não foi possível salvar as notas.';
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            log_message('error', 'Erro ao salvar notas: {message}', ['message' => $exception->getMessage()]);
            $error = 'Não foi possível salvar as notas. Tente novamente.';
        }
        return redirect()->to($id === null ? '/notes/add' : '/notes/' . $id . '/edit')
            ->withInput()->with('error', $error);
    }
}
