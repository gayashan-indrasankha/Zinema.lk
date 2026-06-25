using Zinema.Domain.Enums;

namespace Zinema.Domain.Entities;

public sealed class Collection : AuditableEntity
{
    public string Title { get; set; } = string.Empty;

    public string Slug { get; set; } = string.Empty;

    public string? Description { get; set; }

    public PublishStatus PublishStatus { get; set; } = PublishStatus.Draft;

    public ICollection<Movie> Movies { get; set; } = new List<Movie>();

    public ICollection<MediaAsset> MediaAssets { get; set; } = new List<MediaAsset>();
}
