using Zinema.Domain.Enums;

namespace Zinema.Domain.Entities;

public sealed class MediaAsset : AuditableEntity
{
    public string Title { get; set; } = string.Empty;

    public string FileName { get; set; } = string.Empty;

    public string ContentType { get; set; } = string.Empty;

    public string AssetType { get; set; } = string.Empty;

    public string StorageKey { get; set; } = string.Empty;

    public string? PublicUrl { get; set; }

    public long? FileSizeBytes { get; set; }

    public MediaStatus Status { get; set; } = MediaStatus.PendingUpload;

    public Guid? MovieId { get; set; }

    public Movie? Movie { get; set; }

    public Guid? SeriesId { get; set; }

    public Series? Series { get; set; }

    public Guid? EpisodeId { get; set; }

    public Episode? Episode { get; set; }

    public Guid? CollectionId { get; set; }

    public Collection? Collection { get; set; }

    public ICollection<VideoProcessingJob> VideoProcessingJobs { get; set; } = new List<VideoProcessingJob>();
}
