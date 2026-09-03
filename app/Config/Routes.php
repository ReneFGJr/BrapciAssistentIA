<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('profile', 'Home::profile', ['filter' => 'auth']);
$routes->post('signin', 'Auth::signin', ['filter' => 'csrf']);
$routes->post('logout', 'Auth::logout', ['filter' => 'csrf']);

$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);
$routes->get('chat', 'Chat::index', ['filter' => 'auth']);
$routes->post('chat/messages', 'Chat::send', ['filter' => ['auth', 'csrf']]);
