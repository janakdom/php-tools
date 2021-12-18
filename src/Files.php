<?php
namespace DominikJanak\Tools;

use DominikJanak\Tools\Exceptions\Files\FileAccessException;
use DominikJanak\Tools\Exceptions\Files\FileException;
use InvalidArgumentException;
use RuntimeException;

class Files
{

    const LOWERCASE = "lower";
    const UPPERCASE = "upper";

    /**
     * Vrací jen jméno souboru
     *
     * `/var/www/vhosts/somefile.txt` => `somefile.txt`
     *
     * @param string $path
     * @return string
     */
    static function filename(string $path) :string
    {
        return basename($path);
    }

    /**
     * Přípona souboru
     *
     * `/var/www/vhosts/somefile.txt` => `txt`
     *
     * @param string      $path
     * @param string|null $case self::LOWERCASE nebo self::UPPERCASE. Cokoliv jiného = neměnit velikost přípony.
     * @return string
     */
    static function extension(string $path, string $case = null) :string
    {
        $name = self::filename($path);

        if(preg_match('~\.(\w{1,10})\s*$~', $name, $parts)) {
            if(!$case) {
                return $parts[1];
            }
            if(strtoupper($case) == self::LOWERCASE) {
                return Strings::lower($parts[1]);
            }
            if(strtoupper($case) == self::UPPERCASE) {
                return Strings::upper($parts[1]);
            }
            return $parts[1];
        }
        return "";
    }

    /**
     * Jméno souboru bez přípony.
     *
     * `/var/www/vhosts/somefile.txt` => `somefile`
     *
     * @param string $path
     * @return string
     */
    static function filenameWithoutExtension(string $path) :string
    {
        $path = self::filename($path);
        if(preg_match('~(.*)\.(\w{1,10})$~', $path, $parts)) {
            return $parts[1];
        }
        return $path;
    }

    /**
     * Vrátí jméno souboru, jako kdyby byl přejmenován, ale ve stejném adresáři
     *
     * `/var/www/vhosts/somefile.txt` => `/var/www/vhosts/anotherfile.txt`
     *
     * @param string $path    Původní cesta k souboru
     * @param string $newName Nové jméno souboru
     * @return string
     */
    static function changedFilename(string $path, string $newName) :string
    {
        return self::dir($path) . "/" . $newName;
    }

    /**
     * Jen cesta k adresáři.
     *
     * `/var/www/vhosts/somefile.txt` => `/var/www/vhosts`
     *
     * @param string $path
     * @param bool   $real True = použít realpath()
     * @return string Pokud je $real==true a $path neexistuje, vrací empty string
     */
    static function dir(string $path, bool $real = false) :string
    {
        if($real) {
            $path = realpath($path);
            if($path and is_dir($path)) {
                $path .= "/file";
            }
        }
        return dirname($path);
    }

    /**
     * Přidá do jména souboru něco na konec, před příponu.
     *
     * `/var/www/vhosts/somefile.txt` => `/var/www/vhosts/somefile-affix.txt`
     *
     * @param string $filename
     * @param string $appendString
     * @param bool   $withPath Vracet i s cestou? Anebo jen jméno souboru?
     * @return string
     */
    static function addBeforeExtension(string $filename, string $appendString, bool $withPath = true) :string
    {
        if($withPath) {
            $dir = self::dir($filename) . "/";
        } else {
            $dir = "";
        }
        if(!$dir or $dir == "./") {
            $dir = "";
        }
        $filenameWithoutExtension = self::filenameWithoutExtension($filename);
        $extension = self::extension($filename);
        if($extension) {
            $addExtension = "." . $extension;
        } else {
            $addExtension = "";
        }
        return $dir . $filenameWithoutExtension . $appendString . $addExtension;
    }

    /**
     * Nastaví práva, aby $filename bylo zapisovatelné, ať už je to soubor nebo adresář
     *
     * @param string $filename
     * @return bool Dle úspěchu
     * @throws FileException Pokud zadaná cesta není
     * @throws FileAccessException Pokud změna selže
     */
    static function perms(string $filename) :bool
    {
        if(!file_exists($filename)) {
            throw new FileException("Missing: $filename");
        }
        if(!is_writeable($filename)) {
            throw new FileException("Not writable: $filename");
        }

        if(is_dir($filename)) {
            $ok = chmod($filename, 0777);
        } else {
            $ok = chmod($filename, 0666);
        }

        if(!$ok) {
            throw new FileAccessException("Could not chmod $filename");
        }

        return $ok;
    }

    /**
     * Přesune soubor i s adresářovou strukturou
     *
     * @param string $file Cílový soubor
     * @param string $from Adresář, který brát jako základ
     * @param string $to   Clový adresář
     * @param bool   $copy True (default) = kopírovat, false = přesunout
     * @return string Cesta k novému souboru
     * @throws FileException Když $file není nalezeno nebo když selže kopírování
     * @throws InvalidArgumentException Když $file není umístěno v $from
     */
    static function moveFile(string $file, string $from, string $to, bool $copy = false) :string
    {
        if(!file_exists($file)) {
            throw new FileException("Not found: $file");
        }
        if(!Strings::startsWith($file, $from)) {
            throw new InvalidArgumentException("File $file is not in directory $from");
        }
        $newPath = $to . "/" . Strings::substring($file, Strings::length($from));
        $newDir = self::dir($newPath);
        self::createDirectories($newDir);
        if($copy) {
            $ok = copy($file, $newPath);
        } else {
            $ok = rename($file, $newPath);
        }
        if(!$ok) {
            throw new FileException("Failed copying to $newPath");
        }
        self::perms($newPath);
        return $newPath;
    }

    /**
     * Zkopíruje soubor i s adresářovou strukturou
     *
     * @param string $file
     * @param string $from
     * @param string $to
     * @return string
     */
    static function copyFile(string $file, string $from, string $to) :string
    {
        return self::moveFile($file, $from, $to, true);
    }

    /**
     * Vrátí cestu k souboru, jako kdyby byl umístěn do jiného adresáře i s cestou k sobě.
     *
     * @param string $file Jméno souboru
     * @param string $from Cesta k němu
     * @param string $to   Adresář, kam ho chceš přesunout
     * @return string
     * @throws InvalidArgumentException
     */
    static function rebasedFilename(string $file, string $from, string $to) :string
    {
        if(!Strings::startsWith($file, $from)) {
            throw new InvalidArgumentException("File $file is not in directory $from");
        }
        $secondPart = Strings::substring($file, Strings::length($from));
        if($secondPart[0] == "/") {
            $secondPart = substr($secondPart, 1);
        }
        $newPath = $to . "/" . $secondPart;
        return $newPath;
    }

    /**
     * Ověří, zda soubor je v zadaném adresáři.
     *
     * @param string $file
     * @param string $dir
     * @return bool
     */
    static function isFileInDir(string $file, string $dir) :bool
    {
        if(!Strings::endsWith($dir, "/")) {
            $dir .= "/";
        }
        return Strings::startsWith($file, $dir);
    }

    /**
     * Vytvoří bezpečné jméno pro soubor
     *
     * @param string     $filename
     * @param array|null $unsafeExtensions
     * @param string     $safeExtension
     * @return string
     */
    static function safeName(string $filename, array $unsafeExtensions = null, string $safeExtension = "txt") :string
    {
        if($unsafeExtensions === null) {
            $unsafeExtensions = ["php", "phtml", "inc", "php3", "php4", "php5"];
        }
        if($filename[0] == '.') {
            $filename = substr($filename, 1);
        }
        $filename = str_replace(DIRECTORY_SEPARATOR, '-', $filename);
        $extension = self::extension($filename, "l");
        if(in_array($extension, $unsafeExtensions)) {
            $extension = $safeExtension;
        }
        $name = self::filenameWithoutExtension($filename);
        $name = Strings::safe($name, false);
        if(preg_match('~^(.*)[-_]+$~', $name, $partsName)) {
            $name = $partsName[1];
        }
        if(preg_match('~^[-_]+(.*)$~', $name, $partsName)) {
            $name = $partsName[1];
        }
        $ret = $name;
        if($extension) {
            $ret .= "." . $extension;
        }
        return $ret;
    }

    /**
     * Vytvoří soubor, pokud neexistuje, a udělá ho zapisovatelným
     *
     * @param string $filename
     * @param bool   $createDirectoriesIfNeeded
     * @param string $content Pokud se má vytvořit nový soubor, naplní se tímto obsahem
     * @return string Jméno vytvořného souboru (cesta k němu)
     * @throws InvalidArgumentException
     * @throws FileException
     * @throws FileAccessException
     */
    static function create(string $filename, bool $createDirectoriesIfNeeded = true, string $content = '') :string
    {
        if(!$filename) {
            throw new InvalidArgumentException("Completely missing argument!");
        }
        if(file_exists($filename) and is_dir($filename)) {
            throw new FileException("$filename is directory!");
        }
        if(file_exists($filename)) {
            self::perms($filename);
            return $filename;
        }
        if($createDirectoriesIfNeeded) {
            self::createDirectories(self::dir($filename, false));
        }
        $ok = @touch($filename);
        if(!$ok) {
            throw new FileAccessException("Could not create file $filename");
        }
        self::perms($filename);
        if($content) {
            file_put_contents($filename, $content);
        }
        return $filename;
    }

    /**
     * Vrací práva k určitému souboru či afdresáři jako třímístný string.
     *
     * @param string $path
     * @return string Např. "644" nebo "777"
     * @throws FileException
     */
    static function getPerms(string $path) :string
    {
        //http://us3.php.net/manual/en/function.fileperms.php example #1
        if(!file_exists($path)) {
            throw new FileException("File '$path' is missing");
        }
        return substr(sprintf('%o', fileperms($path)), -3);
    }

    /**
     * Pokusí se vytvořit strukturu adresářů v zadané cestě.
     *
     * @param string $path
     * @return string Vytvořená cesta
     * @throws FileException Když už takto pojmenovaný soubor existuje a jde o obyčejný soubor nebo když vytváření
     *                       selže.
     */
    static function createDirectories(string $path, $perms = 0777) :string
    {
        if(!$path) {
            throw new InvalidArgumentException("\$path can not be empty.");
        }

        if(file_exists($path)) {
            if(is_dir($path)) {
                return $path;
            }
            throw new FileException("\"$path\" is a regular file!");
        }

        $ret = @mkdir($path, $perms, true);
        if(!$ret) {
            throw new FileException("Directory \"$path\ could not be created.");
        }

        return $path;
    }

    /**
     * Vytvoří adresář, pokud neexistuje, a udělá ho obecně zapisovatelným
     *
     * @param string $filename
     * @param bool   $createDirectoriesIfNeeded
     * @return string Jméno vytvořneého adresáře
     * @throws InvalidArgumentException
     * @throws FileException
     * @throws FileAccessException
     */
    static function mkdir(string $filename, bool $createDirectoriesIfNeeded = true) :string
    {
        if(!$filename) {
            throw new InvalidArgumentException("Completely missing argument!");
        }
        if(file_exists($filename) and !is_dir($filename)) {
            throw new FileException("$filename is not a directory!");
        }
        if(file_exists($filename)) {
            self::perms($filename);
            return $filename;
        }
        if($createDirectoriesIfNeeded) {
            self::createDirectories($filename);
        } else {
            $ok = @mkdir($filename);
            if(!$ok) {
                throw new FileAccessException("Could not create directory $filename");
            }
        }
        self::perms($filename);
        return $filename;
    }

    /**
     * Najde volné pojmenování pro soubor v určitém adresáři tak, aby bylo jméno volné.
     * <br />Pokus je obsazené, pokouší se přidávat pomlčku a čísla až do 99, pak přejde na uniqid():
     * <br />freeFilename("/files/somewhere","abc.txt");
     * <br />Bude zkoušet: abc.txt, abc-2.txt, abc-3.txt atd.
     *
     * @param string $path     Adresář
     * @param string $filename Požadované jméno souboru
     * @return string Jméno souboru (ne celá cesta, jen jméno souboru)
     * @throws FileAccessException
     */
    static function freeFilename(string $path, string $filename) :string
    {
        if(!file_exists($path) or !is_dir($path) or !is_writable($path)) {
            throw new FileAccessException("Directory $path is missing or not writeble.");
        }
        if(!file_exists($path . "/" . $filename)) {
            return $filename;
        }
        $maxTries = 99;
        $filenamePart = self::filenameWithoutExtension($filename);
        $extension = self::extension($filename);
        $addExtension = $extension ? ".$extension" : "";
        for($addedIndex = 2; $addedIndex < $maxTries; $addedIndex++) {
            if(!file_exists($path . "/" . $filenamePart . "-" . $addedIndex . $addExtension)) {
                break;
            }
        }
        if($addedIndex == $maxTries) {
            return $filenamePart . "-" . uniqid("") . $addExtension;
        }
        return $filenamePart . "-" . $addedIndex . $addExtension;
    }

    /**
     * Vymaže obsah adresáře
     *
     * @param string $dir
     * @return boolean Dle úspěchu
     * @throws InvalidArgumentException
     */
    static function purgeDir(string $dir) :bool
    {
        if(!is_dir($dir)) {
            throw new InvalidArgumentException("$dir is not directory.");
        }
        $content = glob($dir . "/*");
        if($content) {
            foreach($content as $sub) {
                if($sub == "." or $sub == "..") {
                    continue;
                }
                self::remove($sub);
            }
        }
        return true;
    }

    /**
     * Smaže adresář a rekurzivně i jeho obsah
     *
     * @param string $dir
     * @param int    $depthLock Interní, ochrana proti nekonečné rekurzi
     * @return boolean Dle úspěchu
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws FileAccessException
     */
    static function removeDir(string $dir, int $depthLock = 0) :bool
    {
        if($depthLock > 15) {
            throw new RuntimeException("Recursion too deep at $dir");
        }
        if(!file_exists($dir)) {
            return true;
        }
        if(!is_dir($dir)) {
            throw new InvalidArgumentException("$dir is not directory.");
        }

        $content = glob($dir . "/*");
        if($content) {
            foreach($content as $sub) {
                if($sub == "." or $sub == "..") {
                    continue;
                }
                if(is_dir($sub)) {
                    self::removeDir($sub, $depthLock + 1);
                } else {
                    if(is_writable($sub)) {
                        unlink($sub);
                    } else {
                        throw new FileAccessException("Could not delete file $sub");
                    }
                }
            }
        }
        $ok = rmdir($dir);
        if(!$ok) {
            throw new FileAccessException("Could not remove dir $dir");
        }

        return true;
    }

    /**
     * Smaže $path, ať již je to adresář nebo soubor
     *
     * @param string $path
     * @param bool   $onlyFiles Zakáže mazání adresářů
     * @return boolean Dle úspěchu
     * @throws FileAccessException
     * @throws FileException
     */
    static function remove(string $path, bool $onlyFiles = false) :bool
    {
        if(!file_exists($path)) {
            return true;
        }
        if(is_dir($path)) {
            if($onlyFiles) {
                throw new FileException("$path is a directory!");
            }
            return self::removeDir($path);
        } else {
            $ok = unlink($path);
            if(!$ok) {
                throw new FileAccessException("Could not delete file $path");
            }
        }
        return true;
    }

    /**
     * Stažení vzdáleného souboru pomocí  cURL
     *
     * @param string $url  URL vzdáleného souboru
     * @param string $path Kam stažený soubor uložit?
     * @param bool   $stream
     */
    public static function downloadFile(string $url, string $path, bool $stream = true, bool $checkSSL = true) :void
    {
        $curl = curl_init($url);

        if(!$stream) {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            file_put_contents($path, curl_exec($curl));
        } else {
            $fp = fopen($path, 'w');

            curl_setopt($curl, CURLOPT_FILE, $fp);

            if(!$checkSSL) {
                // stop check the existence of a common name in the SSL peer certificate
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                // stop check the existence of a common name and also verify that it matches the hostname provided
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            }

            curl_exec($curl);
            fclose($fp);
        }

        curl_close($curl);
    }

    /**
     * Vrací maximální nahratelnou velikost souboru.
     *
     * Bere menší z hodnot post_max_size a upload_max_filesize a převede je na obyčejné číslo.
     *
     * @return int Bytes
     */
    static function maxUploadFileSize() :int
    {
        $file_max = Strings::parsePhpNumber(ini_get("post_max_size"));
        $post_max = Strings::parsePhpNumber(ini_get("upload_max_filesize"));
        $php_max = min($file_max, $post_max);
        return $php_max;
    }
}
