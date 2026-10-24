<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/Catalogue/bootstrap.php';
foreach(['Navigation','Contracts','LocalFixtureProvider','Service','Controller'] as $file)require_once __DIR__.'/'.$file.'.php';
