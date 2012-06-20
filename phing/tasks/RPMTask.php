<?php

require_once 'phing/Task.php';

/**
 *  a task that creates RPMSs from Symfony projects
 */

class RPMTask extends Task {

    private $specfile;
    private $topdir ;

    public function __construct(){
        $topdir = '${PWD}/packaging/opensuse-rpm/';
        
    }

    public function setSpecfile ($specfile){
        $this->specfile = $specfile;
    }

    public function setTopdir($topdir){
        $this->topdir = $topdir;
    }

    public function init(){

    }
    
    public function main(){

        $rpmbuld_bin = "rpmbuild";
        # the --define option seems to be undocumented. works though
        $rpmbuild_opts = sprintf('-bb --sign --define "_topdir %s"',$this->topdir);
        $command = sprintf("%s %s %s",$rpmbuld_bin, $rpmbuild_opts, $this->specfile);
        
        $return = 0;
        $output = array();
        exec($command,$output,$return);
        if($return != 0){
            throw new Exception("Building RPM failed:\n". implode("\n",$output));
        }
        return true;
    }

}
