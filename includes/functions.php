<?php
// Small helper functions for the site
function base_url($path = ''){
  $base = rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . 
    $_SERVER['HTTP_HOST'], '/');
  return $base . '/' . ltrim($path, '/');
}

function esc($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
