<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class UploadError {
    const CODE_FileSizeTooLarge = 70001;
    const CODE_FileTypeInvalid = 70002;
    const CODE_UploadFail = 70003;

    static public function FileSizeTooLarge($size) {
        $_message = "file size should less than {$size}";
        $_display = null;
        return new Error(self::CODE_FileSizeTooLarge, $_message, $_display);
    }

    static public function FileTypeInvalid($type) {
        $_message = "you should upload file with {$type} mime type";
        $_display = null;
        return new Error(self::CODE_FileTypeInvalid, $_message, $_display);
    }

    static public function UploadFail() {
        $_message = "Upload Fail";
        $_display = null;
        return new Error(self::CODE_UploadFail, $_message, $_display);
    }
}