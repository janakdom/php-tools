<?php
namespace DominikJanak\Tools;

use DateInterval;
use DateTime;
use InvalidArgumentException;

class Time
{

    /**
     * Formát času jako DateTime objekt v PHP
     */
    const PHP = -1;

    /**
     * Formát času jako Unix Timestamp
     */
    const TIMESTAMP = -2;

    /**
     * Formát času jako Unix Timestamp
     */
    const INT = -2;

    /**
     * Formát času jako string "Y-m-d H:i:s"
     */
    const MYSQL = "Y-m-d H:i:s";

    /**
     * Formát času jako string "Y-m-d"
     */
    const MYSQL_DATE = "Y-m-d";

    /**
     * Formát času jako string "H:i:s"
     */
    const MYSQL_TIME = "H:i:s";

    /**
     * Formát času vhodný pro posílání do JS aplikací
     */
    const JSON = 'c';

    /** minuta v sekundách */
    const MINUTE = 60;

    /** hodina v sekundách */
    const HOUR = 3600;

    /** den v sekundách */
    const DAY = 86400;

    /** týden v sekundách */
    const WEEK = 604800;

    /** měsíc v sekundách */
    const MONTH = 2629800;

    /** rok v sekundách */
    const YEAR = 31557600;

    /**
     * Konverze časového údaje do různých formátů.
     *
     * @param bool|int|string|DateTime $input        Vstupní čas. False = aktuální čas.
     * @param int                      $outputFormat Konstanty
     * @return DateTime|string|int
     * @throws InvalidArgumentException
     */
    static function convert($input, int $outputFormat = self::PHP)
    {
        if($input === false) {
            $input = time();
        }

        if($input instanceof DateTime) {
            if($outputFormat === self::INT) {
                return $input->getTimestamp();
            }
            if(is_string($outputFormat)) {
                return date($outputFormat, $input->getTimestamp());
            }
            if($outputFormat === self::PHP) {
                return $input;
            }
            return null;
        }

        if(!is_numeric($input)) {
            $origInput = $input;
            $input = @strtotime($input);
            if($input === false or $input === -1) {
                $input = str_replace(" ", "", $origInput); // Helps with formats like 13. 5. 2013
                $input = @strtotime($input);
                if($input === false or $input === -1) {
                    throw new InvalidArgumentException("Invalid input to Time::convert: $origInput");
                }
            }
        }

        if($outputFormat === self::INT) {
            return $input;
        }
        if(is_string($outputFormat)) {
            return date($outputFormat, $input);
        }
        if($outputFormat === self::PHP) {
            $d = new DateTime();
            return $d->setTimestamp($input);
        }
        return null;
    }

    /**
     * České vyjádření relativního času k dnešku. Česky.
     *
     * @param mixed $time       Cokoliv, co umí přijmout convertTime()
     * @param bool  $alwaysTime True = i u starých událostí přidávat čas dne
     * @return string
     */
    static function relative($time, bool $alwaysTime = false) :string
    {
        $time = self::convert($time, self::PHP);
        $today = date("Ymd");
        if($time->format("Ymd") == $today) {
            return "dnes v " . $time->format("H.i");
        }
        $time2 = clone $time;
        if($time2->add(new DateInterval("P1D"))->format("Ymd") == $today) {
            return "včera v " . $time->format("H.i");
        }
        $time2 = clone $time;
        if($time2->add(new DateInterval("P2D"))->format("Ymd") == $today) {
            return "předevčírem v " . $time->format("H.i");
        }
        if($time->format("Y") == date("Y")) {
            return $time->format("j. n.") . ($alwaysTime ? (" v " . $time->format("H.i")) : "");
        }
        return $time->format("j. n. Y") . ($alwaysTime ? (" v " . $time->format("H.i")) : "");
    }

    /**
     * Vrátí textový údaj o stáří něčeho; používá různé jednotky. Pouze v češtině.
     *
     * @param int|DateTime $kdy Doba, kdy se stalo to něco (timestamp)
     * @return string Nějaký text typu '8 dní' nebo '7 minut'
     */
    static function age($kdy) :string
    {
        $kdy = self::convert($kdy, self::INT);
        $age = floor((time() - $kdy) / 60);
        if($age < 60) {
            $age = $age . " " . Strings::plural($age, "minuta", "minuty", "minut");
        } else {
            $age = floor($age / 60);
            if($age >= 24) {
                $age = (floor($age / 24));
                $age .= " " . Strings::plural($age, "den", "dny", "dnů");
            } else {
                $age = $age . " " . Strings::plural($age, "hodina", "hodiny", "hodin");
            }
        }
        if($age == -1 or $age == "0 minut") {
            $age = "úplně nové";
        }
        return $age;
    }

    /**
     * Ověří, zda v daný den je nějaký svátek a tudíž není pracovní den. Bude fungovat do roku 2020, pak bude třeba
     * doplnit velikonoce.
     *
     * @param mixed $time
     * @param bool  $weekends True = Vyhodnocovat i víkendy. False = jen státní svátky.
     * @return int 0 = pracovní den. 1 = svátek. 2 = víkend (if $weekends)
     */
    static function holiday($time, bool $weekends = true) :int
    {
        $date = self::convert($time, self::PHP);

        if($weekends) {
            $w = $date->format("w");
            if($w == 0 or $w == 6) {
                return 2;
            }
        }

        $day = $date->format("dm");
        if($day == "0101" or $day == "0105" or $day == "0805" or $day == "0507" or $day == "0607"
           or $day == "2809" or $day == "2810" or $day == "1711" or $day == "2412" or $day == "2512" or $day == "2612") {
            return 1;
        }

        $day = $date->format("Ymd");

        // podpora pro velikonoce do roku 2020
        if($day == "20100405" or $day == "20110425" or $day == "20120409" or $day == "20130401" or $day == "20140421"
           or $day == "20150406" or $day == "20160328" or $day == "20170417" or $day == "20180402" or $day == "20190422" or $day == "20200413") {
            return 1;
        }

        return 0;
    }

    /**
     * Převede DateInterval na počet sekund.
     * Pokud je interval delší než 1 měsíc, pracuje jen přibližně (za měsíc se považuje cca 30,4 dne)
     *
     * @param DateInterval $d
     * @return int
     */
    static function convertInterval(DateInterval $d)
    {
        $i = 0;
        $i += $d->s;
        $i += $d->i * self::MINUTE;
        $i += $d->h * self::HOUR;
        $i += $d->d * self::DAY;
        $i += $d->m * self::MONTH;
        $i += $d->y * self::YEAR;

        return $i;
    }
}
