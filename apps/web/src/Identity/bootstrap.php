<?php
declare(strict_types=1);
namespace Zinema\Identity;
foreach (['Contracts','Password','Guards','Audit','Authentication','Recovery','Session','Upload'] as $component) require_once __DIR__.'/'.$component.'.php';
// Pure definitions only. No production factory binding, DB/session startup, mail or environment reads.
