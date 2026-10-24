<?php
declare(strict_types=1);
namespace Zinema\Content;
require_once __DIR__.'/Runner.php';
use PDO;
use Zinema\Database\Runner;
use Zinema\Database\MigrationFailure;

final class ContentMigrations {
    /** Explicit additive profile, preserving the original four-table loader and SQL. */
    public static function load(string $root):array {
        $base=Runner::load($root.'/database');
        $manifest=json_decode((string)file_get_contents($root.'/database/content-migrations.json'),true,512,JSON_THROW_ON_ERROR);
        $expected=['0005'=>'content_rights_records','0006'=>'content_rights_events'];
        if (($manifest['schemaVersion']??null)!==1 || ($manifest['scope']??null)!=='synthetic-rights-extension' ||
            ($manifest['fullApplicationReady']??true)!==false || count($manifest['migrations']??[])!==2) throw new MigrationFailure('INVALID_CONTENT_MIGRATION_MANIFEST');
        $seen=[];
        foreach ($manifest['migrations'] as $entry) {
            $version=$entry['version']??''; $table=$expected[$version]??null;
            $relative='database/content-migrations/'.$version.'_'.$table.'.sql'; $file=$root.'/'.$relative;
            if ($table===null || ($entry['table']??null)!==$table || ($entry['file']??null)!==$relative ||
                !is_file($file) || is_link($file) || is_link(dirname($file))) throw new MigrationFailure('UNREVIEWED_CONTENT_MIGRATION');
            $sql=(string)file_get_contents($file);
            if (!hash_equals($entry['sha256']??'',hash('sha256',$sql))) throw new MigrationFailure('CONTENT_MIGRATION_CHECKSUM_MISMATCH');
            $definition=Runner::definition($sql);
            if ($definition['table']!==$table) throw new MigrationFailure('UNREVIEWED_CONTENT_MIGRATION');
            $seen[]=$version; $entry['sql']=$sql; $entry['expected']=$definition; $base[]=$entry;
        }
        if ($seen!==array_keys($expected)) throw new MigrationFailure('INVALID_CONTENT_MIGRATION_ORDER');
        return $base;
    }
    public static function apply(PDO $db,array $migrations,?callable $afterDdl=null):array {
        if (array_column($migrations,'table')!==['site_settings','rate_limits','movie_parts','episode_parts','content_rights_records','content_rights_events']) {
            throw new MigrationFailure('INCOMPLETE_CONTENT_MIGRATION_PROFILE');
        }
        $result=Runner::apply($db,$migrations,$afterDdl);
        $result['scope']='synthetic-rights-extension'; return $result;
    }
}
