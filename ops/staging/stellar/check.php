<?php
// CLI-only validation of this sanitized, disabled template. No application bootstrap.
function stellar_candidate_valid($candidate) {
    if (!is_array($candidate) || ($candidate['version'] ?? null)!==1 ||
        ($candidate['purpose'] ?? null)!=='private-staging-candidate') return false;
    foreach (['deployment_enabled','external_connections','production_access','publishing'] as $key)
        if (($candidate[$key] ?? null)!==false) return false;
    $env=$candidate['environment'] ?? [];
    if (($env['APP_ENV'] ?? null)!=='production' || ($env['BASE_URL'] ?? null)!=='https://staging.example.invalid' ||
        ($env['ALLOW_LIVE_INTEGRATIONS'] ?? null)!=='false' || ($env['DB_HOST'] ?? null)!=='localhost' ||
        ($env['DB_PORT'] ?? null)!=='3306' || ($env['UPLOAD_QUOTA_MODE'] ?? null)!=='disabled' ||
        ($env['TRUSTED_PROXY_IPS'] ?? null)!=='') return false;
    foreach (['MAIL','OAUTH','WHATSAPP','MEDIA','EXTRACTOR','PAYMENT'] as $adapter)
        if (($env[$adapter.'_MODE'] ?? null)!=='disabled') return false;
    foreach (['ZINEMA_TWO_HOST_FIXTURES','ZINEMA_PROMOTION_FIXTURES','ZINEMA_EXTRACTOR_FIXTURES'] as $flag)
        if (($env[$flag] ?? null)!=='0') return false;
    foreach (['DB_NAME','DB_USER','DB_PASS'] as $key)
        if (!is_string($env[$key] ?? null) || !str_starts_with($env[$key],'REPLACE_')) return false;
    if (($env['SESSION_SAVE_PATH'] ?? null)!=='/home/REPLACE_ACCOUNT/zinema-private/staging/sessions') return false;
    $storage=$candidate['storage_candidate'] ?? [];
    if (($storage['applied'] ?? null)!==false || ($storage['full_content_on_website'] ?? null)!==false ||
        ($storage['managed_upload_bytes'] ?? null)!==5000000000 || ($storage['quarantine_bytes'] ?? null)!==500000000) return false;
    $backup=$candidate['backup_candidate'] ?? [];$monitor=$candidate['monitoring_candidate'] ?? [];
    return ($backup['enabled'] ?? null)===false && ($backup['minimum_scheduler_interval_seconds'] ?? null)===300 &&
        ($backup['database_interval_seconds'] ?? null)===3600 && ($backup['media_interval_seconds'] ?? null)===86400 &&
        ($backup['key_path'] ?? null)==='/home/REPLACE_ACCOUNT/zinema-private/staging/keys/backup.key' &&
        ($backup['independent_recovery_copy_verified'] ?? null)===false &&
        ($monitor['enabled'] ?? null)===false && ($monitor['collector_interval_seconds'] ?? null)===300 &&
        ($monitor['external_alerts_enabled'] ?? null)===false;
}
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '')===__FILE__) {
    if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
    try { $valid=stellar_candidate_valid(require __DIR__.'/candidate.php'); }
    catch (Throwable $error) { $valid=false; }
    echo json_encode(['valid_disabled_template'=>$valid,'activation'=>'disabled_by_template','runtime_adapter'=>'implemented_not_activated','account_compatibility'=>'not_verified']).PHP_EOL;
    exit($valid ? 0 : 1);
}
