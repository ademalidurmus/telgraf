<?php

declare(strict_types=1);

namespace AAD\Telgraf\Helpers;

/**
 * Class Str
 * @package AAD\Telgraf\Helpers
 */
class Str
{
    /**
     * @link https://github.com/php-telegram-bot/core/issues/1093
     *
     * @param string $text
     * @return string
     */
    public static function escapeMarkdownV2(string $text): string
    {
        $markdown = [
            '#',
            '*',
            '_',
            '=',
            '[',
            ']',
            '(',
            ')',
            // ... rest of markdown entities
        ];
        //'_‘, ’*‘, , ’~‘, ’`‘, ’>‘, ’#‘, ’+‘, ’-‘, ’=‘, ’|‘, ’{‘, ’}‘, ’.‘, ’!‘
        $replacements = [
            '\#',
            '\*',
            '\_',
            '\\=',
            '\[',
            '\]',
            '\(',
            '\)',
            // ... rest of corresponding escaped markdown
        ];

        return str_replace($markdown, $replacements, $text);
    }
}
