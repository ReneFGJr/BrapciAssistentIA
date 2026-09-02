<?php

namespace App\Controllers;

use App\Exceptions\LegacyAuthException;
use App\Libraries\LegacyAuthService;
use App\Models\UserLoginModel;
use RuntimeException;
use Throwable;

class Auth extends BaseController
{
    public function signin()
    {
        $rules = [
            'login'    => 'required|max_length[255]',
            'password' => 'required|max_length[1024]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/')->withInput()
                ->with('error', 'Informe o login e a senha.')
                ->with('error_detail', ['type' => 'validation_error', 'fields' => $this->validator->getErrors()]);
        }

        try {
            $user = (new LegacyAuthService())->login(
                trim((string) $this->request->getPost('login')),
                (string) $this->request->getPost('password')
            );

            (new UserLoginModel())->recordLogin($user['id']);

            session()->regenerate(true);
            session()->set(['logged_in' => true, 'auth_user' => $user]);

            return redirect()->to('/')->with('success', 'Login realizado com sucesso.');
        } catch (LegacyAuthException $exception) {
            return redirect()->to('/')->withInput()
                ->with('error', $exception->getMessage())
                ->with('error_detail', $exception->details());
        } catch (RuntimeException $exception) {
            return redirect()->to('/')->withInput()
                ->with('error', $exception->getMessage())
                ->with('error_detail', ['type' => 'runtime_error', 'message' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            log_message('error', 'Erro inesperado no login: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to('/')->withInput()
                ->with('error', 'Não foi possível concluir o login. Tente novamente.')
                ->with('error_detail', ['type' => 'unexpected_error', 'message' => $exception->getMessage()]);
        }
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/')->with('success', 'Sessão encerrada.');
    }
}
