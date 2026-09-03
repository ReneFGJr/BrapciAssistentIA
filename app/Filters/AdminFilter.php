<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session()->get('auth_user');
        $allowedUserIds = array_filter(array_map(
            'trim',
            explode(',', (string) env('admin.allowedUserIds', ''))
        ));

        $isAdmin = is_array($user)
            && (
                filter_var($user['admin'] ?? false, FILTER_VALIDATE_BOOL)
                || in_array((string) ($user['id'] ?? ''), $allowedUserIds, true)
            );

        if (! $isAdmin) {
            return redirect()->to('/dashboard')->with('error', 'Acesso restrito a administradores.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
