<?php
namespace storyproducer\Storage;
use MicrosoftAzure\Storage\Common\ServicesBuilder;
use MicrosoftAzure\Storage\Common\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;

class LocalFileStorage {
    private $root_path;
    public function __construct($root_path) {
        $this->root_path = $root_path;
    }
    public function PutFile($container, $directory, $filename, $data) {
        $dir = "{$this->root_path}/$container/$directory";
        if (!file_exists($dir) && !is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        // TODO @pwhite: If I put a / between $dir and $filename, there are two
        // slashes, so $filename must be coming with a prepended /. This is
        // probably not the right choice, so it should be changed.
        error_log("Putting file '$dir$filename'");
        file_put_contents("$dir$filename", $data);
    }
}


// @pwhite: This class is completely untested. It was changed from a
// previously tested function, so it should be fairly close to correct, but
// don't trust it for anything important. It is only provided in case Azure
// blob storage is desired to be used again; it would be a shame to rewrite
// code that has already been written.
class AzureBlobStorage {
    private $blobRestProxy;

    public function __construct() {
        $connectionString = getStorageConnectionString();
        $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);
    }

    public function PutFile($container, $directory, $filename, $data) {
        $fullFilepath = "$directory/$filename";
        $this->blobRestProxy->createBlockBlob($container, $fullFilepath, $data);
    }
}
