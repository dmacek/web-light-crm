<?php

declare(strict_types=1);

namespace Renderer\Design;

final class ChameleonEngine
{
    /**
     * @param string $mood
     * @param array{primary?: string, secondary?: string, background?: string} $customPalette
     * @return array<string, string>
     */
    public static function resolveTokens(string $mood, array $customPalette = []): array
    {
        $primary = $customPalette['primary'] ?? '#2563eb';
        $secondary = $customPalette['secondary'] ?? '#1e40af';
        $bg = $customPalette['background'] ?? '#ffffff';

        $fontFamily = 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        $headingFont = 'inherit';
        $radius = '0.75rem';
        $textColor = '#0f172a';
        $cardBg = '#f8fafc';
        $borderColor = '#e2e8f0';

        switch (strtoupper($mood)) {
            case 'TRADITIONAL':
                $fontFamily = 'Georgia, Cambria, "Times New Roman", Times, serif';
                $headingFont = 'Georgia, serif';
                $radius = '0.375rem';
                $cardBg = '#fefce8';
                $borderColor = '#e5e7eb';
                break;

            case 'BOLD':
                $fontFamily = '"Arial Black", Impact, "Segoe UI", sans-serif';
                $headingFont = '"Arial Black", sans-serif';
                $radius = '1rem';
                $cardBg = '#18181b';
                $textColor = '#f4f4f5';
                $borderColor = '#27272a';
                break;

            case 'ELEGANT':
                $fontFamily = 'Optima, Candara, "Segoe UI", sans-serif';
                $headingFont = 'Garamond, "Times New Roman", serif';
                $radius = '1.25rem';
                $cardBg = '#fafaf9';
                $borderColor = '#e7e5e4';
                break;

            case 'MODERN':
            default:
                $fontFamily = 'Inter, system-ui, -apple-system, "Segoe UI", sans-serif';
                $radius = '0.75rem';
                break;
        }

        return [
            'primary' => $primary,
            'secondary' => $secondary,
            'background' => $bg,
            'textColor' => $textColor,
            'cardBg' => $cardBg,
            'borderColor' => $borderColor,
            'fontFamily' => $fontFamily,
            'headingFont' => $headingFont,
            'radius' => $radius,
        ];
    }
}
