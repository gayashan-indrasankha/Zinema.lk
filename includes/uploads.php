<?php
require_once __DIR__ . '/upload_storage.php';
/** New media is validated before any catalogue mutation. Legacy files are retained. */
function store_catalogue_upload($field, $kind = 'image', $existing = '') {
    $file = $_FILES[$field] ?? null;
    if ($file === null || ($file['error'] ?? null) === UPLOAD_ERR_NO_FILE) return $existing;
    $reject = static function () {
        http_response_code(422);
        exit('Upload rejected: use a valid PNG, JPEG or WebP image (5 MiB), or MP4/WebM video (20 MiB).');
    };
    if (!is_array($file) || ($file['error'] ?? -1) !== UPLOAD_ERR_OK ||
        !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) $reject();
    $size = filesize($file['tmp_name']);
    if ($size < 1 || $size > ($kind === 'video' ? 20 : 5) * 1024 * 1024) $reject();
    // PHP may normalize name to a basename; full_path retains the submitted path.
    $name = $file['full_path'] ?? $file['name'] ?? '';
    if (!is_string($name) || preg_match('/[\\\\\/\x00-\x1f]/', $name)) $reject();
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types = $kind === 'video' ? ['mp4' => 'video/mp4', 'webm' => 'video/webm'] :
        ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'];
    if (!isset($types[$extension]) || $types[$extension] !== $mime) $reject();
    $directory = dirname(__DIR__) . '/uploads' . ($kind === 'video' ? '/trailers' : '');
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) $reject();
    $extension = $extension === 'jpeg' ? 'jpg' : $extension;
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $staged = null;
    $source = $file['tmp_name'];
    if ($kind === 'image') {
        $info = @getimagesize($file['tmp_name']);
        // Bound decode memory before GD; rewrite removes metadata and appended payloads.
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 12000000 ||
            !function_exists('imagecreatefromstring')) $reject();
        $image = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        if (!$image) $reject();
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $staged = tempnam(sys_get_temp_dir(), 'zinema-upload-');
        if ($staged === false) $reject();
        $source = $staged;
        $saved = match ($extension) {
            'png' => imagepng($image, $source),
            'jpg' => imagejpeg($image, $source, 90),
            'webp' => imagewebp($image, $source, 90),
        };
        imagedestroy($image);
        if (!$saved || filesize($source) > 5 * 1024 * 1024) { @unlink($staged); $reject(); }
    } else {
        // No shell invocation or automatic transcoding. Containers are not malware scanners.
        $header = file_get_contents($file['tmp_name'], false, null, 0, 32);
        if (($extension === 'mp4' && substr($header, 4, 4) !== 'ftyp') ||
            ($extension === 'webm' && substr($header, 0, 4) !== "\x1a\x45\xdf\xa3")) $reject();

    }
    try {
        return storage_commit_upload($source, ($kind === 'video' ? 'trailers/' : '') . $filename);
    } catch (Throwable $error) {
        if ($staged !== null && is_file($staged)) @unlink($staged);
        http_response_code($error instanceof LocalStorageError && $error->getMessage() === 'quota_exceeded' ? 507 : 503);
        exit($error instanceof LocalStorageError && $error->getMessage() === 'quota_exceeded' ?
            'Local upload quota exceeded. Ask the operator to review storage inventory.' : 'Upload storage unavailable.');
    } finally {
        if ($staged !== null && is_file($staged)) @unlink($staged);
    }
}
