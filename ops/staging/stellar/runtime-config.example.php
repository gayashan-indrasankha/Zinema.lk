<?php
// REVIEW ONLY, outside public_html. Do not rename/enable/upload without owner approval.
return [
    'version'=>1,'purpose'=>'private-staging-runtime','enabled'=>false,
    'mode'=>'private-staging','production_access'=>false,'publishing'=>false,
    'allowed_clients'=>[], // Exact dedicated owner/worker IPs, no proxy headers or CIDR wildcard.
    'extractor_worker_key'=>'REPLACE_PRIVATE_INDEPENDENT_64_HEX_WORKER_KEY',
    'environment'=>[
        'APP_ENV'=>'production','BASE_URL'=>'https://staging.example.invalid',
        'ALLOW_LIVE_INTEGRATIONS'=>'false','TRUSTED_PROXY_IPS'=>'',
        'DB_HOST'=>'localhost','DB_PORT'=>'3306','DB_NAME'=>'REPLACE_ACCOUNT_staging',
        'DB_USER'=>'REPLACE_ACCOUNT_staging','DB_PASS'=>'REPLACE_PRIVATE_DATABASE_PASSWORD',
        'SESSION_SAVE_PATH'=>'/home/REPLACE_ACCOUNT/zinema-private/staging/sessions',
        'UPLOAD_QUOTA_MODE'=>'account',
        'UPLOAD_STORAGE_STATE'=>'/home/REPLACE_ACCOUNT/zinema-private/staging/storage',
        'UPLOAD_STORAGE_OWNER'=>'REPLACE_ZINEMA_ACCOUNT_RANDOM_ID',
        'UPLOAD_MAX_BYTES'=>'5000000000','UPLOAD_MAX_FILES'=>'10000',
        'UPLOAD_QUARANTINE_MAX_BYTES'=>'500000000','UPLOAD_QUARANTINE_MAX_FILES'=>'1000',
        'MAIL_MODE'=>'disabled','OAUTH_MODE'=>'disabled','WHATSAPP_MODE'=>'disabled',
        'MEDIA_MODE'=>'disabled','PAYMENT_MODE'=>'disabled','EXTRACTOR_MODE'=>'disabled',
        'ZINEMA_TWO_HOST_FIXTURES'=>'0','ZINEMA_PROMOTION_FIXTURES'=>'0','ZINEMA_EXTRACTOR_FIXTURES'=>'0',
    ],
];
