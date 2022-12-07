<?php

function get_credentials() {
    $s = file_get_contents('.env');
    $s = trim($s);
    $user = '';
    $token = '';
    foreach (explode("\n", $s) as $line) {
        $a = explode('=', $line);
        if ($a[0] == 'GITHUB_USER') {
            $user = str_replace('"', '', $a[1]);
        } elseif ($a[0] == 'GITHUB_TOKEN') {
            $token = str_replace('"', '', $a[1]);
        }
    }
    return implode(':', [$user, $token]);
}

function fetch($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0'
    ]);
    curl_setopt($ch, CURLOPT_USERPWD, get_credentials());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    sleep(1); // don't exceed rate limit
    $s = curl_exec($ch);
    curl_close($ch);
    return $s;
}

function fetch_json($url) {
    $s = fetch($url);
    return json_decode($s);
}

function dump_json($j) {
    echo json_encode($j, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
}

// silverstripe/admin => silverstripe/silverstripe-admin
function packagist_to_github($packagist) {
    list($account, $repo) = explode('/', $packagist);
    if ($account == 'silverstripe') {
        if (strpos($repo, 'recipe') !== 0 && $repo != 'comment-notifications' && $repo != 'vendor-plugin') {
            $repo = 'silverstripe-' . $repo;
        }
    }
    if ($account == 'cwp') {
        $account = 'silverstripe';
        if (strpos($repo, 'cwp') !== 0) {
            $repo = 'cwp-' . $repo;
        }
        if ($repo == 'cwp-agency-extensions') {
            $repo = 'cwp-agencyextensions';
        }
    }
    if ($account == 'tractorcow' && $repo == 'silverstripe-fluent') {
        $account = 'tractorcow-farm';
    }
    return "$account/$repo";
}

// put in your data here
$data = [
    'silverstripe/admin'
];

foreach ($data as $packagist) {
    $github = packagist_to_github($packagist);

    // fetch data from github API
    // $j = fetch_json("https://api.github.com/repos/$github/branches");
    // dump_json($j);

    // fetch raw file from github
    $j = fetch_json("https://raw.githubusercontent.com/$github/1/composer.json");
    dump_json($j);
}
