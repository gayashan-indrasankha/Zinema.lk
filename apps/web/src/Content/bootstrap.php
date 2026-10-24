<?php
declare(strict_types=1);
namespace Zinema\Content;
require_once __DIR__.'/Contracts.php';
if (PHP_SAPI!=='cli') throw new ContentFailure('HTTP_INTEGRATION_UNAVAILABLE');
require_once __DIR__.'/Validation.php';
require_once __DIR__.'/Policy.php';
require_once __DIR__.'/CatalogueService.php';
require_once __DIR__.'/PdoRepository.php';
