<?php
namespace Mapbender\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Filesystem\Filesystem;

use Mapbender\Component\SymlinkInstaller\SymlinkInstaller;

/**
 * Class MigrationsInstallCommand
 *
 * @package Mapbender\CoreBundle\Command
 */
class MigrationsInstallCommand extends ContainerAwareCommand
{
    const MIGRATIONS_BUNDLE_NAME = 'DoctrineMigrationsBundle';

    /**
     * @var SymfonyStyle
     */
    private $style;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string Installation Type: copy | absolute symlink | relative symlink
     */
    private $installationMethod;

    /**
     * @var string Path to bundles folder in default application migrations folder
     */
    private $bundlesDir;

    /**
     * @var int Command exit code
     */
    private $exitCode = 0;

    /**
     * @var array Summary table rows
     */
    private $outputTableRows = [];

    /**
     * @var SymlinkInstaller
     */
    private $symlinkInstaller;

    public function __construct(SymlinkInstaller $symlinkInstaller, $name = null)
    {
        parent::__construct($name);

        $this->symlinkInstaller = $symlinkInstaller;
    }

    protected function configure()
    {
        $this
            ->setName('migrations:install')
            ->setDescription('Install migrations from all delivered by Mapbender bundles')
            ->addOption('copy', null, InputOption::VALUE_NONE, 'Copy migrations instead of symlink it')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command installs bundle migrations into your doctrine migrations folder (default is <info>app/DoctrineMigrations</info>). 

The command checks if DoctrineMigrationsBundle is installed and stops if it's missing.

A "bundles" directory will be created inside the target directory and the
"DoctrineMigrations" directory of each bundle will be symlink into it. 
It will fall back to hard copies when symbolic links aren't possible.

To copy migration files to each bundle instead of create a symlink, use the <info>--copy</info> option:
<info>php %command.full_name% --copy</info>

EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->style = new SymfonyStyle($this->input, $this->output);

        try {
            $this->checkDoctrineMigrationsBundleStatus();
            $this->installMigrations();
        } catch (\Exception $exception) {
            $this
                ->style
                ->error($exception->getMessage());
        }

        return $this->exitCode;
    }

    /**
     * Check if DoctrineMigrationsBundle is installed in an application
     *
     * @throws \Exception
     */
    protected function checkDoctrineMigrationsBundleStatus()
    {
        $this
            ->output
            ->writeln('Check if DoctrineMigrationBundle is installed in the application');

        $installedBundles = $this->getContainer()->get('kernel')->getBundles();

        if (!isset($installedBundles[self::MIGRATIONS_BUNDLE_NAME])) {
            throw new \Exception('MigrationsDoctrineBundle is not installed in the application. Please install it and run the command again');
        }
    }

    /**
     * Create symlinks|copies of migrations in all application bundles
     */
    protected function installMigrations()
    {
        $this
            ->output
            ->writeln('Install migrations of bundles into application migrations folder');

        $this
            ->setFilesystem()
            ->createBundlesMigrationsFolder()
            ->setInstallationMethod()
            ->installBundlesMigrations()
            ->outputInstallationSummary();
    }

    /**
     * Set filesystem from filesystem service
     *
     * @return $this
     */
    private function setFilesystem()
    {
        $this->filesystem = $this
            ->getContainer()
            ->get('filesystem');

        return $this;
    }

    /**
     * Create bundles folder in defult doctrine migrations application folder
     *
     * @return $this
     */
    protected function createBundlesMigrationsFolder()
    {
        $targetFolder = $this->getContainer()->getParameter('doctrine_migrations.dir_name');

        if (!is_dir($targetFolder)) {
            $this
                ->filesystem
                ->mkdir($targetFolder, 0777);
        }

        $this->bundlesDir = $targetFolder.'/bundles/';

        $this->filesystem->mkdir($this->bundlesDir, 0777);

        return $this;
    }

    /**
     * Set installation method depends on input data
     *
     * @return $this
     */
    protected function setInstallationMethod()
    {
        if ($this->input->getOption('copy')) {
            $this->installationMethod = SymlinkInstaller::METHOD_COPY;
            $this
                ->style
                ->text('Installing migrations as <info>hard copies</info>.');
        } else {
            $this->installationMethod = SymlinkInstaller::METHOD_RELATIVE_SYMLINK;
            $this
                ->style
                ->text('Trying to install migrations as <info>relative symbolic links</info>.');

        }

        $this->style->newLine();

        return $this;
    }

    /**
     * Crete symlinks|copies of migration files into corresponding folders
     *
     * @return $this
     */
    protected function installBundlesMigrations()
    {
        $validMigrationDirs = [];

        $bundles = $this
            ->getContainer()
            ->get('kernel')
            ->getBundles();

        /** @var BundleInterface $bundle */
        foreach ($bundles as $bundle) {
            $originDir = $bundle->getPath().'/DoctrineMigrations/';

            if (!is_dir($originDir)) {
                continue;
            }

            $migrationDir = preg_replace('/bundle$/', '', strtolower($bundle->getName()));
            $targetDir = $this->bundlesDir . $migrationDir;
            $validMigrationDirs[] = $migrationDir;

            if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
                $rowContent = sprintf("%s\n-> %s", $bundle->getName(), $targetDir);
            } else {
                $rowContent = $bundle->getName();
            }

            try {
                $this->filesystem->remove($targetDir);

                $this
                    ->symlinkInstaller
                    ->setOriginDir($originDir)
                    ->setTargetDir($targetDir)
                    ->installSymlinks($this->installationMethod);

                $this->addSummaryTableRow($rowContent);

            } catch (\Exception $e) {
                $this->exitCode = 1;
                $this->outputTableRows[] = [
                    sprintf('<fg=red;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98"),
                    $rowContent,
                    $e->getMessage()
                ];
            }
        }

        return $this;
    }

    /**
     * Add summary info about bundle migrations installation
     *
     * @param $message
     */
    private function addSummaryTableRow($message)
    {
        $method = $this->symlinkInstaller->getMethodSymlinksAreInstalledBy();

        if ($method === $this->installationMethod) {
            $this->outputTableRows[] = [
                sprintf('<fg=green;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94"),
                $message,
                $method,
            ];
        } else {
            $this->outputTableRows[] = [
                sprintf('<fg=yellow;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'WARNING' : '!'),
                $message,
                $method,
            ];
        }
    }

    /**
     * Show summary of migrations installation
     *
     * @return $this
     */
    protected function outputInstallationSummary()
    {
        $this->style->table(array('', 'Bundle', 'Method / Error'), $this->outputTableRows);

        if (0 !== $this->exitCode) {
            $this->style->error('Some errors occurred while installing migrations.');
        } else {
            $this->style->success('All migrations were successfully installed.');
        }

        return $this;
    }


}