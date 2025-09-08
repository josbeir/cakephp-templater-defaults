<?php
declare(strict_types=1);

namespace TemplaterDefaults\View;

use Cake\Utility\Hash;
use Cake\View\StringTemplate as StringTemplateBase;
use RuntimeException;

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

                $this->defaults[$name] = $template['defaults'];
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
                $attribute_key = $match[1];
                $attribute_value = $match[3];

                if (str_starts_with($attribute_value, '!!')) {
                    $attribute_value = substr($attribute_value, 2);
                    unset($defaults[$attribute_key]);
                }

                if ($attribute_value === 'false') {
                    unset($defaults[$attribute_key]);
                    continue;
                }

                if (is_array($defaults)) {
                    $defaults_value = $defaults[$attribute_key] ?? null;

                    if (is_array($defaults_value)) {
                        $attribute_value = (array)$attribute_value;
                    }
                }

                $attributes[$attribute_key] = $attribute_value;
            }

            $merged = [];
            if (is_callable($defaults)) {
                $merged = $defaults($attributes);
                if (!is_array($merged)) {
                    throw new RuntimeException(
                        sprintf('`defaults` for template %s callable must return an array.', $name),
                    );
                }
            } else {
                $merged = Hash::merge($defaults, $attributes);
            }

            $attributes_string = $this->formatAttributes($merged);

            $formatted = preg_replace('/<(\w+)([^>]*)>/', '<$1' . $attributes_string . '>', $formatted);
        }

        return (string)$formatted;
    }
}
