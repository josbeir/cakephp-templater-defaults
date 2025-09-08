<?php
declare(strict_types=1);

namespace TemplaterDefaults\Test\TestCase\View;

use Cake\TestSuite\TestCase;
use RuntimeException;
use TemplaterDefaults\View\StringTemplate;

class StringTemplateTest extends TestCase
{
    protected StringTemplate $templater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templater = new StringTemplate([
            'plain' => '<strong>{{text}}</strong>',
            'input' => [
                'template' => '<input type="{{type}}" name="{{name}}"{{attrs}}>',
                'defaults' => ['class' => 'defaultClass'],
            ],
            'input_extra_attribs' => [
                'template' => '<input type="{{type}}" name="{{name}}"{{attrs}}>',
                'defaults' => [
                    'attrib1' => 'Annakin!',
                    'attrib2' => 'I have the higher ground.',
                    'attrib3' => ['merge-1'],
                    'class' => ['defaultClass'],
                ],
            ],
            'no_html_tample' => [
                'template' => '{{content}}',
                'defaults' => ['class' => ['defaultClass']],
            ],
            'callable' => [
                'template' => '<input type="{{type}}" name="{{name}}"{{attrs}}>',
                'defaults' => function (array $attributes): array {
                    $attributes['attrib1'] = 'Do. Or do not!';
                    $attributes['attrib2'] = 'There is no try.';
                    $attributes['class'] = [$attributes['class'], 'callableClass'];

                    return $attributes;
                },
            ],
            'callable_invalid' => [
                'template' => '<input type="{{type}}" name="{{name}}"{{attrs}}>',
                'defaults' => function (array $attributes): string {
                    return 'not good...';
                },
            ],
        ]);
    }

    public function testPlain(): void
    {
        $formatted = $this->templater->format('plain', ['text' => 'Hello world']);
        $this->assertEquals('<strong>Hello world</strong>', $formatted);
    }

    public function testDefault(): void
    {
        $formatted = $this->templater->format('input', [
            'type' => 'text',
            'name' => 'myName',
        ]);

        $this->assertEquals('<input class="defaultClass" type="text" name="myName">', $formatted);
    }

    public function testFalseValue(): void
    {
        $formatted = $this->templater->format('input', [
            'attrs' => $this->templater->formatAttributes([
                'class' => 'false',
            ]),
            'type' => 'text',
            'name' => 'myName',
        ]);

        $this->assertEquals('<input type="text" name="myName">', $formatted);
    }

    public function testByPassValue(): void
    {
        $formatted = $this->templater->format('input_extra_attribs', [
            'attrs' => $this->templater->formatAttributes([
                'class' => '!!my other class',
            ]),
            'type' => 'text',
            'name' => 'myName',
        ]);

        $this->assertStringContainsString('class="my other class"', $formatted);
    }

    public function testExtraAttribs(): void
    {
        $formatted = $this->templater->format('input_extra_attribs', [
            'attrs' => $this->templater->formatAttributes([
                'class' => 'customClass',
                'attrib3' => ['merge-2', 'merge-3'],
            ]),
        ]);

        $this->assertEquals('<input attrib1="Annakin!" attrib2="I have the higher ground." attrib3="merge-1 merge-2 merge-3" class="defaultClass customClass" type="" name="">', $formatted);
    }

    public function testNoHtml(): void
    {
        $formatted = $this->templater->format('no_html_tample', [
            'content' => 'Hello world',
            'attrs' => $this->templater->formatAttributes(['class' => 'customClass']),
        ]);

        $this->assertEquals('Hello world', $formatted);
    }

    public function testCallable(): void
    {
        $formatted = $this->templater->format('callable', [
            'type' => 'text',
            'name' => 'myName',
            'attrs' => $this->templater->formatAttributes([
                'class' => 'customClass',
            ]),
        ]);

        $this->assertStringContainsString('attrib1="Do. Or do not!"', $formatted);
        $this->assertStringContainsString('attrib2="There is no try."', $formatted);
        $this->assertStringContainsString('class="customClass callableClass"', $formatted);
    }

    public function testInvalidCallable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('`defaults` for template callable_invalid callable must return an array.');

        $this->templater->format('callable_invalid', [
            'type' => 'text',
            'name' => 'myName',
        ]);
    }
}
