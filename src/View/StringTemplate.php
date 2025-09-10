<?php
declare(strict_types=1);

namespace TemplaterDefaults\View;

use Cake\View\StringTemplate as StringTemplateBase;

class StringTemplate extends StringTemplateBase
{
    /**
     * Extracts named attributes from the formatted string for a given template.
     *
     * @param string $templateName The template name.
     * @param string &$formatted The formatted string (modified in-place).
     * @return array Extracted attributes.
     */
    protected function extractNamed(string &$formatted): array
    {
        $pattern = '/\s*\b(\w+(?:-\w+)*):(\w+(?:-\w+)*)=(["\'])(.*?)\3\s*/';
        $attributes = [];

        $formatted = (string)preg_replace_callback($pattern, function (array $matches) use (&$attributes): string {
            $attribute = $matches[1];
            $option = $matches[2];
            $value = $matches[4];

            $attributes[$option][$attribute][] = $value;

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
        $named = $this->extractNamed($formatted);

        if (preg_match('/<(\w+)([^>]*)>/', $formatted, $matches)) {
            $attributesString = $matches[2];
            preg_match_all(
                '/(\w+(?:-\w+)*(?::\w+(?:-\w+)*)?)=(["\'])([^"\']*)\2/',
                $attributesString,
                $attrMatches,
                PREG_SET_ORDER,
            );

            $attributes = [];

            foreach ($attrMatches as $match) {
                $key = $match[1];
                $value = $match[3];
                $attributes[$key][] = htmlspecialchars_decode($value);
            }

            if (isset($named['swap'])) {
                foreach ($named['swap'] as $key => $replacements) {
                    if (isset($attributes[$key])) {
                        $replacements = array_filter($replacements);
                        if (empty($replacements)) {
                            unset($attributes[$key]);
                        } else {
                            $attributes[$key] = $replacements;
                        }
                    }
                }
            }

            $attributesString = $this->formatAttributes($attributes);
            $formatted = preg_replace('/<(\w+)([^>]*)>/', '<$1' . $attributesString . '>', $formatted, 1);
        }

        return (string)$formatted;
    }
}
