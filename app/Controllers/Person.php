<?php

namespace App\Controllers;

use App\Libraries\PersonAccessService;
use App\Models\PersonModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use Throwable;

class Person extends BaseController
{
    private function actor(): array
    {
        return (array) session('auth_user');
    }

    public function index(): string
    {
        $query = $this->request->getGet('q');
        $search = is_string($query) ? trim($query) : '';
        $model = new PersonModel();
        $persons = $model->whereIn('id', (new PersonAccessService())->accessibleIds($this->actor()))
            ->searchByName($search)->paginate(25);
        return view('main', ['content' => view('person/index', [
            'persons' => $persons, 'search' => $search, 'pager' => $model->pager,
        ])]);
    }

    public function import()
    {
        $file = $this->request->getFile('contacts_csv');
        if ($file === null || ! $file->isValid() || $file->hasMoved()
            || strtolower(pathinfo($file->getClientName(), PATHINFO_EXTENSION)) !== 'csv'
            || $file->getSize() === 0 || $file->getSize() > 5 * 1024 * 1024) {
            return redirect()->to('/person')->with('error', 'Selecione um arquivo CSV válido de até 5 MB (respeitando também o limite de upload do servidor).');
        }
        try {
            $result = (new \App\Libraries\PersonImportService())->import($file->getTempName(), $this->actor());
            return redirect()->to('/person')->with('success', sprintf(
                'Importação: %d novos, %d existentes, %d inválidos. Fotos: %d salvas, %d indisponíveis e %d pendentes (envie o mesmo CSV novamente para continuar). %d contatos com correspondência ambígua.',
                $result['imported'], $result['duplicates'], $result['invalid'], $result['photos'], $result['photo_errors'], $result['photo_pending'], $result['ambiguous']
            ));
        } catch (Throwable $exception) {
            log_message('error', 'Erro ao importar pessoas: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to('/person')->with('error', 'Não foi possível importar. Verifique o formato do arquivo CSV enviado e tente novamente.');
        }
    }

    public function photo(int $id)
    {
        $service = new PersonAccessService();
        $service->access($id, $this->actor(), true);
        $file = $this->request->getFile('photo');
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to('/person/' . $id)->with('error', 'Selecione uma fotografia válida de até 5 MB.');
        }
        $directory = FCPATH . 'repository/photo';
        $name = null;
        try {
            $name = \App\Libraries\PersonPhoto::save($file->getTempName(), $directory);
            if (! $service->update($id, ['photo' => $name], $this->actor())) {
                throw new \RuntimeException('Falha ao atualizar a fotografia.');
            }
        } catch (Throwable $exception) {
            if ($name !== null && is_file($directory . '/' . $name)) {
                unlink($directory . '/' . $name);
            }
            log_message('error', 'Erro ao salvar fotografia: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to('/person/' . $id)->with('error', $exception instanceof InvalidArgumentException
                ? $exception->getMessage() : 'Não foi possível salvar a fotografia.');
        }
        return redirect()->to('/person/' . $id)->with('success', 'Fotografia atualizada.');
    }

    public function show(int $id): string
    {
        $service = new PersonAccessService();
        $grant = $service->access($id, $this->actor());
        $person = $this->person($id);
        $isOwner = $grant['user_own'] === (string) $this->actor()['id'];
        return view('main', ['content' => view('person/show', [
            'person' => $person, 'canEdit' => $grant['access_level'] === 'edit',
            'isOwner' => $isOwner, 'shares' => $isOwner ? $service->shares($id, $this->actor()) : [],
        ])]);
    }

    public function new(): string
    {
        if (session()->getFlashdata('person_form')) {
            return view('main', ['content' => $this->personForm( ['person' => null])]);
        }
        $value = $this->request->getGet('q');
        $query = is_string($value) ? trim($value) : '';
        $error = null;
        $result = null;
        if ($value !== null && ($query === '' || mb_strlen($query) > 254)) {
            $error = 'Informe um nome ou e-mail com até 254 caracteres.';
        } elseif ($query !== '') {
            try {
                $lookup = new \App\Libraries\BrapciUserLookup();
                $page = $this->request->getGet('page');
                $result = $lookup->search($query, is_scalar($page) ? max(1, (int) $page) : 1);
                $source = $this->request->getGet('source');
                $selected = null;
                if ($source !== null) {
                    $selected = is_string($source) && ctype_digit($source)
                        ? $lookup->findMatch($query, $source) : null;
                    if ($selected === null) {
                        throw PageNotFoundException::forPageNotFound('Cadastro não encontrado nesta busca.');
                    }
                } elseif ($result['total'] === 1) {
                    $selected = $result['users'][0];
                }
                if ($selected !== null || $result['total'] === 0) {
                    return view('main', ['content' => $this->personForm( [
                        'person' => null,
                        'prefill' => $selected !== null ? $lookup::prefill($selected) : $lookup::fromQuery($query),
                        'lookupMessage' => $selected !== null
                            ? 'Cadastro localizado. Confira os dados antes de salvar.'
                            : 'Nenhum cadastro encontrado. Complete os dados para cadastrar a pessoa.',
                    ])]);
                }
            } catch (PageNotFoundException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                log_message('error', 'Erro na busca de usuários Brapci: {message}', ['message' => $exception->getMessage()]);
                $error = 'Não foi possível consultar os cadastros. Tente novamente.';
                $result = null;
            }
        }
        return view('main', ['content' => view('person/lookup', [
            'query' => $query, 'result' => $result, 'error' => $error,
        ])]);
    }

    public function edit(int $id): string
    {
        (new PersonAccessService())->access($id, $this->actor(), true);
        return view('main', ['content' => $this->personForm( ['person' => $this->person($id)])]);
    }

    public function create()
    {
        $data = $this->personData();
        if ($data === null) {
            return redirect()->to('/person/new')->withInput()->with('person_form', true)->with('error', implode(' ', $this->validator->getErrors()));
        }
        try {
            $id = (new PersonAccessService())->create($data, $this->actor());
            return redirect()->to('/person/' . $id)->with('success', 'Pessoa cadastrada com sucesso.');
        } catch (Throwable $exception) {
            log_message('error', 'Erro ao criar pessoa: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to('/person/new')->withInput()->with('person_form', true)->with('error', 'Não foi possível salvar o cadastro.');
        }
    }

    public function update(int $id)
    {
        $service = new PersonAccessService();
        $service->access($id, $this->actor(), true);
        $data = $this->personData();
        if ($data === null) {
            return redirect()->to('/person/' . $id . '/edit')->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }
        if (! $service->update($id, $data, $this->actor())) {
            return redirect()->to('/person/' . $id . '/edit')->withInput()->with('error', 'Não foi possível salvar.');
        }
        return redirect()->to('/person/' . $id)->with('success', 'Cadastro atualizado.');
    }

    public function share(int $id)
    {
        $service = new PersonAccessService();
        $service->access($id, $this->actor(), false, true);
        if (! $this->validate([
            'email' => 'required|valid_email|max_length[254]',
            'access_level' => 'required|in_list[read,edit]',
            'expires_at' => 'permit_empty|max_length[16]',
        ])) {
            return redirect()->to('/person/' . $id)->withInput()->with('error', 'Confira o e-mail, o nível e a expiração.');
        }
        try {
            $service->share($id, $this->actor(), (string) $this->request->getPost('email'),
                (string) $this->request->getPost('access_level'), (string) $this->request->getPost('expires_at'));
        } catch (InvalidArgumentException $exception) {
            return redirect()->to('/person/' . $id)->withInput()->with('error', $exception->getMessage());
        }
        return redirect()->to('/person/' . $id)->with('success', 'Acesso compartilhado.');
    }

    public function revoke(int $id, int $grantId)
    {
        (new PersonAccessService())->revoke($id, $grantId, $this->actor());
        return redirect()->to('/person/' . $id)->with('success', 'Acesso revogado.');
    }

    private function person(int $id): array
    {
        $person = (new PersonModel())->find($id);
        if ($person === null) {
            throw PageNotFoundException::forPageNotFound('Pessoa não encontrada.');
        }
        $institution = empty($person['institution_id']) ? null : (new \App\Models\InstitutionModel())->find($person['institution_id']);
        $person['institution'] = $institution['name'] ?? null;
        return $person;
    }

    private function personForm(array $data): string
    {
        $data['institutions'] = (new \App\Models\InstitutionModel())->orderBy('name')->orderBy('id')->findAll();
        $name = trim((string) ($data['prefill']['institution'] ?? ''));
        if ($name !== '') {
            $matches = array_values(array_filter($data['institutions'],
                static fn (array $institution): bool => mb_strtolower(trim($institution['name'])) === mb_strtolower($name)));
            if (count($matches) === 1) {
                $data['prefill']['institution_id'] = $matches[0]['id'];
            }
        }
        return view('person/form', $data);
    }

    private function personData(): ?array
    {
        $rules = [
            'nickname' => 'required|max_length[150]', 'full_name' => 'required|max_length[255]',
            'cpf' => 'permit_empty|max_length[14]', 'phone_1' => 'permit_empty|max_length[30]',
            'phone_2' => 'permit_empty|max_length[30]', 'email_1' => 'permit_empty|valid_email|max_length[254]',
            'email_2' => 'permit_empty|valid_email|max_length[254]', 'institution_id' => 'permit_empty|is_natural_no_zero|is_not_unique[institutions.id]',
        ];
        $data = [];
        foreach ($rules as $field => $rule) {
            $value = $this->request->getPost($field);
            $data[$field] = is_string($value) ? trim($value) : $value;
        }
        return $this->validateData($data, $rules) ? $data : null;
    }
}