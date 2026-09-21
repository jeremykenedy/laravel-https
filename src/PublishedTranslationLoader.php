<?php

namespace jeremykenedy\LaravelHttps;

use Illuminate\Translation\FileLoader;

class PublishedTranslationLoader extends FileLoader
{
    private $loader;

    private $publishedPath;

    private $languagePath;

    public function __construct($loader, $path, $languagePath)
    {
        $this->loader = $loader;
        $this->publishedPath = $path;
        $this->languagePath = $languagePath;
    }

    public function load($locale, $group, $namespace = null)
    {
        $lines = $this->loader->load($locale, $group, $namespace);

        if ($namespace !== 'LaravelHttps' || $group !== 'laravel-https') {
            return $lines;
        }

        $paths = method_exists($this->loader, 'paths') ? $this->loader->paths() : [$this->languagePath];

        foreach ($paths as $path) {
            if (is_file($path.'/vendor/LaravelHttps/'.$locale.'/laravel-https.php')) {
                return $lines;
            }
        }

        $file = $this->publishedPath.'/'.$locale.'/laravel-https.php';

        return is_file($file) ? array_replace_recursive($lines, require $file) : $lines;
    }

    public function addNamespace($namespace, $hint)
    {
        $this->loader->addNamespace($namespace, $hint);
    }

    public function namespaces()
    {
        return $this->loader->namespaces();
    }

    public function addJsonPath($path)
    {
        $this->loader->addJsonPath($path);
    }

    public function addPath($path)
    {
        $this->loader->addPath($path);
    }

    public function paths()
    {
        return $this->loader->paths();
    }

    public function jsonPaths()
    {
        return $this->loader->jsonPaths();
    }
}
