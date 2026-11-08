<?php

$expectedFiles = array(
    'tests/GuardScriptsTest.php',
    'tests/StatssalesGraphTest.php',
    'tests/StatssalesHookTest.php',
    'tests/StatssalesInstallTest.php',
    'tests/StatssalesMetadataTest.php',
    'tests/StatssalesSetOptionTest.php',
    'tests/StatssalesStatesTest.php',
    'tests/StatssalesTotalsTest.php',
    'tests/StatssalesUpgradeTest.php',
);

$expectedTestCount = 39;
$diagnosticPatterns = array(
    'Notice:',
    'Warning:',
    'Deprecated:',
    'Strict Standards:',
    'Parse error:',
    'Fatal error:',
);

$root = dirname(__DIR__);
$seed = isset($argv[1]) ? (int) $argv[1] : 20261108;

if (!preg_match('/^PHP 5\.6\.40/', shell_exec('php -v'))) {
    fwrite(STDERR, "PHP version must be 5.6.40\n");
    exit(1);
}

$found = array();
foreach (scandir($root . '/tests') as $entry) {
    if (substr($entry, -8) === 'Test.php') {
        $found[] = 'tests/' . $entry;
    }
}
sort($found);
if ($found !== $expectedFiles) {
    fwrite(STDERR, "Test file drift\nexpected:\n" . implode("\n", $expectedFiles) . "\nfound:\n" . implode("\n", $found) . "\n");
    exit(1);
}

$files = $expectedFiles;
srand($seed);
shuffle($files);

$phar = getenv('STATSSALES_PHPUNIT_PHAR');
if (!$phar || !is_file($phar)) {
    fwrite(STDERR, "STATSSALES_PHPUNIT_PHAR missing\n");
    exit(1);
}

$tmpDir = sys_get_temp_dir() . '/statssales-phpunit-' . getmypid() . '-' . $seed;
if (!mkdir($tmpDir, 0700, true) && !is_dir($tmpDir)) {
    fwrite(STDERR, "Cannot create temp dir\n");
    exit(1);
}

$bootstrap = $root . '/tests/bootstrap.php';
$fileEntries = '';
foreach ($files as $rel) {
    $fileEntries .= '            <file>' . $root . '/' . $rel . "</file>\n";
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="' . $bootstrap . '"
         colors="false"
         backupGlobals="false"
         processIsolation="false"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true">
    <testsuites>
        <testsuite name="module-tests">
' . $fileEntries . '        </testsuite>
    </testsuites>
</phpunit>
';

$xmlPath = $tmpDir . '/phpunit.xml';
file_put_contents($xmlPath, $xml);

$cmd = escapeshellarg(PHP_BINARY)
    . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d date.timezone=UTC '
    . escapeshellarg($phar)
    . ' --configuration ' . escapeshellarg($xmlPath);

$output = array();
$exit = 0;
exec($cmd . ' 2>&1', $output, $exit);

$fullOutput = implode("\n", $output);
foreach ($diagnosticPatterns as $pat) {
    if (strpos($fullOutput, $pat) !== false) {
        fwrite(STDERR, "Diagnostic in PHPUnit output: $pat\n$fullOutput\n");
        exit(1);
    }
}

$okLine = null;
foreach ($output as $line) {
    if (preg_match('/^OK \(\d+ tests?, \d+ assertions?\)$/', $line)) {
        $okLine = $line;
    }
}

if ($exit !== 0) {
    fwrite(STDERR, $fullOutput . "\n");
    exit($exit);
}

if (!$okLine) {
    fwrite(STDERR, "Missing exact OK summary line\n" . $fullOutput . "\n");
    exit(1);
}

if (!preg_match('/^OK \(' . $expectedTestCount . ' tests?, \d+ assertions?\)$/', $okLine)) {
    fwrite(STDERR, "Unexpected OK summary: $okLine\n");
    exit(1);
}

echo $fullOutput . "\n";
