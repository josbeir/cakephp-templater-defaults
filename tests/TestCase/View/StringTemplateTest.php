<?php
declare(strict_types=1);

namespace TemplaterDefaults\Test\TestCase\View;

use Cake\TestSuite\TestCase;
use Cake\View\Helper\FormHelper;
use Cake\View\View;
use TemplaterDefaults\View\StringTemplate;

class StringTemplateTest extends TestCase
{
    protected StringTemplate $templater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templater = new StringTemplate([
            'input' => '<input class="bg-red-800 p-2 text-white rounded-sm" type="{{type}}" name="{{name}}"{{attrs}}>',
            'no_Html' => '{{label}}',
        ]);
    }

    public function testPlain(): void
    {
        $formatted = $this->templater->format('input', [
            'type' => 'input',
            'name' => 'test',
            'attrs' => $this->templater->formatAttributes([
                'class' => 'default-class runtime-class another class',
            ]),
        ]);

        $this->assertSame(
            '<input class="bg-red-800 p-2 text-white rounded-sm default-class runtime-class another class" type="input" name="test">',
            $formatted,
        );
    }

    public function testNamedSwap(): void
    {
        $formatted = $this->templater->format('input', [
            'type' => 'input',
            'name' => 'test',
            'attrs' => $this->templater->formatAttributes([
                'class' => 'default-class runtime-class another class',
                'class:swap' => 'swapped-class',
            ]),
        ]);

        $this->assertSame(
            '<input class="swapped-class" type="input" name="test">',
            $formatted,
        );
    }

    public function testEmptySwap(): void
    {
        $formatted = $this->templater->format('input', [
            'type' => 'input',
            'name' => 'test',
            'attrs' => $this->templater->formatAttributes([
                'class' => 'default-class runtime-class another class',
                'class:swap' => '',
            ]),
        ]);

        $this->assertSame(
            '<input type="input" name="test">',
            $formatted,
        );
    }

    public function testWithFormHelper(): void
    {
        $View = new View();
        $helper = new FormHelper($View, [
            'templateClass' => StringTemplate::class,
            'templates' => [
                'input' => '<input class="class1" type="{{type}}" name="{{name}}"{{attrs}}>',
            ],
        ]);

        $control = $helper->control('field', [
            'type' => 'text',
            'class' => 'class2 class3',
        ]);

        $this->assertEquals(
            '<div class="input text"><label for="field">Field</label><input class="class1 class2 class3" type="text" name="field" id="field"></div>',
            $control,
        );
    }

    public function testWithFormHelperSwap(): void
    {
        $View = new View();
        $helper = new FormHelper($View, [
            'templateClass' => StringTemplate::class,
            'templates' => [
                'input' => '<input class="class1" type="{{type}}" name="{{name}}"{{attrs}}>',
            ],
        ]);

        $control = $helper->control('field', [
            'type' => 'text',
            'class:swap' => ['swapped-class', 'swapped-class2'],
        ]);

        $this->assertStringContainsString(
            '<input class="swapped-class swapped-class2" type="text" name="field" id="field">',
            $control,
        );
    }

    public function testIdentifierFiltering(): void
    {
        $formatted = $this->templater->format('input', [
            'type' => 'input',
            'name' => 'test',
            'attrs' => $this->templater->formatAttributes([
                'class' => 'default-class',
                'class:swap' => 'swapped-class',
                'data-value:unsupported' => 'should-remain',
                'x-model.fill' => 'someValue',
                '@click.prevent' => 'submit()',
            ]),
        ]);

        $this->assertSame(
            '<input class="swapped-class" type="input" name="test" data-value:unsupported="should-remain" x-model.fill="someValue" @click.prevent="submit()">',
            $formatted,
        );
    }

    public function testAttributesWithEncodedQuotes(): void
    {
        $this->templater = new StringTemplate([
            'div' => '<div class="fieldset"{{attrs}}>{{content}}</div>',
        ]);

        $formatted = $this->templater->format('div', [
            'content' => 'test content',
            'attrs' => $this->templater->formatAttributes([
                'x-show' => '["basic","digest"].includes(auth_type)',
                'class:swap' => 'new-class',
            ]),
        ]);

        $this->assertSame(
            '<div class="new-class" x-show="[&quot;basic&quot;,&quot;digest&quot;].includes(auth_type)">test content</div>',
            $formatted,
        );
    }
}
