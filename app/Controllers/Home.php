<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $this->response->setHeader('Cache-Control', 'private, no-store, max-age=0');
        $actor = session('auth_user');
        if (session('logged_in') === true && is_array($actor) && !empty($actor['id'])) {
            return view('main', ['content' => view('home/daily',
                (new \App\Libraries\DailySummary())->forUser($actor))]);
        }
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
