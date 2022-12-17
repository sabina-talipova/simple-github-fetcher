<?php

include './modules.php';

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
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_URL, $url);
    curl_setopt($curl_request, CURLOPT_USERPWD, get_credentials());
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0',
        // "Authorization: ". get_credentials() ."",
        // "Accept: application/vnd.github+json",
        // "X-GitHub-Api-Version: 2022-11-28"
    ]);

    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
    sleep(1); // don't exceed rate limit
    $result = curl_exec($curl_request);
    curl_close($curl_request);
    return $result;
}

function fetch_json($url) {
    $result = fetch($url);
    return json_decode($result);
}

function dump_json($json) {
    $string = json_encode($json, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
    file_put_contents('modules.txt', $string, FILE_APPEND);
}

function packagist_to_github($packagist) {
    list($account, $repo) = explode('/', $packagist);
    if ($account == 'silverstripe') {
        if (strpos($repo, 'recipe') !== 0 && $repo != 'comment-notifications' && $repo != 'vendor-plugin' && $repo != 'eslint-config') {
            $repo = 'silverstripe-' . $repo;
        }
    }
    if ($account == 'colymba') {
        $repo = 'GridfieldBulkEditingTools';
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
    $arr = [
        "cc" => "creative-commoners/$repo",
        "ss" => "$account/$repo"
    ];
    return $arr;
    // return "$account/$repo";
}

function getComposerRequire($composer) {
    $arr = [
        'require' => $composer->require,
        'require-dev' => $composer->{'require-dev'},
    ];
    return $arr;
}

function outputFormat($name, $version, $composer) {
    return [
        'module' => $name,
        'last_branch' => $version,
        'composer' => getComposerRequire($composer),
    ];
}

function identifyLastBranch($array) {
    $currentLast = 0;
    foreach($array as $key => $val) {
        foreach($val as $k => $v) {
            if ($k != 'name') {
                continue;
            }
            $version = (float) $v;
            if ($version > 0) {
                $currentLast = $version < $currentLast ? $currentLast : $version;
            }
        }
    }

    return $currentLast;
}

foreach ($modules as $packagist) {
    $githubSS = packagist_to_github($packagist)["ss"];
    $githubCC = packagist_to_github($packagist)["cc"];

    // fetch data from github API
    $json = fetch_json("https://api.github.com/repos/$githubSS/branches");
    $lastVersion = identifyLastBranch($json);

    //fetch raw file from github
    // $j = fetch_json("https://raw.githubusercontent.com/$github/$lastVersion/composer.json");
    $j = fetch_json("https://raw.githubusercontent.com/$githubCC/pulls/$lastVersion/upgrade-cms5/composer.json");
    
    dump_json(outputFormat($githubSS, $lastVersion, $j));
}