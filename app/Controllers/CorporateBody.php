<?php
namespace App\Controllers;

use App\Libraries\RorLookup;
use App\Models\InstitutionModel;
use Throwable;

class CorporateBody extends BaseController
{
    public function index(): string
    {
        $model = new InstitutionModel();
        return view('main', ['content' => view('corporatebody/index', [
            'institutions' => $model->orderBy('name')->orderBy('id')->paginate(25),
            'pager' => $model->pager,
        ])]);
    }

    public function new(): string
    {
        $query = $this->request->getGet('q');
        $query = is_string($query) ? trim($query) : '';
        $id = $this->request->getGet('ror');
        $page = $this->request->getGet('page');
        $page = is_string($page) && ctype_digit($page) ? max(1, min(500, (int) $page)) : 1;
        $result = null;
        $prefill = [];
        $error = null;
        try {
            $ror = new RorLookup();
            if ($id !== null) {
                $prefill = $ror->find(is_string($id) ? $id : '');
            } elseif ($query !== '') {
                if (mb_strlen($query) > 200) {
                    throw new \InvalidArgumentException('Use até 200 caracteres na busca.');
                }
                $result = $ror->search($query, $page);
            }
        } catch (Throwable $exception) {
            log_message('error', 'Consulta ROR: {message}', ['message' => $exception->getMessage()]);
            $error = 'Não foi possível consultar o ROR. Tente novamente ou preencha o cadastro manualmente.';
        }
        return view('main', ['content' => view('corporatebody/new', compact('query', 'page', 'result', 'prefill', 'error'))]);
    }

    private function institution(int $id): array
    {
        return (new InstitutionModel())->find($id)
            ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Instituição não encontrada.');
    }

    public function show(int $id): string
    {
        return view('main', ['content' => view('corporatebody/show', ['institution' => $this->institution($id)])]);
    }

    public function edit(int $id): string
    {
        return view('main', ['content' => view('corporatebody/new', [
            'editing' => true, 'prefill' => $this->institution($id),
            'query' => '', 'page' => 1, 'result' => null, 'error' => null,
        ])]);
    }

    public function update(int $id)
    {
        $this->institution($id);
        return $this->save($id);
    }

    public function create()
    {
        return $this->save();
    }

    private function save(?int $id = null)
    {
        $model = new InstitutionModel();
        $data = [];
        foreach (['name', 'ror_id', 'acronym', 'address', 'city', 'state', 'country', 'country_code',
            'latitude', 'longitude', 'established_year'] as $field) {
            $value = $this->request->getPost($field);
            if ($value !== null && ! is_string($value)) {
                return redirect()->to($id === null ? '/corporatebody/new' : '/corporatebody/' . $id . '/edit')->with('error', 'Dados inválidos.');
            }
            $value = trim((string) $value);
            $data[$field] = $value === '' ? null : $value;
        }
        if ($data['country_code'] !== null) {
            $data['country_code'] = strtoupper($data['country_code']);
        }
        try {
            if ($data['ror_id'] !== null && $model->where('ror_id', $data['ror_id'])->where('id !=', $id ?? 0)->first() !== null) {
                return redirect()->to($id === null ? '/corporatebody/new' : '/corporatebody/' . $id . '/edit')->withInput()->with('error', 'Esta instituição já está cadastrada com este ROR.');
            }
            if (($id === null ? $model->insert($data) : $model->update($id, $data)) === false) {
                return redirect()->to($id === null ? '/corporatebody/new' : '/corporatebody/' . $id . '/edit')->withInput()->with('error', implode(' ', $model->errors()));
            }
        } catch (Throwable $exception) {
            log_message('error', 'Cadastro de instituição: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to($id === null ? '/corporatebody/new' : '/corporatebody/' . $id . '/edit')->withInput()->with('error', 'Não foi possível salvar a instituição. Confira se o ROR já está cadastrado.');
        }
        return redirect()->to($id === null ? '/corporatebody' : '/corporatebody/' . $id)->with('success', $id === null ? 'Instituição cadastrada.' : 'Instituição atualizada.');
    }
}