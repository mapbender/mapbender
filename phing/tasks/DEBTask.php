<?php

require_once 'phing/Task.php';

/**
 *  a task that creates DEBs from Symfony projects
 */

class DEBTask extends Task {

    private $buildroot;

    public function setBuildroot ($buildroot){
        $this->buildroot = $buildroot;
    }

    public function init(){

    }
    
    public function main(){

        // copy tarball to tmpdir
        // extract tarball to tmpdir
        // copy template debian/ (create initial with cd <extracted>; dh_make )
        // debian/install can be used to install files
        // run dpkg-buildpackage
        // TODO: does it make sens to have a "phing install" task that installs into /usr/local ? The problem seems to be
        // that dpkg-buildpackage thinks that the presence of build.xml signifies the ant buildsystem
        $command = sprintf("debuild -us -uc");
        
        $return = 0;
        $output = array();
        $olddir = getcwd();
        chdir($this->buildroot);
        exec($command,$output,$return);
        chdir($olddir);
        if($return != 0){
            throw new Exception("Building Deb failed:\n". implode("\n",$output));
        }
        return true;
    }
}
