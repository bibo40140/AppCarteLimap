<?php

require __DIR__ . '/../api/db.php';
require __DIR__ . '/../api/helpers.php';

$pdo = get_db();

$activityIcons = [
    'porc' => 'https://cdn-icons-png.flaticon.com/512/7302/7302490.png',
    'charcuterie' => 'https://cdn-icons-png.flaticon.com/512/12186/12186762.png',
    'boeuf' => 'https://cdn-icons-png.flaticon.com/512/7903/7903163.png',
    'veau' => 'https://cdn-icons-png.flaticon.com/512/7903/7903163.png',
    'agneau' => 'https://cdn-icons-png.flaticon.com/512/1676/1676770.png',
    'poulet' => 'https://cdn-icons-png.flaticon.com/512/9323/9323846.png',
    'canard' => 'https://cdn-icons-png.flaticon.com/512/6101/6101149.png',
    'viande autre' => 'https://cdn-icons-png.flaticon.com/512/1718/1718431.png',
    'fruit' => 'https://cdn-icons-png.flaticon.com/512/601/601984.png',
    'légume' => 'https://cdn-icons-png.flaticon.com/512/10107/10107602.png',
    'fromage chèvre' => 'https://cdn-icons-png.flaticon.com/512/3390/3390254.png',
    'fromage brebis' => 'https://cdn-icons-png.flaticon.com/512/3390/3390254.png',
    'fromage vache' => 'https://cdn-icons-png.flaticon.com/512/3390/3390254.png',
    'oeuf' => 'https://cdn-icons-png.flaticon.com/512/837/837560.png',
    'miel' => 'https://cdn-icons-png.flaticon.com/512/686/686589.png',
    'boulangerie' => 'https://cdn-icons-png.flaticon.com/512/883/883561.png',
    'huile' => 'https://cdn-icons-png.flaticon.com/512/2674/2674505.png',
    'farine' => 'https://cdn-icons-png.flaticon.com/512/1046/1046784.png',
    'boisson autre' => 'https://cdn-icons-png.flaticon.com/512/2405/2405479.png',
    'vin' => 'https://cdn-icons-png.flaticon.com/512/2548/2548534.png',
    'pecheur' => 'https://cdn-icons-png.flaticon.com/512/3075/3075977.png',
];

$defaultProducerIcon = '/assets/icons/default-producer.svg';
$defaultClientLogo = '/assets/icons/default-client.svg';

$stmt = $pdo->query('SELECT id, name FROM activities');
$activities = $stmt->fetchAll();

$updateActivity = $pdo->prepare('UPDATE activities SET icon_url=:icon_url WHERE id=:id');
$updatedActivities = 0;
foreach ($activities as $activity) {
    $nameNorm = normalize_text((string)$activity['name']);
    $icon = $activityIcons[$nameNorm] ?? $defaultProducerIcon;
    $updateActivity->execute([
        ':icon_url' => $icon,
        ':id' => (int)$activity['id'],
    ]);
    $updatedActivities++;
}

$updatedClients = $pdo->exec("UPDATE clients SET logo_url='" . $defaultClientLogo . "' WHERE COALESCE(logo_url,'') = ''");

echo "Icônes activités mises à jour: " . $updatedActivities . PHP_EOL;
echo "Logos clients fallback ajoutés: " . (int)$updatedClients . PHP_EOL;
