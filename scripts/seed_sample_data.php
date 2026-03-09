<?php

require __DIR__ . '/../api/db.php';
require __DIR__ . '/../api/helpers.php';

$pdo = get_db();

$activities = [
    ['family' => 'viande', 'name' => 'porc'],
    ['family' => 'viande', 'name' => 'charcuterie'],
    ['family' => 'viande', 'name' => 'boeuf'],
    ['family' => 'viande', 'name' => 'veau'],
    ['family' => 'viande', 'name' => 'agneau'],
    ['family' => 'viande', 'name' => 'poulet'],
    ['family' => 'viande', 'name' => 'canard'],
    ['family' => 'viande', 'name' => 'viande autre'],
    ['family' => 'Fruits & légumes', 'name' => 'fruit'],
    ['family' => 'Fruits & légumes', 'name' => 'légume'],
    ['family' => 'produits laitiers', 'name' => 'fromage chèvre'],
    ['family' => 'produits laitiers', 'name' => 'fromage vache'],
    ['family' => 'produits laitiers', 'name' => 'yaourt'],
    ['family' => 'produits laitiers', 'name' => 'lait'],
    ['family' => 'épicerie', 'name' => 'épicerie salée'],
    ['family' => 'épicerie', 'name' => 'épicerie sucrée'],
    ['family' => 'épicerie', 'name' => 'farine'],
    ['family' => 'épicerie', 'name' => 'épices'],
    ['family' => 'épicerie', 'name' => 'légumineuses'],
    ['family' => 'épicerie', 'name' => 'huile'],
    ['family' => 'boissons', 'name' => 'boisson autre'],
    ['family' => 'boissons', 'name' => 'vin'],
    ['family' => 'boulangerie', 'name' => 'boulangerie'],
    ['family' => 'apiculture', 'name' => 'miel'],
    ['family' => 'mer', 'name' => 'pecheur'],
    ['family' => 'autres', 'name' => 'oeuf'],
    ['family' => 'autres', 'name' => 'hygiène/entretien'],
];

$labels = [
    ['name' => 'AB', 'color' => '#3cba54'],
    ['name' => 'idoki', 'color' => '#ffa726'],
    ['name' => 'demeter', 'color' => '#ff7043'],
    ['name' => 'AOP', 'color' => '#42a5f5'],
    ['name' => 'IGP', 'color' => '#7e57c2'],
    ['name' => 'Label rouge', 'color' => '#ef5350'],
    ['name' => 'Ossau iraty', 'color' => '#ff7043'],
    ['name' => 'nature et progres', 'color' => '#7e57c2'],
    ['name' => 'HVE', 'color' => '#ffa726'],
    ['name' => 'bienvenue à la ferme', 'color' => '#ef5350'],
    ['name' => 'Sans label', 'color' => '#000000'],
];

$clients = [
    ['name' => 'Coopaz', 'client_type' => 'Épicerie', 'address' => '6 rue de molinie', 'postal_code' => '40140', 'city' => 'Azur', 'phone' => '0672833332', 'email' => 'epiceriecoopaz@gmail.com', 'website' => 'https://www.coopaz.fr', 'logo_url' => 'https://i.imgur.com/4GCVbYP.png', 'latitude' => 43.798959, 'longitude' => -1.303089],
    ['name' => 'AMAP Labenne', 'client_type' => 'AMAP', 'address' => 'Place de la République', 'postal_code' => '40530', 'city' => 'Labenne', 'phone' => '07 69 67 32 07', 'email' => 'labenneamap@gmail.com', 'website' => 'https://www.amap-labenne.com/', 'logo_url' => 'https://amap-labenne.com/wp-content/uploads/2022/06/logoAmap.png', 'latitude' => 43.5951101, 'longitude' => -1.425469],
    ['name' => 'AMAP Ondres', 'client_type' => 'AMAP', 'address' => 'Parking Larrendart', 'postal_code' => '40440', 'city' => 'Ondres', 'phone' => '06 79 39 67 65', 'email' => 'amapondres@gmail.com', 'website' => 'https://www.amap-labenne.com/', 'logo_url' => 'https://amap-labenne.com/wp-content/uploads/2022/06/logoAmap.png', 'latitude' => 43.5627891, 'longitude' => -1.4461303],
    ['name' => 'AMAP Capbreton', 'client_type' => 'AMAP', 'address' => 'Marché couvert', 'postal_code' => '40130', 'city' => 'Capbreton', 'phone' => '06 08 61 86 68', 'email' => 'amap.capbreton@gmail.com', 'website' => 'https://www.amap-labenne.com/', 'logo_url' => 'https://amap-labenne.com/wp-content/uploads/2022/06/logoAmap.png', 'latitude' => 43.637844, 'longitude' => -1.420939],
    ['name' => 'Les petits ruisseaux', 'client_type' => 'AMAP', 'address' => 'Rivière-Saas-et-Gourby', 'postal_code' => '40180', 'city' => 'Rivière-Saas-et-Gourby', 'phone' => '06 18 83 33 66', 'email' => '', 'website' => 'https://lespetitsruisseaux.wixsite.com/transition', 'logo_url' => 'https://i.imgur.com/9ypZy8G.jpeg', 'latitude' => 43.6824730, 'longitude' => -1.1499299],
    ['name' => 'AMAP du Moun', 'client_type' => 'AMAP', 'address' => '1120 chemin de Thore', 'postal_code' => '40000', 'city' => 'Mont de Marsan', 'phone' => '', 'email' => '', 'website' => 'https://amapdumoun.fr/', 'logo_url' => 'https://i.imgur.com/w0Yqbkz.png', 'latitude' => 43.8946576, 'longitude' => -0.5205978],
    ['name' => 'AMAP de Dax', 'client_type' => 'AMAP', 'address' => '27 rue de l\'Epargne', 'postal_code' => '40100', 'city' => 'Dax', 'phone' => '', 'email' => '', 'website' => 'https://www.amap-dax.fr/', 'logo_url' => 'https://i.imgur.com/IgoZAlO.jpeg', 'latitude' => 43.7075184, 'longitude' => -1.0404061],
    ['name' => 'AMAP de Tarnos', 'client_type' => 'AMAP', 'address' => '1 allée de la Ferme', 'postal_code' => '40220', 'city' => 'Tarnos', 'phone' => '06 88 56 61 56', 'email' => '', 'website' => 'https://amap-tarnos.fr/', 'logo_url' => 'https://amap-tarnos.fr/wp-content/themes/amap-tarnos/images/logo-header-amap-tarnos.jpg', 'latitude' => 43.5268695, 'longitude' => -1.4544326],
    ['name' => 'Ecorx', 'client_type' => 'Épicerie', 'address' => '', 'postal_code' => '', 'city' => '', 'phone' => '', 'email' => '', 'website' => '', 'logo_url' => '', 'latitude' => null, 'longitude' => null],
];

$suppliers = [
    ['name' => 'Bio Pays Landais', 'address' => '293 route du pays de gosse', 'postal_code' => '40230', 'city' => 'Saint-Geours-de-Maremne', 'phone' => '05 24 26 32 33', 'email' => 'vente@bio-pays-landais.fr', 'website' => 'https://bio-pays-landais.com/', 'supplier_type' => 'Coopérative', 'activities' => ['épicerie salée', 'légume', 'fruit', 'boisson autre'], 'labels' => ['AB'], 'client_names' => ['Coopaz'], 'latitude' => 43.713614, 'longitude' => -1.236721],
    ['name' => 'Zabal Oil', 'address' => 'Route de Louhossoa', 'postal_code' => '64640', 'city' => 'HELETTE', 'phone' => '05 59 37 35 40', 'email' => 'contact@huiles-zabaloil.fr', 'website' => 'https://zabaloil.com/', 'supplier_type' => 'Producteur', 'activities' => ['huile'], 'labels' => ['AB'], 'client_names' => ['Coopaz'], 'latitude' => 43.3063561, 'longitude' => -1.2677623],
    ['name' => 'Romain Mouly', 'address' => '141 route de robert', 'postal_code' => '40140', 'city' => 'SOUSTONS', 'phone' => '', 'email' => '', 'website' => 'https://www.aventure.bio/', 'supplier_type' => 'Producteur', 'activities' => ['légume'], 'labels' => ['AB'], 'client_names' => ['Coopaz'], 'latitude' => 43.769801, 'longitude' => -1.281163],
    ['name' => 'AVENTURE BIO', 'address' => '18 rue de Cran', 'postal_code' => '74000', 'city' => 'ANNECY', 'phone' => '', 'email' => 'slf@aventure.bio', 'website' => '', 'supplier_type' => 'Grossiste', 'activities' => ['épicerie sucrée', 'épicerie salée', 'boisson autre', 'farine', 'épices', 'légumineuses', 'huile', 'hygiène/entretien'], 'labels' => ['AB'], 'client_names' => ['Coopaz'], 'latitude' => 45.9058169, 'longitude' => 6.1189246],
    ['name' => 'EARL CHRISTIAN BRUN', 'address' => '1298 Route d\'Herm', 'postal_code' => '40140', 'city' => 'MAGESCQ', 'phone' => '05 58 47 71 50', 'email' => 'christian.brun6@wanadoo.fr', 'website' => '', 'supplier_type' => 'Producteur', 'activities' => ['poulet', 'viande autre'], 'labels' => ['Sans label'], 'client_names' => ['Coopaz'], 'latitude' => 43.7839679, 'longitude' => -1.1884735],
    ['name' => 'FROMAGERIE ST EUTROPE', 'address' => 'Rue des Sources', 'postal_code' => '40260', 'city' => 'TALLER', 'phone' => '06 83 95 04 88', 'email' => 'natalieguerin@gmail.com', 'website' => '', 'supplier_type' => 'Producteur', 'activities' => ['fromage chèvre'], 'labels' => ['Sans label'], 'client_names' => ['Coopaz'], 'latitude' => 43.8719271, 'longitude' => -1.0734429],
    ['name' => 'SARL DARRIGADE', 'address' => '36 Chemin de Roucheou', 'postal_code' => '40140', 'city' => 'SOUSTONS', 'phone' => '09 77 73 60 56', 'email' => 'contact@ferme-darrigade.fr', 'website' => 'https://ferme-darrigade.fr/', 'supplier_type' => 'Producteur', 'activities' => ['légume', 'canard', 'épicerie salée'], 'labels' => ['Sans label'], 'client_names' => ['Coopaz'], 'latitude' => 43.7442801, 'longitude' => -1.306147],
    ['name' => 'FERME HORDILLER', 'address' => '228 chemin de Labaste', 'postal_code' => '40300', 'city' => 'Labatut', 'phone' => '06 89 93 54 54', 'email' => 'fermehordiller@gmail.com', 'website' => '', 'supplier_type' => 'Producteur', 'activities' => ['oeuf', 'boeuf', 'veau', 'huile', 'légume', 'canard', 'farine', 'fromage vache', 'yaourt', 'lait', 'porc', 'viande autre'], 'labels' => ['AB', 'Sans label'], 'client_names' => ['Coopaz', 'Ecorx', 'AMAP Labenne', 'AMAP de Dax', 'AMAP de Tarnos', 'AMAP Ondres', 'AMAP Capbreton'], 'latitude' => 43.5687338, 'longitude' => -1.0146198],
    ['name' => 'Le Gavroche', 'address' => '248 A impasse st Joseph', 'postal_code' => '40230', 'city' => 'BENESSE MAREMNE', 'phone' => '06 08 82 89 06', 'email' => '', 'website' => '', 'supplier_type' => 'Producteur', 'activities' => ['pecheur'], 'labels' => ['Sans label'], 'client_names' => ['Coopaz'], 'latitude' => 43.6398026, 'longitude' => -1.3650046],
    ['name' => 'LE PAIN DICI', 'address' => '617 rue de la Poterie', 'postal_code' => '40260', 'city' => 'CASTETS', 'phone' => '06 25 90 24 54', 'email' => 'contact@lepaindici.fr', 'website' => 'https://www.lepaindici.fr/', 'supplier_type' => 'Producteur', 'activities' => ['boulangerie'], 'labels' => ['AB'], 'client_names' => ['Coopaz'], 'latitude' => 43.8774387, 'longitude' => -1.1441516],
    ['name' => 'RUCHER DE BRANA', 'address' => '30 Chemin du Brana', 'postal_code' => '40140', 'city' => 'SOUSTONS', 'phone' => '06 72 48 98 35', 'email' => '', 'website' => '', 'supplier_type' => 'Producteur', 'activities' => ['miel'], 'labels' => ['Sans label'], 'client_names' => ['Coopaz'], 'latitude' => 43.7254508, 'longitude' => -1.2757614],
    ['name' => 'LABASTE Eric', 'address' => 'lieu dit Gaouyous', 'postal_code' => '40300', 'city' => 'St Lon les mines', 'phone' => '683771115', 'email' => 'labaste.eric@gmail.com', 'website' => '', 'supplier_type' => 'Producteur', 'activities' => ['canard', 'huile', 'farine', 'fruit', 'fromage chèvre', 'vin', 'boulangerie'], 'labels' => ['AB'], 'client_names' => ['Coopaz', 'Ecorx', 'AMAP du Moun', 'AMAP Labenne', 'AMAP Ondres', 'AMAP Capbreton'], 'latitude' => 43.624161, 'longitude' => -1.163],
];

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['import_conflicts', 'import_batches', 'client_suppliers', 'supplier_labels', 'supplier_activities', 'suppliers', 'clients', 'labels', 'activities'] as $table) {
        $pdo->exec('TRUNCATE TABLE ' . $table);
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    $activityStmt = $pdo->prepare('INSERT INTO activities (name, family, icon_url, is_active) VALUES (:name, :family, :icon_url, 1)');
    foreach ($activities as $activity) {
        $activityStmt->execute([
            ':name' => $activity['name'],
            ':family' => $activity['family'],
            ':icon_url' => '',
        ]);
    }

    $labelStmt = $pdo->prepare('INSERT INTO labels (name, color, is_active) VALUES (:name, :color, 1)');
    foreach ($labels as $label) {
        $labelStmt->execute([':name' => $label['name'], ':color' => $label['color']]);
    }

    $clientStmt = $pdo->prepare('INSERT INTO clients (name, client_type, address, city, postal_code, country, latitude, longitude, phone, email, website, logo_url, is_active) VALUES (:name, :client_type, :address, :city, :postal_code, :country, :latitude, :longitude, :phone, :email, :website, :logo_url, 1)');
    foreach ($clients as $client) {
        $clientStmt->execute([
            ':name' => $client['name'],
            ':client_type' => $client['client_type'],
            ':address' => $client['address'],
            ':city' => $client['city'],
            ':postal_code' => $client['postal_code'],
            ':country' => 'France',
            ':latitude' => $client['latitude'],
            ':longitude' => $client['longitude'],
            ':phone' => $client['phone'],
            ':email' => $client['email'],
            ':website' => $client['website'],
            ':logo_url' => $client['logo_url'],
        ]);
    }

    $activityRows = $pdo->query('SELECT id, name FROM activities')->fetchAll();
    $labelRows = $pdo->query('SELECT id, name FROM labels')->fetchAll();
    $clientRows = $pdo->query('SELECT id, name FROM clients')->fetchAll();

    $activityMap = [];
    foreach ($activityRows as $row) {
        $activityMap[normalize_text($row['name'])] = (int)$row['id'];
    }
    $labelMap = [];
    foreach ($labelRows as $row) {
        $labelMap[normalize_text($row['name'])] = (int)$row['id'];
    }
    $clientMap = [];
    foreach ($clientRows as $row) {
        $clientMap[$row['name']] = (int)$row['id'];
    }

    $supplierStmt = $pdo->prepare('INSERT INTO suppliers (name, normalized_name, address, city, postal_code, country, latitude, longitude, phone, email, website, supplier_type, activity_text, notes) VALUES (:name, :normalized_name, :address, :city, :postal_code, :country, :latitude, :longitude, :phone, :email, :website, :supplier_type, :activity_text, :notes)');
    $linkClientStmt = $pdo->prepare('INSERT IGNORE INTO client_suppliers (client_id, supplier_id, source) VALUES (:client_id, :supplier_id, :source)');
    $linkActivityStmt = $pdo->prepare('INSERT IGNORE INTO supplier_activities (supplier_id, activity_id) VALUES (:supplier_id, :activity_id)');
    $linkLabelStmt = $pdo->prepare('INSERT IGNORE INTO supplier_labels (supplier_id, label_id) VALUES (:supplier_id, :label_id)');

    foreach ($suppliers as $supplier) {
        $supplierStmt->execute([
            ':name' => $supplier['name'],
            ':normalized_name' => normalize_text($supplier['name']),
            ':address' => $supplier['address'],
            ':city' => $supplier['city'],
            ':postal_code' => $supplier['postal_code'],
            ':country' => 'France',
            ':latitude' => $supplier['latitude'],
            ':longitude' => $supplier['longitude'],
            ':phone' => $supplier['phone'],
            ':email' => $supplier['email'],
            ':website' => $supplier['website'],
            ':supplier_type' => $supplier['supplier_type'],
            ':activity_text' => implode('; ', $supplier['activities']),
            ':notes' => 'Seed depuis extrait Excel',
        ]);

        $supplierId = (int)$pdo->lastInsertId();

        foreach ($supplier['client_names'] as $clientName) {
            if (!isset($clientMap[$clientName])) {
                continue;
            }
            $linkClientStmt->execute([
                ':client_id' => $clientMap[$clientName],
                ':supplier_id' => $supplierId,
                ':source' => 'seed',
            ]);
        }

        foreach ($supplier['activities'] as $activityName) {
            $key = normalize_text($activityName);
            if (!isset($activityMap[$key])) {
                continue;
            }
            $linkActivityStmt->execute([
                ':supplier_id' => $supplierId,
                ':activity_id' => $activityMap[$key],
            ]);
        }

        foreach ($supplier['labels'] as $labelName) {
            $key = normalize_text($labelName);
            if (!isset($labelMap[$key])) {
                continue;
            }
            $linkLabelStmt->execute([
                ':supplier_id' => $supplierId,
                ':label_id' => $labelMap[$key],
            ]);
        }
    }

    $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')->execute([':k' => 'org_name', ':v' => 'Limap']);
    $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')->execute([':k' => 'org_logo_url', ':v' => 'https://i.imgur.com/0M4EM5x.png']);

    echo "Seed OK\n";
    echo "Clients: " . $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn() . "\n";
    echo "Activités: " . $pdo->query('SELECT COUNT(*) FROM activities')->fetchColumn() . "\n";
    echo "Labels: " . $pdo->query('SELECT COUNT(*) FROM labels')->fetchColumn() . "\n";
    echo "Fournisseurs: " . $pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn() . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Erreur seed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
