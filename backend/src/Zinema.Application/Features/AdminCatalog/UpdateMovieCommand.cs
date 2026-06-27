using Zinema.Domain.Enums;

namespace Zinema.Application.Features.AdminCatalog;

public sealed record UpdateMovieCommand(
    Guid Id,
    string Title,
    string? Slug,
    string? Description,
    int? ReleaseYear,
    int? RuntimeMinutes,
    string? Language,
    IReadOnlyCollection<Guid> GenreIds,
    PublishStatus PublishStatus);
