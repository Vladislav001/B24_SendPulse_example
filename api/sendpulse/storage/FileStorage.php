<?php
/**
 * File token storage
 * Class File
 */

namespace Sendpulse\RestApi\Storage;
require_once(__DIR__ . "/TokenStorageInterface.php");

class FileStorage implements TokenStorageInterface
{

    /**
     * @var string
     */
    protected $storageFolder = '';

    /**
     * File constructor.
     *
     * @param string $storageFolder
     */
    public function __construct($storageFolder = '')
    {
        $this->storageFolder = $storageFolder;
    }

    /**
     * @param $key string
     * @param $token
     *
     * @return void
     */
    public function set($key, $token)
    {
        $tokenFile = fopen($this->storageFolder . $key, 'wb');
        fwrite($tokenFile, $token);
        fclose($tokenFile);
    }

    /**
     * @param $key string
     *
     * @return mixed
     */
    public function get($key)
    {
        $filePath = $this->storageFolder . $key;
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }

        return null;
    }
    
    /**
     * @param  $key string
     * 
     * @return void
     */
    public function delete($key) 
    {
        $filePath = $this->storageFolder . $key;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }
}
