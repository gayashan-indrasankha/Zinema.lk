using Zinema.Domain.Enums;

namespace Zinema.Domain.Entities;

public sealed class VideoProcessingJob : AuditableEntity
{
    public Guid MediaAssetId { get; set; }

    public MediaAsset? MediaAsset { get; set; }

    public VideoProcessingJobStatus Status { get; set; } = VideoProcessingJobStatus.Pending;

    public string SourceStorageKey { get; set; } = string.Empty;

    public string? OutputStoragePrefix { get; set; }

    public string? ErrorMessage { get; set; }

    public int AttemptCount { get; set; }

    public DateTimeOffset QueuedAt { get; set; } = DateTimeOffset.UtcNow;

    public DateTimeOffset? StartedAt { get; set; }

    public DateTimeOffset? CompletedAt { get; set; }
}
