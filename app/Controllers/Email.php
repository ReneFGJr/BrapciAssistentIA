<?php
namespace App\Controllers;

use App\Models\EmailUserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use Throwable;

class Email extends BaseController
{
    public function html(int $accountId, int $messageId)
    {
        $message = (new \App\Models\EmailUserRowModel())->findOwned($accountId, $messageId, (array) session('auth_user'));
        return $this->response->setContentType('text/html', 'UTF-8')->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('X-Content-Type-Options', 'nosniff')->setHeader('Referrer-Policy', 'no-referrer')
            ->setHeader('Content-Security-Policy', "sandbox allow-popups allow-popups-to-escape-sandbox; default-src 'none'; script-src 'none'; style-src 'unsafe-inline'; img-src data:; base-uri 'none'; form-action 'none'; frame-ancestors 'self'")
            ->setBody(\App\Libraries\EmailHtml::document($message['email_html'] ?? ''));
    }

    public function fetchHtml(int $accountId, int $messageId)
    {
        try {
            $actor = (array) session('auth_user');
            $model = new \App\Models\EmailUserRowModel();
            $message = $model->findOwned($accountId, $messageId, $actor);
            if (empty($message['email_html_loaded'])) {
                $account = (new EmailUserModel())->connection($accountId, $actor);
                $result = (new \App\Libraries\EmailAttachments())->fetch($account, $message, null, true);
                if (!$model->where('user_id', (string) $actor['id'])->update($messageId, ['email_html' => $result['html'], 'email_html_loaded' => 1])) throw new \RuntimeException('Falha ao salvar HTML.');
                $message['email_html'] = $result['html'];
            }
            return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['has_html' => !empty($message['email_html']), 'csrf' => csrf_hash()]);
        } catch (PageNotFoundException $e) { throw $e;
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setHeader('Cache-Control', 'no-store')->setJSON(['error' => 'Não foi possível recuperar a formatação original. Confira se a conta está ativa e se a mensagem ainda existe no servidor.', 'csrf' => csrf_hash()]);
        }
    }

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
            return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('error', 'Não foi possível baixar o anexo. Confira a conexão e se a mensagem ainda está no servidor. Limite por anexo: 20 MB.');
        }
    }

    public function sendMessage(int $accountId, int $messageId)
    {
        $sentConfirmed = false;
        try {
            $actor = (array) session('auth_user');
            $message = (new \App\Models\EmailUserRowModel())->findOwned($accountId, $messageId, $actor);
            $action = $this->request->getPost('action');
            $reply = $this->request->getPost('reply');
            $account = (new EmailUserModel())->connection($accountId, $actor);
            $outgoing = new \App\Libraries\EmailOutgoing();
            $action = is_string($action) ? $action : '';
            $reply = is_string($reply) ? $reply : '';
            $mail = $outgoing->prepare($message, array_replace($actor, ['email' => $action === 'forward' ? ($actor['email'] ?? '') : $account['email_user']]), $action, $reply);
            $outgoing->smtpConfiguration($account);
            $sent = new \App\Models\EmailSentModel();
            $sentId = $sent->recordPending($message, $actor, $account, $action, $reply, $mail);
            $outgoing->send($message, $actor, $action, $reply, $account);
            $sentConfirmed = true;
            $sent->confirmSent($sentId, $actor);
            if ($this->request->isAJAX()) return $this->response->setHeader('Cache-Control', 'no-store')->setJSON(['message' => 'Envio aceito pelo servidor SMTP para ' . $mail['to'] . (!empty($mail['cc']) ? ' (Cc: ' . $mail['cc'] . ')' : '') . '. Consulte E-mails enviados para ver o conteúdo.', 'sent' => true, 'csrf' => csrf_hash()]);
            return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('email_sent_original_id', $messageId)->with('success', 'E-mail enviado com sucesso pela conta ' . $account['email_user'] . '.');
        } catch (PageNotFoundException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            log_message('error', 'Email outgoing failed: account {account_id}, exception {exception_type}, confirmed {confirmed}.', ['account_id' => $accountId, 'exception_type' => get_class($e), 'confirmed' => $sentConfirmed ? 'yes' : 'no']);
            $error = 'O envio não foi confirmado. Confira o servidor, a porta e a segurança SMTP em Gerenciar contas. O servidor de recebimento (IMAP) pode ser diferente do servidor de envio (SMTP).';
            if (!empty($account['smtp_host'])) $error .= ' Servidor SMTP utilizado: ' . $account['smtp_host'] . ':' . ($account['smtp_port'] ?? 587) . '.';
        }
        if ($this->request->isAJAX()) return $this->response->setStatusCode($sentConfirmed ? 200 : 502)->setHeader('Cache-Control', 'no-store')->setJSON(['error' => $sentConfirmed ? 'O servidor confirmou o envio, mas houve falha ao atualizar o histórico. Não reenvie esta resposta.' : $error, 'sent' => $sentConfirmed, 'csrf' => csrf_hash()]);
        if ($sentConfirmed) return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('error', 'O servidor confirmou o envio, mas houve falha ao atualizar o histórico. Não reenvie esta resposta.');
        $draft = $this->request->getPost('reply');
        return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('error', $error)
            ->with('email_reply_draft', ['id' => $messageId, 'action' => $this->request->getPost('action') === 'reply_all' ? 'reply_all' : 'reply', 'text' => is_string($draft) ? mb_substr($draft, 0, 50000) : '']);
    }

    public function saveFilter(int $id = 0)
    {
        if ($id !== 0) (new EmailUserModel())->findOwned($id, (array) session('auth_user'));
        $filter = $this->request->getPost('filter');
        if (!is_string($filter) || !in_array($filter, ['all', '0', '2', '3', '4'], true)) {
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
        $requestedMessage = $this->request->getGet('message');
        $openMessage = is_string($requestedMessage) && ctype_digit($requestedMessage) && (int) $requestedMessage > 0;
        if ($openMessage) {
            // A direct message link loads only that saved message, never the whole inbox.
            $message = (new \App\Models\EmailUserRowModel())->forUser($actor)->where('id', (int) $requestedMessage)->first();
            if ($message === null) throw PageNotFoundException::forPageNotFound();
            $messages = [$message];
        } else {
            // Show the user's imported messages without contacting the mail server.
            $messages = (new \App\Models\EmailUserRowModel())->unified($actor);
        }
        $sentMessages = $messages === [] ? [] : (new \App\Models\EmailSentModel())->forUser($actor);
        $calendarActive = false;
        try {
            $privateCalendar = (new \App\Models\PrivateGoogleServiceModel())->summary($actor);
            $calendarActive = !empty($privateCalendar['connected']) || (new \App\Models\UserServiceModel())->googleSummary($actor) !== null;
        } catch (Throwable $e) { /* Email remains available if calendar credentials need repair. */ }
        return view('main', ['content' => view('email/account', ['account' => $account, 'accounts' => $accounts, 'messages' => $messages, 'sentMessages' => $sentMessages,
            'calendarActive' => $calendarActive,
            'meetings' => $messages === [] ? [] : (new \App\Models\EmailMeetingModel())->forUser($actor),
            'spamSenders' => (new \App\Models\EmailSpamSenderModel())->forUser($actor)])]);
    }

    public function sent(): string
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        return view('main', ['content' => view('email/sent', ['messages' => (new \App\Models\EmailSentModel())->forUser((array) session('auth_user'))])]);
    }

    public function inboxRedirect()
    {
        return redirect()->to(site_url('email/account'));
    }

    private function manualSyncRequested(): bool
    {
        return $this->request->getMethod() === 'POST'
            && hash_equals('1', (string) $this->request->getPost('manual_sync'));
    }

    public function syncAll()
    {
        if (!$this->manualSyncRequested()) {
            return redirect()->to(site_url('email/account'))
                ->with('error', 'A caixa de e-mail somente é consultada ao clicar em Atualizar.');
        }
        $actor = (array) session('auth_user');
        $accounts = (new EmailUserModel())->forUser($actor);
        $count = 0;
        $failed = 0;
        foreach ($accounts as $account) {
            if ((int) $account['status'] !== 1) continue;
            $connection = [];
            try {
                $connection = (new EmailUserModel())->connection((int) $account['id'], $actor);
                $count += (new \App\Libraries\EmailInbox())->sync($connection, $actor, new \App\Models\EmailUserRowModel());
            } catch (Throwable $e) {
                $failed++;
                log_message('error', 'Email sync account {account_id}, stage {sync_stage}, exception {exception_type}, origin {origin_file}:{origin_line}; detail: {type_detail}; calls: {call_path}.', \App\Libraries\EmailSyncDiagnostic::context($e, (int) $account['id'], 'sincronização unificada', $connection ?? []));
            }
        }
        $message = $count . ' mensagem(ns) importada(s).' . ($failed ? ' Falha em ' . $failed . ' conta(s). Consulte o registro de erros do servidor.' : ' Consulta concluída.');
        return redirect()->to(site_url('email/account'))->with($failed ? 'error' : 'success', $message);
    }

    public function sync(int $id)
    {
        if (!$this->manualSyncRequested()) {
            return redirect()->to(site_url('email/account'))
                ->with('error', 'A caixa de e-mail somente é consultada ao clicar em Atualizar.');
        }
        if ($id === 0) return $this->syncAll();
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
            log_message('error', 'Email sync account {account_id}, stage {sync_stage}, exception {exception_type}, code {exception_code}, origin {origin_file}:{origin_line}; detail: {type_detail}; calls: {call_path}.', \App\Libraries\EmailSyncDiagnostic::context($e, $id, $stage, $account ?? []));
            $error = 'Falha no banco de dados de produção. Verifique a conexão, as permissões e aplique as migrações pendentes com php spark migrate.';
        } catch (Throwable $e) {
            log_message('error', 'Email sync account {account_id}, stage {sync_stage}, exception {exception_type}, code {exception_code}, origin {origin_file}:{origin_line}; detail: {type_detail}; calls: {call_path}.', \App\Libraries\EmailSyncDiagnostic::context($e, $id, $stage, $account ?? []));
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
            if (!empty($message['server_deleted_at'])) {
                if ($this->request->isAJAX()) return $this->response->setJSON(['message' => 'Mensagem já excluída do IMAP. Cópia preservada no banco.', 'csrf' => csrf_hash()]);
                return redirect()->to(site_url('email/account'))->with('success', 'Mensagem já excluída do IMAP. Cópia preservada no banco.');
            }
            $account = (new EmailUserModel())->connection($accountId, $actor);
            $deletedFromServer = (new \App\Libraries\EmailMessageDeletion())->delete($account, $message);
            $model->markServerDeleted($accountId, $messageId, $actor);
            if ($this->request->isAJAX()) return $this->response->setJSON(['message' => 'Mensagem removida da caixa de entrada. Cópia preservada no banco.', 'csrf' => csrf_hash()]);
            return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('success', $deletedFromServer
                ? 'Mensagem excluída do servidor IMAP. Cópia preservada no banco.'
                : 'A mensagem já não estava na caixa de entrada do servidor. Cópia preservada no banco.');
        } catch (PageNotFoundException $e) {
            throw $e;
        } catch (\RuntimeException | InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Não foi possível concluir a exclusão. A cópia local foi mantida.';
        }
        if ($this->request->isAJAX()) return $this->response->setStatusCode(502)->setJSON(['error' => $error, 'csrf' => csrf_hash()]);
        return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('error', $error);
    }

    public function messageStatus(int $accountId, int $messageId)
    {
        try {
            $status = $this->request->getPost('email_status');
            (new \App\Models\EmailUserRowModel())->changeStatus($accountId, $messageId, is_string($status) ? $status : '', (array) session('auth_user'));
            return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('success', 'Status atualizado.');
        } catch (PageNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('error', 'Não foi possível alterar o status.');
        }
    }

    public function spam(int $accountId, int $messageId)
    {
        try {
            (new \App\Models\EmailSpamSenderModel())->blockMessage($accountId, $messageId, (array) session('auth_user'));
            return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('success', 'Remetente adicionado à lista de SPAM. As mensagens desse remetente aparecem somente em SPAM.');
        } catch (PageNotFoundException $e) { throw $e;
        } catch (InvalidArgumentException $e) {
            return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('error', $e->getMessage());
        } catch (Throwable $e) {
            return redirect()->to(site_url('email/account/' . $accountId) . '?message=' . $messageId)->with('error', 'Não foi possível adicionar o remetente à lista de SPAM.');
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
