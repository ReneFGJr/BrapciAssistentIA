<?php
namespace App\Controllers;

class UserSchedule extends Schedule
{
    protected string $schedulePath = 'userSchedule';
    protected string $configurationPath = 'tools/usergoogleSchedule';
    protected bool $readStoredForUser = false;

    protected function active(): ?array
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        return (new \App\Libraries\GoogleCalendarOAuth())->access((array) session('auth_user'));
    }
}
