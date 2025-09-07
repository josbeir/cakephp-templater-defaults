<?php
declare(strict_types=1);

namespace TemplaterDefaults\Test\TestCase\View;

use Cake\TestSuite\TestCase;
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
}
