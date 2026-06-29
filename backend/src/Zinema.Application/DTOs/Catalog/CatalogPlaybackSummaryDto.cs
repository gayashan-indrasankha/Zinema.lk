namespace Zinema.Application.DTOs.Catalog;

public sealed record CatalogPlaybackSummaryDto(
    bool Available,
    string? PlaybackUrl,
    string? ManifestUrl,
    string Status,
    string? Reason);
