<?php

namespace Tests\Unit;

use App\Services\DocxTemplateService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class DocxTemplateServiceTest extends TestCase
{
    private array $temporary = [];

    protected function tearDown(): void
    {
        foreach ($this->temporary as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    private function docx(string $body, array $extra = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        $this->temporary[] = $path;
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'</w:body></w:document>');
        foreach ($extra as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $path;
    }

    public function test_replaces_split_markers_preserving_runs_table_header_and_literal_values(): void
    {
        $path = $this->docx('<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Olá {{ cli</w:t></w:r><w:r><w:t>ente.nome }} — {{ cliente.nome }}.</w:t></w:r></w:p><w:tbl><w:tr><w:tc><w:p><w:r><w:t>{{ campo.fatos }}</w:t></w:r></w:p></w:tc></w:tr></w:tbl>', [
            'word/header1.xml' => '<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>{{ escritorio.nome }}</w:t></w:r></w:p></w:hdr>',
            'word/media/image.png' => 'preserved-image',
        ]);
        $service = new DocxTemplateService;
        $this->assertSame(['cliente.nome', 'campo.fatos', 'escritorio.nome'], $service->inspect($path));
        $result = $service->render($path, ['cliente.nome' => 'João & <Ana>', 'campo.fatos' => "Linha 1\n{{ cliente.nome }}", 'escritorio.nome' => 'Escritório']);
        $this->assertStringContainsString('Olá João & <Ana> — João & <Ana>.', $result['text']);
        $this->assertStringContainsString('{{ cliente.nome }}', $result['text']);
        $this->assertStringContainsString('<w:b/>', $result['parts']['word/document.xml']);
        $this->assertStringContainsString('<w:tbl>', $result['parts']['word/document.xml']);
        $this->assertStringContainsString('<w:br/>', $result['parts']['word/document.xml']);
        $this->assertStringContainsString('&amp; &lt;Ana&gt;', $result['parts']['word/document.xml']);
        $output = tempnam(sys_get_temp_dir(), 'out');
        $this->temporary[] = $output;
        $service->write($path, $output, $result['parts']);
        $zip = new ZipArchive;
        $zip->open($output);
        $this->assertSame('preserved-image', $zip->getFromName('word/media/image.png'));
        $this->assertStringContainsString('Escritório', $zip->getFromName('word/header1.xml'));
        $zip->close();
        $this->assertSame(['cliente.nome', 'campo.fatos', 'escritorio.nome'], $service->inspect($path));
    }

    public function test_rejects_malformed_marker(): void
    {
        $this->expectException(ValidationException::class);
        (new DocxTemplateService)->inspect($this->docx('<w:p><w:r><w:t>{{ nome incompleto }}</w:t></w:r></w:p>'));
    }

    public function test_conditional_sections_across_runs_preserve_punctuation_and_required_fields(): void
    {
        $source = $this->docx('<w:p><w:r><w:t>{{ cliente.nome }}{{#se cliente.identidade cliente.orgao_emissor}}, RG </w:t></w:r><w:r><w:rPr><w:b/></w:rPr><w:t>{{ cliente.identidade }}/{{ cliente.orgao_emissor }}{{/se}}.{{#se processo.numero}} Ação {{ processo.numero }}{{/se}}</w:t></w:r></w:p>');
        $service = new DocxTemplateService;
        $empty = $service->render($source, ['cliente.nome' => 'Ana', 'cliente.identidade' => '123', 'cliente.orgao_emissor' => '  ']);
        $this->assertSame('Ana.', $empty['text']);
        $this->assertSame(['cliente.nome'], $empty['required']);
        $this->assertStringNotContainsString('RG', $empty['parts']['word/document.xml']);
        $filled = $service->render($source, ['cliente.nome' => 'Ana', 'cliente.identidade' => '123', 'cliente.orgao_emissor' => 'SSP', 'processo.numero' => '0001']);
        $this->assertSame('Ana, RG 123/SSP. Ação 0001', $filled['text']);
        $this->assertStringContainsString('<w:b/>', $filled['parts']['word/document.xml']);
        $this->assertStringNotContainsString('{{', $filled['parts']['word/document.xml']);
        $dom = new \DOMDocument;
        $dom->loadXML($filled['parts']['word/document.xml']);
        $this->assertSame($filled['text'], $dom->documentElement->textContent);
        $this->assertContains('processo.numero', $service->inspect($source));
    }

    public function test_rejects_unclosed_nested_and_cross_paragraph_blocks(): void
    {
        foreach ([
            '<w:p><w:r><w:t>{{#se campo.a}}Texto</w:t></w:r></w:p>',
            '<w:p><w:r><w:t>{{#se campo.a}}{{#se campo.b}}Texto{{/se}}{{/se}}</w:t></w:r></w:p>',
            '<w:p><w:r><w:t>{{#se campo.a}}</w:t></w:r></w:p><w:p><w:r><w:t>{{/se}}</w:t></w:r></w:p>',
        ] as $body) {
            try {
                (new DocxTemplateService)->inspect($this->docx($body));
                $this->fail('Malformed block accepted');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('file', $error->errors());
            }
        }
    }

    public function test_real_word_package_with_multiline_text_and_header_footer(): void
    {
        $source = __DIR__.'/../fixtures/word-template.docx';
        $service = new DocxTemplateService;
        $values = array_fill_keys($service->inspect($source), "Valor com acentuação\nSegunda linha");
        $result = $service->render($source, $values);
        $this->assertArrayHasKey('word/footer1.xml', $result['parts']);
        $this->assertStringContainsString('Segunda linha', $result['parts']['word/header1.xml']);
        $this->assertStringNotContainsString('{{', $result['parts']['word/document.xml']);
    }

    public function test_rejects_external_xml_entities(): void
    {
        $this->expectException(ValidationException::class);
        (new DocxTemplateService)->inspect($this->docx('', ['word/settings.xml' => '<!DOCTYPE x [<!ENTITY a SYSTEM "file:///test">]><x>&a;</x>']));
    }

    public function test_rejects_external_image_relationships_and_embedded_objects(): void
    {
        $this->expectException(ValidationException::class);
        (new DocxTemplateService)->inspect($this->docx('', ['word/_rels/document.xml.rels' => '<Relationships><Relationship TargetMode="External" Type="image" Target="https://example.com/image"/></Relationships>']));
    }
}
