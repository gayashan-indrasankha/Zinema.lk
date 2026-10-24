<?php
declare(strict_types=1);
header('Cache-Control: private, no-store');
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'self'; media-src 'self' blob:; img-src 'self'; base-uri 'none'; frame-ancestors 'self'; form-action 'none'");
header('Referrer-Policy: no-referrer');header('X-Content-Type-Options: nosniff');
$type=$playbackType??'movie';if(!in_array($type,['movie','series','episode'],true))$type='movie';
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Zinema playback</title><link rel="stylesheet" href="/css/hls-player.css"><script defer src="/js/vendor/hls/hls.min.js"></script><script defer src="/js/hls-player.js"></script></head>
<body><main class="zinema-playback" data-zinema-playback data-content-type="<?=htmlspecialchars($type,ENT_QUOTES,'UTF-8')?>">
<a href="/index.php">Browse Shots</a><a href="/movies.php">Browse movies</a>
<h1 data-field="title">Content details</h1><p data-field="synopsis"></p><dl><div><dt>Year</dt><dd data-field="releaseYear"></dd></div><div><dt>Language</dt><dd data-field="language"></dd></div><div><dt>Duration (seconds)</dt><dd data-field="runtimeSeconds"></dd></div></dl>
<p role="status" aria-live="polite">Checking availability...</p><button type="button" data-watch disabled>Watch</button>
<video controls playsinline preload="none" aria-label="Authorized movie player" hidden></video>
<label><input type="checkbox" data-remember>Remember progress in this tab for up to 24 hours</label>
<button type="button" data-retry hidden>Retry playback</button><button type="button" data-restart hidden>Restart from beginning</button>
<p>Playback requires current rights, access permission and an available protected source.</p>
</main></body></html>
