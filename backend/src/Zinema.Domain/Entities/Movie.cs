using Zinema.Domain.Enums;

namespace Zinema.Domain.Entities;

public sealed class Movie : AuditableEntity
{
    public string Title { get; set; } = string.Empty;

    public string Slug { get; set; } = string.Empty;

    public string? Description { get; set; }

    public int? ReleaseYear { get; set; }

    public int? RuntimeMinutes { get; set; }

    public string? Language { get; set; }

    public PublishStatus PublishStatus { get; set; } = PublishStatus.Draft;

    public Guid? CollectionId { get; set; }

    public Collection? Collection { get; set; }

    public ICollection<MovieGenre> MovieGenres { get; set; } = new List<MovieGenre>();

    public ICollection<MediaAsset> MediaAssets { get; set; } = new List<MediaAsset>();
}
