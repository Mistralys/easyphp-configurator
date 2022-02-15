<?php

declare(strict_types=1);

namespace EPConf;

use AppUtils\FileHelper;
use AppUtils\IniHelper;

class Configurator
{
    public const ERROR_EASYPHP_FOLDER_NOT_FOUND = 41701;
    
   /**
    * @var string
    */
    protected string $easyPHPFolder;

    /**
     * @var Configurator_Error[]
     */
    protected array $errors = array();

    /**
     * @var array<string,float>
     */
    protected array $iniFiles = array();
    
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

        $this->downloadCertificateFile();
        $this->detectINIFiles();
        
        foreach($this->iniFiles as $file => $version)
        {
            $this->processFile($file, $version);
        }
        
        return empty($this->errors);
    }

    private function detectINIFiles() : void
    {
        $inis = FileHelper::createFileFinder($this->easyPHPFolder.'/eds-binaries/php')
            ->includeExtensions(array('ini'))
            ->makeRecursive()
            ->getAll();

        foreach($inis as $file)
        {
            $this->initINIFile($file);
        }
    }

    private function initINIFile(string $file) : void
    {
        $name = basename($file);

        if($name !== 'php.ini')
        {
            return;
        }

        $version = $this->detectVersion(dirname($file));

        $this->iniFiles[$file] = $version;
    }

    private function detectVersion(string $dir) : float
    {
        $snapshotFile = $dir.'/snapshot.txt';

        $lines = FileHelper::readLines($snapshotFile);

        foreach($lines as $line)
        {
            if(strpos($line, 'Version:') === 0)
            {
                return (float)trim(str_replace('Version:', '', $line));
            }
        }

        die('No version detected in ['.$snapshotFile.'].');
    }

    private function downloadCertificateFile() : void
    {
        $pem = FileHelper::downloadFile('https://curl.haxx.se/ca/cacert.pem');
        FileHelper::saveFile($this->easyPHPFolder.'/cacert.pem', $pem);

        $this->log('cacert.pem downloaded and saved.');
    }
    
    protected function addError(string $message, int $code) : bool
    {
        $this->errors[] = new Configurator_Error(
            $message,
            $code
        );
        
        return false;
    }

    /**
     * @var array<string,bool>
     */
    protected array $extensions = array(
        'bz2' => true,
        'curl' => true,
        'ftp' => true,
        'fileinfo' => false,
        'gd2' => true,
        'gettext' => true,
        'gmp' => false,
        'intl' => false,
        'imap' => false,
        'interbase' => false,
        'ldap' => false,
        'mbstring' => true,
        'exif' => true, // Must be after mbstring as it depends on it
        'mysqli' => true,
        'oci8_12c' => false,
        'odbc' => false,
        'openssl' => true,
        'pdo_firebird' => false,
        'pdo_mysql' => true,
        'pdo_oci' => false,
        'pdo_odbc' => false,
        'pdo_pgsql' => false,
        'pdo_sqlite' => true,
        'pgsql' => false,
        'shmop' => false,
        'snmp' => false,
        'soap' => false,
        'sockets' => true,
        'sqlite3' => true,
        'tidy' => false,
        'xmlrpc' => false,
        'xsl' => true,
    );
    
    protected function processFile(string $file, float $version) : void
    {
        $parser = IniHelper::createFromFile($file);
        
        // save a copy of the original if this is the first time
        // we edit it.
        if(!$parser->sectionExists('EPConf'))
        {
            copy($file, $file.'.epconf.orig');
        }
            
        $parser->setValue('EPConf/rewritten', 1);
        $parser->setValue('PHP/max_execution_time', 90);
        $parser->setValue('PHP/memory_limit', '600M');
        $parser->setValue('PHP/upload_max_filesize', '200M');
        $parser->setValue('PHP/post_max_size', '200M');
        $parser->setValue('PHP/extension', $this->getExtensions($version));
        $parser->setValue('curl/curl.cainfo', $this->easyPHPFolder.'\cacert.pem');
        $parser->setValue('openssl/openssl.cafile', $this->easyPHPFolder.'\cacert.pem');
        
        $parser->saveToFile($file);
        
        $path = dirname($file);
        $name = basename($path);
        
        $this->log('php.ini updated in '.$name);
    }
    
    protected function log(string $message) : void
    {
        echo $message.PHP_EOL;
    }

    /**
     * @return string[]
     */
    protected function getExtensions(float $phpVersion) : array
    {
        $keep = array();
        $prefix = '';
        if($phpVersion < 7.4)
        {
            $prefix = 'php_';
        }
        
        foreach($this->extensions as $name => $enabled)
        {
            if($enabled) {
                $keep[] = $prefix.$name;
            }
        }
        
        return $keep;
    }

    /**
     * @return Configurator_Error[]
     */
    public function getErrors() : array
    {
        return $this->errors;
    }
}
