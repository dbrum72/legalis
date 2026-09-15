<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class DocxTemplateService
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const TOKEN = '/\{\{\s*([a-z][a-z0-9_]*\.[a-z][a-z0-9_]*)\s*\}\}/';

    // Blocks are confined to one paragraph. Every condition must be filled.
    private const BLOCK = '/\{\{\s*#se\s+([a-z][a-z0-9_]*\.[a-z][a-z0-9_]*(?:\s+[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*)*)\s*\}\}(.*?)\{\{\s*\/se\s*\}\}/s';

    private function paragraphPlan(string $text, ?array $values): array
    {
        preg_match_all(self::BLOCK, $text, $blocks, PREG_OFFSET_CAPTURE);
        $fields = $required = $edits = $regions = [];
        $remaining = $text;
        foreach ($blocks[0] as $index => [$block, $offset]) {
            $body = $blocks[2][$index][0];
            if (str_contains($body, '#se')) {
                $this->invalid('Blocos condicionais não podem ser aninhados.');
            }
            $guards = preg_split('/\s+/', trim($blocks[1][$index][0]));
            $active = $values === null || collect($guards)->every(fn ($key) => trim((string) ($values[$key] ?? '')) !== '');
            $fields = array_merge($fields, $guards);
            $bodyStart = $blocks[2][$index][1];
            $end = $offset + strlen($block);
            $regions[] = [$offset, $end, $active];
            if ($active) {
                $edits[] = [$offset, $bodyStart - $offset, ''];
                $edits[] = [$bodyStart + strlen($body), $end - $bodyStart - strlen($body), ''];
            } else {
                $edits[] = [$offset, strlen($block), ''];
            }
        }
        $remaining = preg_replace(self::BLOCK, '$2', $remaining);
        $remaining = preg_replace(self::TOKEN, '', $remaining);
        if (str_contains($remaining, '{{') || str_contains($remaining, '}}')) {
            $this->invalid('Marcador inválido. Use {{ grupo.campo }} ou {{#se grupo.campo}}texto{{/se}} no mesmo parágrafo, sem aninhar blocos.');
        }
        preg_match_all(self::TOKEN, $text, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $index => [$token, $offset]) {
            $key = $matches[1][$index][0];
            $fields[] = $key;
            $active = true;
            foreach ($regions as [$start, $end, $enabled]) {
                if ($offset >= $start && $offset < $end) {
                    $active = $enabled;
                }
            }
            if ($active) {
                $required[] = $key;
                $edits[] = [$offset, strlen($token), (string) ($values[$key] ?? '')];
            }
        }
        usort($edits, fn ($a, $b) => $b[0] <=> $a[0]);

        return compact('fields', 'required', 'edits');
    }

    public function inspect(string $path): array
    {
        return $this->process($path)['fields'];
    }

    public function render(string $path, array $values): array
    {
        return $this->process($path, $values);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }

    private function xml(string $xml): DOMDocument
    {
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
            $this->invalid('O Word não pode conter entidades XML externas.');
        }
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            if (! $dom->loadXML($xml, LIBXML_NONET)) {
                $this->invalid('O arquivo contém XML inválido.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $dom->encoding = 'UTF-8';

        return $dom;
    }

    private function process(string $path, ?array $values = null): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            $this->invalid('Selecione um arquivo Word .docx válido.');
        }
        $parts = [];
        $fields = [];
        $preview = [];
        $required = [];
        try {
            if ($zip->numFiles > 1000 || $zip->locateName('word/document.xml') === false || $zip->locateName('[Content_Types].xml') === false) {
                $this->invalid('Estrutura Word não suportada. Salve como Documento do Word (.docx).');
            }
            $size = 0;
            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];
                $size += $stat['size'];
                if ($size > 30 * 1024 * 1024 || isset($names[$name]) || str_contains($name, '..') || str_contains($name, '\\') || str_starts_with($name, '/')) {
                    $this->invalid('Arquivo excede os limites ou contém entradas inválidas.');
                }
                $names[$name] = true;
                if (preg_match('/vbaProject|embeddings\/|activeX\//i', $name)) {
                    $this->invalid('Remova macros e objetos incorporados do modelo.');
                }
                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    $this->invalid('Não foi possível ler o arquivo.');
                }
                if (! str_ends_with($name, '.xml') && ! str_ends_with($name, '.rels')) {
                    continue;
                }
                $dom = $this->xml($content);
                $xpath = new DOMXPath($dom);
                if (str_ends_with($name, '.rels')) {
                    foreach ($xpath->query('//*[local-name()="Relationship"]') as $rel) {
                        if ($rel->getAttribute('TargetMode') === 'External' && ! str_ends_with($rel->getAttribute('Type'), '/hyperlink')) {
                            $this->invalid('Incorpore imagens e remova vínculos externos do modelo.');
                        }
                    }
                }
                if (! preg_match('#^word/(document|header\d+|footer\d+|footnotes|endnotes)\.xml$#', $name)) {
                    if (str_contains($dom->textContent, '{{')) {
                        $this->invalid('Há marcadores em uma parte não suportada do Word.');
                    }

                    continue;
                }
                $xpath->registerNamespace('w', self::NS);
                if ($name === 'word/document.xml' && $dom->documentElement->namespaceURI !== self::NS) {
                    $this->invalid('Use o formato Documento do Word padrão, sem Strict Open XML.');
                }
                if ($xpath->query('//w:ins | //w:del | //w:altChunk | //w:instrText')->length) {
                    $this->invalid('Aceite as revisões e converta campos dinâmicos do Word em texto antes de importar.');
                }
                foreach ($xpath->query('//w:p[not(ancestor::w:p)]') as $paragraph) {
                    $nodes = iterator_to_array($xpath->query('.//w:t', $paragraph));
                    $text = implode('', array_map(fn ($n) => $n->textContent, $nodes));
                    $plan = $this->paragraphPlan($text, $values);
                    foreach ($plan['fields'] as $field) {
                        $fields[$field] = true;
                    }
                    $required = array_merge($required, $plan['required']);
                    if (count($fields) > 100) {
                        $this->invalid('Use até 100 campos por modelo.');
                    }
                    if ($values !== null) {
                        // Reverse offsets keep earlier matches stable, even across Word runs.
                        foreach ($plan['edits'] as [$offset, $tokenLength, $value]) {
                            $cursor = 0;
                            $end = $offset + $tokenLength;
                            $inserted = false;
                            foreach ($nodes as $node) {
                                $original = $node->textContent;
                                $length = strlen($original);
                                if ($cursor < $end && $cursor + $length > $offset) {
                                    $startInNode = max(0, $offset - $cursor);
                                    $endInNode = min($length, $end - $cursor);
                                    $node->textContent = substr($original, 0, $startInNode).($inserted ? '' : $value).substr($original, $endInNode);
                                    $node->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                                    $inserted = true;
                                }
                                $cursor += $length;
                            }
                        }
                        foreach ($nodes as $node) {
                            if (! str_contains($node->textContent, "\n")) {
                                continue;
                            }
                            $lines = explode("\n", str_replace("\r", '', $node->textContent));
                            foreach ($lines as $lineIndex => $line) {
                                if ($lineIndex) {
                                    $node->parentNode->insertBefore($dom->createElementNS(self::NS, 'w:br'), $node);
                                }
                                $part = $dom->createElementNS(self::NS, 'w:t');
                                $part->appendChild($dom->createTextNode($line));
                                $part->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
                                $node->parentNode->insertBefore($part, $node);
                            }
                            $node->parentNode->removeChild($node);
                        }
                    }
                    if ($values !== null) {
                        foreach ($plan['edits'] as [$offset, $length, $value]) {
                            $text = substr_replace($text, $value, $offset, $length);
                        }
                    }
                    $preview[] = $text;
                }
                $parts[$name] = $dom->saveXML();
            }
        } finally {
            $zip->close();
        }

        return ['fields' => array_keys($fields), 'required' => array_values(array_unique($required)), 'parts' => $parts, 'text' => implode("\n", $preview)];
    }

    public function write(string $source, string $destination, array $parts): void
    {
        if (! copy($source, $destination)) {
            throw new \RuntimeException('Falha ao copiar modelo.');
        }
        $zip = new ZipArchive;
        if ($zip->open($destination) !== true) {
            throw new \RuntimeException('Falha ao abrir documento.');
        }
        try {
            foreach ($parts as $name => $xml) {
                if (! $zip->addFromString($name, $xml)) {
                    throw new \RuntimeException('Falha ao gravar documento.');
                }
            }
        } finally {
            if (! $zip->close()) {
                throw new \RuntimeException('Falha ao concluir documento.');
            }
        }
    }
}
