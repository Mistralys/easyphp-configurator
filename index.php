<?php

    $root = __DIR__;
    
    $autoload = realpath($root.'/vendor/autoload.php');
    
    // we need the autoloader to be present
    if(!file_exists($autoload)) {
        die('<b>ERROR:</b> Autoloader not present. Run composer update first.');
    }
    
    /**
     * The composer autoloader
     */
    require_once $autoload;

    $confFile = 'config.php';
    $confPath = realpath($root.'/config.php');
    
    if($confPath === false) {
        die(sprintf('<b>ERROR:</b> Configuration file [%s] not found.', $confFile));
    }
    
    require_once $confPath;

    header('Content-Type:text/plain: encoding=UTF-8');
    
    $conf = new EPConf\Configurator(APP_EASYPHP_PATH);

    if($conf->process() === true) 
    {
        echo PHP_EOL;
        echo 'SUCCESS: All PHP ini files adjusted successfully.';
    }
    
    $errors = $conf->getErrors();
    
    if(!empty($errors)) 
    {
        echo '<pre style="background:#fff;font-family:monospace;font-size:14px;color:#444;padding:16px;border:solid 1px #999;border-radius:4px;">';
        print_r($errors);
        echo '</pre>';
    }
