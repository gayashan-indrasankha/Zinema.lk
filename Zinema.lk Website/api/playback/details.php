<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/apps/web/src/Playback/bootstrap.php';
\Zinema\Playback\Http::run('details');
