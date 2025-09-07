<?php
declare(strict_types=1);

namespace TemplaterDefaults\View;

use Cake\Utility\Hash;
use Cake\View\StringTemplate as StringTemplateBase;

class StringTemplate extends StringTemplateBase
{
    protected array $defaults = [];

    /**
     * @inheritDoc
     */
    public function add(array $templates, bool $merge = true)
    {
        foreach ($templates as $name => $template) {
            if (is_array($template)) {
                $template += [
                    'template' => '',
                    'defaults' => [],
                ];

                $this->defaults[$name] = $template['defaults'] ?? [];
                $templates[$name] = $template['template'];
            }
        }

        return parent::add($templates);
    }

    /**
     * @inheritDoc
     */
    public function format(string $name, array $data): string
    {
        $formatted = parent::format($name, $data);
        $defaults = $this->defaults[$name] ?? null;

        if ($defaults && preg_match('/<(\w+)([^>]*)>/', $formatted, $matches)) {
            $attributesString = $matches[2];

            preg_match_all('/(\w+)=(["\'])([^"\']*)\2/', $attributesString, $attrMatches, PREG_SET_ORDER);

            $attributes = [];
            foreach ($attrMatches as $match) {
                $attributes_key = $match[1];
                $attribute_value = $match[3];
                $defaults_value = $defaults[$attributes_key] ?? null;

                if (is_array($defaults_value)) {
                    $attribute_value = (array)$attribute_value;
                }

                $attributes[$attributes_key] = $attribute_value;
            }

            $merged = Hash::merge($defaults, $attributes);
            $attributes_string = $this->formatAttributes($merged);

            $formatted = preg_replace('/<(\w+)([^>]*)>/', '<$1' . $attributes_string . '>', $formatted);
        }

        return (string)$formatted;
    }
}
