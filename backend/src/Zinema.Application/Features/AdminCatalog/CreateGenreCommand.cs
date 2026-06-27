namespace Zinema.Application.Features.AdminCatalog;

public sealed record CreateGenreCommand(
    string Name,
    string? Slug,
    string? Description);
