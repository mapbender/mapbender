<?php

require_once 'phing/Task.php';

/**
 *  a task that creates RPMSs from Symfony projects
 */

class NSISTask extends Task {

    private $file;
    private $dir;

    public function setFile ($file){
        $this->file = $file;
    }
    public function setDir ($dir){
        $this->dir = $dir;
    }

    public function init(){

    }
    
    public function main(){

        $nsis_bin = "makensis";
        $command = sprintf("%s %s",$nsis_bin,  $this->file);
        
        $return = 0;
        $output = array();
        $currdir = getcwd();
        if($this->dir){
            chdir($this->dir);
        }
        exec($command,$output,$return);
        chdir($currdir);
        if($return != 0){
            throw new Exception("Building NSIS failed:\n". implode("\n",$output));
        }
        return true;
    }

}
