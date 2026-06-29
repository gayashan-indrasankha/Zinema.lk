namespace Zinema.Application.DTOs.Catalog;

public sealed record MovieDetailDto(
    Guid Id,
    string Title,
    string Slug,
    string? Description,
    int? ReleaseYear,
    int? RuntimeMinutes,
    string? Language,
    IReadOnlyList<GenreDto> Genres,
    MediaAssetDto? Poster,
    MediaAssetDto? Backdrop,
    IReadOnlyList<MediaAssetDto> MediaAssets)
{
    public CatalogPlaybackSummaryDto Playback { get; init; } = new(
        Available: false,
        PlaybackUrl: null,
        ManifestUrl: null,
        Status: "NoSource",
        Reason: "Playback source is not available yet.");
}
