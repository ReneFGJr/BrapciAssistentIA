<?php
namespace App\Libraries;

class AuthSession
{
    public static function valid(bool $touch = false): bool
    {
        $session = session();
        $actor = $session->get('auth_user');
        if ($session->get('logged_in') !== true || !is_array($actor) || empty($actor['id'])) return false;
        $now = time();
        $lastActivity = (int) ($session->get('auth_last_activity') ?? $now);
        $expiration = (int) config('Session')->expiration;
        if ($expiration > 0 && $now - $lastActivity >= $expiration) {
            $session->remove(['logged_in', 'auth_user', 'auth_last_activity']);
            return false;
        }
        if ($touch) $session->set('auth_last_activity', $now);
        return true;
    }
}
