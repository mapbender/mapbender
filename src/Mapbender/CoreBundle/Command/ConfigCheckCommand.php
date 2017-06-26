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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigCheckCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setDescription('Check Mapbender requirements')
            ->setHelp("The <info>mapbender:config:check</info> checks Mapbender requirements")
            ->setName('mapbender:config:check');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io=new SymfonyStyle($input,$output);
        $this->checkDatabaseConnection($io);
        $this->checkPhpVersion($io);
        $this->checkSystemRequirements($io);
        $this->checkAssets($io);
        $this->checkFastCGI($io);
        $this->checkModRewrite($io);
        $this->checkPhpIni($io);
        $this->getLoadedPhpExtensions($io);
        $this->checkPermissions($io);
    }

    protected function checkDatabaseConnection(SymfonyStyle $output){
        /** @var  Registry $doctrine */
        /** @var  Connection $connection*/
        $output->title("Check Database connections");
        $headers = ['Connection','Status','Message'];
        $rows=[];
        $success=true;
        $doctrine=$this->getContainer()->get('doctrine');
        $connections=$doctrine->getConnections();
        foreach ($connections AS $connection){
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

    protected function checkPhpVersion(SymfonyStyle $output = null)
    {
        $output->title("Check PHP Version");
        $headers = ['Your Version','Required Version','Message'];
        $rows=[];
        //check PHP-Version >= 5.5.4
        $success = true;
        if (version_compare(phpversion(), '5.5.4', '<')) {
            $success = false;
            $rows[]=[phpversion(),'5.5.4','<fg=red>Too low</>'];
        } else {
            $rows[]=[phpversion(),'5.5.4','<fg=green>ok</>'];
        }
        $output->table($headers,$rows);
        return $success;
    }

    /**
     * @param OutputInterface $output
     * @return bool
     * @todo  APACHE mod_rewrite;
     */
    protected function checkSystemRequirements(SymfonyStyle $output = null){
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
    protected function checkPermissions(SymfonyStyle $output = null){
        $output->title("Check Permissions");
        $headers = ['Folder', 'User', 'Group','Permissions'];
        $rows=[];
        $success = true;
        $folders= array('app/logs/','app/cache/','web/uploads/','web/xmlschemas/','web/');
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        foreach ($folders as $folder){
            $filename = $rootDir.'/../'.$folder;
            $permission= substr(sprintf('%o',fileperms($filename)), -4);
            $stat = stat($filename);
            $ownername=posix_getpwuid($stat['uid'])['name'];
            $grpname=posix_getpwuid($stat['gid'])['name'];
            $rows[]=[$folder,$ownername,$grpname,$permission];
        }
        $output->table($headers,$rows);
        return $success;
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    protected function checkAssets(SymfonyStyle $output = null){
        $output->title("Check Asset Folders");
        $headers = ['Folder','is Symlink?'];
        $rows=[];
        $success = true;
        $ignoreFolders= array('.','..','.gitignore','.gitkeep');
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $webDirs=scandir($rootDir.'/../web/bundles');
        foreach ($webDirs as $webDir){
            if(!in_array($webDir,$ignoreFolders)){
                if(is_link($rootDir.'/../web/bundles/'.$webDir)){
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
    protected function checkFastCGI(SymfonyStyle $output = null){
        $output->title('Check FastCGI');
        $matches=array();
        $outputCmd='';
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
    protected function checkModRewrite(SymfonyStyle $output = null){
        $output->title('Check Apache mod_rewrite');
        $matches=array();
        $outputCmd='';
        if($this->isLinux()){
            $outputCmd=shell_exec('a2query -m rewrite');
            if(isset($outputCmd)){
                $output->writeln($outputCmd);
            }else{
                $output->writeln('mod_rewrite not Found');
            }
        }
    }

    protected function checkPhpIni(SymfonyStyle $output = null){
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

    protected function getLoadedPhpExtensions(SymfonyStyle $output = null){
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

