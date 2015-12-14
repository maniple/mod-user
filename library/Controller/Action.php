<?php

class ModUser_Controller_Action extends Zefram_Controller_Action
{
    public function getSecurity()
    {
        return $this->getResource('user.sessionManager');    
    }

    public function getSecurityContext()
    {
        return $this->getSecurity();
    }

    public function getUserManager()
    {
        return $this->getResource('user.userManager');
    }

    public function preDispatch()
    {
        $config = $this->getResource('config');
        $scriptPath = @$config['ModUser']['scriptPath'];

        if (strpos($scriptPath, ':locale') !== false) {
            $scriptPath = str_replace(':locale', $this->getResource('locale'), $scriptPath);
        }

        $this->getResource('view')->addScriptPath($scriptPath);
    }
}