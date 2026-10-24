<?php
declare(strict_types=1);
namespace Zinema\Playback;
final class Navigation {
    public static function shotHref(array $shot):?string{
        foreach(['id','linked_content_id'] as $key)if(!isset($shot[$key])||!preg_match('/^[1-9][0-9]{0,17}$/D',(string)$shot[$key]))return null;
        if(!in_array($shot['linked_content_type']??null,['movie','series'],true))return null;
        return 'shot-content.php?id='.rawurlencode((string)$shot['id']);
    }
}
