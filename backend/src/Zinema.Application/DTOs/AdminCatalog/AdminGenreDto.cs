namespace Zinema.Application.DTOs.AdminCatalog;

public sealed record AdminGenreDto(
    Guid Id,
    string Name,
    string Slug,
    string? Description);
