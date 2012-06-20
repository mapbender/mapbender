<?php

require_once 'phing/Task.php';

/**
 *  a task that creates RPMSs from Symfony projects
 */

class RPMTask extends Task {

    private $specfile;
    private $topdir ;
    private $sign;

    public function __construct(){
        $this->topdir = '${PWD}/packaging/opensuse-rpm/';
        $this->sign = false;
        
    }

    public function setSpecfile ($specfile){
        $this->specfile = $specfile;
    }

    public function setTopdir($topdir){
        $this->topdir = $topdir;
    }
    
    public function setSign($sign){
        if (strtolower($sign) == "yes" || $sign == "1" || strtolower($sign) == "true"){
            $this->sign = true;
        }
    }

    public function init(){

    }
    
    public function main(){

        $rpmbuld_bin = "rpmbuild";
        $singopt == "";
        if($this->sign){
            $signopt = "--sign";
        }
        # the --define option seems to be undocumented. works though
        $rpmbuild_opts = sprintf('-bb %s --define "_topdir %s"',$signopt, $this->topdir);
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
