<?php
declare(strict_types=1);
namespace Zinema\Content;

final class Validation {
    public const TYPES = ['movie', 'series', 'episode', 'shot'];
    public const STATES = ['draft', 'pending_review', 'approved', 'published', 'rejected', 'unpublished', 'revoked'];
    // Explicit supported product subset, not a claim to cover every ISO territory/language.
    public const TERRITORIES = ['LK', 'IN', 'GB', 'US', 'CA', 'AU'];
    public const LANGUAGES = ['si', 'ta', 'en', 'hi', 'ko', 'ja', 'fr', 'es'];
    public const GENRES = ['action', 'adventure', 'animation', 'comedy', 'documentary', 'drama', 'family', 'fantasy', 'horror', 'romance', 'science_fiction', 'thriller'];
    public const PERMISSIONS = ['metadata'=>'metadata_display', 'poster'=>'display', 'film'=>'stream', 'episode'=>'stream', 'shot'=>'preview', 'trailer'=>'preview', 'subtitle'=>'subtitle'];

    public static function require(bool $condition, string $code): void {
        if (!$condition) throw new ContentFailure($code);
    }
    public static function keys(array $input, array $required, array $optional = []): void {
        self::require(!array_diff($required, array_keys($input)) && !array_diff(array_keys($input), [...$required, ...$optional]), 'INVALID_FIELDS');
    }
    public static function id(mixed $id): string {
        self::require(is_string($id) && preg_match('/^[a-f0-9]{32}$/D', $id) === 1, 'INVALID_ID');
        return $id;
    }
    public static function text(mixed $value, int $max, bool $empty = false): string {
        self::require(is_string($value) && preg_match('//u', $value) === 1 && strlen($value) <= $max &&
            ($empty || trim($value) !== '') && !preg_match('/[<>\x00-\x08\x0B-\x1F\x7F]/', $value), 'INVALID_TEXT');
        return trim($value);
    }
    public static function one(mixed $value, array $allowed): string {
        self::require(is_string($value) && in_array($value, $allowed, true), 'UNSUPPORTED_VALUE');
        return $value;
    }
    public static function integer(mixed $value, int $min, int $max): int {
        self::require(is_int($value) && $value >= $min && $value <= $max, 'INVALID_INTEGER'); return $value;
    }
    public static function strings(mixed $value, int $max, callable $validate): array {
        self::require(is_array($value) && array_is_list($value) && count($value) > 0 && count($value) <= $max, 'INVALID_LIST');
        $result = array_map($validate, $value);
        self::require(count($result) === count(array_unique($result, SORT_REGULAR)), 'DUPLICATE_VALUE');
        return $result;
    }
    public static function utc(mixed $value): string {
        self::require(is_string($value), 'INVALID_UTC_TIME');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new \DateTimeZone('UTC'));
        self::require($date !== false && $date->format('Y-m-d\TH:i:s\Z') === $value, 'INVALID_UTC_TIME');
        return $value;
    }
    public static function reference(mixed $value, string $prefix): string {
        self::require(is_string($value) && preg_match('/^' . preg_quote($prefix, '/') . ':[a-f0-9]{32}$/D', $value) === 1, 'UNSAFE_REFERENCE');
        return $value;
    }
    public static function catalogue(mixed $value, string $type): ?array {
        if ($value === null) return null;
        self::require(is_array($value), 'INVALID_CATALOGUE_REFERENCE'); self::keys($value, ['id', 'parent']);
        self::require(is_string($value['id']) && preg_match('/^[1-9][0-9]{0,17}$/D', $value['id']) === 1, 'INVALID_CATALOGUE_REFERENCE');
        $parent = $value['parent'];
        if ($type === 'episode') self::require(is_array($parent), 'PARENT_REQUIRED');
        if (in_array($type, ['movie', 'series'], true)) self::require($parent === null, 'UNEXPECTED_PARENT');
        if ($parent !== null) {
            self::require(is_array($parent), 'INVALID_PARENT'); self::keys($parent, ['type','id']);
            self::one($parent['type'], $type === 'episode' ? ['series'] : ['movie','series']);
            self::require(is_string($parent['id']) && preg_match('/^[1-9][0-9]{0,17}$/D', $parent['id']) === 1, 'INVALID_PARENT');
        }
        return ['id'=>$value['id'], 'parent'=>$parent];
    }
    public static function metadata(array $input): array {
        self::keys($input, ['type','title','synopsis','genres','language','runtime_seconds','tags','release_year','guidance','credits','catalogue']);
        $type = self::one($input['type'], self::TYPES);
        self::require(is_array($input['credits']) && array_is_list($input['credits']) && count($input['credits']) <= 30, 'INVALID_CREDITS');
        $credits=[]; foreach ($input['credits'] as $credit) {
            self::require(is_array($credit), 'INVALID_CREDITS'); self::keys($credit, ['role','name']);
            $credits[]=['role'=>self::one($credit['role'], ['cast','director','writer','producer']), 'name'=>self::text($credit['name'],100)];
        }
        self::require(is_array($input['tags']) && count($input['tags']) <= 12 && array_is_list($input['tags']), 'INVALID_TAGS');
        $tags = array_map(static fn($t)=>self::text($t,40), $input['tags']);
        self::require(count($tags) === count(array_unique($tags)), 'DUPLICATE_VALUE');
        return ['type'=>$type, 'title'=>self::text($input['title'],200), 'synopsis'=>self::text($input['synopsis'],4000,true),
            'genres'=>self::strings($input['genres'],6,static fn($g)=>self::one($g,self::GENRES)),
            'language'=>self::one($input['language'],self::LANGUAGES), 'runtime_seconds'=>self::integer($input['runtime_seconds'],0,86400),
            'tags'=>$tags, 'release_year'=>self::integer($input['release_year'],1888,2200),
            'guidance'=>self::text($input['guidance'],100,true), 'credits'=>$credits, 'catalogue'=>self::catalogue($input['catalogue'],$type)];
    }
    public static function asset(array $input, string $type): array {
        self::keys($input, ['role','mime','duration_seconds','sha256','storage_ref','provenance_ref']);
        $role=self::one($input['role'], array_keys(self::PERMISSIONS));
        $allowed=['movie'=>['metadata','poster','film','trailer','subtitle'], 'series'=>['metadata','poster','trailer'],
            'episode'=>['metadata','poster','episode','trailer','subtitle'], 'shot'=>['metadata','poster','shot','subtitle']];
        self::require(in_array($role,$allowed[$type],true),'ASSET_CONTENT_MISMATCH');
        $mimes=['metadata'=>['application/json'],'poster'=>['image/jpeg','image/png'], 'subtitle'=>['text/vtt']];
        $mime=self::one($input['mime'],$mimes[$role] ?? ['video/mp4','application/vnd.apple.mpegurl']);
        self::require(is_string($input['sha256']) && preg_match('/^[a-f0-9]{64}$/D',$input['sha256'])===1,'INVALID_CHECKSUM');
        return ['role'=>$role,'mime'=>$mime,'duration_seconds'=>self::integer($input['duration_seconds'],0,86400),
            'sha256'=>$input['sha256'],'storage_ref'=>self::reference($input['storage_ref'],'asset'),
            'provenance_ref'=>self::reference($input['provenance_ref'],'provenance'),'available'=>false];
    }
    public static function grant(array $input): array {
        self::keys($input,['asset_id','holder_id','permission','channel','territories','starts_at','ends_at','evidence_ref']);
        $start=self::utc($input['starts_at']); $end=self::utc($input['ends_at']); self::require($end>$start,'INVALID_LICENSE_WINDOW');
        return ['asset_id'=>self::id($input['asset_id']),'holder_id'=>self::id($input['holder_id']),
            'permission'=>self::one($input['permission'],array_values(self::PERMISSIONS)),
            'channel'=>self::one($input['channel'],['web','messaging','download']),
            'territories'=>self::strings($input['territories'],6,static fn($t)=>self::one($t,self::TERRITORIES)),
            'starts_at'=>$start,'ends_at'=>$end,'evidence_ref'=>self::reference($input['evidence_ref'],'evidence'),
            'verified_by'=>null,'revoked'=>false];
    }
}
