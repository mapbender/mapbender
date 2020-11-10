<?php

namespace Mapbender\Component\DoctrineMigrationsHelper;

use Doctrine\DBAL\Migrations\Finder\AbstractFinder;
use Doctrine\DBAL\Migrations\Finder\MigrationDeepFinderInterface;

/**
 * Class MigrationFinder
 * Extends Doctrine\DBAL\Migrations\Finder\RecursiveRegexFinder
 *
 * @package Mapbender\Component
 */
class MigrationFinder extends AbstractFinder implements MigrationDeepFinderInterface
{

    /**
     * {@inheritdoc}
     */
    public function findMigrations($directory, $namespace = null)
    {
        $dir = $this->getRealPath($directory);

        return $this->loadMigrations($this->getMatches($this->createIterator($dir)), $namespace);
    }

    /**
     * Create a recursive iterator to find all the migrations in the subdirectories.
     * @param $dir
     * @return \RegexIterator
     */
    private function createIterator($dir)
    {
        return new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ),
            $this->getPattern(),
            \RegexIterator::GET_MATCH
        );
    }

    private function getPattern()
    {
        return sprintf('#^.+\\%sVersion[^\\%s]{1,255}\\.php$#i', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
    }

    /**
     * Transform the recursiveIterator result array of array into the expected array of migration file
     * @param $iteratorFilesMatch
     * @return array
     */
    private function getMatches($iteratorFilesMatch)
    {
        $files = [];
        foreach ($iteratorFilesMatch as $file) {
            $files[] = $file[0];
        }

        return $files;
    }
}
