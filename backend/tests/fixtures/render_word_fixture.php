<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = new \App\Services\DocxTemplateService();
$source = __DIR__.'/word-template.docx';
$result = $service->render($source, [
    'escritorio.nome' => 'Escritório de demonstração',
    'pasta.nome' => 'Preparação de documento',
    'data.hoje' => '15/09/2026',
    'campo.texto' => "Texto de demonstração com acentos, João & Maria e sinais < >.\nSegunda linha para conferir as quebras no Word.",
    'campo.referencia' => 'DEMO-001',
]);
$service->write($source, $argv[1], $result['parts']);
echo "Documento de teste gerado.\n";
