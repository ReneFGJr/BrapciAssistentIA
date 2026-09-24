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

$routes->get('dashboard/admin', 'AdminApps::index', ['filter' => ['auth', 'admin']]);
$routes->post('dashboard/admin/apps', 'AdminApps::create', ['filter' => ['auth', 'admin', 'csrf']]);
$routes->post('dashboard/admin/apps/(:num)/update', 'AdminApps::update/$1', ['filter' => ['auth', 'admin', 'csrf']]);
$routes->post('dashboard/admin/apps/(:num)/delete', 'AdminApps::delete/$1', ['filter' => ['auth', 'admin', 'csrf']]);

$routes->get('person', 'Person::index', ['filter' => 'auth']);
$routes->get('person/(:num)', 'Person::show/$1', ['filter' => 'auth']);
$routes->get('person/new', 'Person::new', ['filter' => 'auth']);
$routes->post('person', 'Person::create', ['filter' => ['auth', 'csrf']]);
$routes->get('person/(:num)/edit', 'Person::edit/$1', ['filter' => 'auth']);
$routes->post('person/(:num)/update', 'Person::update/$1', ['filter' => ['auth', 'csrf']]);
$routes->post('person/(:num)/share', 'Person::share/$1', ['filter' => ['auth', 'csrf']]);
$routes->post('person/(:num)/shares/(:num)/revoke', 'Person::revoke/$1/$2', ['filter' => ['auth', 'csrf']]);

$routes->post('person/import', 'Person::import', ['filter' => ['auth', 'csrf']]);

$routes->post('person/(:num)/photo', 'Person::photo/$1', ['filter' => ['auth', 'csrf']]);

$routes->get('corporatebody', 'CorporateBody::index', ['filter' => 'auth']);
$routes->get('corporatebody/new', 'CorporateBody::new', ['filter' => 'auth']);
$routes->post('corporatebody', 'CorporateBody::create', ['filter' => ['auth', 'csrf']]);
$routes->get('corporatebody/(:num)', 'CorporateBody::show/$1', ['filter' => 'auth']);
$routes->get('corporatebody/(:num)/edit', 'CorporateBody::edit/$1', ['filter' => 'auth']);
$routes->post('corporatebody/(:num)/update', 'CorporateBody::update/$1', ['filter' => ['auth', 'csrf']]);
$routes->get('kanban', 'Kanban::index', ['filter' => 'auth']);
$routes->get('kanban/new', 'Kanban::new', ['filter' => 'auth']);
$routes->post('kanban', 'Kanban::create', ['filter' => ['auth', 'csrf']]);
$routes->get('kanban/(:num)/edit', 'Kanban::edit/$1', ['filter' => 'auth']);
$routes->post('kanban/(:num)/update', 'Kanban::update/$1', ['filter' => ['auth', 'csrf']]);