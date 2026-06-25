namespace Zinema.Application.DTOs.Catalog;

public sealed record GenreDto(
    Guid Id,
    string Name,
    string Slug);
