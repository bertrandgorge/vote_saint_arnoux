<?php
// Import de la liste des architectes depuis un fichier TSV.
// Colonnes reconnues (via l'en-tête) : Nom, Prénom, Agence, Ville / implantation, Site internet, Drive.
// Une agence déjà présente (même nom) n'est pas dupliquée : seuls ses champs vides sont complétés.

function parse_architects_tsv(string $content): array
{
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $header = str_getcsv(array_shift($lines), "\t", '"', '');

    $map = [];
    foreach ($header as $i => $label) {
        $key = mb_strtolower(trim($label));
        $field = match (true) {
            str_starts_with($key, 'prénom'), str_starts_with($key, 'prenom') => 'firstname',
            $key === 'nom', $key === 'référent', $key === 'referent'            => 'lastname',
            str_starts_with($key, 'agence')                                     => 'agency',
            str_starts_with($key, 'ville')                                      => 'city',
            str_starts_with($key, 'site')                                       => 'website',
            str_contains($key, 'drive')                                         => 'drive_url',
            default                                                             => null,
        };
        if ($field) {
            $map[$field] = $i;
        }
    }
    if (!isset($map['agency'])) {
        throw new RuntimeException('Colonne « Agence » introuvable dans l’en-tête du fichier.');
    }

    $rows = [];
    foreach ($lines as $line) {
        if (trim($line) === '') {
            continue;
        }
        $cols = str_getcsv($line, "\t", '"', '');
        $get = fn ($f) => isset($map[$f]) ? trim($cols[$map[$f]] ?? '') : '';
        if ($get('agency') === '') {
            continue;
        }
        $rows[] = [
            'agency'    => $get('agency'),
            'referent'  => trim($get('firstname') . ' ' . $get('lastname')),
            'city'      => $get('city'),
            'website'   => $get('website'),
            'drive_url' => $get('drive_url'),
        ];
    }
    return $rows;
}

/** @return array{created:int, updated:int} */
function import_architects(array $rows): array
{
    $created = $updated = 0;
    foreach ($rows as $row) {
        $existing = db_one('SELECT * FROM architects WHERE LOWER(agency) = LOWER(?)', [$row['agency']]);
        if (!$existing) {
            create_architect($row);
            $created++;
            continue;
        }
        $changes = [];
        foreach (['referent', 'city', 'website', 'drive_url'] as $f) {
            $value = in_array($f, ['website', 'drive_url'], true) ? normalize_url($row[$f]) : $row[$f];
            if ($existing[$f] === '' && $value !== '') {
                $changes[$f] = $value;
            }
        }
        if ($changes) {
            $set = implode(', ', array_map(fn ($f) => "$f = ?", array_keys($changes)));
            db_exec("UPDATE architects SET $set WHERE id = ?", [...array_values($changes), $existing['id']]);
            $updated++;
        }
    }
    return ['created' => $created, 'updated' => $updated];
}
