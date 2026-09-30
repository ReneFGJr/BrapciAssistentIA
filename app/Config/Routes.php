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
$routes->get('tools', 'Tools::index', ['filter' => 'auth']);
$routes->get('tools/googleSchedule', 'GoogleSchedule::index', ['filter' => 'auth']);
$routes->post('tools/googleSchedule', 'GoogleSchedule::save', ['filter' => ['auth', 'csrf']]);
$routes->get('tools/subjects', 'Subjects::index', ['filter' => 'auth']);
$routes->get('tools/subjects/add', 'Subjects::add', ['filter' => 'auth']);
$routes->get('tools/subjects/(:num)/edit', 'Subjects::edit/$1', ['filter' => 'auth']);
$routes->post('tools/subjects', 'Subjects::create', ['filter' => ['auth', 'csrf']]);
$routes->post('tools/subjects/(:num)/update', 'Subjects::update/$1', ['filter' => ['auth', 'csrf']]);
$routes->post('tools/subjects/(:num)/delete', 'Subjects::delete/$1', ['filter' => ['auth', 'csrf']]);
$routes->get('tools/usergoogleSchedule', 'UserGoogleSchedule::index', ['filter' => 'auth']);
$routes->post('tools/usergoogleSchedule', 'UserGoogleSchedule::save', ['filter' => ['auth', 'csrf']]);
$routes->post('tools/usergoogleSchedule/connect', 'UserGoogleSchedule::connect', ['filter' => ['auth', 'csrf']]);
$routes->get('tools/usergoogleSchedule/callback', 'UserGoogleSchedule::callback', ['filter' => 'auth']);
$routes->post('tools/usergoogleSchedule/delete', 'UserGoogleSchedule::delete', ['filter' => ['auth', 'csrf']]);
$routes->get('userSchedule', 'UserSchedule::index', ['filter' => 'auth']);
$routes->post('userSchedule/sync', 'UserSchedule::sync', ['filter' => ['auth', 'csrf']]);
$routes->post('userSchedule/(:num)/subject', 'UserSchedule::subject/$1', ['filter' => ['auth', 'csrf']]);
$routes->get('tools/(:segment)', 'Tools::show/$1', ['filter' => 'auth']);
$routes->get('notes', 'Notes::index', ['filter' => 'auth']);
$routes->get('notes/add', 'Notes::add', ['filter' => 'auth']);
$routes->get('notes/(:num)', 'Notes::show/$1', ['filter' => 'auth']);
$routes->post('notes/add', 'Notes::create', ['filter' => ['auth', 'csrf']]);
$routes->get('notes/(:num)/edit', 'Notes::edit/$1', ['filter' => 'auth']);
$routes->post('notes/(:num)/update', 'Notes::update/$1', ['filter' => ['auth', 'csrf']]);

$routes->post('notes/(:num)/participants', 'Notes::addParticipant/$1', ['filter' => ['auth', 'csrf']]);
$routes->post('notes/(:num)/participants/(:num)/remove', 'Notes::removeParticipant/$1/$2', ['filter' => ['auth', 'csrf']]);

$routes->get('notes/(:num)/participants/search', 'Notes::searchParticipants/$1', ['filter' => 'auth']);

$routes->post('notes/(:num)/categories', 'Notes::addCategory/$1', ['filter' => ['auth', 'csrf']]);
$routes->post('notes/(:num)/categories/new', 'Notes::createCategory/$1', ['filter' => ['auth', 'csrf']]);
$routes->post('notes/(:num)/categories/(:num)/remove', 'Notes::removeCategory/$1/$2', ['filter' => ['auth', 'csrf']]);

$routes->post('notes/(:num)/tasks', 'Notes::createTask/$1', ['filter' => ['auth', 'csrf']]);

$routes->get('notes/(:num)/subjects/(:num)', 'Notes::related/$1/$2', ['filter' => 'auth']);

$routes->post('tools/googleSchedule/services/(:num)/delete', 'GoogleSchedule::delete/$1', ['filter' => ['auth', 'csrf']]);

$routes->get('schedule', 'Schedule::index', ['filter' => 'auth']);
$routes->post('schedule/sync', 'Schedule::sync', ['filter' => ['auth', 'csrf']]);
$routes->post('schedule/(:num)/subject', 'Schedule::subject/$1', ['filter' => ['auth', 'csrf']]);
