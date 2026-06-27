using Zinema.Application.DTOs.Catalog;

namespace Zinema.Application.DTOs.AdminCatalog;

public sealed record AdminMovieDto(
    Guid Id,
    string Title,
    string Slug,
    string? Description,
    int? ReleaseYear,
    int? RuntimeMinutes,
    string? Language,
    string PublishStatus,
    IReadOnlyList<GenreDto> Genres);
