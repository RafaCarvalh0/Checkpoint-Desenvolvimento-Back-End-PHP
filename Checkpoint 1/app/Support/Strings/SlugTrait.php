<?php

namespace App\Support\Strings;

trait SlugTrait
{
    protected function makeSlug(string $value): string
    {
        $value = trim($value);
        $value = $this->removeAccents($value);
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value === '' ? 'item' : $value;
    }

    private function removeAccents(string $value): string
    {
        $value = $this->repairMojibake($value);
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if ($converted !== false) {
            return preg_replace('/[\^~`\'"]([A-Za-z])/', '$1', $converted) ?? $converted;
        }

        return strtr($value, [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
            'Á' => 'A',
            'À' => 'A',
            'Ã' => 'A',
            'Â' => 'A',
            'Ä' => 'A',
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Í' => 'I',
            'Ì' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ó' => 'O',
            'Ò' => 'O',
            'Õ' => 'O',
            'Ô' => 'O',
            'Ö' => 'O',
            'Ú' => 'U',
            'Ù' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ç' => 'C',
        ]);
    }

    private function repairMojibake(string $value): string
    {
        return strtr($value, [
            'Ã¡' => 'á',
            'Ã ' => 'à',
            'Ã£' => 'ã',
            'Ã¢' => 'â',
            'Ã¤' => 'ä',
            'Ã©' => 'é',
            'Ã¨' => 'è',
            'Ãª' => 'ê',
            'Ã«' => 'ë',
            'Ã­' => 'í',
            'Ã¬' => 'ì',
            'Ã®' => 'î',
            'Ã¯' => 'ï',
            'Ã³' => 'ó',
            'Ã²' => 'ò',
            'Ãµ' => 'õ',
            'Ã´' => 'ô',
            'Ã¶' => 'ö',
            'Ãº' => 'ú',
            'Ã¹' => 'ù',
            'Ã»' => 'û',
            'Ã¼' => 'ü',
            'Ã§' => 'ç',
            'Ã' => 'Á',
            'Ã€' => 'À',
            'Ãƒ' => 'Ã',
            'Ã‚' => 'Â',
            'Ã„' => 'Ä',
            'Ã‰' => 'É',
            'Ãˆ' => 'È',
            'ÃŠ' => 'Ê',
            'Ã‹' => 'Ë',
            'Ã' => 'Í',
            'ÃŒ' => 'Ì',
            'ÃŽ' => 'Î',
            'Ã' => 'Ï',
            'Ã“' => 'Ó',
            'Ã’' => 'Ò',
            'Ã•' => 'Õ',
            'Ã”' => 'Ô',
            'Ã–' => 'Ö',
            'Ãš' => 'Ú',
            'Ã™' => 'Ù',
            'Ã›' => 'Û',
            'Ãœ' => 'Ü',
            'Ã‡' => 'Ç',
        ]);
    }
}
