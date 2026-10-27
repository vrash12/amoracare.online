<?php
declare(strict_types=1);
$config = json_decode(file_get_contents(__DIR__.'/release-config.json'), true, 512, JSON_THROW_ON_ERROR);
$root = $config['root'];
$release = $config['release'];
if (realpath($root) !== $root || realpath(__DIR__) !== $release || !str_starts_with($release, '/home/u330835917/deployments/login-email-')) throw new RuntimeException('Unexpected paths');
$manifest = json_decode(file_get_contents("$release/patch/manifest.json"), true, 512, JSON_THROW_ON_ERROR);
$run = function (array $arguments) use ($root): void {
    $process = proc_open($arguments, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    if (!is_resource($process)) throw new RuntimeException('Could not start command');
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException($out.$err);
    if ((in_array(__DIR__.'/verify.php', $arguments, true) || in_array(__DIR__.'/snapshot.php', $arguments, true)) && !str_contains($out, '"result": "passed"')) throw new RuntimeException($out.$err);
    echo $out;
};
if (file_exists("$release/deployed.json")) throw new RuntimeException('Release already deployed');
foreach ($manifest as $file => $hashes) {
    if (str_contains($file, '..') || str_starts_with($file, '/')) throw new RuntimeException('Invalid path');
    $current = is_file("$root/$file") ? hash_file('sha256', "$root/$file") : null;
    if ($current !== $hashes['before'] || hash_file('sha256', "$release/patch/$file") !== $hashes['after']) throw new RuntimeException("Source changed since review: $file");
    $backup = "$release/original/$file";
    if (file_exists($backup)) throw new RuntimeException('Backup already exists');
    if ($hashes['before'] !== null) {
        if (!is_dir(dirname($backup))) mkdir(dirname($backup), 0700, true);
        if (!copy("$root/$file", $backup)) throw new RuntimeException('Backup failed');
        chmod($backup, 0600);
    }
    $run([PHP_BINARY, '-l', "$release/patch/$file"]);
}
$run([PHP_BINARY, "$release/snapshot.php", 'preflight']);
$run([PHP_BINARY, "$release/verify.php", 'stage']);
$run([PHP_BINARY, "$root/artisan", 'down', '--retry=30']);
try {
    $run([PHP_BINARY, "$release/snapshot.php", 'before']);
    $run([PHP_BINARY, "$root/artisan", 'migrate', '--force', '--realpath', '--path='.$release.'/patch/'.$config['migration']]);
    foreach ($manifest as $file => $hashes) {
        $destination = "$root/$file";
        if (!is_dir(dirname($destination))) mkdir(dirname($destination), 0755, true);
        $temporary = $destination.'.login-email-new';
        if (!copy("$release/patch/$file", $temporary) || !chmod($temporary, 0644) || !rename($temporary, $destination)) throw new RuntimeException("Could not replace $file");
    }
    $run([PHP_BINARY, "$root/artisan", 'route:clear']);
    $run([PHP_BINARY, "$root/artisan", 'view:clear']);
    $run([PHP_BINARY, "$root/artisan", 'view:cache']);
    $run([PHP_BINARY, "$release/verify.php", 'live']);
    $run([PHP_BINARY, "$release/snapshot.php", 'after']);
    $run([PHP_BINARY, "$root/artisan", 'queue:restart']);
} catch (Throwable $error) {
    if (is_file("$release/database-before.json")) $run([PHP_BINARY, "$release/snapshot.php", 'restore']);
    foreach ($manifest as $file => $hashes) {
        if ($hashes['before'] === null) {
            if (is_file("$root/$file")) unlink("$root/$file");
        } else {
            copy("$release/original/$file", "$root/$file");
            chmod("$root/$file", 0644);
        }
    }
    $run([PHP_BINARY, "$root/artisan", 'route:clear']);
    $run([PHP_BINARY, "$root/artisan", 'view:clear']);
    throw $error;
} finally {
    $run([PHP_BINARY, "$root/artisan", 'up']);
}
file_put_contents("$release/deployed.json", json_encode(['deployed_utc' => gmdate(DATE_ATOM), 'source_commit' => $config['source_commit'], 'files' => $manifest], JSON_PRETTY_PRINT));
chmod("$release/deployed.json", 0600);
echo "DEPLOYED\n";
