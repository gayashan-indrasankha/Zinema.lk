using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.Catalog;

namespace Zinema.Application.Features.Catalog;

public interface ICatalogQueryService
{
    Task<PagedResultDto<MovieListItemDto>> GetMoviesAsync(
        GetMoviesQuery query,
        CancellationToken cancellationToken = default);

    Task<Result<MovieDetailDto>> GetMovieBySlugAsync(
        GetMovieBySlugQuery query,
        CancellationToken cancellationToken = default);

    Task<IReadOnlyList<GenreDto>> GetGenresAsync(
        GetGenresQuery query,
        CancellationToken cancellationToken = default);
}
