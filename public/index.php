<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$method = $_SERVER['REQUEST_METHOD'];
$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/') ?: '/';

if ($method === 'POST') {
    check_csrf();
}

// ---------------------------------------------------------------- Connexion

if ($path === '/connexion') {
    if ($method === 'POST') {
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $member = db_one('SELECT * FROM members WHERE LOWER(email) = ?', [$email]);
        if ($member) {
            $link = create_login_link((int) $member['id'], config('email_link_ttl_hours') * 3600);
            send_login_email($member, $link);
        }
        // Même réponse que l'adresse soit connue ou non
        render('login', ['sent' => true, 'email' => $email]);
        exit;
    }
    if (current_member()) {
        redirect('/');
    }
    render('login', ['sent' => false, 'email' => '']);
    exit;
}

if (preg_match('~^/auth/([a-f0-9]{64})$~', $path, $m)) {
    if (login_with_token($m[1])) {
        redirect('/');
    }
    render('message', [
        'title'   => 'Lien expiré',
        'message' => 'Ce lien de connexion n’est plus valide. Demandez-en un nouveau.',
        'action'  => ['/connexion', 'Recevoir un nouveau lien'],
    ]);
    exit;
}

if ($path === '/deconnexion' && $method === 'POST') {
    logout();
    redirect('/connexion');
}

// ---------------------------------------------------------------- Pages du jury

$me = require_login();
$canVote = in_array($me['role'], ['votant', 'consultatif'], true);

if ($path === '/') {
    render('home', [
        'me'         => $me,
        'architects' => get_architects(),
        'ranking'    => ranking(),
        'progress'   => member_progress((int) $me['id']),
        'canVote'    => $canVote,
    ]);
    exit;
}

if (preg_match('~^/architecte/(\d+)(/tableau)?$~', $path, $m)) {
    $architect = get_architect((int) $m[1]) ?? not_found();
    if (!empty($m[2])) {
        render('dashboard', ['me' => $me, 'architect' => $architect, 'data' => architect_dashboard((int) $architect['id'])]);
    } else {
        $ids = array_column(get_architects(), 'id');
        $pos = array_search($architect['id'], $ids);
        render('architect', [
            'me'        => $me,
            'architect' => $architect,
            'scores'    => member_scores((int) $me['id'], (int) $architect['id']),
            'canVote'   => $canVote,
            'prevId'    => $ids[$pos - 1] ?? null,
            'nextId'    => $ids[$pos + 1] ?? null,
            'position'  => $pos + 1,
            'count'     => count($ids),
        ]);
    }
    exit;
}

// ---------------------------------------------------------------- API JSON

if ($path === '/api/score' && $method === 'POST') {
    if (!$canVote) {
        json_response(['error' => 'Vous n’êtes pas membre du jury.'], 403);
    }
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $architectId = (int) ($input['architect_id'] ?? 0);
    $criterion = (int) ($input['criterion'] ?? 0);
    $score = $input['score'] ?? null;
    if (!get_architect($architectId) || !isset(criteria()[$criterion])
        || ($score !== null && (!is_int($score) || $score < 0 || $score > 5))) {
        json_response(['error' => 'Requête invalide'], 400);
    }
    save_score((int) $me['id'], $architectId, $criterion, $score);
    json_response(['ok' => true, 'total' => round_or_null(weighted_total(member_scores((int) $me['id'], $architectId)), 1)]);
}

if (preg_match('~^/api/architecte/(\d+)/tableau$~', $path, $m)) {
    get_architect((int) $m[1]) ?? json_response(['error' => 'Introuvable'], 404);
    json_response(architect_dashboard((int) $m[1]));
}

if ($path === '/api/classement') {
    json_response(ranking());
}

// ---------------------------------------------------------------- Réglages (admin)

if (str_starts_with($path, '/reglages')) {
    require_admin();
    require APP_ROOT . '/src/settings.php';
    exit;
}

not_found();
