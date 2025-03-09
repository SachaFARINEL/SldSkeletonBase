<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SOLEDIS
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SOLEDIS GROUP is strictly forbidden.
 *
 * @author    SOLEDIS <prestashop@groupe-soledis.com>
 * @copyright 2025 SOLEDIS
 * @license   All Rights Reserved
 * @developer FARINEL Sacha
 */
declare(strict_types=1);

namespace Soledis\SldSkeletonCore\Utils;

use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

class NamespaceChanger
{
    private string $oldNamespace;
    private string $newNamespace;
    private Filesystem $filesystem;


    public function __construct(
        private readonly string $vendorPath,
        string                  $oldNamespace,
        string                  $newNamespace
    )
    {
        $this->filesystem = new Filesystem();
        $this->validateDirectory($vendorPath);

        $this->oldNamespace = $this->cleanNamespace($oldNamespace);
        $this->newNamespace = $this->cleanNamespace($newNamespace);
    }

    public function changeNamespaces(): void
    {
        $finder = new Finder();

        $finder->files()
            ->in($this->vendorPath)
            ->name('*.php');

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            if (!$filePath) {
                continue;
            }

            try {
                $this->updateNamespaceInFile($filePath);
            } catch (Throwable $e) {
                dump($e);
            }
        }
    }

    private function validateDirectory(string $path): void
    {
        if (!$this->filesystem->exists($path) || !is_dir($path)) {
            throw new InvalidArgumentException("The path '{$path}' does not exist or is not a valid directory . ");
        }
    }

    private function cleanNamespace(string $namespace): string
    {
        return rtrim($namespace, '\\');
    }

    private function updateNamespaceInFile(string $filePath): void
    {
        $contents = file_get_contents($filePath);
        $updatedContents = $this->replaceNamespace($contents);

        if ($contents !== $updatedContents) {
            $this->filesystem->dumpFile($filePath, $updatedContents);
        }
    }

    private function replaceNamespace(string $contents): string
    {
        $contents = preg_replace(
            "/namespace\s+" . preg_quote($this->oldNamespace, '/') . "(.*);/",
            "namespace {$this->newNamespace}$1;",
            $contents
        );

        return preg_replace(
            "/use\s+" . preg_quote($this->oldNamespace, '/') . "(.*);/",
            "use {$this->newNamespace}$1;",
            $contents
        );
    }
}

