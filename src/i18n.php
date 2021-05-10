<?php
/**
 * contains a i18n class
 *
 * @package         tourBase
 * @subpackage      Core\i18n
 * @author          David Lienhard <david@t-error.ch>
 * @copyright       tourasia
 */

declare(strict_types=1);

namespace DavidLienhard\i18n;

use DavidLienhard\i18n\i18nInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

/**
 * class for internationalization
 *
 * @author          David Lienhard <david@t-error.ch>
 * @copyright       tourasia
 */
class i18n implements i18nInterface
{
    /**
     * Language file path
     * This is the path for the language files. You must use the '{LANGUAGE}' placeholder for the language or the script wont find any language files.
     */
    protected string $filePath = "./lang/lang_{LANGUAGE}.ini";

    /**
     * Cache file path
     * This is the path for all the cache files. Best is an empty directory with no other files in it.
     */
    protected string $cachePath = "./langcache/";

    /**
     * Fallback language
     * This is the language which is used when there is no language file for all other user languages. It has the lowest priority.
     * Remember to create a language file for the fallback!!
     */
    protected string $fallbackLang = "en";

    /**
     * Merge in fallback language
     * Whether to merge current language's strings with the strings of the fallback language ($fallbackLang).
     */
    protected bool $mergeFallback = false;

    /** The class name of the compiled class that contains the translated texts. */
    protected string $prefix = "L";

    /**
     * Forced language
     * If you want to force a specific language define it here.
     */
    protected string|null $forcedLang = null;

    /**
     * This is the separator used if you use sections in your ini-file.
     * For example, if you have a string 'greeting' in a section 'welcomepage' you will can access it via 'L::welcomepage_greeting'.
     * If you changed it to 'ABC' you could access your string via 'L::welcomepageABCgreeting'
     */
    protected string $sectionSeparator = "_";


    /*
     * The following properties are only available after calling init().
     */

    /**
     * User languages
     * These are the languages the user uses.
     * Normally, if you use the getUserLangs-method this array will be filled in like this:
     * 1. Forced language
     * 2. Language in $_GET['lang']
     * 3. Language in $_SESSION['lang']
     * 4. Fallback language
     *
     * @var     array       $userLangs
     */
    protected array $userLangs = [];

    /** the language that has been applied after running the initialization */
    protected string|null $appliedLang = null;

    /** path to the language file that has been used */
    protected string|null $langFilePath = null;

    /** path to the cache file that has been used */
    protected string|null $cacheFilePath = null;

    /** whether the class has been initialized */
    protected bool $isInitialized = false;

    /* filesystem to use */
    private Filesystem $filesystem;


    /**
     * Constructor
     * The constructor sets all important settings. All params are optional, you can set the options via extra functions too.
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string|null     $filePath       This is the path for the language files. You must use the '{LANGUAGE}' placeholder for the language.
     * @param           string|null     $cachePath      This is the path for all the cache files. Best is an empty directory with no other files in it. No placeholders.
     * @param           string|null     $fallbackLang   This is the language which is used when there is no language file for all other user languages. It has the lowest priority.
     * @param           string|null     $prefix         The class name of the compiled class that contains the translated texts. Defaults to 'L'.
     * @return          void
     * @uses            self::$filePath
     * @uses            self::$cachePath
     * @uses            self::$fallbackLang
     * @uses            self::$prefix
     */
    public function __construct(
        string $filePath = null,
        string $cachePath = null,
        string $fallbackLang = null,
        string $prefix = null,
        Filesystem $filesystem = null
    ) {
        if ($filePath !== null) {
            $this->filePath = $filePath;
        }

        if ($cachePath !== null) {
            $this->cachePath = $cachePath;
        }

        if ($fallbackLang !== null) {
            $this->fallbackLang = $fallbackLang;
        }

        if ($prefix !== null) {
            $this->prefix = $prefix;
        }

        if ($filesystem === null) {
            $adapter = new LocalFilesystemAdapter("/");
            $this->filesystem = new Filesystem($adapter);
        }
    }

    /**
     * initializes the class
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @throws          \BadMethodCallException     if the object is already initialized
     * @throws          \RuntimeException           if no language file can be found
     * @throws          \Exception                  if the cache-file could ne be created
     * @uses            self::isInitialized()
     * @uses            self::$isInitialized
     * @uses            self::$userLangs
     * @uses            self::getUserLangs()
     * @uses            self::$appliedLang
     * @uses            self::$langFilePath
     * @uses            self::getConfigFilename()
     * @uses            self::$cacheFilePath
     * @uses            self::$cachePath
     * @uses            self::$prefix
     * @uses            self::$fallbackLang
     * @uses            self::$mergeFallback
     * @uses            self::load()
     * @uses            self::compile()
     */
    public function init(): void
    {
        if ($this->isInitialized()) {
            throw new \BadMethodCallException(
                "This object from class ".__CLASS__." is already initialized. ".
                "It is not possible to init one object twice!"
            );
        }

        $this->isInitialized = true;

        $this->userLangs = $this->getUserLangs();

        // search for language file
        $this->appliedLang = null;
        foreach ($this->userLangs as $priority => $langcode) {
            $langFilePath = $this->getConfigFilename($langcode);
            if ($this->filesystem->fileExists($this->langFilePath)) {
                $this->langFilePath = $langFilePath;
                $this->appliedLang = $langcode;
                break;
            }
        }

        if ($this->appliedLang === null || $this->langFilePath === null) {
            throw new \RuntimeException(
                "No language file was found."
            );
        }

        // search for cache file
        $this->cacheFilePath = $this->cachePath."/i18n_".md5($this->langFilePath)."_".$this->prefix."_".$this->appliedLang.".cache.php";

        // create cache path if necessary
        try {
            $this->filesystem->createDirectory($this->cachePath);
        } catch (FilesystemException | UnableToCreateDirectory $e) {
            throw new \Exception(
                "could not create cache path '".$this->cachePath."'",
                $e->getCode(),
                $e
            );
        }

        // whether we need to create a new cache file
        $outdated = !$this->filesystem->fileExists($this->cacheFilePath)
            || $this->filesystem->lastModified($this->cacheFilePath) < $this->filesystem->lastModified($this->langFilePath) // the language config was updated
            || ($this->mergeFallback && $this->filesystem->lastModified($this->cacheFilePath) < $this->filesystem->lastModified($this->getConfigFilename($this->fallbackLang))); // the fallback language config was updated

        if ($outdated) {
            $config = $this->load($this->langFilePath);
            if ($this->mergeFallback) {
                $config = array_replace_recursive($this->load($this->getConfigFilename($this->fallbackLang)), $config);
            }

            $compiled = "<?php\n".
                "declare(strict_types=1);\n\n".
                "use \\DavidLienhard\\i18n\\i18nCacheInterface;\n\n".
                "class ".$this->prefix." implements i18nCacheInterface\n".
                "{\n".
                $this->compile($config)."\n".
                "    public static function __callStatic(string \$string, array | null \$args) : mixed\n".
                "    {\n".
                "        return vsprintf(constant(\"self::\".\$string), \$args);\n".
                "    }\n".
                "}\n\n".
                "function ".$this->prefix."(string \$string, array | null \$args = null) : mixed\n".
                "{\n".
                "    \$return = constant(\"".$this->prefix."::\".\$string);\n".
                "    return \$args ? vsprintf(\$return, \$args) : \$return;\n".
                "}";

            if (!$this->filesystem->fileExists($this->cachePath)) {
                try {
                    $this->filesystem->createDirectory($this->cachePath);
                } catch (FilesystemException | UnableToCreateDirectory $e) {
                    throw new \Exception(
                        "could not create cache path '".$this->cachePath."'",
                        $e->getCode(),
                        $e
                    );
                }
            }

            try {
                $this->filesystem->write($this->cacheFilePath, $compiled);
            } catch (FilesystemException | UnableToWriteFile $e) {
                throw new \Exception(
                    "Could not write cache file to path '".$this->cacheFilePath."'. Is it writable?",
                    $e->getCode(),
                    $e
                );
            }
        }//end if

        require_once $this->cacheFilePath;
    }

    /**
     * return whether the class is initialized or not
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     */
    public function isInitialized() : bool
    {
        return $this->isInitialized;
    }

    /**
     * returns the applied language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     */
    public function getAppliedLang() : string|null
    {
        return $this->appliedLang;
    }

    /**
     * returns the cache path
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     */
    public function getCachePath() : string
    {
        return $this->cachePath;
    }

    /**
     * returns the fallback language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     */
    public function getFallbackLang() : string
    {
        return $this->fallbackLang;
    }

    /**
     * sets the path of the lanuage files
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string  $filePath   filepath to set
     */
    public function setFilePath(string $filePath): void
    {
        $this->fail_after_init();
        $this->filePath = $filePath;
    }

    /**
     * sets the path to the cache files
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string  $cachePath  cache path to set
     */
    public function setCachePath(string $cachePath): void
    {
        $this->fail_after_init();
        $this->cachePath = $cachePath;
    }

    /**
     * sets a fallback language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string  $fallbackLang   language to set
     */
    public function setFallbackLang(string $fallbackLang): void
    {
        $this->fail_after_init();
        $this->fallbackLang = $fallbackLang;
    }

    /**
     * whether to merge the fallback languages or not
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           bool    $mergeFallback  merge fallback language
     */
    public function setMergeFallback(bool $mergeFallback): void
    {
        $this->fail_after_init();
        $this->mergeFallback = $mergeFallback;
    }

    /**
     * sets the prefix for the result class
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string  $prefix     prefix to set
     */
    public function setPrefix(string $prefix): void
    {
        $this->fail_after_init();
        $this->prefix = $prefix;
    }

    /**
     * sets a forced language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string  $forcedLang     forced language to set
     */
    public function setForcedLang(string $forcedLang): void
    {
        $this->fail_after_init();
        $this->forcedLang = $forcedLang;
    }

    /**
     * sets as section separator
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string      $sectionSeparator       section separator to set
     * @uses            self::fail_after_init()
     * @uses            self::$sectionSeparator
     */
    public function setSectionSeparator(string $sectionSeparator): void
    {
        $this->fail_after_init();
        $this->sectionSeparator = $sectionSeparator;
    }

    /**
     * getUserLangs()
     * Returns the user languages
     * Normally it returns an array like this:
     * 1. Forced language
     * 2. Language in $_GET['lang']
     * 3. Language in $_SESSION['lang']
     * 4. HTTP_ACCEPT_LANGUAGE
     * 5. Fallback language
     * Note: duplicate values are deleted.
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @return          array       with the user languages sorted by priority
     * @uses            self::$forcedLang
     * @uses            self::$fallbackLang
     */
    public function getUserLangs() : array
    {
        $userLangs = [];

        // Highest priority: forced language
        if ($this->forcedLang !== null) {
            $userLangs[] = $this->forcedLang;
        }

        // 2nd highest priority: GET parameter 'lang'
        if (isset($_GET['lang']) && is_string($_GET['lang'])) {
            $userLangs[] = $_GET['lang'];
        }

        // 3rd highest priority: SESSION parameter 'lang'
        if (isset($_SESSION['lang']) && is_string($_SESSION['lang'])) {
            $userLangs[] = $_SESSION['lang'];
        }

        // 4th highest priority: HTTP_ACCEPT_LANGUAGE
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $part) {
                $userLangs[] = strtolower(substr($part, 0, 2));
            }
        }

        // Lowest priority: fallback
        $userLangs[] = $this->fallbackLang;

        // remove duplicate elements
        $userLangs = array_unique($userLangs);

        // remove illegal userLangs
        $userLangs2 = [];
        foreach ($userLangs as $key => $value) {
            // only allow a-z, A-Z and 0-9 and _ and -
            if (preg_match("/^[a-zA-Z0-9_-]*\$/", $value) === 1) {
                $userLangs2[$key] = $value;
            }
        }

        return $userLangs2;
    }

    /**
     * returns the path to the configuration file
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string          $langcode           language code to use
     * @uses            self::$filePath
     */
    protected function getConfigFilename(string $langcode) : string
    {
        return str_replace("{LANGUAGE}", $langcode, $this->filePath);
    }

    /**
     * loads the source file and returns it as an array
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           string          $filename           file to load
     * @return          array
     * @throws          \InvalidArgumentException           if the extenstion of the given file is not supported
     */
    protected function load(string $filename) : array
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        switch ($extension) {
            case "properties":
            case "ini":
                $config = parse_ini_file($filename, true);
                break;
            case "yml":
            case "yaml":
                $config = spyc_load_file($filename);
                break;
            case "json":
                try {
                    $fileContent = $this->filesystem->read($filename);
                } catch (FilesystemException | UnableToReadFile $e) {
                    throw new \Exception(
                        "unable to read language file '".$filename."'",
                        $e->getCode(),
                        $e
                    );
                }
                $config = json_decode($fileContent, true);
                break;
            default:
                throw new \InvalidArgumentException(
                    $extension." is not a valid extension!"
                );
        }//end switch
        return $config;
    }

    /**
     * recursively compiles an associative array to PHP code.
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @param           array           $config         configuration to parse
     * @param           string          $prefix         prefix to use infront of the const
     * @throws          \InvalidArgumentException
     */
    protected function compile(array $config, string $prefix = "") : string
    {
        $code = "";
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $code .= $this->compile($value, $prefix.$key.$this->sectionSeparator);
            } else {
                $fullName = $prefix.$key;
                if (!preg_match("/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\$/", $fullName)) {
                    throw new \InvalidArgumentException(
                        __CLASS__.": Cannot compile translation key ".$fullName." because it is not a valid PHP identifier."
                    );
                }
                $code .= "    const ".$fullName." = '".str_replace("'", "\\'", strval($value))."';\n";
            }
        }
        return $code;
    }

    /**
     * checks if the class already has been initialized
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       tourasia
     * @throws          \BadMethodCallException if the class is already initalized
     * @uses            self::$isInitialized
     */
    protected function fail_after_init(): void
    {
        if ($this->isInitialized()) {
            throw new \BadMethodCallException("This ".__CLASS__." object is already initalized, so you can not change any settings.");
        }
    }
}
