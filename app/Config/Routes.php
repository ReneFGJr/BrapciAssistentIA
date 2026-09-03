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

$routes->get('notepad', 'Notepad::index', ['filter' => 'auth']);
$routes->post('notepad', 'Notepad::create', ['filter' => ['auth', 'csrf']]);
$routes->post('notepad/(:num)/update', 'Notepad::update/$1', ['filter' => ['auth', 'csrf']]);
$routes->post('notepad/(:num)/delete', 'Notepad::delete/$1', ['filter' => ['auth', 'csrf']]);
