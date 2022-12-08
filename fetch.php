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
    return implode(' ', [$user, $token]);
}

function fetch($url) {
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_URL, $url);
    // curl_setopt($curl_request, CURLOPT_USERPWD, get_credentials());
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0',
        "Authorization: ". get_credentials() ."",
        "Accept: application/vnd.github+json",
        "X-GitHub-Api-Version: 2022-11-28"
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
    echo json_encode($json, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
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
    return "$account/$repo";
}

function getComposerRequire($composer) {
    return $composer["require"];
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

$modules = [
    'bringyourownideas/silverstripe-maintenance',
    'bringyourownideas/silverstripe-composer-update-checker',
    'colymba/gridfield-bulk-editing-tools',
    'composer/installers',
    'cwp/agency-extensions',
    'cwp/cwp',
    'cwp/cwp-core',
    'cwp/cwp-search',
    'cwp/starter-theme',
    'cwp/watea-theme',
    'cwp-themes/default',
    'dnadesign/silverstripe-elemental',
    'dnadesign/silverstripe-elemental-subsites',
    'dnadesign/silverstripe-elemental-userforms',
    'lekoala/silverstripe-debugbar',
    'silverstripe/activedirectory',
    'silverstripe/admin',
    'silverstripe/asset-admin',
    'silverstripe/assets',
    'silverstripe/auditor',
    'silverstripe/behat-extension',
    'silverstripe/blog',
    'silverstripe/campaign-admin',
    'silverstripe/ckan-registry',
    'silverstripe/cms',
    'silverstripe/comment-notifications',
    'silverstripe/comments',
    'silverstripe/config',
    'silverstripe/content-widget',
    'silverstripe/contentreview',
    'silverstripe/crontask',
    'silverstripe/documentconverter',
    'silverstripe/elemental-bannerblock',
    'silverstripe/elemental-fileblock',
    'silverstripe/environmentcheck',
    'silverstripe/errorpage',
    'silverstripe/eslint-config',
    'silverstripe/externallinks',
    'silverstripe/framework',
    'silverstripe/fulltextsearch',
    'silverstripe/graphql',
    'silverstripe/graphql-devtools',
    'silverstripe/gridfieldqueuedexport',
    'silverstripe/html5',
    'silverstripe/hybridsessions',
    'silverstripe/iframe',
    'silverstripe/installer',
    'silverstripe/ldap',
    'silverstripe/lumberjack',
    'silverstripe/mimevalidator',
    'silverstripe/postgresql',
    'silverstripe/realme',
    'silverstripe/session-manager',
    'silverstripe/recipe-authoring-tools',
    'silverstripe/recipe-blog',
    'silverstripe/recipe-ccl',
    'silverstripe/recipe-cms',
    'silverstripe/recipe-collaboration',
    'silverstripe/recipe-content-blocks',
    'silverstripe/recipe-core',
    'silverstripe/recipe-form-building',
    'silverstripe/recipe-plugin',
    'silverstripe/recipe-reporting-tools',
    'silverstripe/recipe-services',
    'silverstripe/recipe-solr-search',
    'silverstripe/registry',
    'silverstripe/reports',
    'silverstripe/restfulserver',
    'silverstripe/securityreport',
    'silverstripe/segment-field',
    'silverstripe/sharedraftcontent',
    'silverstripe/siteconfig',
    'silverstripe/sitewidecontent-report',
    'silverstripe/spamprotection',
    'silverstripe/sqlite3',
    'silverstripe/sspak',
    'silverstripe/staticpublishqueue',
    'silverstripe/subsites',
    'silverstripe/tagfield',
    'silverstripe/taxonomy',
    'silverstripe/textextraction',
    'silverstripe/userforms',
    'silverstripe/vendor-plugin',
    'silverstripe/versioned',
    'silverstripe/versioned-admin',
    'silverstripe/versionfeed',
    'silverstripe/widgets',
    'silverstripe/webpack-config',
    'silverstripe-themes/simple',
    'symbiote/silverstripe-advancedworkflow',
    'symbiote/silverstripe-gridfieldextensions',
    'symbiote/silverstripe-multivaluefield',
    'symbiote/silverstripe-queuedjobs',
    'tractorcow/classproxy',
    'tractorcow/silverstripe-fluent',
    'tractorcow/silverstripe-proxy-db',
    'undefinedoffset/sortablegridfield',
    'silverstripe/mfa',
    'silverstripe/totp-authenticator',
    'silverstripe/webauthn-authenticator',
    'silverstripe/login-forms',
    'silverstripe/security-extensions',
];

foreach ($modules as $packagist) {
    $github = packagist_to_github($packagist);

    // fetch data from github API
    $json = fetch_json("https://api.github.com/repos/$github/branches");
    $lastVersion = identifyLastBranch($json);

    //fetch raw file from github
    $j = fetch_json("https://raw.githubusercontent.com/$github/$lastVersion/composer.json");
    
    dump_json(outputFormat($github, $lastVersion, $j));
}