<?php
require __DIR__ . '/google_schedule.php';

// Reproduce a deployment with the original calendar table and new application code.
// Native SQLite DROP avoids the framework's table-rebuild path altering FK names.
$db->query('ALTER TABLE test_service_google_schedule DROP COLUMN meeting_url');
$db->query('ALTER TABLE test_service_google_schedule DROP COLUMN google_url');
$model->saveGoogle($actor, 'owner@example.test', 'Test_Api_Key');
$connection = $model->googleConnection($actor);
$db->resetDataCache();
$legacy = new App\Models\GoogleScheduleModel($db);
$legacy->replaceSnapshot($connection, [$conference]);
$saved = $legacy->upcoming($connection)->first();
verifyService($saved['title'] === $conference['title'] && $saved['description'] === $conference['description'], 'Calendar synchronization works before optional link migration');
$legacy->storeCreated($connection, array_replace($conference, ['google_event_id' => 'legacy-created']));
verifyService(count($legacy->upcoming($connection)->findAll()) === 2, 'Created meetings also work with legacy schema');
(new App\Database\Migrations\AddScheduleLinks(Config\Database::forge($db)))->up();
$db->resetDataCache();
$legacy->replaceSnapshot($connection, [$conference]);
verifyService($legacy->upcoming($connection)->first()['meeting_url'] === $conference['meeting_url'], 'Next synchronization restores conference links after migration');
echo "Schedule legacy schema: event collection, meeting storage and recovery after migration passed.\n";
