<?php

namespace SureShinde\MobileDesktopDetect;

use BadMethodCallException;
use Detection\Exception\MobileDetectException;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Detection\MobileDetect;

class Agent extends MobileDetect
{
    /**
     * List of desktop devices.
     * @var array
     */
    protected static array $desktopDevices = [
        'Macintosh' => 'Macintosh',
    ];

    /**
     * List of additional operating systems.
     * @var array
     */
    protected static array $additionalOperatingSystems = [
        'Windows' => 'Windows',
        'Windows NT' => 'Windows NT',
        'OS X' => 'Mac OS X',
        'Debian' => 'Debian',
        'Ubuntu' => 'Ubuntu',
        'Macintosh' => 'PPC',
        'OpenBSD' => 'OpenBSD',
        'Linux' => 'Linux',
        'ChromeOS' => 'CrOS',
    ];

    /**
     * List of additional browsers.
     * @var array
     */
    protected static array $additionalBrowsers = [
        'Opera Mini' => 'Opera Mini',
        'Opera' => 'Opera|OPR',
        'Edge' => 'Edge|Edg',
        'Coc Coc' => 'coc_coc_browser',
        'UCBrowser' => 'UCBrowser',
        'Vivaldi' => 'Vivaldi',
        'Chrome' => 'Chrome',
        'Firefox' => 'Firefox',
        'Safari' => 'Safari',
        'IE' => 'MSIE|IEMobile|MSIEMobile|Trident/[.0-9]+',
        'Netscape' => 'Netscape',
        'Mozilla' => 'Mozilla',
        'WeChat'  => 'MicroMessenger',
    ];

    /**
     * List of additional properties.
     * @var array
     */
    protected static array $additionalProperties = [
        // Operating systems
        'Windows' => 'Windows NT [VER]',
        'Windows NT' => 'Windows NT [VER]',
        'OS X' => 'OS X [VER]',
        'BlackBerryOS' => ['BlackBerry[\w]+/[VER]', 'BlackBerry.*Version/[VER]', 'Version/[VER]'],
        'AndroidOS' => 'Android [VER]',
        'ChromeOS' => 'CrOS x86_64 [VER]',

        // Browsers
        'Opera Mini' => 'Opera Mini/[VER]',
        'Opera' => [' OPR/[VER]', 'Opera Mini/[VER]', 'Version/[VER]', 'Opera [VER]'],
        'Netscape' => 'Netscape/[VER]',
        'Mozilla' => 'rv:[VER]',
        'IE' => ['IEMobile/[VER];', 'IEMobile [VER]', 'MSIE [VER];', 'rv:[VER]'],
        'Edge' => ['Edge/[VER]', 'Edg/[VER]'],
        'Vivaldi' => 'Vivaldi/[VER]',
        'Coc Coc' => 'coc_coc_browser/[VER]',
    ];

    /**
     * @var CrawlerDetect
     */
    protected static CrawlerDetect $crawlerDetect;

    const DETECTION_TYPE_MOBILE = 'mobile';
    const DETECTION_TYPE_EXTENDED = 'extended';

    /**
     * Get all detection rules. These rules include the additional
     * platforms and browsers and utilities.
     * @return array
     */
    public static function getDetectionRulesExtended(): array
    {
        static $rules;

        if (!$rules) {
            $rules = static::mergeRules(
                static::$desktopDevices, // NEW
                static::$phoneDevices,
                static::$tabletDevices,
                static::$operatingSystems,
                static::$additionalOperatingSystems, // NEW
                static::$browsers,
                static::$additionalBrowsers
            );
        }

        return $rules;
    }

    public function getRules(): array
    {
        static $rules;

        if (!$rules) {
            $rules = array_merge(
                static::$browsers,
                static::$operatingSystems,
                static::$phoneDevices,
                static::$tabletDevices,
                static::getDetectionRulesExtended()
            );
        }

        return $rules;
    }

    /**
     * @return CrawlerDetect
     */
    public function getCrawlerDetect(): CrawlerDetect
    {
        if (static::$crawlerDetect === null) {
            static::$crawlerDetect = new CrawlerDetect();
        }

        return static::$crawlerDetect;
    }

    public static function getBrowsers(): array
    {
        return static::mergeRules(
            static::$additionalBrowsers,
            static::$browsers
        );
    }

    public static function getOperatingSystems(): array
    {
        return static::mergeRules(
            static::$operatingSystems,
            static::$additionalOperatingSystems
        );
    }

    public static function getPlatforms(): array
    {
        return static::mergeRules(
            static::$operatingSystems,
            static::$additionalOperatingSystems
        );
    }

    public static function getDesktopDevices(): array
    {
        return static::$desktopDevices;
    }

    public static function getProperties(): array
    {
        return static::mergeRules(
            static::$additionalProperties,
            static::$properties
        );
    }

    /**
     * Get accept languages.
     * @param string $acceptLanguage
     * @return array
     */
    public function languages($acceptLanguage = null): array
    {
        if ($acceptLanguage === null) {
            $acceptLanguage = $this->getHttpHeader('HTTP_ACCEPT_LANGUAGE');
        }

        if (!$acceptLanguage) {
            return [];
        }

        $languages = [];

        // Parse accept language string.
        foreach (explode(',', $acceptLanguage) as $piece) {
            $parts = explode(';', $piece);
            $language = strtolower($parts[0]);
            $priority = empty($parts[1]) ? 1. : floatval(str_replace('q=', '', $parts[1]));

            $languages[$language] = $priority;
        }

        // Sort languages by priority.
        arsort($languages);

        return array_keys($languages);
    }

    /**
     * Match a detection rule and return the matched key.
     * @param  array $rules
     * @param  string|null $userAgent
     * @return string|bool
     */
    protected function findDetectionRulesAgainstUA(array $rules, $userAgent = null): bool|string
    {
        // Loop given rules
        foreach ($rules as $key => $regex) {
            if (empty($regex)) {
                continue;
            }

            // Check match
            if ($this->match($regex, $userAgent)) {
                return $key ?: reset($this->matchesArray);
            }
        }

        return false;
    }

    /**
     * Get the browser name.
     * @param string|null $userAgent
     * @return string|bool
     */
    public function browser(string $userAgent = null): bool|string
    {
        return $this->findDetectionRulesAgainstUA(static::getBrowsers(), $userAgent);
    }

    /**
     * Get the platform name.
     * @param string|null $userAgent
     * @return string|bool
     */
    public function platform(string $userAgent = null): bool|string
    {
        return $this->findDetectionRulesAgainstUA(static::getPlatforms(), $userAgent);
    }

    /**
     * Get the device name.
     * @param string|null $userAgent
     * @return string|bool
     */
    public function device(string $userAgent = null): bool|string
    {
        $rules = static::mergeRules(
            static::getDesktopDevices(),
            static::getPhoneDevices(),
            static::getTabletDevices()
        );

        return $this->findDetectionRulesAgainstUA($rules, $userAgent);
    }

    /**
     * Check if the device is a desktop computer.
     * @param string|null $userAgent deprecated
     * @param array $httpHeaders deprecated
     * @return bool
     * @throws MobileDetectException
     */
    public function isDesktop(string $userAgent = null, $httpHeaders = null): bool
    {
        // Check specifically for cloudfront headers if the useragent === 'Amazon CloudFront'
        if ($this->getUserAgent() === 'Amazon CloudFront') {
            if(array_key_exists('HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER', $httpHeaders)) {
                return $httpHeaders['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER'] === 'true';
            }
        }

        return !$this->isMobile($userAgent, $httpHeaders) && !$this->isTablet($userAgent, $httpHeaders) && !$this->isRobot($userAgent);
    }

    /**
     * Check if the device is a mobile phone.
     * @param string|null $userAgent deprecated
     * @param array|null $httpHeaders deprecated
     * @return bool
     * @throws MobileDetectException
     */
    public function isPhone(string $userAgent = null, array $httpHeaders = null): bool
    {
        return $this->isMobile($userAgent, $httpHeaders) && !$this->isTablet($userAgent, $httpHeaders);
    }

    /**
     * Get the robot name.
     * @param string|null $userAgent
     * @return string|bool
     */
    public function robot(string $userAgent = null): bool|string
    {
        if ($this->getCrawlerDetect()->isCrawler($userAgent ?: $this->userAgent)) {
            return ucfirst($this->getCrawlerDetect()->getMatches());
        }

        return false;
    }

    /**
     * Check if device is a robot.
     * @param string|null $userAgent
     * @return bool
     */
    public function isRobot(string $userAgent = null): bool
    {
        return $this->getCrawlerDetect()->isCrawler($userAgent ?: $this->userAgent);
    }

    /**
     * Get the device type
     * @param null $userAgent
     * @param null $httpHeaders
     * @return string
     * @throws MobileDetectException
     */
    public function deviceType($userAgent = null, $httpHeaders = null): string
    {
        if ($this->isDesktop($userAgent, $httpHeaders)) {
            return "desktop";
        } elseif ($this->isPhone($userAgent, $httpHeaders)) {
            return "phone";
        } elseif ($this->isTablet($userAgent, $httpHeaders)) {
            return "tablet";
        } elseif ($this->isRobot($userAgent)) {
            return "robot";
        }

        return "other";
    }

    public function version($propertyName, $type = 'text'): float|bool|string
    {
        if (empty($propertyName)) {
            return false;
        }

        // set the $type to the default if we don't recognize the type
        if ($type !== 'text' && $type !== 'float') {
            $type = 'text';
        }

        $properties = self::getProperties();

        // Check if the property exists in the properties array.
        if (true === isset($properties[$propertyName])) {

            // Prepare the pattern to be matched.
            // Make sure we always deal with an array (string is converted).
            $properties[$propertyName] = (array) $properties[$propertyName];

            foreach ($properties[$propertyName] as $propertyMatchString) {
                if (is_array($propertyMatchString)) {
                    $propertyMatchString = implode("|", $propertyMatchString);
                }

                $propertyPattern = str_replace('[VER]', '([\w._\+]+)', $propertyMatchString);

                // Identify and extract the version.
                preg_match(sprintf('#%s#is', $propertyPattern), $this->userAgent, $match);

                if (false === empty($match[1])) {
                    $version = ($type === 'float' ? $this->prepareVersionNo($match[1]) : $match[1]);

                    return $version;
                }
            }
        }

        return false;
    }

    /**
     * Merge multiple rules into one array.
     * @param array $all
     * @return array
     */
    protected static function mergeRules(...$all): array
    {
        $merged = [];

        foreach ($all as $rules) {
            foreach ($rules as $key => $value) {
                if (empty($merged[$key])) {
                    $merged[$key] = $value;
                } elseif (is_array($merged[$key])) {
                    $merged[$key][] = $value;
                } else {
                    $merged[$key] .= '|' . $value;
                }
            }
        }

        return $merged;
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $arguments)
    {
        // Make sure the name starts with 'is', otherwise
        if (!str_starts_with($name, 'is')) {
            throw new BadMethodCallException("No such method exists: $name");
        }

        $key = substr($name, 2);

        return $this->matchUAAgainstKey($key);
    }

    /**
     * Search for a certain key in the rules array.
     * If the key is found the try to match the corresponding
     * regex against the User-Agent.
     *
     * @param string $key
     * @param null $userAgent deprecated
     * @return bool
     */
    private function matchUAAgainstKey($key, $userAgent = null): bool
    {
        // Make the keys lowercase, so we can match: isIphone(), isiPhone(), isiphone(), etc.
        $key = strtolower($key);

        //change the keys to lower case
        $_rules = array_change_key_case($this->getRules());

        if (array_key_exists($key, $_rules)) {
            if (empty($_rules[$key])) {
                return false;
            }

            return $this->match($_rules[$key], $userAgent);
        }

        return false;
    }
}
