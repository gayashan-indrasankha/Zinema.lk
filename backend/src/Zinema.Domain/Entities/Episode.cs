using Zinema.Domain.Enums;

namespace Zinema.Domain.Entities;

public sealed class Episode : AuditableEntity
{
    public Guid SeriesId { get; set; }

    public Series? Series { get; set; }

    public int SeasonNumber { get; set; }

    public int EpisodeNumber { get; set; }

    public string Title { get; set; } = string.Empty;

    public string Slug { get; set; } = string.Empty;

    public string? Description { get; set; }

    public DateOnly? AirDate { get; set; }

    public int? RuntimeMinutes { get; set; }

    public PublishStatus PublishStatus { get; set; } = PublishStatus.Draft;

    public ICollection<MediaAsset> MediaAssets { get; set; } = new List<MediaAsset>();
}
