<?php

if (! function_exists('money')) {
    function money($amount): string
    {
        return number_format((float) $amount, 2, ',', ' ').' €';
    }
}

if (! function_exists('rich_description')) {
    /**
     * Met en forme une description de ligne (devis, facture) :
     * « - » ou « • » en début de ligne = puce, ligne finissant par « : » = intertitre en gras.
     */
    function rich_description(?string $text): string
    {
        $html = '';
        $list = false;
        foreach (preg_split('/\R/', trim((string) $text)) as $line) {
            $line = trim($line);
            if (preg_match('/^[-•*]\s*(.+)$/u', $line, $m)) {
                $html .= ($list ? '' : '<ul>').'<li>'.e($m[1]).'</li>';
                $list = true;

                continue;
            }
            if ($list) {
                $html .= '</ul>';
                $list = false;
            }
            if ($line === '') {
                $html .= '<div class="gap"></div>';
            } elseif (str_ends_with($line, ':')) {
                $html .= '<div class="subtitle">'.e($line).'</div>';
            } else {
                $html .= '<div>'.e($line).'</div>';
            }
        }

        return $html.($list ? '</ul>' : '');
    }
}

if (! function_exists('pdf_number')) {
    function pdf_number($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',');
    }
}
