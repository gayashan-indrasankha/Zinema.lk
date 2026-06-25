namespace Zinema.Application.DTOs.Catalog;

public sealed record MovieListItemDto(
    Guid Id,
    string Title,
    string Slug,
    string? Description,
    int? ReleaseYear,
    int? RuntimeMinutes,
    string? Language,
    IReadOnlyList<GenreDto> Genres,
    MediaAssetDto? Poster);
