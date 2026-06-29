namespace Zinema.Application.DTOs.VideoProcessingJobs;

public sealed record VideoProcessingJobDto(
    Guid Id,
    Guid MediaAssetId,
    string? MediaAssetTitle,
    string SourceStorageKey,
    string? OutputStoragePrefix,
    string Status,
    string? ErrorMessage,
    int AttemptCount,
    DateTimeOffset QueuedAt,
    DateTimeOffset? StartedAt,
    DateTimeOffset? CompletedAt,
    DateTimeOffset CreatedAt,
    DateTimeOffset? UpdatedAt);
