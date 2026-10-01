<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class Chat extends BaseController
{
    public function index(): string
    {
        return view('main', [
            'content' => view('chat/index'),
        ]);
    }

    public function send(): ResponseInterface
    {
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
                    'messages' => [['role' => 'user', 'content' => $message]],
                    'stream' => false,
                ],
                'connect_timeout' => 5,
                'timeout'         => 30,
                'http_errors'     => false,
            ]);
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
            ] + $tls);
            return $this->response->setStatusCode(502)->setJSON([
                'error' => is_array($responsePayload) && is_string($responsePayload['error'] ?? null)
                    ? $responsePayload['error'] : 'O servidor Ollama não conseguiu processar a mensagem.',
            ]);
        }

        return $this->response
            ->setJSON(['response' => is_array($responsePayload)
                ? (string) ($responsePayload['message']['content'] ?? '') : $body]);
    }
}
