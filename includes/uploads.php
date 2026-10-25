<?php
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
    $target = $directory . '/' . $filename;
    if ($kind === 'image') {
        $info = @getimagesize($file['tmp_name']);
        // Bound decode memory before GD; rewrite removes metadata and appended payloads.
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 12000000 ||
            !function_exists('imagecreatefromstring')) $reject();
        $image = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        if (!$image) $reject();
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $saved = match ($extension) {
            'png' => imagepng($image, $target),
            'jpg' => imagejpeg($image, $target, 90),
            'webp' => imagewebp($image, $target, 90),
        };
        imagedestroy($image);
        if (!$saved) $reject();
    } else {
        // No shell invocation or automatic transcoding. Containers are not malware scanners.
        $header = file_get_contents($file['tmp_name'], false, null, 0, 32);
        if (($extension === 'mp4' && substr($header, 4, 4) !== 'ftyp') ||
            ($extension === 'webm' && substr($header, 0, 4) !== "\x1a\x45\xdf\xa3")) $reject();
        if (!move_uploaded_file($file['tmp_name'], $target)) $reject();
    }
    chmod($target, 0644);
    return 'uploads/' . ($kind === 'video' ? 'trailers/' : '') . $filename;
}
