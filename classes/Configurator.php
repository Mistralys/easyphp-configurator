<?php

declare(strict_types=1);

namespace EPConf;

use AppUtils\FileHelper;

class Configurator
{
    const ERROR_EASYPHP_FOLDER_NOT_FOUND = 41701;
    
   /**
    * @var string
    */
    protected $easyPHPFolder;
    
    protected $errors = array();
    
    protected $iniFiles = array();
    
   /**
    * @param string $easyPHPFolder The absolute path to the EasyPHP install folder.
    */
    public function __construct(string $easyPHPFolder)
    {
        $this->easyPHPFolder = $easyPHPFolder;
    }
    
    public function process() : bool
    {
        $this->errors = array();
        
        if(!is_dir($this->easyPHPFolder)) {
            return $this->addError(
                sprintf('The target folder does not exist at [%s].', $this->easyPHPFolder),
                self::ERROR_EASYPHP_FOLDER_NOT_FOUND
            );
        }
        
        $pem = FileHelper::downloadFile('https://curl.haxx.se/ca/cacert.pem');
        FileHelper::saveFile($this->easyPHPFolder.'/cacert.pem', $pem);
        
        $this->log('cacert.pem downloaded and saved.');
        
        $inis = \AppUtils\FileHelper::createFileFinder($this->easyPHPFolder.'/eds-binaries/php')
        ->includeExtensions(array('ini'))
        ->makeRecursive()
        ->getAll();
        
        foreach($inis as $file) 
        {
            $name = basename($file);
            if($name == 'php.ini') {
                $this->iniFiles[] = $file;
            }
        }
        
        foreach($this->iniFiles as $file)
        {
            $this->processFile($file);
        }
        
        return empty($this->errors);
    }
    
    protected function addError(string $message, int $code) : bool
    {
        $this->errors[] = new Configurator_Error(
            $message,
            $code
        );
        
        return false;
    }
    
    protected $extensions = array(
        'php_bz2' => true,
        'php_curl' => true,
        'php_fileinfo' => false,
        'php_gd2' => true,
        'php_gettext' => true,
        'php_gmp' => false,
        'php_intl' => false,
        'php_imap' => false,
        'php_interbase' => false,
        'php_ldap' => false,
        'php_mbstring' => true,
        'php_exif' => true, // Must be after mbstring as it depends on it
        'php_mysqli' => true,
        'php_oci8_12c' => false,
        'odbc' => false,
        'php_openssl' => true,
        'php_pdo_firebird' => false,
        'php_pdo_mysql' => true,
        'php_pdo_oci' => false,
        'php_pdo_odbc' => false,
        'php_pdo_pgsql' => false,
        'php_pdo_sqlite' => true,
        'php_pgsql' => false,
        'php_shmop' => false,
        'php_snmp' => false,
        'php_soap' => false,
        'php_sockets' => true,
        'php_sqlite3' => true,
        'php_tidy' => false,
        'php_xmlrpc' => false,
        'php_xsl' => true,
    );
    
    protected function processFile(string $file)
    {
        $parser = \AppUtils\IniHelper::createFromFile($file);
        
        // save a copy of the original if this is the first time
        // we edit it.
        if(!$parser->sectionExists('EPConf')) {
            copy($file, $file.'.epconf.orig');
        }
            
        $parser->setValue('EPConf/rewritten', 1);
        $parser->setValue('PHP/max_execution_time', 90);
        $parser->setValue('PHP/memory_limit', '600M');
        $parser->setValue('PHP/upload_max_filesize', '200M');
        $parser->setValue('PHP/post_max_size', '200M');
        $parser->setValue('PHP/extension', $this->getExtensions());
        $parser->setValue('curl/curl.cainfo', $this->easyPHPFolder.'\cacert.pem');
        $parser->setValue('openssl/openssl.cafile', $this->easyPHPFolder.'\cacert.pem');
        
        $parser->saveToFile($file);
        
        $path = dirname($file);
        $name = basename($path);
        
        $this->log('php.ini updated in '.$name);
    }
    
    protected function log(string $message)
    {
        echo $message.PHP_EOL;
    }
    
    protected function getExtensions()
    {
        $keep = array();
        
        foreach($this->extensions as $name => $enabled)
        {
            if($enabled) {
                $keep[] = $name; 
            }
        }
        
        return $keep;
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
}
