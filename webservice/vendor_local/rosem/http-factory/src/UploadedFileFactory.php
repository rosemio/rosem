<?php

namespace Rosem\Http\Factory;

use Psr\Http\Message\UploadedFileInterface;
use Psrnext\Http\Factory\UploadedFileFactoryInterface;
use Zend\Diactoros\UploadedFile;

class UploadedFileFactory implements UploadedFileFactoryInterface
{
    public function createUploadedFile(
        $file,
        $size = null,
        $error = \UPLOAD_ERR_OK,
        $clientFilename = null,
        $clientMediaType = null
    ) : UploadedFileInterface {
        if ($size === null) {
            if (is_string($file)) {
                $size = filesize($file);
            } else {
                $stats = fstat($file);
                $size = $stats['size'];
            }
        }

        return new UploadedFile($file, $size, $error, $clientFilename, $clientMediaType);
    }
}
