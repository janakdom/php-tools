<?php
namespace DominikJanak\Tools;

use ArrayAccess;

use function preg_replace_callback;

class Strings
{

    /**
     * Skloňuje řetězec dle českých pravidel řetězec
     *
     * @param int         $amount
     * @param string      $one  Lze použít dvě procenta - %% - pro nahrazení za $amount
     * @param string|null $two
     * @param string|null $five Vynechat nebo null = použít $two
     * @param string|null $zero Vynechat nebo null = použít $five
     * @return string
     */
    static function plural(int $amount, string $one, string $two = null, string $five = null, string $zero = null) :string
    {
        if($two === null) {
            $two = $one;
        }
        if($five === null) {
            $five = $two;
        }
        if($zero === null) {
            $zero = $five;
        }
        if($amount == 1) {
            return str_replace("%%", $amount, $one);
        }
        if($amount > 1 and $amount < 5) {
            return str_replace("%%", $amount, $two);
        }
        if($amount == 0) {
            return str_replace("%%", $amount, $zero);
        }
        return str_replace("%%", $amount, $five);
    }

    /**
     * strlen pro UTF-8
     *
     * @param string $input
     * @return int
     */
    static function length(string $input) :int
    {
        return mb_strlen($input, "utf-8");
    }

    /**
     * strlen pro UTF-8
     *
     * @param string $input
     * @return int
     */
    static function strlen(string $input) :int
    {
        return self::length($input);
    }

    /**
     * substr() pro UTF-8
     *
     * @param string   $input
     * @param int      $start
     * @param int|null $length
     * @return string
     */
    static function substring(string $input, int $start, int $length = null) :string
    {
        return self::substr($input, $start, $length, "utf-8");
    }

    /**
     * substr() pro UTF-8
     *
     * @param string   $input
     * @param int      $start
     * @param int|null $length
     * @return string
     */
    static function substr(string $input, int $start, int $length = null) :string
    {
        if($length === null) {
            $length = self::length($input) - $start;
        }
        return mb_substr($input, $start, $length, "utf-8");
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     * @return false|int
     */
    static function strpos(string $haystack, string $needle, int $offset = 0)
    {
        return mb_strpos($haystack, $needle, $offset, "utf-8");
    }

    /**
     * @param string $string
     * @return string
     */
    static function lower(string $string) :string
    {
        return mb_strtolower($string, "utf-8");
    }

    /**
     * @param $string
     * @return string
     */
    static function upper($string) :string
    {
        return mb_strtoupper($string, "utf-8");
    }

    /**
     * Otestuje zda řetězec obsahuje hledaný výraz
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function contains(string $haystack, string $needle) :bool
    {
        return strpos($haystack, $needle) !== false;
    }

    /**
     * Otestuje zda řetězec obsahuje hledaný výraz, nedbá na velikost znaků
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function icontains(string $haystack, string $needle) :bool
    {
        return stripos($haystack, $needle) !== false;
    }

    /**
     * Funkce pro zkrácení dlouhého textu na menší délku.
     * Ořezává tak, aby nerozdělovala slova, a případně umí i odstranit HTML znaky.
     *
     * @param string $text        Původní (dlouhý) text
     * @param int    $length      Požadovaná délka textu. Oříznutý text nebude mít přesně tuto délku, může být o nějaké
     *                            znaky kratší nebo delší podle toho, kde končí slovo.
     * @param mixed $ending      Pokud dojde ke zkrácení textu, tak s na jeho konec přilepí tento řetězec. Defaultně
     *                            trojtečka (jako HTML entita &amp;hellip;). TRUE = &amp;hellip; (nemusíš si pak
     *                            pamatovat tu entitu)
     * @param bool   $stripHtml   Mají se odstraňovat HTML tagy? True = odstranit. Zachovají se pouze <br />, a všechny
     *                            konce řádků (\n i \r) budou nahrazeny za <br />. Odstraňování je důležité, jinak by
     *                            mohlo dojít k ořezu uprostřed HTML tagu, anebo by nebyl nějaký tag správně ukončen.
     *                            Pro ořezávání se zachováním html tagů je shortenHtml().
     * @param bool   $ignoreWords Ignorovat slova a rozdělit přesně.
     * @return string Zkrácený text
     */
    static function shorten(string $text, int $length, $ending = "&hellip;",
        bool $stripHtml = true, bool $ignoreWords = false) :string
    {
        if($stripHtml) {
            $text = self::br2nl($text);
            $text = strip_tags($text);
        }
        $text = trim($text);
        if($ending === true) {
            $ending = "&hellip;";
        }

        $needsTrim = (self::strlen($text) > $length);
        if(!$needsTrim) {
            return $text;
        }

        $hardTrimmed = self::substr($text, 0, $length);
        $nextChar = self::substr($text, $length, 1);
        if(!preg_match('~[\s.,/\-]~', $nextChar)) {
            $endingRemains = preg_match('~[\s.,/\-]([^\s.,/\-]*)$~', $hardTrimmed, $foundParts);
            if($endingRemains) {
                $endingLength = self::strlen($foundParts[1]);
                $hardTrimmed = self::substr($hardTrimmed, 0, -1 * $endingLength - 1);
            }
        }

        $hardTrimmed .= $ending;

        return $hardTrimmed;
    }

    /**
     * Všechny tagy BR (ve formě &lt;br> i &lt;br />) nahradí za \n (LF)
     *
     * @param string $input
     * @return string
     */
    static function br2nl(string $input) :string
    {
        return preg_replace('~<br\s*/?>~i', "\n", $input);
    }

    /**
     * Nahradí nové řádky za &lt;br />, ale nezanechá je tam.
     *
     * @param string $input
     * @return string
     */
    static function nl2br(string $input) :string
    {
        $input = str_replace("\r\n", "\n", $input);
        return str_replace("\n", "<br />", $input);
    }

    /**
     * Nahradí entity v řetězci hodnotami ze zadaného pole.
     *
     * @param string            $string
     * @param array|ArrayAccess $valuesArray
     * @param callback          $escapeFunction  Funkce, ktrsou se prožene každá nahrazená entita (např. kvůli
     *                                           escapování paznaků). Defaultně Html::escape()
     * @param string            $entityDelimiter Jeden znak
     * @param string            $entityNameChars Rozsah povolených znaků v názvech entit
     * @return string
     */
    static function replaceEntities(string $string, $valuesArray, $escapeFunction = "!!default",
        string $entityDelimiter = "%", string $entityNameChars = 'a-z0-9_-') :string
    {
        if($escapeFunction === "!!default") {
            $escapeFunction = "\\DominikJanak\\Tools\\Html::escape";
        }
        $arrayMode = is_array($valuesArray);
        $arrayAccessMode = (!is_array($valuesArray) and $valuesArray instanceof ArrayAccess);
        return preg_replace_callback('~' . preg_quote($entityDelimiter) . '([' . $entityNameChars . ']+)' . preg_quote($entityDelimiter) . '~i',
            function($found) use ($valuesArray, $escapeFunction, $arrayMode, $arrayAccessMode) {
                if($arrayMode and key_exists($found[1], $valuesArray)) {
                    $v = $valuesArray[$found[1]];
                    if($escapeFunction) {
                        $v = call_user_func_array($escapeFunction, [$v]);
                    }
                    return $v;
                }
                if($arrayAccessMode) {
                    if(isset($valuesArray[$found[1]])) {
                        $v = $valuesArray[$found[1]];
                        if($escapeFunction) {
                            $v = call_user_func_array($escapeFunction, [$v]);
                        }
                        return $v;
                    }
                }
                if(!$arrayAccessMode and !$arrayMode) {
                    if(property_exists($valuesArray, $found[1])) {
                        $v = $valuesArray->{$found[1]};
                        if($escapeFunction) {
                            $v = call_user_func_array($escapeFunction, [$v]);
                        }
                        return $v;
                    }
                }

                return $found[0];
            }, $string);
    }

    /**
     * Převede číslo s lidsky čitelným násobitelem, jako to zadávané v php.ini (např. 100M jako 100 mega), na normální
     * číslo
     *
     * @param string $number
     * @return float|int|null False, pokud je vstup nepřevoditelný
     */
    static function parsePhpNumber(string $number)
    {
        $number = trim($number);

        if(is_numeric($number)) {
            return $number * 1;
        }

        if(preg_match('~^(-?)([0-9\.,]+)([kmgt]?)$~i', $number, $parts)) {
            $base = self::number($parts[2]);

            switch($parts[3]) {
                case "T":
                case "t":
                    $base *= 1024;
                case "G":
                case "g":
                    $base *= 1024;
                case "M":
                case "m":
                    $base *= 1024;
                case "K":
                case "k":
                    $base *= 1024;
            }

            if($parts[1]) {
                $c = -1;
            } else {
                $c = 1;
            }
            return $base * $c;
        }
        return null;
    }

    /**
     * Naformátuje telefonní číslo
     *
     * @param string      $input
     * @param bool        $international        Nechat/přidat mezinárodní předvolbu?
     * @param bool|string $spaces               Přidat mezery pro trojčíslí? True = mezery. False = žádné mezery.
     *                                          String = zadaný řetězec použít jako mezeru.
     * @param string      $internationalPrefix  Prefix pro mezinárodní řpedvolbu, používá se většinou "+" nebo "00"
     * @param string      $defaultInternational Výchozí mezinárodní předvolba (je-li $international == true a $input je
     *                                          bez předvolby). Zadávej BEZ prefixu.
     * @return string
     */

    static function phoneNumberFormatter(string $input, bool $international = true, $spaces = false,
        string $internationalPrefix = "+", string $defaultInternational = "420") :string
    {
        if(!trim($input)) {
            return "";
        }

        if($spaces === true) {
            $spaces = " ";
        }
        $filteredInput = preg_replace('~\D~', '', $input);

        $parsedInternational = "";
        $parsedMain = "";
        if(strlen($filteredInput) > 9) {
            $parsedInternational = self::substr($filteredInput, 0, -9);
            $parsedMain = self::substr($filteredInput, -9);
        } else {
            $parsedMain = $filteredInput;
        }
        if(self::startsWith($parsedInternational, $internationalPrefix)) {
            $parsedInternational = self::substr($parsedInternational, self::strlen($internationalPrefix));
        }

        if($spaces) {
            $spacedMain = "";
            $len = self::strlen($parsedMain);
            for($i = $len; $i > -3; $i -= 3) {
                $spacedMain = self::substr($parsedMain, ($i >= 0 ? $i : 0), ($i >= 0 ? 3 : (3 - $i * -1)))
                              . ($spacedMain ? ($spaces . $spacedMain) : "");
            }
        } else {
            $spacedMain = $parsedMain;
        }

        $output = "";
        if($international) {
            if(!$parsedInternational) {
                $parsedInternational = $defaultInternational;
            }
            $output .= $internationalPrefix . $parsedInternational;
            if($spaces) {
                $output .= $spaces;
            }
        }
        $output .= $spacedMain;

        return $output;
    }

    /**
     * Začíná $string na $startsWith?
     *
     * @param string $string
     * @param string $startsWith
     * @param bool   $caseSensitive
     * @return bool
     */
    static function startsWith(string $string, string $startsWith, bool $caseSensitive = true) :bool
    {
        $len = self::strlen($startsWith);
        if($caseSensitive) {
            return self::substr($string, 0, $len) == $startsWith;
        }
        return self::lower(self::substr($string, 0, $len)) == self::lower($startsWith);
    }

    /**
     * Končí $string na $endsWith?
     *
     * @param string $string
     * @param string $endsWith
     * @param bool   $caseSensitive
     * @return bool
     */
    static function endsWith(string $string, string $endsWith, bool $caseSensitive = true) :bool
    {
        $len = self::strlen($endsWith);
        if($caseSensitive) {
            return self::substr($string, -1 * $len) == $endsWith;
        }
        return self::lower(self::substr($string, -1 * $len)) == self::lower($endsWith);
    }

    /**
     * Ošetří zadanou hodnotu tak, aby z ní bylo číslo.
     * (normalizuje desetinnou čárku na tečku a ověří is_numeric).
     *
     * @param mixed     $string
     * @param int|float $default      Vrátí se, pokud $vstup není čílený řetězec ani číslo (tj. je array, object, bool
     *                                nebo nenumerický řetězec)
     * @param bool      $positiveOnly Dáš-li true, tak se záporné číslo bude považovat za nepřijatelné a vrátí se
     *                                $default (vhodné např. pro strtotime)
     * @return int|float
     */
    static function number($string, $default = 0, bool $positiveOnly = false)
    {
        if(is_bool($string) or is_object($string) or is_array($string)) {
            return $default;
        }
        $string = str_replace([",", " "], [".", ""], trim($string));
        if(!is_numeric($string)) {
            return $default;
        }
        $string = $string * 1; // Convert to number
        if($positiveOnly and $string < 0) {
            return $default;
        }
        return $string;
    }

    /**
     * Funkce zlikviduje z řetězce všechno kromě číselných znaků a vybraného desetinného oddělovače.
     *
     * @param string $string
     * @param string $decimalPoint
     * @param string $convertedDecimalPoint Takto lze normalizovat desetinný oddělovač.
     * @return string
     */
    static function numberOnly(string $string, string $decimalPoint = ".", string $convertedDecimalPoint = ".") :string
    {
        $output = "";
        for($i = 0; $i < strlen($string); $i++) {
            $znak = substr($string, $i, 1);
            if(is_numeric($znak)) {
                $output .= $znak;
            } else {
                if($znak == $decimalPoint) {
                    $output .= $convertedDecimalPoint;
                }
            }
        }
        return $output;
    }

    /**
     * Převede řetězec na základní alfanumerické znaky a pomlčky [a-z0-9.], umožní nechat tečku (vhodné pro jména
     * souborů)
     * <br />Alias pro webalize()
     *
     * @param string $string
     * @param bool   $allowDot Povolit tečku?
     * @return string
     */
    static function safe(string $string, bool $allowDot = true) :string
    {
        return self::webalize($string, $allowDot ? "." : "");
    }

    /**
     * Converts to ASCII.
     *
     * @param string $s UTF-8 encoding
     * @return string  ASCII
     * @author Nette Framework
     */
    public static function toAscii(string $s) :string
    {
        $s = preg_replace('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{2FF}\x{370}-\x{10FFFF}]#u', '', $s);
        $s = strtr($s, '`\'"^~', "\x01\x02\x03\x04\x05");
        if(ICONV_IMPL === 'glibc') {
            $s = @iconv('UTF-8', 'WINDOWS-1250//TRANSLIT', $s); // intentionally @
            $s = strtr($s, "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe\x9c\x9a\xba\x9d\x9f\x9e"
                           . "\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3"
                           . "\xd4\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8"
                           . "\xe9\xea\xeb\xec\xed\xee\xef\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe\x96",
                "ALLSSSSTZZZallssstzzzRAAAALCCCEEEEIIDDNNOOOOxRUUUUYTsraaaalccceeeeiiddnnooooruuuuyt-");
        } else {
            $s = @iconv('UTF-8', 'ASCII//TRANSLIT', $s); // intentionally @
        }
        $s = str_replace(['`', "'", '"', '^', '~'], '', $s);
        return strtr($s, "\x01\x02\x03\x04\x05", '`\'"^~');
    }

    /**
     * Převede řetězec na základní alfanumerické znaky a pomlčky [a-z0-9.-]
     *
     * @param string      $s        Řetězec, UTF-8 encoding
     * @param string|null $charlist allowed characters jako regexp
     * @param bool        $lower    Zmenšit na malá písmena?
     * @return string
     * @author Nette Framework
     */
    public static function webalize(string $s, string $charlist = null, bool $lower = true) :string
    {
        $s = self::toAscii($s);
        if($lower) {
            $s = strtolower($s);
        }
        $s = preg_replace('#[^a-z0-9' . preg_quote($charlist, '#') . ']+#i', '-', $s);
        return trim($s, '-');
    }

    /**
     * Převede číselnou velikost na textové výjádření v jednotkách velikosti (KB,MB,...)
     *
     * @param int $size
     * @param int $decimalPrecision
     * @return string
     */
    public static function formatSize(int $size, int $decimalPrecision = 2) :string
    {
        $borders = [
            1208925819614629174706176 => 'YB',
            1180591620717411303424 => 'ZB',
            1152921504606846976 => 'EB',
            1125899906842624 => 'PB',
            1099511627776 => 'TB',
            1073741824 => 'GB',
            1048576 => 'MB',
            1024 => 'kB',
            0 => 'B',
        ];

        foreach($borders as $border => $unit) {
            if(abs($size) >= $border) {
                return round($size / $border, $decimalPrecision) . ' '. $unit;
            }
        }
        return 'Unknown';
    }

    /**
     * Ošetření paznaků v HTML kódu
     *
     * @param string $input
     * @param bool   $doubleEncode
     *
     * @return string
     */
    public static function specialchars(string $input, bool $doubleEncode = false) :string
    {
        return htmlspecialchars($input, ENT_QUOTES, 'utf-8', $doubleEncode);
    }

}
