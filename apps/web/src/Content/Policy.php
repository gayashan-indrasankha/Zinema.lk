<?php
declare(strict_types=1);
namespace Zinema\Content;

final class Policy {
    public static function context(array $context): array {
        Validation::keys($context,['territory','channel']);
        return ['territory'=>Validation::one($context['territory'],Validation::TERRITORIES),
            'channel'=>Validation::one($context['channel'],['web','messaging','download'])];
    }
    /** Rights eligibility only. Never returns a media locator, entitlement or playback token. */
    public static function rights(array $record, string $assetId, array $context, \DateTimeImmutable $now): array {
        $deny=static fn(string $reason):array=>['allowed'=>false,'reason'=>$reason];
        try { $context=self::context($context); Validation::id($assetId); }
        catch (ContentFailure $error) { return $deny('INVALID_RIGHTS_CONTEXT'); }
        $asset=$record['assets'][$assetId]??null;
        if (!is_array($asset) || ($asset['available']??false)!==true) return $deny('ASSET_UNAVAILABLE');
        $permission=Validation::PERMISSIONS[$asset['role']??'']??null;
        if ($permission===null) return $deny('UNSUPPORTED_ASSET_ROLE');
        $utc=$now->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        foreach ($record['grants']??[] as $grant) {
            if (!is_array($grant) || !is_string($grant['holder_id']??null)) continue;
            $holder=$record['holders'][$grant['holder_id']??'']??null;
            if (!is_array($holder)) continue;
            if (($grant['asset_id']??null)!==$assetId || ($grant['permission']??null)!==$permission ||
                ($grant['channel']??null)!==$context['channel'] || !in_array($context['territory'],$grant['territories']??[],true) ||
                !is_string($grant['verified_by']??null) || ($grant['revoked']??true)!==false ||
                !is_string($holder['verified_by']??null) || ($holder['revoked']??true)!==false ||
                !is_string($grant['starts_at']??null) || !is_string($grant['ends_at']??null) ||
                $utc<$grant['starts_at'] || $utc>=$grant['ends_at']) continue;
            try {
                Validation::utc($grant['starts_at']); Validation::utc($grant['ends_at']);
                Validation::id($grant['verified_by']); Validation::id($holder['verified_by']);
                Validation::id($grant['entered_by']??null); Validation::id($holder['entered_by']??null);
                Validation::reference($grant['evidence_ref']??null,'evidence');
                Validation::reference($holder['verification_ref']??null,'evidence');
                if ($grant['verified_by']===($grant['entered_by']??null) || $holder['verified_by']===($holder['entered_by']??null)) continue;
            } catch (ContentFailure $error) { continue; }
            return ['allowed'=>true,'reason'=>'RIGHTS_ELIGIBLE'];
        }
        return $deny('NO_ACTIVE_VERIFIED_GRANT');
    }
    public static function available(array $record,string $assetId,array $context,\DateTimeImmutable $now):array {
        if (($record['state']??null)!=='published' || !in_array($assetId,$record['publication']['asset_ids']??[],true) ||
            !in_array($context['territory'],$record['publication']['territories']??[],true) ||
            ($record['publication']['channel']??null)!==$context['channel']) return ['allowed'=>false,'reason'=>'NOT_PUBLISHED_FOR_CONTEXT'];
        return self::rights($record,$assetId,$context,$now);
    }
    public static function canStream(): array {
        // There is deliberately no switch to bypass missing real entitlement/CDN integration.
        return ['allowed'=>false,'reason'=>'ENTITLEMENT_AND_DELIVERY_UNAVAILABLE'];
    }
}
