<?php
namespace App\Filters;

use App\Libraries\AuthSession;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SessionExpiryFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session('logged_in') !== null && !AuthSession::valid(true)) {
            session()->remove(['logged_in', 'auth_user', 'auth_last_activity']);
            return redirect()->to(base_url('/'))->setHeader('Cache-Control', 'no-store');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
