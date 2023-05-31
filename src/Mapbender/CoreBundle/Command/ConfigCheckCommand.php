<?php

namespace Mapbender\CoreBundle\Command;

/**
 * This Command Checks the Requirements for the Server
 *
 * Step 1 (checkDatabaseConnection):
 * Check all configurated Database connections
 *
 * Step 2 (checkPhpVersion):
 * check the Servers PHP version
 *
 * Step 3(checkSystemRequirements):
 * checks if 'sqlite3','curl','gd','intl','mbstring','fileinfo','openssl' and 'bz2' are loaded
 * and if using php version 7 or greater check also if 'zip' and 'xml' are loaded
 *
 * Step 4 (checkAssets):
 * Lists all Assetfolders and indicate whether they are symlinks or not.
 *
 * Step 5 (checkFastCGI):
 * checks if FastCGI is enabled
 *
 * Step 6 (checkModRewrite):
 * checks if ModRewrite is enabled
 *
 * Step 7 (checkPhpIni):
 * Shows the Configurated parameter for 'date.timezeone','max_input_vars','MaxRequestLen','max_execution_time','memory_limit','upload_max_filesize',
 * 'oci8.max_persistent','oci8.default_prefetch','session.save_handler','zend_extension','opcache.enable','opcache.memory_consumption',
 * 'opcache.interned_strings_buffer','opcache.max_accelerated_files' and 'opcache.max_wasted_percentage'.
 *
 * Step 8 (getLoadedPhpExtensions):
 * List all Loaded PHP extensions
 *
 * Step 9 (checkPermissions):
 * Displays the Permission Owner and Group for 'app/logs/','app/cache/','web/uploads/','web/xmlschemas/' and 'web/' Directory
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigCheckCommand extends Command
{
    /** @var ManagerRegistry */
    protected $managerRegistry;
    protected $rootDir;

    public function __construct(ManagerRegistry $managerRegistry,
                                $rootDir)
    {
        $this->managerRegistry = $managerRegistry;
        $this->rootDir = $rootDir;
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setDescription('Check Mapbender requirements')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io=new SymfonyStyle($input,$output);
        $this->checkDatabaseConnection($io);
        $this->checkSystemRequirements($io);
        $this->checkAssets($io);
        $this->checkFastCGI($io);
        $this->checkModRewrite($io);
        $this->checkPhpIni($io);
        $this->getLoadedPhpExtensions($io);
        $this->checkPermissions($io);
    }

    protected function checkDatabaseConnection(SymfonyStyle $output){
        $output->title("Check Database connections");
        $headers = ['Connection','Status','Message'];
        $rows=[];
        $success=true;
        $connections = $this->managerRegistry->getConnections();
        foreach ($connections AS $connection){
            /** @var Connection $connection*/
            try {
                $connection->isConnected();
                $connection->connect();
                $rows[]=[$connection->getDatabase(),'successfull','<fg=green>ok</>'];
            } catch (ConnectionException $e) {
                $success = false;
                $rows[]=[$connection->getDatabase(),'Error','<fg=red>'.$e->getMessage().'</>'];
            }
        }
        $output->table($headers,$rows);
        return $success;
    }

    /**
     * @param SymfonyStyle $output
     * @return bool
     * @todo  APACHE mod_rewrite;
     */
    protected function checkSystemRequirements(SymfonyStyle $output)
    {
        $output->title("Check System Requirements");
        $headers = ['Extension name','Is loaded?','Message'];
        $rows=[];
        $success = true;
        $requiredExtensions = array('sqlite3','curl','gd','intl','mbstring','fileinfo','openssl','bz2');
        $requiredExtensionsPhp7=array('zip','xml');
        foreach ($requiredExtensions as $requiredExtension){
            if(extension_loaded($requiredExtension)){
                $rows[]=[$requiredExtension,'yes','<fg=green>ok</>'];

            }else{
                $rows[]=[$requiredExtension,'no','<fg=red>Extension is required</>'];
                $success = false;
            }
        }
        if (version_compare(phpversion(), '7.0.0', '>=')) {
            foreach ($requiredExtensionsPhp7 as $requiredExtension){
                if(extension_loaded($requiredExtension)){
                    $rows[]=[$requiredExtension,'yes','<fg=green>ok</>'];
                }else{
                    $rows[]=[$requiredExtension,'no','<fg=red>Extension is required for PHP 7</>'];
                    $success = false;
                }

            }
        }
        $output->table($headers,$rows);
        return $success;
    }

    /**
     * @todo: maybe this only works on Linux
     */
    protected function checkPermissions(SymfonyStyle $output)
    {
        $output->title("Check Permissions");
        $headers = ['Folder', 'User', 'Group','Permissions'];
        $rows=[];
        $success = true;

        $folders= array('app/logs/','app/cache/','web/uploads/','web/xmlschemas/','web/');
        foreach ($folders as $folder){
            $filename = $this->rootDir . '/../' . $folder;
            $info = new \SplFileInfo($filename);
            $permission= substr(sprintf('%o', $info->getPerms()), -4);
            $owner = $info->getOwner();
            $group = $info->getGroup();
            if (function_exists('\posix_getpwuid')) {
                $ownerInfo = posix_getpwuid($owner);
                $groupInfo = posix_getpwuid($group);
                $owner = $ownerInfo ? $ownerInfo['name'] : $owner;
                $group = $groupInfo ? $groupInfo['name'] : $group;
            }
            $rows[] = [$folder, $owner, $group, $permission];
        }
        $output->table($headers,$rows);
        return $success;
    }

    /**
     * @param SymfonyStyle $output
     * @return bool
     */
    protected function checkAssets(SymfonyStyle $output)
    {
        $output->title("Check Asset Folders");
        $headers = ['Folder','is Symlink?'];
        $rows=[];
        $success = true;
        $ignoreFolders= array('.','..','.gitignore','.gitkeep');
        $webDirs = scandir($this->rootDir.'/../web/bundles');
        foreach ($webDirs as $webDir){
            if(!in_array($webDir,$ignoreFolders)){
                if (is_link($this->rootDir.'/../web/bundles/'.$webDir)) {
                    $rows[]=[$webDir,'yes'];
                }else{
                    $rows[]=[$webDir,'no'];
                }
            }
        }
        $output->table($headers,$rows);
        return $success;
    }

    /**
     * @todo: this only works on Linux
     *
     */
    protected function checkFastCGI(SymfonyStyle $output)
    {
        $output->title('Check FastCGI');
        if($this->isLinux()){
            $outputCmd=shell_exec('a2query -m| grep fcgi');
            if(isset($outputCmd)){
                $output->writeln($outputCmd);
            }else{
                $output->writeln('FastCGI not Found');
            }
        }
    }

    /**
     * @todo: this only works on Linux
     *
     */
    protected function checkModRewrite(SymfonyStyle $output)
    {
        $output->title('Check Apache mod_rewrite');
        if($this->isLinux()){
            $outputCmd=shell_exec('a2query -m rewrite');
            if(isset($outputCmd)){
                $output->writeln($outputCmd);
            }else{
                $output->writeln('mod_rewrite not Found');
            }
        }
    }

    protected function checkPhpIni(SymfonyStyle $output)
    {
        $output->title("Check PHP ini");
        $headers = ['Parameter','Value'];
        $rows=[];
        $checks=array('date.timezeone','max_input_vars','MaxRequestLen','max_execution_time','memory_limit','upload_max_filesize',
            'oci8.max_persistent','oci8.default_prefetch','session.save_handler','zend_extension','opcache.enable','opcache.memory_consumption',
            'opcache.interned_strings_buffer','opcache.max_accelerated_files','opcache.max_wasted_percentage',);
        foreach ($checks as $check){

            $rows[]=[$check,ini_get($check)];
        }
        $output->table($headers,$rows);
    }

    protected function getLoadedPhpExtensions(SymfonyStyle $output)
    {
        $output->title("Loaded PHP Extensions");
        $loadedExtensions= get_loaded_extensions ();
        if(count($loadedExtensions)!=0){
            $output->listing($loadedExtensions);
        }else{
            $output->writeln('No Extension Loaded');
        }

    }

    protected function isLinux(){
        if(PHP_OS =="Linux"){
            return true;
        }
        return false;
    }

}

