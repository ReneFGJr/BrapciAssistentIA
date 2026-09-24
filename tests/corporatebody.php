<?php
ob_start();
require __DIR__ . '/institutions.php';
$migration->up();
Config\Services::injectMock('request', Config\Services::incomingrequest(null, false));
$record = [
    'id' => 'https://ror.org/00xmkp704',
    'names' => [['value' => 'Example <Institute>', 'types' => ['ror_display']], ['value' => 'EX', 'types' => ['acronym']]],
    'locations' => [['geonames_details' => ['name' => 'Ghent', 'country_name' => 'Belgium', 'country_code' => 'BE',
        'country_subdivision_name' => 'Flanders', 'lat' => 51.05, 'lng' => 3.71667]]],
    'established' => 1992,
];
$data = App\Libraries\RorLookup::map($record);
checkInstitution($data['name'] === 'Example <Institute>' && $data['city'] === 'Ghent' && $data['acronym'] === 'EX', 'ROR v2 mapping');
$inserted = (new App\Models\InstitutionModel($db))->insert($data);
checkInstitution($inserted !== false, 'ROR mapped data saves');
try {
    (new App\Models\InstitutionModel($db))->insert($data);
    throw new LogicException('Duplicate ROR accepted');
} catch (CodeIgniter\Database\Exceptions\DatabaseException $expected) {
    checkInstitution(true, 'Duplicate ROR blocked');
}
try {
    (new App\Libraries\RorLookup())->find('https://example.test/');
    throw new LogicException('Invalid ROR accepted');
} catch (InvalidArgumentException $expected) {
    checkInstitution(true, 'Invalid ROR rejected before HTTP');
}
$html = view('corporatebody/new', [
    'query' => '<script>', 'page' => 1, 'result' => ['total' => 1, 'items' => [$data]],
    'prefill' => $data, 'error' => null,
]);

checkInstitution(! str_contains($html, '<Institute>') && ! str_contains($html, '<script>'), 'ROR data escaped in view');
checkInstitution(str_contains($html, 'Salvar instituição'), 'Creation form renders');
if (in_array('--live', $argv, true)) {
    $lookup = new App\Libraries\RorLookup();
    $result = $lookup->search('Universidade Federal do Rio Grande do Sul');
    checkInstitution(count($result['items']) > 0, 'Live ROR search');
    $selected = $lookup->find(basename($result['items'][0]['ror_id']));
    checkInstitution($selected['name'] !== '', 'Live ROR selection');
    echo 'Live ROR: ' . $selected['name'] . PHP_EOL;
}
echo "Corporate body checks passed.\n";