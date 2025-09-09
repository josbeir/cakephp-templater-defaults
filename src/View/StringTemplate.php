<?php
declare(strict_types=1);

namespace TemplaterDefaults\View;

use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\StringTemplate as StringTemplateBase;
use RuntimeException;

class StringTemplate extends StringTemplateBase
{
    /**
     * Stores default attributes for each template.
     *
     * @var array<string, array|callable>
     */
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
     * Extracts named attributes from the formatted string for a given template.
     *
     * @param string $templateName The template name.
     * @param string &$formatted The formatted string (modified in-place).
     * @return array Extracted attributes.
     */
    protected function extractNamed(string $templateName, string &$formatted): array
    {
        $dashedName = Inflector::dasherize($templateName);
        $pattern = '/\s*\b' . preg_quote($dashedName, '/') . ':(\w+(?:-\w+)*)=(["\'])(.*?)(\2)\s*/';
        $attributes = [];
        $formatted = (string)preg_replace_callback($pattern, function (array $matches) use (&$attributes): string {
            $attributes[$matches[1]] = [$matches[3]];

            return '';
        }, $formatted);

        // Clean up extra spaces
        $formatted = (string)preg_replace(['/\s+/', '/\s+>/'], [' ', '>'], $formatted);

        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function format(string $name, array $data): string
    {
        $formatted = parent::format($name, $data);
        $defaults = $this->defaults[$name] ?? [];

        $callable = null;
        if (is_callable($defaults)) {
            $callable = $defaults;
            $defaults = [];
        }

        $namedAttributes = $this->extractNamed($name, $formatted);
        $defaults = Hash::merge($defaults, $namedAttributes);

        if (($callable || $defaults) && preg_match('/<(\w+)([^>]*)>/', $formatted, $matches)) {
            $attributesString = $matches[2];
            preg_match_all('/(\w+)=(["\'])([^"\']*)\2/', $attributesString, $attrMatches, PREG_SET_ORDER);
            $attributes = [];
            foreach ($attrMatches as $match) {
                $key = $match[1];
                $value = $match[3];

                // Remove default if value starts with '!!'
                if (str_starts_with($value, '!!')) {
                    $value = substr($value, 2);
                    unset($defaults[$key]);
                }

                // Remove default if value is 'false'
                if ($value === 'false') {
                    unset($defaults[$key]);
                    continue;
                }

                $defaultValue = $defaults[$key] ?? null;
                if (is_array($defaultValue)) {
                    $value = (array)$value;
                }

                $attributes[$key] = $value;
            }

            if ($callable !== null) {
                $finalAttributes = $callable($attributes);
                if (!is_array($finalAttributes)) {
                    throw new RuntimeException(
                        sprintf('`defaults` for template %s callable must return an array.', $name),
                    );
                }
            } else {
                $finalAttributes = Hash::merge($defaults, $attributes);
            }

            $finalAttributes['escape'] = false;

            $attributesString = $this->formatAttributes($finalAttributes);
            $formatted = preg_replace('/<(\w+)([^>]*)>/', '<$1' . $attributesString . '>', $formatted, 1);
        }

        return (string)$formatted;
    }
}
