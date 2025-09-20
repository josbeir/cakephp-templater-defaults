<?php
declare(strict_types=1);

namespace TemplaterDefaults\View;

use Cake\View\StringTemplate as StringTemplateBase;

class StringTemplate extends StringTemplateBase
{
    /**
     * Extracts named attributes from the formatted string.
     *
     * @param string $formatted The formatted string to extract named attributes from.
     * @return array An associative array of named attributes.
     */
    protected function extractNamed(string &$formatted): array
    {
        $supportedIdentifiers = ['swap'];
        $pattern = '/\s*(\w+(?:-\w+)*):(\w+(?:-\w+)*)=(["\'])(.*?)\3\s*/';
        $attributes = [];

        $formatted = (string)preg_replace_callback(
            $pattern,
            function (array $matches) use (&$attributes, $supportedIdentifiers): string {
                $attribute = $matches[1];
                $option = $matches[2];
                $value = $matches[4];

                if (in_array($option, $supportedIdentifiers, true)) {
                    $attributes[$option][$attribute][] = $value;

                    return '';
                }

                return $matches[0];
            },
            $formatted,
        );

        // Clean up extra spaces
        $formatted = (string)preg_replace('/(<[^>]*)\s+([^>]*>)/', '$1 $2', $formatted);
        $formatted = (string)preg_replace('/(<[^>]*)\s+>/', '$1>', $formatted);

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
                '/([a-zA-Z@][a-zA-Z0-9\-.:]*(?::\w+(?:-\w+)*)?)=(["\'])([^"\']*)\2/',
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
                        if ($replacements === []) {
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
