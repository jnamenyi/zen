<?php

declare(strict_types=1);

namespace WoohooLabs\Zen\Container;

use WoohooLabs\Zen\Config\AbstractCompilerConfig;
use WoohooLabs\Zen\Utils\FileSystemUtil;

use function array_merge;
use function array_unique;
use function array_values;

final class PreloadCompiler
{
    /**
     * @param string[] $preloadedClassFiles
     */
    public function compile(AbstractCompilerConfig $compilerConfig, array $preloadedClassFiles): string
    {
        $relativeBasePath = $compilerConfig->getPreloadConfig()->getRelativeBasePath();
        $preloadedFiles = $compilerConfig->getPreloadConfig()->getPreloadedFiles();
        $preloadedClassFiles = array_values($preloadedClassFiles);
        $files = array_unique(array_merge($preloadedFiles, $preloadedClassFiles));

        $preloader = "<?php\n\n";

        foreach ($files as $file) {
            $file = FileSystemUtil::getRelativeFilename($relativeBasePath, $file);

            $preloader .= "opcache_compile_file('$file');\n";
        }

        return $preloader;
    }
}
