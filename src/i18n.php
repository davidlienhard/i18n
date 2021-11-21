<?php declare(strict_types=1);

/**
 * contains a i18n class
 *
 * @author          David Lienhard <david@t-error.ch>
 * @copyright       David Lienhard
 */

namespace DavidLienhard\i18n;

use DavidLienhard\i18n\i18nInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use Symfony\Component\Yaml\Yaml;

/**
 * class for internationalization
 *
 * @author          David Lienhard <david@t-error.ch>
 * @copyright       David Lienhard
 */
class i18n implements i18nInterface
{
    /**
     * current version number of this library
     */
    protected string $version = "1.0.6";

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

    /** optional namespace to use in created class */
    protected string|null $namespace = null;

    /* filesystem to use to read data */
    private Filesystem $sourceFilesystem;

    /* filesystem to use to write cache data */
    private Filesystem $cacheFilesystem;


    /**
     * Constructor
     * The constructor sets all important settings. All params are optional, you can set the options via extra functions too.
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string|null     $filePath           This is the path for the language files. You must use the '{LANGUAGE}' placeholder for the language.
     * @param           string|null     $cachePath          This is the path for all the cache files. Best is an empty directory with no other files in it. No placeholders.
     * @param           string|null     $fallbackLang       This is the language which is used when there is no language file for all other user languages. It has the lowest priority.
     * @param           string|null     $prefix             The class name of the compiled class that contains the translated texts. Defaults to 'L'.
     * @param           Filesystem|null $sourceFilesystem   filesystem object to use to read data
     * @param           Filesystem|null $cacheFilesystem    filesystem object to use to write cache data
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
        Filesystem $sourceFilesystem = null,
        Filesystem $cacheFilesystem = null
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

        if ($sourceFilesystem === null) {
            $adapter = new LocalFilesystemAdapter("/");
            $this->sourceFilesystem = new Filesystem($adapter);
        }

        if ($cacheFilesystem === null) {
            $adapter = new LocalFilesystemAdapter("/");
            $this->cacheFilesystem = new Filesystem($adapter);
        }
    }

    /**
     * initializes the class
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
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

        $this->getAppliedLanguage();

        if ($this->appliedLang === null || $this->langFilePath === null) {
            throw new \RuntimeException(
                "No language file was found."
            );
        }

        // define hash to use in cache path
        $hash = \md5(
            $this->langFilePath.
            $this->version.
            ($this->namespace ?? "")
        );

        // define name of cache file
        $this->cacheFilePath = $this->cachePath."/i18n_".
            $hash."_".
            $this->prefix."_".
            $this->appliedLang.".cache.php";

        if ($this->isOutdated()) {
            $config = $this->load($this->langFilePath);
            if ($this->mergeFallback) {
                $config = array_replace_recursive($this->load($this->getConfigFilename($this->fallbackLang)), $config);
            }

            $compiled = $this->createCacheFile($config);

            if (!$this->cacheFilesystem->fileExists($this->cachePath)) {
                try {
                    $this->cacheFilesystem->createDirectory($this->cachePath);
                } catch (FilesystemException | UnableToCreateDirectory $e) {
                    throw new \Exception(
                        "could not create cache path '".$this->cachePath."'",
                        intval($e->getCode()),
                        $e
                    );
                }
            }

            try {
                $this->cacheFilesystem->write($this->cacheFilePath, $compiled);
            } catch (FilesystemException | UnableToWriteFile $e) {
                throw new \Exception(
                    "Could not write cache file to path '".$this->cacheFilePath."'. Is it writable?",
                    intval($e->getCode()),
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
     * @copyright       David Lienhard
     */
    public function isInitialized() : bool
    {
        return $this->isInitialized;
    }

    /**
     * returns the applied language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     */
    public function getAppliedLang() : string|null
    {
        return $this->appliedLang;
    }

    /**
     * returns the cache path
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     */
    public function getCachePath() : string
    {
        return $this->cachePath;
    }

    /**
     * returns the fallback language
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     */
    public function getFallbackLang() : string
    {
        return $this->fallbackLang;
    }

    /**
     * sets the path of the lanuage files
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
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
     * @copyright       David Lienhard
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
     * @copyright       David Lienhard
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
     * @copyright       David Lienhard
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
     * @copyright       David Lienhard
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
     * @copyright       David Lienhard
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
     * @copyright       David Lienhard
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
     * sets the namespace for the class
     * null means no namespace
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string|null $namespace              namespace to set
     * @uses            self::$namespace
     */
    public function setNamespace(string|null $namespace) : void
    {
        $this->fail_after_init();
        $this->namespace = $namespace;
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
     * @copyright       David Lienhard
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
     * @copyright       David Lienhard
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
     * @copyright       David Lienhard
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
                $config = \parse_ini_string($this->getFileContents($filename), true);
                break;
            case "yml":
            case "yaml":
                $config = Yaml::parse($this->getFileContents($filename)) ;
                break;
            case "json":
                $config = \json_decode($this->getFileContents($filename), true);
                break;
            default:
                throw new \InvalidArgumentException(
                    $extension." is not a valid extension!"
                );
        }//end switch

        if (!is_array($config)) {
            throw new \Exception("unable to parse language files");
        }

        return $config;
    }

    /**
     * recursively compiles an associative array to PHP code.
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
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
     * @copyright       David Lienhard
     * @throws          \BadMethodCallException if the class is already initalized
     * @uses            self::$isInitialized
     */
    protected function fail_after_init(): void
    {
        if ($this->isInitialized()) {
            throw new \BadMethodCallException("This ".__CLASS__." object is already initalized, so you can not change any settings.");
        }
    }

    /**
     * checks whether the cache file is valid or outdated
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     */
    protected function isOutdated() : bool
    {
        $cacheFilePath = $this->cacheFilePath ?? "";
        $langFilePath = $this->langFilePath ?? "";

        return !$this->cacheFilesystem->fileExists($cacheFilePath)
            || $this->cacheFilesystem->lastModified($cacheFilePath) < $this->sourceFilesystem->lastModified($langFilePath) // the language config was updated
            || ($this->mergeFallback && $this->cacheFilesystem->lastModified($cacheFilePath) < $this->sourceFilesystem->lastModified($this->getConfigFilename($this->fallbackLang))); // the fallback language config was updated
    }

    /**
     * creates the contents of the cache file
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           array           $config         configuration to use to vreate the file
     */
    protected function createCacheFile(array $config) : string
    {
        return
            "<?php declare(strict_types=1);\n\n".
            ($this->namespace !== null ? "namespace ".$this->namespace.";\n\n" : "").
            "use \\DavidLienhard\\i18n\\i18nCacheInterface;\n\n".
            "class ".$this->prefix." implements i18nCacheInterface\n".
            "{\n".
            $this->compile($config)."\n".
            "    /**\n".
            "     * calls the static properties set in the class\n".
            "     *\n".
            "     * @param           string          \$string         name of the property to call\n".
            "     * @param           array|null      \$args           arguments for translation\n".
            "     */\n".
            "    public static function __callStatic(string \$string, array|null \$args) : mixed\n".
            "    {\n".
            "        return \\vsprintf(\\constant(\"self::\".\$string), \$args);\n".
            "    }\n\n".
            "    /**\n".
            "     * used to get properties set in the class\n".
            "     *\n".
            "     * @param           string          \$string         name of the property to call\n".
            "     * @param           array|null      \$args           arguments for translation\n".
            "     */\n".
            "    public static function get(string \$string, array|null \$args = null) : mixed\n".
            "    {\n".
            "        \$return = \\constant(\"self::\".\$string);\n".
            "        return \$args ? \\vsprintf(\$return, \$args) : \$return;\n".
            "    }\n".
            "}\n\n".
            "/**\n".
            " * used to get properties set in the class\n".
            " *\n".
            " * @deprecated\n".
            " * @param           string          \$string         name of the property to call\n".
            " * @param           array|null      \$args           arguments for translation\n".
            " */\n".
            "function ".$this->prefix."(string \$string, array|null \$args = null) : mixed\n".
            "{\n".
            "    \\trigger_error(\"this function is deprecated. use '".$this->prefix."::get()' instead\", E_USER_DEPRECATED);\n".
            "    \$return = \\constant(\"".$this->prefix."::\".\$string);\n".
            "    return \$args ? \\vsprintf(\$return, \$args) : \$return;\n".
            "}";
    }

    /**
     * get language to apply
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     */
    protected function getAppliedLanguage() : void
    {
        $this->appliedLang = null;
        foreach ($this->userLangs as $priority => $langcode) {
            $langFilePath = $this->getConfigFilename($langcode);
            if ($this->sourceFilesystem->fileExists($langFilePath)) {
                $this->langFilePath = $langFilePath;
                $this->appliedLang = $langcode;
                break;
            }
        }
    }

    /**
     * returns the content of the given file
     *
     * @author          David Lienhard <david.lienhard@tourasia.ch>
     * @copyright       David Lienhard
     * @param           string          $filename       path to the file to read
     */
    protected function getFileContents(string $filename) : string
    {
        try {
            return $this->sourceFilesystem->read($filename);
        } catch (FilesystemException | UnableToReadFile $e) {
            throw new \Exception(
                "unable to read language file '".$filename."'",
                intval($e->getCode()),
                $e
            );
        }
    }
}
