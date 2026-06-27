using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.AdminCatalog;

namespace Zinema.Application.Features.AdminCatalog;

public interface IAdminCatalogService
{
    Task<Result<AdminMovieDto>> CreateMovieAsync(
        CreateMovieCommand command,
        CancellationToken cancellationToken = default);

    Task<Result<AdminMovieDto>> UpdateMovieAsync(
        UpdateMovieCommand command,
        CancellationToken cancellationToken = default);

    Task<Result> DeleteMovieAsync(
        Guid id,
        CancellationToken cancellationToken = default);

    Task<Result<AdminMovieDto>> PublishMovieAsync(
        Guid id,
        CancellationToken cancellationToken = default);

    Task<Result<AdminMovieDto>> UnpublishMovieAsync(
        Guid id,
        CancellationToken cancellationToken = default);

    Task<Result<AdminGenreDto>> CreateGenreAsync(
        CreateGenreCommand command,
        CancellationToken cancellationToken = default);

    Task<Result<AdminGenreDto>> UpdateGenreAsync(
        UpdateGenreCommand command,
        CancellationToken cancellationToken = default);

    Task<Result> DeleteGenreAsync(
        Guid id,
        CancellationToken cancellationToken = default);
}
