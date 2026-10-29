<?php
// Delivery admission is disabled: render a customer message before DB/token access.
http_response_code(503);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('Pragma: no-cache');
$catalogue=htmlspecialchars(BASE_URL.'/movies',ENT_QUOTES,'UTF-8');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Delivery unavailable | Zinema.lk</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0c1020;color:#f5f7ff;font:17px/1.6 system-ui,sans-serif}main{max-width:34rem;padding:2rem}h1{font-size:2rem;line-height:1.2}p{color:#cbd3e8}a{display:inline-block;background:#6679ed;color:white;padding:.7rem 1rem;border-radius:.5rem;text-decoration:none}a:focus-visible{outline:3px solid white;outline-offset:4px}</style>
</head><body><main><h1>Delivery unavailable</h1>
<p>Full movies and episodes are delivered through WhatsApp. Delivery is unavailable right now.</p>
<p>You can continue browsing the catalogue. No delivery request has been created.</p>
<a href="<?= $catalogue ?>">Browse movies</a></main></body></html>
