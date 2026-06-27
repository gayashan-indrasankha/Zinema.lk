using Zinema.Domain.Enums;

namespace Zinema.Domain.Entities;

public sealed class Series : AuditableEntity
{
    public string Title { get; set; } = string.Empty;

    public string Slug { get; set; } = string.Empty;

    public string? Description { get; set; }

    public int? ReleaseYear { get; set; }

    public string? Language { get; set; }

    public PublishStatus PublishStatus { get; set; } = PublishStatus.Draft;

    public ICollection<Episode> Episodes { get; set; } = new List<Episode>();

    public ICollection<MediaAsset> MediaAssets { get; set; } = new List<MediaAsset>();
}
