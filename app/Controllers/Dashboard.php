<?php

namespace App\Controllers;

use App\Models\ApplicationModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $user = session()->get('auth_user');
        $userId = is_array($user) ? (string) ($user['id'] ?? '') : '';

        return view('main', [
            'content' => view('dashboard/index', [
                'apps' => (new ApplicationModel())->getAccessibleApps($userId),
            ]),
        ]);
    }
}
