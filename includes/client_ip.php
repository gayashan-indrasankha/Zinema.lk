<?php
// Never trust forwarded headers from arbitrary clients. Configure exact proxy IPs.
function recovery_client_ip($server=null,$trusted=null) {
    $server=$server ?? $_SERVER;
    $peer=$server['REMOTE_ADDR'] ?? '';
    if (!is_string($peer) || !filter_var($peer,FILTER_VALIDATE_IP)) return '0.0.0.0';
    $trusted=$trusted ?? (getenv('TRUSTED_PROXY_IPS') ?: '');
    $proxies=array_filter(array_map('trim',explode(',',$trusted)));
    foreach ($proxies as $proxy) if (!filter_var($proxy,FILTER_VALIDATE_IP)) throw new RuntimeException('Invalid proxy configuration');
    if (in_array($peer,$proxies,true)) {
        // Require one edge-overwritten address. Never select the first attacker-controlled hop.
        $forwarded=$server['HTTP_X_REAL_IP'] ?? '';
        if (is_string($forwarded) && filter_var($forwarded,FILTER_VALIDATE_IP)) return $forwarded;
    }
    return $peer;
}
