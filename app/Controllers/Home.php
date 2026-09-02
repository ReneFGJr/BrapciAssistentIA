<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $data = [
            'content' => view('auth/signin'),
        ];

        return view('main', $data);
    }

    public function profile(): string
    {
        $data = [
            'content' => view('User/profile', [
                'user' => session()->get('auth_user'),
            ]),
        ];

        return view('main', $data);
    }
}
