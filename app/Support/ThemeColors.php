<?php

namespace App\Support;

/**
 * Couleur de thème d'un utilisateur (colonne users.theme) : un nom de la palette « classiques » ou
 * « pastels », ou une couleur libre au format #rrggbb. Le texte posé dessus (blanc ou sombre) se
 * choisit automatiquement selon le contraste, pour rester lisible sur les teintes claires.
 */
class ThemeColors
{
    /** Teintes d'origine. blue, purple et green gardent exactement le rendu qu'elles avaient. */
    public const CLASSIQUES = [
        'blue' => ['Bleu', '#0000ff'],
        'azure' => ['Azur', '#1f6fd4'],
        'indigo' => ['Indigo', '#4b0082'],
        'purple' => ['Violet', '#800080'],
        'pink' => ['Rose', '#d6336c'],
        'red' => ['Rouge', '#d63939'],
        'orange' => ['Orange', '#f76707'],
        'yellow' => ['Jaune', '#f5c400'],
        'lime' => ['Citron vert', '#74b816'],
        'green' => ['Vert', '#008000'],
    ];

    public const PASTELS = [
        'ciel' => ['Ciel', '#8ec5f0'],
        'pervenche' => ['Pervenche', '#a5b4f5'],
        'lavande' => ['Lavande', '#c3a9ec'],
        'mauve' => ['Mauve', '#dcaae8'],
        'rose-pale' => ['Rose pâle', '#f4b0cb'],
        'corail' => ['Corail', '#f7ab98'],
        'peche' => ['Pêche', '#f8c999'],
        'beurre' => ['Beurre', '#f1dd8c'],
        'menthe' => ['Menthe', '#a9dfb0'],
        'sauge' => ['Sauge', '#a3cfbf'],
    ];

    public const DEFAUT = 'blue';
    public const TEXTE_SOMBRE = '#182433';

    /** Noms de la palette + couleur libre #rrggbb. */
    public static function estValide(?string $valeur): bool
    {
        if ($valeur === null) {
            return false;
        }

        return isset(self::CLASSIQUES[$valeur]) || isset(self::PASTELS[$valeur])
            || (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $valeur);
    }

    /** Valeurs acceptées pour les noms (hors couleur libre). */
    public static function noms(): array
    {
        return array_merge(array_keys(self::CLASSIQUES), array_keys(self::PASTELS));
    }

    /**
     * Valeur enregistrée → couleurs prêtes à l'emploi :
     * nom, hex, fg (texte), sombre (texte sombre ?), rgb, darken, lt, lt_rgb.
     */
    public static function resolve(?string $valeur): array
    {
        if (!self::estValide($valeur)) {
            $valeur = self::DEFAUT;
        }

        $hex = strtolower(self::CLASSIQUES[$valeur][1] ?? self::PASTELS[$valeur][1] ?? $valeur);
        $fg = self::contraste($hex, '#ffffff') >= self::contraste($hex, self::TEXTE_SOMBRE) ? '#ffffff' : self::TEXTE_SOMBRE;
        $lt = self::melanger($hex, '#ffffff', 0.9);

        return [
            'nom' => $valeur,
            'hex' => $hex,
            'fg' => $fg,
            'sombre' => $fg !== '#ffffff',
            'rgb' => implode(', ', self::rgb($hex)),
            'darken' => self::melanger($hex, '#000000', 0.12),
            'lt' => $lt,
            'lt_rgb' => implode(', ', self::rgb($lt)),
        ];
    }

    /** Rapport de contraste WCAG entre deux couleurs. */
    public static function contraste(string $a, string $b): float
    {
        $l1 = self::luminance($a);
        $l2 = self::luminance($b);

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    private static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function (int $v) {
            $c = $v / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /** @return array{0:int,1:int,2:int} */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /** Mélange $hex avec $avec ; $part (0 à 1) = proportion de $avec. */
    private static function melanger(string $hex, string $avec, float $part): string
    {
        $a = self::rgb($hex);
        $b = self::rgb($avec);

        return sprintf('#%02x%02x%02x',
            (int) round($a[0] * (1 - $part) + $b[0] * $part),
            (int) round($a[1] * (1 - $part) + $b[1] * $part),
            (int) round($a[2] * (1 - $part) + $b[2] * $part),
        );
    }
}
