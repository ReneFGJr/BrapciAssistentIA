<?php
namespace App\Controllers;

use App\Models\EmailUserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use Throwable;

class Email extends BaseController
{
    public function prefer(int $id)
    {
        try {
            (new EmailUserModel())->setPreferred($id, (array) session('auth_user'));
            return redirect()->to(site_url('email/config'))->with('success', 'Conta preferencial definida. Mensagens repetidas serão exibidas por essa conta.');
        } catch (PageNotFoundException $e) { throw $e;
        } catch (Throwable $e) {
            return redirect()->to(site_url('email/config'))->with('error', 'Não foi possível definir a conta preferencial.');
        }
    }

    public function attachments(int $accountId, int $messageId)
    {
        try {
            $actor = (array) session('auth_user');
            $message = (new \App\Models\EmailUserRowModel())->findOwned($accountId, $messageId, $actor);
            $account = (new EmailUserModel())->connection($accountId, $actor);
            $files = (new \App\Libraries\EmailAttachments())->fetch($account, $message);
            return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['files' => $files]);
        } catch (PageNotFoundException $e) { throw $e;
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setHeader('Cache-Control', 'no-store')->setJSON(['error' => 'Não foi possível consultar os anexos. Confira se a conta está ativa e a mensagem ainda existe no servidor.']);
        }
    }

    public function attachmentPreview(int $accountId, int $messageId, string $section)
    {
        try {
            if (!preg_match('/^[1-9][0-9]*(?:\.[1-9][0-9]*)*$/D', $section)) throw PageNotFoundException::forPageNotFound();
            $actor = (array) session('auth_user');
            $message = (new \App\Models\EmailUserRowModel())->findOwned($accountId, $messageId, $actor);
            $account = (new EmailUserModel())->connection($accountId, $actor);
            $file = (new \App\Libraries\EmailAttachments())->fetch($account, $message, $section);
            $mime = \App\Libraries\EmailAttachmentPreview::mime($file['name'], $file['content']);
            if ($mime === null) return $this->response->setStatusCode(415)->setHeader('Cache-Control', 'no-store')->setJSON(['error' => 'Este formato não possui prévia. Use o botão de download para abrir no seu computador.']);
            return $this->response->setContentType($mime)->setHeader('Cache-Control', 'private, no-store')
                ->setHeader('X-Content-Type-Options', 'nosniff')->setHeader('Content-Security-Policy', "sandbox; default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; frame-ancestors 'self'")
                ->setBody($file['content']);
        } catch (PageNotFoundException $e) { throw $e;
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setHeader('Cache-Control', 'no-store')->setJSON(['error' => 'Não foi possível carregar o anexo. Confira a conexão com o servidor.']);
        }
    }

    public function attachmentDownload(int $accountId, int $messageId, string $section)
    {
        try {
            if (!preg_match('/^[1-9][0-9]*(?:\.[1-9][0-9]*)*$/D', $section)) throw PageNotFoundException::forPageNotFound();
            $actor = (array) session('auth_user');
            $message = (new \App\Models\EmailUserRowModel())->findOwned($accountId, $messageId, $actor);
            $account = (new EmailUserModel())->connection($accountId, $actor);
            $file = (new \App\Libraries\EmailAttachments())->fetch($account, $message, $section);
            return $this->response->download($file['name'], $file['content'])->setContentType('application/octet-stream')->setHeader('Cache-Control', 'private, no-store')->setHeader('X-Content-Type-Options', 'nosniff');
        } catch (PageNotFoundException $e) { throw $e;
        } catch (Throwable $e) {
            return redirect()->to(site_url('email/account/' . $accountId))->with('error', 'Não foi possível baixar o anexo. Confira a conexão e se a mensagem ainda está no servidor. Limite por anexo: 20 MB.');
        }
    }

    public function sendMessage(int $accountId, int $messageId)
    {
        try {
            $actor = (array) session('auth_user');
            $message = (new \App\Models\EmailUserRowModel())->findOwned($accountId, $messageId, $actor);
            $action = $this->request->getPost('action');
            $reply = $this->request->getPost('reply');
            $account = (new EmailUserModel())->connection($accountId, $actor);
            (new \App\Libraries\EmailOutgoing())->send($message, $actor, is_string($action) ? $action : '', is_string($reply) ? $reply : '', $account);
            return redirect()->to(site_url('email/account/' . $accountId))->with('success', 'E-mail enviado com sucesso pela conta ' . $account['email_user'] . '.');
        } catch (PageNotFoundException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Não foi possível confirmar o envio. Confira as configurações SMTP da conta de e-mail.';
        }
        $draft = $this->request->getPost('reply');
        return redirect()->to(site_url('email/account/' . $accountId))->with('error', $error)
            ->with('email_reply_draft', ['id' => $messageId, 'text' => is_string($draft) ? mb_substr($draft, 0, 50000) : '']);
    }

    public function saveFilter(int $id = 0)
    {
        if ($id !== 0) (new EmailUserModel())->findOwned($id, (array) session('auth_user'));
        $filter = $this->request->getPost('filter');
        if (!is_string($filter) || !in_array($filter, ['all', '0', '2', '3'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['csrf' => csrf_hash()]);
        }
        session()->set('email_inbox_filter', $filter);
        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['csrf' => csrf_hash()]);
    }

    public function markRead(int $accountId, int $messageId)
    {
        $status = (new \App\Models\EmailUserRowModel())->markRead($accountId, $messageId, (array) session('auth_user'));
        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['status' => $status, 'csrf' => csrf_hash()]);
    }

    public function account(int $id = 0): string
    {
        $this->response->setHeader('Cache-Control', 'no-store')->setHeader('Referrer-Policy', 'no-referrer');
        $actor = (array) session('auth_user');
        $accounts = (new EmailUserModel())->forUser($actor);
        $account = $id === 0
            ? ['id' => 0, 'email_user' => '', 'status' => 0, 'protocol' => 'imap']
            : (new EmailUserModel())->findOwned($id, $actor);
        $messages = (new \App\Models\EmailUserRowModel())->unified($actor);
        return view('main', ['content' => view('email/account', ['account' => $account, 'accounts' => $accounts, 'messages' => $messages])]);
    }

    public function sync(int $id)
    {
        $stage = 'ambiente PHP';
        try {
            if (!function_exists('imap_open')) throw new \App\Exceptions\EmailInboxException('A extensão IMAP não está disponível no PHP que atende o site em produção. Habilite-a nesse PHP e reinicie o serviço web/PHP.');
            $actor = (array) session('auth_user');
            $stage = 'leitura das credenciais';
            $account = (new EmailUserModel())->connection($id, $actor);
            $stage = 'consulta e importação';
            $count = (new \App\Libraries\EmailInbox())->sync($account, $actor, new \App\Models\EmailUserRowModel());
            if ($this->request->isAJAX()) return $this->response->setJSON(['count' => $count, 'csrf' => csrf_hash()]);
            return redirect()->to(site_url('email/account/' . $id))->with('success', 'Consulta concluída: ' . $count . ' mensagem(ns) importada(s). Atualize novamente para buscar mais.');
        } catch (PageNotFoundException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (\App\Exceptions\EmailInboxException $e) {
            $error = $e->getMessage();
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            log_message('error', 'Email sync account {account}, stage {stage}, exception {type}, code {code}, at {file}:{line}.', ['account' => $id, 'stage' => $stage, 'type' => get_class($e), 'code' => $e->getCode(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
            $error = 'Falha no banco de dados de produção. Verifique a conexão, as permissões e aplique as migrações pendentes com php spark migrate.';
        } catch (Throwable $e) {
            log_message('error', 'Email sync account {account}, stage {stage}, exception {type}, code {code}, at {file}:{line}.', ['account' => $id, 'stage' => $stage, 'type' => get_class($e), 'code' => $e->getCode(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
            $error = $stage === 'leitura das credenciais'
                ? 'Não foi possível ler a senha da conta. Verifique se encryption.key em produção é a chave original usada para criptografar as contas, ou cadastre novamente a senha neste ambiente.'
                : 'Falha na etapa: ' . $stage . '. Consulte o registro de erros em writable/logs no servidor de produção. Mensagens já importadas foram preservadas.';
        }
        if ($this->request->isAJAX()) return $this->response->setStatusCode(502)->setJSON(['error' => $error, 'csrf' => csrf_hash()]);
        return redirect()->to(site_url('email/account/' . $id))->with('error', $error);
    }

    public function messageDelete(int $accountId, int $messageId)
    {
        try {
            $actor = (array) session('auth_user');
            $model = new \App\Models\EmailUserRowModel();
            $message = $model->findOwned($accountId, $messageId, $actor);
            $account = (new EmailUserModel())->connection($accountId, $actor);
            $deletedFromServer = (new \App\Libraries\EmailMessageDeletion())->delete($account, $message);
            $model->deleteFor($accountId, $messageId, $actor);
            return redirect()->to(site_url('email/account/' . $accountId))->with('success', $deletedFromServer
                ? 'Mensagem excluída do servidor IMAP e do sistema.'
                : 'A mensagem já não estava na caixa de entrada do servidor. A cópia local foi removida.');
        } catch (PageNotFoundException $e) {
            throw $e;
        } catch (\RuntimeException | InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Não foi possível concluir a exclusão. A cópia local foi mantida.';
        }
        return redirect()->to(site_url('email/account/' . $accountId))->with('error', $error);
    }

    public function messageStatus(int $accountId, int $messageId)
    {
        try {
            $status = $this->request->getPost('email_status');
            (new \App\Models\EmailUserRowModel())->changeStatus($accountId, $messageId, is_string($status) ? $status : '', (array) session('auth_user'));
            return redirect()->to(site_url('email/account/' . $accountId))->with('success', 'Status atualizado.');
        } catch (PageNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            return redirect()->to(site_url('email/account/' . $accountId))->with('error', 'Não foi possível alterar o status.');
        }
    }

    public function index(): string
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        return view('main', ['content' => view('email/index', ['accounts' => (new EmailUserModel())->forUser((array) session('auth_user'))])]);
    }

    public function edit(int $id = 0): string
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        $account = $id === 0 ? null : (new EmailUserModel())->findOwned($id, (array) session('auth_user'));
        return view('main', ['content' => view('email/form', ['account' => $account])]);
    }

    public function save(int $id = 0)
    {
        $values = [];
        foreach (['email_user', 'pass_user', 'mail_user', 'mail_port_user', 'protocol', 'security', 'status', 'smtp_host', 'smtp_port', 'smtp_security'] as $field) $values[$field] = $this->request->getPost($field);
        try {
            (new EmailUserModel())->saveFor($id === 0 ? null : $id, $values, (array) session('auth_user'));
            return redirect()->to(site_url('email/config'))->with('success', 'Conta de e-mail salva com sucesso.');
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            $error = 'Não foi possível salvar a conta de e-mail.';
        }
        unset($values['pass_user']);
        // Preserve only non-secret scalar fields; never flash the submitted password.
        $values = array_filter($values, 'is_string');
        return redirect()->to(site_url('email/edit/' . $id))->with('email_values', $values)->with('error', $error);
    }

    public function delete(int $id)
    {
        try {
            (new EmailUserModel())->deleteFor($id, (array) session('auth_user'));
            return redirect()->to(site_url('email/config'))->with('success', 'Conta de e-mail excluída.');
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return redirect()->to(site_url('email/config'))->with('error', 'Não foi possível excluir a conta.');
        }
    }
}
