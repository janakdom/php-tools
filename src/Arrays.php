<?php
namespace DominikJanak\Tools;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use Traversable;

class Arrays
{

    /**
     * Pokud je pole, převede na řetězec, jinak nechá být
     *
     * @param array|mixed $value
     * @param string      $separator
     * @return mixed
     */
    static function dearrayize($value, string $separator = ";")
    {
        if(is_array($value)) {
            return implode($separator, $value);
        }
        return $value;
    }

    /**
     * Transformace dvoj(či více)-rozměrných polí či Traversable objektů
     *
     * @param array $input       Vstupní pole.
     * @param mixed $outputKeys  Jak mají být tvořeny indexy výstupního pole?
     *                           <br />False = numericky indexovat od 0.
     *                           <br />True = zachovat původní indexy.
     *                           <br />Cokoliv jiného - použít takto pojmenovanou hodnotu z druhého rozměru
     * @param mixed $outputValue Jak mají být tvořeny hodnoty výstupního pole?
     *                           <br />True = zachovat původní položky
     *                           <br />String nebo array = vybrat pouze takto pojmenovanou položku nebo položky.
     *                           <br />False = původní index. Může být zadán i jako prvek pole, pak bude daný prvek mít
     *                           index [key].
     * @return mixed
     */
    static function transform(array $input, $outputKeys, $outputValue)
    {
        $input = self::arrayize($input);
        $output = [];

        foreach($input as $inputI => $inputR) {
            if(is_array($outputValue)) {
                $novaPolozka = [];
                foreach($outputValue as $ov) {
                    if($ov === false) {
                        $novaPolozka["key"] = $inputI;
                    } else {
                        if(isset($inputR[$ov])) {
                            $novaPolozka[$ov] = $inputR[$ov];
                        } else {
                            $novaPolozka[$ov] = null;
                        }
                    }
                }
            } else {
                if($outputValue === true) {
                    $novaPolozka = $inputR;
                } else {
                    if($outputValue === false) {
                        $novaPolozka = $inputI;
                    } else {
                        if(isset($inputR[$outputValue])) {
                            $novaPolozka = $inputR[$outputValue];
                        } else {
                            $novaPolozka = null;
                        }
                    }
                }
            }

            if($outputKeys === false) {
                $output[] = $novaPolozka;
            } else {
                if($outputKeys === true) {
                    $output[$inputI] = $novaPolozka;
                } else {
                    if(isset($inputR[$outputKeys])) {
                        $output[$inputR[$outputKeys]] = $novaPolozka;
                    } else {
                        $output[] = $novaPolozka;
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Zajistí, aby zadaný argument byl array.
     *
     * Převede booly nebo nully na []], pole nechá být, ArrayAccess a Traversable
     * také, vše ostatní převede na array(0=>$hodnota)
     *
     * @param mixed $value
     * @param bool  $forceArrayFromObject True = Traversable objekty také převádět na array
     * @return array|ArrayAccess|Traversable
     */
    static function arrayize($value, bool $forceArrayFromObject = false)
    {
        if(is_array($value)) {
            return $value;
        }

        if(is_bool($value) || $value === null) {
            return [];
        }

        if($value instanceof Traversable) {
            if($forceArrayFromObject) {
                return iterator_to_array($value);
            }
            return $value;
        }

        if($value instanceof ArrayAccess) {
            return $value;
        }

        return [0 => $value];
    }

    /**
     * Seřadí prvky pole dle klíčů hodnot jiného pole
     *
     * @param array $dataArray
     * @param array $keysArray
     * @return null
     */
    static function sortByExternalKeys(array $dataArray, array $keysArray) :?array
    {
        $returnArray = [];
        foreach($keysArray as $k) {
            if(isset($dataArray[$k])) {
                $returnArray[$k] = $dataArray[$k];
            } else {
                $returnArray[$k] = null;
            }
        }
        return $returnArray;
    }

    /**
     * Ze zadaného pole vybere jen ty položky, které mají klíč udaný v druhém poli.
     *
     * @param array|ArrayAccess $array        Asociativní pole
     * @param string|array      $requiredKeys Pole klíčů nebo řada parametrů
     * @return array
     */
    static function filterByKeys($array, array ...$requiredKeys) :array
    {
        if(is_array($requiredKeys[0])) {
            $requiredKeys = $requiredKeys[0];
        }

        if(is_array($array)) {
            return array_intersect_key($array, array_fill_keys($requiredKeys, true));
        }
        if($array instanceof ArrayAccess) {
            $ret = [];
            foreach($requiredKeys as $k) {
                if(isset($array[$k])) {
                    $ret[$k] = $array[$k];
                }
            }
            return $ret;
        }

        throw new InvalidArgumentException("Argument must be an array or object with ArrayAccess");
    }

    /**
     * Z dvojrozměrného pole udělá trojrozměrné, kde první index bude sdružovat řádky dle konkrétní hodnoty.
     *
     * @param array       $data
     * @param string      $groupBy    Název políčka v $data, podle něhož se má sdružovat
     * @param bool|string $orderByKey False (def.) = nechat, jak to přišlo pod ruku. True = seřadit dle sdružované
     *                                hodnoty. String "desc" = sestupně.
     * @return array
     */
    static public function group(array $data, string $groupBy, bool $orderByKey = false) :array
    {
        $output = [];
        foreach($data as $index => $radek) {
            if(!isset($radek[$groupBy])) {
                $radek[$groupBy] = "0";
            }
            if(!isset($output[$radek[$groupBy]])) {
                $output[$radek[$groupBy]] = [];
            }
            $output[$radek[$groupBy]][$index] = $radek;
        }
        if($orderByKey) {
            ksort($output);
        }
        if($orderByKey === "desc") {
            $output = array_reverse($output);
        }
        return $output;
    }

    /**
     * Zadané dvourozměrné pole nebo traversable objekt přeindexuje tak, že jeho jednotlivé indexy
     * budou tvořeny určitým prvkem nebo public vlastností z každého prvku.
     *
     * Pokud některý z prvků vstupního pole neobsahuje $keyName, zachová se jeho původní index.
     *
     * @param array|Traversable $input   Vstupní pole/objekt
     * @param string            $keyName Podle čeho indexovat
     * @return array
     */
    static public function indexByKey($input, string $keyName) :array
    {
        if(!is_array($input) and !($input instanceof Traversable)) {
            throw new InvalidArgumentException("Given argument must be an array or traversable object.");
        }

        $returnedArray = [];

        foreach($input as $index => $f) {
            if(is_array($f)) {
                $key = array_key_exists($keyName, $f) ? $f[$keyName] : $index;
                $returnedArray[$key] = $f;
            } else {
                if(is_object($f)) {
                    $key = property_exists($f, $keyName) ? $f->$keyName : $index;
                    $returnedArray[$key] = $f;
                } else {
                    if(!isset($returnedArray[$index])) {
                        $returnedArray[$index] = $f;
                    }
                }
            }
        }

        return $returnedArray;
    }

    /**
     * Zruší z pole všechny výskyty určité hodnoty.
     *
     * @param array $dataArray
     * @param mixed $valueToDelete     Nesmí být null!
     * @param bool  $keysInsignificant True = přečíslovat vrácené pole, indexy nejsou podstatné. False = nechat původní
     *                                 indexy.
     * @param bool  $strict            == nebo ===
     * @return array Upravené $dataArray
     */
    static public function deleteValue(array $dataArray, $valueToDelete,
        bool $keysInsignificant = true, bool $strict = false) :array
    {
        if($valueToDelete === null) {
            throw new InvalidArgumentException("\$valueToDelete cannot be null.");
        }
        $keys = array_keys($dataArray, $valueToDelete, $strict);
        if($keys) {
            foreach($keys as $k) {
                unset($dataArray[$k]);
            }
            if($keysInsignificant) {
                $dataArray = array_values($dataArray);
            }
        }
        return $dataArray;
    }

    /**
     * Zruší z jednoho pole všechny hodnoty, které se vyskytují ve druhém poli.
     * Ve druhém poli musí jít o skalární typy, objekty nebo array povedou k chybě.
     *
     * @param array $dataArray
     * @param array $arrayOfValuesToDelete
     * @param bool  $keysInsignificant True = přečíslovat vrácené pole, indexy nejsou podstatné. False = nechat původní
     *                                 indexy.
     * @return array Upravené $dataArray
     */
    static public function deleteValues(array $dataArray, array $arrayOfValuesToDelete,
        bool $keysInsignificant = true) :array
    {
        $arrayOfValuesToDelete = self::arrayize($arrayOfValuesToDelete);
        $invertedDeletes = array_fill_keys($arrayOfValuesToDelete, true);
        foreach($dataArray as $i => $r) {
            if(isset($invertedDeletes[$r])) {
                unset($dataArray[$i]);
            }
        }
        if($keysInsignificant) {
            $dataArray = array_values($dataArray);
        }

        return $dataArray;
    }

    /**
     * Obohatí $mainArray o nějaké prvky z $mixinArray. Obě pole by měla být dvourozměrná pole, kde
     * první rozměr je ID a další rozměr je asociativní pole s nějakými vlastnostmi.
     * <br />Data z $mainArray se považují za prioritnější a správnější, a pokud již příslušný prvek obsahují,
     * nepřepíší se tím z $mixinArray.
     *
     * @param array             $mainArray
     * @param array             $mixinArray
     * @param bool|array|string $fields        True = obohatit vším, co v $mixinArray je. Jinak string/array stringů.
     * @param array             $changeIndexes Do $mainField lze použít jiné indexy, než v originále. Sem zadej
     *                                         "překladovou tabulku" ve tvaru array([original_key] => new_key). Ve
     *                                         $fields používej již indexy po přejmenování.
     * @return array Obohacené $mainArray
     */
    static public function enrich(array $mainArray, array $mixinArray,
        bool $fields = true, array $changeIndexes = []) :array
    {
        if($fields !== true) {
            $fields = self::arrayize($fields);
        }
        foreach($mixinArray as $mixinId => $mixinData) {
            if(!isset($mainArray[$mixinId])) {
                continue;
            }
            if($changeIndexes) {
                foreach($changeIndexes as $fromI => $toI) {
                    if(isset($mixinData[$fromI])) {
                        $mixinData[$toI] = $mixinData[$fromI];
                        unset($mixinData[$fromI]);
                    } else {
                        $mixinData[$toI] = null;
                    }
                }
            }
            if($fields === true) {
                $mainArray[$mixinId] += $mixinData;
            } else {
                foreach($fields as $field) {
                    if(!isset($mainArray[$mixinId][$field])) {
                        if(isset($mixinData[$field])) {
                            $mainArray[$mixinId][$field] = $mixinData[$field];
                        } else {
                            $mainArray[$mixinId][$field] = null;
                        }
                    }
                }
            }
        }
        return $mainArray;
    }

    /**
     * Z dvourozměrného pole udělá zpět jednorozměrné
     *
     * @param array $array
     * @return array
     */
    static public function flatten(array $array) :array
    {
        $out = [];
        foreach($array as $i => $subArray) {
            foreach($subArray as $value) {
                $out[] = $value;
            }
        }
        return $out;
    }

    /**
     * Normalizuje hodnoty v poli do rozsahu &lt;0-1&gt;
     *
     * @param array $array
     * @return array
     */
    static public function normaliseValues(array $array) :array
    {
        $array = self::arrayize($array);
        if(!$array) {
            return $array;
        }
        $minValue = min($array);
        $maxValue = max($array);
        if($maxValue == $minValue) {
            $minValue -= 1;
        }
        foreach($array as $index => $value) {
            $array[$index] = ($value - $minValue) / ($maxValue - $minValue);
        }
        return $array;
    }

    /**
     * Rekurzivně převede traversable objekt na obyčejné array.
     *
     * @param Traversable $traversable
     * @param int         $depth Interní, pro kontorlu nekonečné rekurze
     * @return array
     * @throws RuntimeException
     */
    static function traversableToArray(Traversable $traversable, int $depth = 0) :array
    {
        $output = [];
        if($depth > 10) {
            throw new RuntimeException("Recursion is too deep.");
        }
        if(!is_array($traversable) and !($traversable instanceof Traversable)) {
            throw new InvalidArgumentException("\$traversable must be an array or Traversable object.");
        }
        foreach($traversable as $i => $r) {
            if(is_array($r) or ($r instanceof Traversable)) {
                $output[$i] = self::traversableToArray($r, $depth + 1);
            } else {
                $output[$i] = $r;
            }
        }
        return $output;
    }

    /**
     * Porovná, zda jsou hodnoty ve dvou polích stejné. Nezáleží na indexech ani na pořadí prvků v poli.
     *
     * @param array $array1
     * @param array $array2
     * @param bool  $strict Používat ===
     * @return boolean True = stejné. False = rozdílné.
     */
    static function compareValues(array $array1, array $array2, bool $strict = false) :bool
    {
        if(count($array1) != count($array2)) {
            return false;
        }

        $array1 = array_values($array1);
        $array2 = array_values($array2);
        sort($array1, SORT_STRING);
        sort($array2, SORT_STRING);

        foreach($array1 as $i => $r) {
            if($array2[$i] != $r) {
                return false;
            }
            if($strict and $array2[$i] !== $r) {
                return false;
            }
        }

        return true;
    }

    /**
     * Rekurzivní změna kódování libovolného typu proměnné (array, string, atd., kromě objektů).
     *
     * @param string $from       Vstupní kódování
     * @param string $to         Výstupní kódování
     * @param mixed  $array      Co překódovat
     * @param bool   $keys       Mají se iconvovat i klíče? Def. false.
     * @param int    $checkDepth Tento parametr ignoruj, používá se jako pojistka proti nekonečné rekurzi.
     * @return mixed
     */
    static function iconv(string $from, string $to, $array, bool $keys = false, int $checkDepth = 0)
    {
        if(is_object($array)) {
            return $array;
        }
        if(!is_array($array)) {
            if(is_string($array)) {
                return iconv($from, $to, $array);
            } else {
                return $array;
            }
        }
        if($checkDepth > 20) {
            return $array;
        }

        $output = [];
        foreach($array as $i => $r) {
            if($keys) {
                $i = iconv($from, $to, $i);
            }
            $output[$i] = self::iconv($from, $to, $r, $keys, $checkDepth + 1);
        }
        return $output;
    }

    /**
     * Vytvoří kartézský součin.
     * <code>
     * $input = array(
     *        "barva" => array("red", "green"),
     *        "size" => array("small", "big")
     * );
     *
     * $output = array(
     *        [0] => array("barva" => "red", "size" => "small"),
     *        [1] => array("barva" => "green", "size" => "small"),
     *        [2] => array("barva" => "red", "size" => "big"),
     *        [3] => array("barva" => "green", "size" => "big")
     * );
     *
     * </code>
     * @param array $input
     * @return array
     */
    static function cartesian(array $input) :array
    {
        $input = array_filter($input);

        $result = [[]];

        foreach($input as $key => $values) {
            $append = [];

            foreach($result as $product) {
                foreach($values as $item) {
                    $product[$key] = $item;
                    $append[] = $product;
                }
            }

            $result = $append;
        }

        return $result;
    }

    /**
     * Zjistí, zda je pole asociativní
     *
     * @param array $array
     * @return bool
     * @author Michael Pavlista
     */
    public static function isAssoc(array $array) :bool
    {
        return empty($array) || !self::isNumeric($array);
    }

    /**
     * Zjistí, zda má pole pouze číselné indexy
     *
     * @param array $array
     * @return bool
     * @author Michael Pavlista
     */
    public static function isNumeric(array $array) :bool
    {
        return empty($array) || is_numeric(implode('', array_keys($array)));
    }

    /**
     * @param array $old
     * @param array $new
     * @return array
     *
     * @author Paul's Simple Diff Algorithm v 0.1
     * (C) Paul Butler 2007 <http://www.paulbutler.org/>
     * May be used and distributed under the zlib/libpng license.
     */
    public static function diff(array $old, array $new) :array
    {
        $matrix = [];
        $maxlen = 0;
        foreach($old as $oindex => $ovalue) {
            $nkeys = array_keys($new, $ovalue);
            foreach($nkeys as $nindex) {
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if($matrix[$oindex][$nindex] > $maxlen) {
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if($maxlen == 0) {
            return [['d' => $old, 'i' => $new]];
        }
        return array_merge(
            self::diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            self::diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
    }
}
