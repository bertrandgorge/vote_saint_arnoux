<?php
// Grille d'évaluation — Sélection 1. Les pondérations totalisent 100.

return [
    1 => [
        'short'  => 'Vision',
        'title'  => 'Vision du projet — compréhension des enjeux pastoraux et du programme',
        'weight' => 25,
        'low'    => 'Note interchangeable avec n’importe quelle consultation ; vocabulaire de programme recopié sans appropriation.',
        'high'   => 'Compréhension de la dimension pastorale autant que technique : le candidat a saisi qu’il s’agit d’un lieu de vie communautaire, pas seulement d’un ERP.',
    ],
    2 => [
        'short'  => 'Motivation',
        'title'  => 'Lettre de candidature et motivation',
        'weight' => 20,
        'low'    => 'Lettre type ; erreurs sur le nom du maître d’ouvrage ou de l’opération ; silence sur la disponibilité.',
        'high'   => 'Motivation située : pourquoi cette opération, pour cette agence, maintenant. Lettre signée du mandataire, engageant le groupement sur sa disponibilité aux dates du concours.',
    ],
    3 => [
        'short'  => 'Groupement',
        'title'  => 'Organisation du groupement — équipe, moyens, complémentarité architecte et bureaux d’études',
        'weight' => 17,
        'low'    => 'Compétences déclarées sans titulaire identifié ; absence de la compétence cultuelle, acoustique, hydraulique, de scénographie ou de démolition ; groupement constitué pour l’occasion sans antécédent.',
        'high'   => 'Toutes les compétences du règlement §4 couvertes et nommées, avec le bureau d’études qui les porte — y compris la scénographie et la compétence construction et démolition. Complémentarité démontrée par des collaborations antérieures.',
    ],
    4 => [
        'short'  => 'Références',
        'title'  => 'Références — expérience cultuelle ou diocésaine, établissement recevant du public ; caractère significatif et adéquation au projet',
        'weight' => 15,
        'low'    => 'Liste de projets prestigieux sans lien avec l’opération ; rôle du candidat non explicité ; références portées par un cotraitant et non par le mandataire ; contacts non joignables.',
        'high'   => 'Deux références au moins réellement comparables : ERP recevant du public, lieu cultuel, équipement institutionnel, site contraint ou projet phasé. Le rôle exact du candidat y est précisé, ainsi que le coût et l’issue. Chaque référence est vérifiable : maître d’ouvrage, contact joignable, année de livraison.',
    ],
    5 => [
        'short'  => 'Assurances',
        'title'  => 'Assurances, garanties professionnelles et solidité financière du mandataire',
        'weight' => 13,
        'low'    => 'Attestations périmées, manquantes pour un cotraitant, ou montants de garantie sans rapport avec le coût de l’ouvrage.',
        'high'   => 'Attestations en cours de validité pour le mandataire et chaque cotraitant, décennale mentionnant expressément l’opération ou son objet.',
    ],
    6 => [
        'short'  => 'RSE',
        'title'  => 'Responsabilité sociétale et environnementale du candidat, dont compétence et références en performance environnementale',
        'weight' => 10,
        'note'   => 'Le critère RSE porte sur la démarche du candidat lui-même, pas sur le projet ; la qualité environnementale annoncée du projet relève des sélections 2 et 3',
        'low'    => 'Déclaration d’intention sans preuve ; confusion avec la performance environnementale du projet, qui s’apprécie en Sélections 2 et 3.',
        'high'   => 'Démarche propre à l’agence et à ses cotraitants, documentée, avec au moins une référence de performance environnementale.',
    ],
];
