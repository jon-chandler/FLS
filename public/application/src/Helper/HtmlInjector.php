<?php

declare(strict_types = 1);

namespace Application\Helper;

/**
 * HtmlInjector is used for injecting content into HTML
 */
class HtmlInjector
{
    /**
     * Inject content into HTML by using {{key}} bindings
     * 
     * @param string $template
     * @param array $contents key => value
     * 
     * @return string
     */
    public static function inject(string $template, array $contents): string
    {
        foreach ($contents as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }

    /**
     * ** THIS FUNCTION DOES NOT BELONG IN THIS CLASS AND SHOULD BE MOVED **
     * 
     * @var int|null $score
     * @return string
     */
    public function mapScoreToClass($score): string
    {
        if ($score === null) {
            return 'good';
        }

        switch ($score) {
            case 0:
                $class = 'bad';
            break;
            case 1:
                $class = 'average';
            break;
            case 2:
                $class = 'good';
            break;
            default: 
                $class = 'unknown';
            break;
        }

        return (string) $class;
    }
}
