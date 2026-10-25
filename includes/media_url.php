<?php
// A hostname substring is not URL validation. This function performs no I/O.
function valid_facebook_url($value) {
    if (!is_string($value) || strlen($value)>512 || preg_match('/[\x00-\x20\\\\]/',$value)) return false;
    $url=parse_url($value);
    return $url && ($url['scheme'] ?? '')==='https' &&
        in_array(strtolower($url['host'] ?? ''),['facebook.com','www.facebook.com','m.facebook.com','web.facebook.com','touch.facebook.com','fb.com','www.fb.com','fb.watch'],true) &&
        !isset($url['user']) && !isset($url['pass']) && (!isset($url['port']) || $url['port']===443);
}
