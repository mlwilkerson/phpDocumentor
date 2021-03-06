<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace phpDocumentor\Configuration;

use League\Uri\Contracts\UriInterface;
use phpDocumentor\Dsn;
use phpDocumentor\Path;
use Webmozart\Assert\Assert;
use function array_map;
use function array_unique;
use function count;
use function current;
use function end;
use function explode;
use function implode;

final class CommandlineOptionsMiddleware implements MiddlewareInterface
{
    /** @var array<string|string[]> */
    private $options;

    /** @var ConfigurationFactory */
    private $configFactory;

    /** @var Dsn */
    private $currentWorkingDir;

    /**
     * @param array<string|string[]> $options
     */
    public function __construct(array $options, ConfigurationFactory $configFactory, string $currentWorkingDir)
    {
        $this->options = $options;
        $this->configFactory = $configFactory;
        $this->currentWorkingDir = Dsn::createFromString($currentWorkingDir);
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string, cache: string}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}} $configuration
     *
     * @return array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string|Path}, versions: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    public function __invoke(array $configuration, ?UriInterface $uri = null) : array
    {
        $configuration = $this->overwriteDestinationFolder($configuration);
        $configuration = $this->disableCache($configuration);
        $configuration = $this->overwriteCacheFolder($configuration);
        $configuration = $this->overwriteTitle($configuration);
        $configuration = $this->overwriteTemplates($configuration);
        $configuration = $this->overwriteSettings($configuration);

        if (!isset($configuration['phpdocumentor']['versions'])) {
            $configuration['phpdocumentor']['versions'][] = $this->createDefaultVersionSettings();
        }

        if ($this->shouldReduceNumberOfVersionsToOne($configuration)) {
            $configuration['phpdocumentor']['versions'] = [
                end($configuration['phpdocumentor']['versions']),
            ];
        }

        foreach ($configuration['phpdocumentor']['versions'] as &$version) {
            $version = $this->setDirectoriesInPath($version);
            $version = $this->setFilesInPath($version);
            $version = $this->registerExtensions($version);
            $version = $this->overwriteIgnoredPaths($version);
            $version = $this->overwriteIgnoredTags($version);
            $version = $this->overwriteMarkers($version);
            $version = $this->overwriteIncludeSource($version);
            $version = $this->overwriteVisibility($version);
            $version = $this->overwriteExamples($version);
            $version = $this->overwriteEncoding($version);
            $version = $this->overwriteDefaultPackageName($version);
        }

        return $configuration;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string, cache: string}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}} $configuration
     *
     * @return array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteDestinationFolder(array $configuration) : array
    {
        if (isset($this->options['target']) && $this->options['target']) {
            $configuration['phpdocumentor']['paths']['output'] = Dsn::createFromString($this->options['target'])
                ->resolve($this->currentWorkingDir);
        }

        return $configuration;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * Changes the given configuration array so that the cache handling is disabled.
     *
     * @param array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}} $configuration
     *
     * @return array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function disableCache(array $configuration) : array
    {
        if (isset($this->options['force']) && $this->options['force']) {
            $configuration['phpdocumentor']['use-cache'] = false;
        }

        return $configuration;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}} $configuration
     *
     * @return array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string|Path}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteCacheFolder(array $configuration) : array
    {
        if (isset($this->options['cache-folder']) && $this->options['cache-folder']) {
            $configuration['phpdocumentor']['paths']['cache'] = new Path($this->options['cache-folder']);
        }

        return $configuration;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string|Path}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}} $configuration
     *
     * @return array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string|Path}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteTitle(array $configuration) : array
    {
        if (isset($this->options['title']) && $this->options['title']) {
            $configuration['phpdocumentor']['title'] = $this->options['title'];
        }

        return $configuration;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * Changes the given configuration array to feature the templates from the options.
     *
     * @param array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string|Path}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}} $configuration
     *
     * @return array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string|Path}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteTemplates(array $configuration) : array
    {
        if (isset($this->options['template']) && $this->options['template']) {
            $configuration['phpdocumentor']['templates'] = array_map(
                static function ($templateName) {
                    return ['name' => $templateName];
                },
                (array) $this->options['template']
            );
        }

        return $configuration;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function setFilesInPath(array $version) : array
    {
        $filename = $this->options['filename'] ?? null;
        if (!$filename) {
            return $version;
        }

        $filename = explode(',', implode(',', $filename));

        if (!isset($version['api'])) {
            $version['api'] = $this->createDefaultApiSettings();
        }

        $version['api'][0]['source']['paths'] = array_map(
            static function ($path) {
                return new Path($path);
            },
            $filename
        );

        return $version;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function setDirectoriesInPath(array $version) : array
    {
        /** @var string|string[]|null $directory */
        $directory = $this->options['directory'] ?? '';
        if (!$directory) {
            return $version;
        }

        // this will ensure that if we receive an array with comma-separated values; that we make a single array with
        // all values split
        $directory = explode(',', implode(',', $directory));

        $currentApiConfig = current($version['api'] ?? []);
        if (!$currentApiConfig) {
            $currentApiConfig = current($this->createDefaultApiSettings());
        }

        // Reset the current config, because directory is overwriting the config.
        $currentApiConfig['source']['paths'] = [];

        $version['api'] = [];
        foreach ($directory as $path) {
            // If the passed directory is an absolute path this should be handled as a new Api
            // A version may contain multiple APIs.
            if (Path::isAbsolutePath($path)) {
                $apiConfig = $currentApiConfig;
                $apiConfig['source']['dsn'] = Dsn::createFromString($path);
                $apiConfig['source']['paths'] = [new Path('./')];

                $version['api'][] = $apiConfig;
            } else {
                $currentApiConfig['source']['paths'][] = new Path($path);
            }
        }

        if (count($currentApiConfig['source']['paths']) > 0) {
            $version['api'][] = $currentApiConfig;
        }

        return $version;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function registerExtensions(array $version) : array
    {
        if (!isset($this->options['extensions']) || !$this->options['extensions']) {
            return $version;
        }

        if (!isset($version['api'])) {
            $version['api'] = $this->createDefaultApiSettings();
        }

        Assert::isArray($this->options['extensions']);

        $version['api'][0]['extensions'] = $this->options['extensions'];

        return $version;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteIgnoredPaths(array $version) : array
    {
        if (!isset($this->options['ignore']) || !$this->options['ignore']) {
            return $version;
        }

        if (!isset($version['api'])) {
            $version['api'] = $this->createDefaultApiSettings();
        }

        $version['api'][0]['ignore']['paths'] = array_map(
            static function ($path) {
                return new Path($path);
            },
            $this->options['ignore']
        );

        return $version;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteIgnoredTags(array $version) : array
    {
        if (!isset($this->options['ignore-tags']) || !$this->options['ignore-tags']) {
            return $version;
        }

        if (!isset($version['api'])) {
            $version['api'] = $this->createDefaultApiSettings();
        }

        Assert::isArray($this->options['ignore-tags']);

        $version['api'][0]['ignore-tags'] = $this->options['ignore-tags'];

        return $version;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteMarkers(array $version) : array
    {
        if (!isset($this->options['markers']) || !$this->options['markers']) {
            return $version;
        }

        if (!isset($version['api'])) {
            $version['api'] = $this->createDefaultApiSettings();
        }

        Assert::isArray($this->options['markers']);

        $version['api'][0]['markers'] = $this->options['markers'];

        return $version;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteIncludeSource(array $version) : array
    {
        if (!isset($this->options['sourcecode'])) {
            return $version;
        }

        if (!isset($version['api'])) {
            $version['api'] = $this->createDefaultApiSettings();
        }

        $version['api'][0]['include-source'] = $this->options['sourcecode'];

        return $version;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteVisibility(array $version) : array
    {
        /** @var string[]|string|null $visibilityFlags */
        $visibilityFlags = $this->options['visibility'] ?? null;
        if (!$visibilityFlags) {
            return $version;
        }

        if (!isset($version['api'])) {
            $version['api'] = $this->createDefaultApiSettings();
        }

        $visibilities = array_unique(explode(',', implode(',', $visibilityFlags)));
        $version['api'][0]['visibility'] = $visibilities;

        return $version;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteDefaultPackageName(array $version) : array
    {
        if (!isset($this->options['defaultpackagename']) || !$this->options['defaultpackagename']) {
            return $version;
        }

        if (!isset($version['api'])) {
            $version['api'] = $this->createDefaultApiSettings();
        }

        $version['api'][0]['default-package-name'] = $this->options['defaultpackagename'];

        return $version;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteExamples(array $version) : array
    {
        /** @var string|null $examples */
        $examples = $this->options['examples-dir'] ?? null;
        if (!$examples) {
            return $version;
        }

        if (!isset($version['api'])) {
            $version['api'] = $this->createDefaultApiSettings();
        }

        $version['api'][0]['examples'] = [
            'dsn' => Dsn::createFromString($examples),
            'paths' => ['./'],
        ];

        return $version;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function createDefaultVersionSettings() : array
    {
        return current($this->configFactory->createDefault()['phpdocumentor']['versions']);
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @return array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function createDefaultApiSettings() : array
    {
        return $this->createDefaultVersionSettings()['api'];
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * If the source path was influenced; we can no longer reliable render multiple versions as such we reduce
     * the list of versions to the last one; assuming that is the most recent / desirable one.
     *
     * @param array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string|Path}, versions: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}} $configuration
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function shouldReduceNumberOfVersionsToOne(array $configuration) : bool
    {
        return (($this->options['filename'] ?? '') !== '' || ($this->options['directory'] ?? '') !== '')
            && count($configuration['phpdocumentor']['versions']) > 1;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string|Path}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}} $configuration
     *
     * @return array{phpdocumentor: array{configVersion: string, title?: string, use-cache?: bool, paths?: array{output: string|Dsn, cache: string|Path}, versions?: array<string, array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}>, settings?: array<mixed>, templates?: non-empty-list<string>}}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteSettings(array $configuration) : array
    {
        if (!($configuration['phpdocumentor']['settings'] ?? null)) {
            $configuration['phpdocumentor']['settings'] = [];
        }

        foreach (($this->options['setting'] ?? []) as $setting) {
            [$key, $value] = explode('=', $setting);

            if (!$key || !$value) {
                continue;
            }

            if ($value === 'on' || $value === 'true') {
                $value = true;
            }

            if ($value === 'off' || $value === 'false') {
                $value = false;
            }

            $configuration['phpdocumentor']['settings'][$key] = $value;
        }

        return $configuration;
    }

    //phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array} $version
     *
     * @return array{api: array<int, array{ignore-tags: array, extensions: non-empty-array<string>, markers: non-empty-array<string>, visibillity: string, source: array{dsn: Dsn, paths: array}, ignore: array{paths: array}}>, apis: array, guides: array}
     */
    //phpcs:enable Generic.Files.LineLength.TooLong
    private function overwriteEncoding(array $version) : array
    {
        /** @var string|null $encoding */
        $encoding = $this->options['encoding'] ?? null;
        if (!$encoding) {
            return $version;
        }

        if (!isset($version['api'])) {
            $version['api'] = $this->createDefaultApiSettings();
        }

        $version['api'][0]['encoding'] = $encoding;

        return $version;
    }
}
