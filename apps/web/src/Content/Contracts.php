<?php
declare(strict_types=1);
namespace Zinema\Content;

final class ContentFailure extends \RuntimeException {
    public function __construct(public readonly string $reason) { parent::__construct($reason); }
}
interface Authorization {
    /** Trusted server adapter, never capabilities or actor IDs supplied in a request. */
    public function actor(string $permission, string $recordId): ?string;
}
final class DenyAuthorization implements Authorization {
    public function actor(string $permission, string $recordId): ?string { return null; }
}
interface Catalogue {
    /** Verify canonical identity AND the declared parent relationship. Never creates titles. */
    public function exists(string $type, string $id, ?array $parent): bool;
}
final class UnverifiedCatalogue implements Catalogue {
    public function exists(string $type, string $id, ?array $parent): bool { return false; }
}
interface Clock { public function now(): \DateTimeImmutable; }
final class UtcClock implements Clock {
    public function now(): \DateTimeImmutable { return new \DateTimeImmutable('now', new \DateTimeZone('UTC')); }
}
interface Repository {
    public function find(string $id): ?array;
    public function create(array $record, array $event): void;
    /** Atomic compare-and-swap of record plus append-only audit event. */
    public function save(int $expectedVersion, array $record, array $event): void;
    public function scan(string $afterId, int $limit): array;
    public function events(string $id, int $afterVersion, int $limit): array;
}
