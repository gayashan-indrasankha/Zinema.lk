namespace Zinema.Application.Features.AdminCatalog;

public sealed record UpdateGenreCommand(
    Guid Id,
    string Name,
    string? Slug,
    string? Description);
