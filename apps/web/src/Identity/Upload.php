<?php
declare(strict_types=1);
namespace Zinema\Identity;

/** Byte inspection only. No public endpoint, move, shell command or persistence. */
final class Upload {
    public static function image(string $name,string $bytes,int $error=UPLOAD_ERR_OK):array {
        if ($error!==UPLOAD_ERR_OK || strlen($bytes)<16 || strlen($bytes)>2097152
            || !preg_match('/^[A-Za-z0-9_-]{1,80}\.(png|jpg|jpeg)$/Di',$name,$m)
            || str_contains($bytes,'<?')) throw new SecurityFailure('UPLOAD_REJECTED',400);
        if (!class_exists(\finfo::class)) throw new SecurityFailure('UPLOAD_INSPECTION_UNAVAILABLE',503);
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);$image=@getimagesizefromstring($bytes);
        $expected=strtolower($m[1])==='png'?'image/png':'image/jpeg';
        if ($mime!==$expected || !$image || ($image['mime']??'')!==$expected || $image[0]<1 || $image[1]<1
            || $image[0]>4096 || $image[1]>4096 || $image[0]*$image[1]>16000000) throw new SecurityFailure('UPLOAD_REJECTED',400);
        return ['name'=>bin2hex(random_bytes(16)).($expected==='image/png'?'.png':'.jpg'),'mime'=>$mime,'bytes'=>strlen($bytes),'sha256'=>hash('sha256',$bytes)];
    }
    public static function persist():never {
        // Require trusted ownership/RBAC/CSRF, canonical re-encoding, approved storage and audit adapter first.
        throw new SecurityFailure('UPLOAD_STORAGE_NOT_CONFIGURED',503);
    }
}
