<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $actor = session()->get('auth_user');
        if (session()->get('logged_in') !== true || !is_array($actor) || empty($actor['id'])) {
            return redirect()->to(site_url('/'))->with('error', 'Faça login para continuar.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
