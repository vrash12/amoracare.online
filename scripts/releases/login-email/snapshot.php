<?php
declare(strict_types=1);

$config = json_decode(file_get_contents(__DIR__.'/release-config.json'), true, 512, JSON_THROW_ON_ERROR);
$root = $config['root'];
require "$root/vendor/autoload.php";
$app = require "$root/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
$schema = $db->getSchemaBuilder();
$mode = $argv[1] ?? 'preflight';
$snapshot = __DIR__.'/database-before.json';
$tables = ['users', 'children', 'parent_matching_profiles', 'adoption_cases', 'adoption_case_documents',
    'adoption_matching_runs', 'adoption_matching_results', 'donors', 'donations', 'donation_items'];
$rows = fn ($table) => $db->table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
if ($mode === 'preflight') {
    $collision = $db->table('users')->whereNull('deleted_at')->selectRaw('LOWER(TRIM(email)) AS email, COUNT(*) AS total')
        ->groupByRaw('LOWER(TRIM(email))')->havingRaw('COUNT(*) > 1')->exists();
    if ($collision) throw new RuntimeException('Existing accounts have normalized email conflicts');
    $unique = array_filter($schema->getIndexes('users'), fn ($i) => $i['unique'] && $i['columns'] === ['email']);
    if (!$unique) throw new RuntimeException('Email unique constraint missing');
    echo json_encode(['result' => 'passed', 'mode' => $mode, 'users' => $db->table('users')->count(),
        'deleted_accounts' => $db->table('users')->whereNotNull('deleted_at')->count(), 'unique_email_index' => true], JSON_PRETTY_PRINT).PHP_EOL;
    exit;
}
if ($mode === 'before') {
    if (file_exists($snapshot)) throw new RuntimeException('Snapshot already exists');
    $data = [];
    foreach ($tables as $table) $data[$table] = $rows($table);
    file_put_contents($snapshot, json_encode($data, JSON_THROW_ON_ERROR));
    chmod($snapshot, 0600);
} elseif ($mode === 'restore') {
    // Only used during this release's maintenance window, before accepting user traffic.
    $data = json_decode(file_get_contents($snapshot), true, 512, JSON_THROW_ON_ERROR);
    $db->transaction(function () use ($data, $db, $schema) {
        foreach ($data['users'] as $row) {
            if ($schema->hasColumn('users', 'deleted_email') && !array_key_exists('deleted_email', $row)) $row['deleted_email'] = null;
            $db->table('users')->where('id', $row['id'])->update($row);
        }
        $db->table('migrations')->where('migration', '2026_10_28_000001_release_deleted_user_emails')->delete();
    });
} else {
    $data = json_decode(file_get_contents($snapshot), true, 512, JSON_THROW_ON_ERROR);
    foreach ($data as $table => $before) {
        $after = $rows($table);
        if ($table !== 'users') {
            if ($after !== $before) throw new RuntimeException("Unexpected changes to $table");
            continue;
        }
        if (count($before) !== count($after)) throw new RuntimeException('User count changed');
        foreach ($before as $i => $old) {
            $new = $after[$i];
            if ($old['deleted_at'] !== null) {
                if ($new['deleted_email'] !== $old['email'] || !str_ends_with($new['email'], '@deleted.invalid') || $new['remember_token'] !== null) throw new RuntimeException('Deleted account not correctly archived');
                $new['email'] = $old['email']; $new['remember_token'] = $old['remember_token'];
            } else {
                if ($new['email'] !== strtolower(trim($old['email'])) || $new['deleted_email'] !== null) throw new RuntimeException('Active account changed unexpectedly');
                $new['email'] = $old['email'];
            }
            unset($new['deleted_email']);
            if ($new !== $old) throw new RuntimeException('Unrelated user fields changed');
        }
    }
}
echo json_encode(['result' => 'passed', 'mode' => $mode, 'counts' => array_map('count', $data)], JSON_PRETTY_PRINT).PHP_EOL;
