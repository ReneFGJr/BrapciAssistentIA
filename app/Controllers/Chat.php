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
        $payload = $this->request->getJSON(true);
        $message = trim((string) ($payload['message'] ?? $this->request->getPost('message') ?? ''));

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

        $endpoint = trim((string) env('chat.endpoint', ''));

        if ($endpoint === '') {
            return $this->response->setStatusCode(503)->setJSON([
                'error' => 'O servidor de chat não está configurado.',
            ]);
        }

        try {
            $response = service('curlrequest')->post($endpoint, [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'message' => $message,
                    'user'    => session('auth_user'),
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

        return $this->response
            ->setStatusCode($response->getStatusCode())
            ->setJSON(is_array($responsePayload) ? $responsePayload : ['response' => $body]);
    }
}