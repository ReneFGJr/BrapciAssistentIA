<?php

namespace App\Controllers;

use App\Models\ChatContextModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class Chat extends BaseController
{
    public function index(): string
    {
        $contexts = new ChatContextModel();
        $userId = $this->userId();
        $conversations = $contexts->conversations($userId);
        $selectedId = (int) $this->request->getGet('conversation');
        $selected = $selectedId > 0 ? $contexts->conversation($selectedId, $userId) : null;
        if ($selectedId > 0 && $selected === null) throw PageNotFoundException::forPageNotFound();
        if ($selected === null && $conversations !== []) {
            $selected = $contexts->conversation((int) $conversations[0]['id'], $userId);
        }
        return view('main', [
            'content' => view('chat/index', ['conversations' => $conversations, 'selected' => $selected]),
        ]);
    }

    public function create()
    {
        try {
            $id = (new ChatContextModel())->createConversation($this->userId());
            return redirect()->to(site_url('chat') . '?conversation=' . $id);
        } catch (Throwable $exception) {
            log_message('error', 'Falha ao criar conversa: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to(site_url('chat'))->with('error', 'Não foi possível criar a conversa.');
        }
    }

    public function rename(int $id)
    {
        try {
            if (!(new ChatContextModel())->renameConversation($id, $this->userId(), (string) $this->request->getPost('title'))) {
                throw PageNotFoundException::forPageNotFound();
            }
            return redirect()->to(site_url('chat') . '?conversation=' . $id)->with('success', 'Conversa renomeada.');
        } catch (PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return redirect()->to(site_url('chat') . '?conversation=' . $id)->with('error', $exception->getMessage());
        }
    }

    public function delete(int $id)
    {
        if (!(new ChatContextModel())->deleteConversation($id, $this->userId())) {
            throw PageNotFoundException::forPageNotFound();
        }
        return redirect()->to(site_url('chat'))->with('success', 'Conversa excluída.');
    }

    public function send(): ResponseInterface
    {
        // The CSRF filter regenerates the token before the controller runs.
        // Return the fresh value so AJAX forms can use it on the next request.
        $this->response->setHeader(csrf_header(), csrf_hash());
        $message = trim((string) ($this->request->getPost('message') ?? ''));
        if ($message === '' && str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'application/json')) {
            try {
                $payload = $this->request->getJSON(true);
                $message = trim((string) ($payload['message'] ?? ''));
            } catch (Throwable) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'JSON inválido.']);
            }
        }

        if ($message === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => 'A mensagem é obrigatória.',
            ]);
        }

        if (mb_strlen($message) > 4000) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => 'A mensagem deve ter no máximo 4000 caracteres.',
            ]);
        }

        $contexts = new ChatContextModel();
        $conversationId = (int) $this->request->getPost('conversation_id');
        try {
            if ($conversationId < 1) $conversationId = $contexts->createConversation($this->userId());
            $conversation = $contexts->conversation($conversationId, $this->userId());
            if ($conversation === null) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Conversa não encontrada.']);
            }
            $messages = $conversation['messages'];
            $messages[] = ['role' => 'user', 'content' => $message];
        } catch (Throwable $exception) {
            log_message('error', 'Falha ao carregar contexto do chat: {message}', ['message' => $exception->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Não foi possível carregar a conversa.']);
        }

        $endpoint = rtrim(trim((string) env('chat.endpoint', 'https://ollama.brapci.inf.br/')), '/');

        if ($endpoint === '') {
            return $this->response->setStatusCode(503)->setJSON([
                'error' => 'O servidor de chat não está configurado.',
            ]);
        }

        try {
            $client = service('curlrequest');
            $caBundle = trim((string) env('chat.caBundle', ini_get('curl.cainfo')));
            if ($caBundle !== '' && (!is_file($caBundle) || !is_readable($caBundle))) {
                return $this->response->setStatusCode(503)->setJSON([
                    'error' => 'O arquivo de certificados do chat não está disponível.',
                ]);
            }
            $tls = ['verify' => $caBundle !== '' ? $caBundle : true];
            $model = trim((string) env('chat.model', ''));
            if ($model === '') {
                $modelsResponse = $client->get($endpoint . '/api/tags', [
                    'headers' => ['Accept' => 'application/json'],
                    'connect_timeout' => 5,
                    'timeout' => 15,
                    'http_errors' => false,
                ] + $tls);
                $modelsPayload = json_decode((string) $modelsResponse->getBody(), true);
                $model = trim((string) ($modelsPayload['models'][0]['name'] ?? ''));
                if ($modelsResponse->getStatusCode() >= 400 || $model === '') {
                    return $this->response->setStatusCode(502)->setJSON([
                        'error' => 'Nenhum modelo está disponível no servidor Ollama.',
                    ]);
                }
            }
            $response = $client->post($endpoint . '/api/chat', [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'stream' => false,
                ],
                'connect_timeout' => 5,
                'timeout'         => 30,
                'http_errors'     => false,
            ] + $tls);
        } catch (Throwable $exception) {
            log_message('error', 'Falha ao enviar mensagem ao servidor de chat: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return $this->response->setStatusCode(502)->setJSON([
                'error' => 'Não foi possível acessar o servidor de chat.',
            ]);
        }

        $body = (string) $response->getBody();
        $responsePayload = json_decode($body, true);

        if ($response->getStatusCode() >= 400) {
            log_message('error', 'Ollama respondeu com HTTP {status}: {body}', [
                'status' => $response->getStatusCode(),
                'body' => mb_substr($body, 0, 1000),
            ]);
            return $this->response->setStatusCode(502)->setJSON([
                'error' => is_array($responsePayload) && is_string($responsePayload['error'] ?? null)
                    ? $responsePayload['error'] : 'O servidor Ollama não conseguiu processar a mensagem.',
            ]);
        }

        $answer = is_array($responsePayload) ? trim((string) ($responsePayload['message']['content'] ?? '')) : trim($body);
        if ($answer === '') {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'O Ollama retornou uma resposta vazia.']);
        }
        $messages[] = ['role' => 'assistant', 'content' => $answer];
        try {
            if (!$contexts->saveMessages($conversationId, $this->userId(), $messages)) {
                throw new \RuntimeException('Conversa não encontrada.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Falha ao salvar contexto do chat: {message}', ['message' => $exception->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON(['error' => 'A resposta foi recebida, mas não pôde ser salva.']);
        }

        return $this->response
            ->setJSON(['response' => $answer, 'conversation_id' => $conversationId]);
    }

    private function userId(): string
    {
        $userId = (string) (((array) session('auth_user'))['id'] ?? '');
        if ($userId === '') throw PageNotFoundException::forPageNotFound();
        return $userId;
    }
}
