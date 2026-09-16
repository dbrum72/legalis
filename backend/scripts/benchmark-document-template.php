<?php

// Read-only microbenchmark of the committed synthetic fixture; no database or output DOCX.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = new \App\Services\DocxTemplateService;
$source = __DIR__.'/../tests/fixtures/word-template.docx';
$values = [
    'escritorio.nome' => 'Escritório de demonstração',
    'pasta.nome' => 'Preparação de documento',
    'data.hoje' => '16/09/2026',
    'campo.texto' => "Texto com acentos, João & Maria e sinais < >.\nSegunda linha.",
    'campo.referencia' => 'DEMO-001',
];
for ($warmup = 0; $warmup < 5; $warmup++) {
    $service->render($source, $values);
}
memory_reset_peak_usage();
$durations = [];
for ($iteration = 0; $iteration < 50; $iteration++) {
    $start = hrtime(true);
    $service->render($source, $values);
    $durations[] = (hrtime(true) - $start) / 1_000_000;
}
sort($durations);
echo json_encode([
    'scope' => 'DOCX XML render only; excludes HTTP, database, file output and visual rendering',
    'php' => PHP_VERSION,
    'fixture_sha256' => hash_file('sha256', $source),
    'fixture_bytes' => filesize($source),
    'warmup' => 5,
    'samples' => count($durations),
    'concurrency' => 1,
    'p50_ms' => round(($durations[24] + $durations[25]) / 2, 3),
    'p95_ms' => round($durations[47], 3),
    'max_ms' => round(max($durations), 3),
    'process_peak_mib' => round(memory_get_peak_usage(true) / 1_048_576, 2),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
