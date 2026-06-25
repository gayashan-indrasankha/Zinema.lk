using Microsoft.AspNetCore.Mvc;
using Zinema.Api.Contracts.Catalog;
using Zinema.Application.DTOs.Catalog;
using Zinema.Application.Features.Catalog;

namespace Zinema.Api.Controllers;

[ApiController]
[Route("api/catalog")]
public sealed class CatalogController(ICatalogQueryService catalogQueryService) : ControllerBase
{
    [HttpGet("movies")]
    [ProducesResponseType(typeof(PagedResultDto<MovieListItemDto>), StatusCodes.Status200OK)]
    public async Task<ActionResult<PagedResultDto<MovieListItemDto>>> GetMovies(
        [FromQuery] MovieSearchRequest request,
        CancellationToken cancellationToken)
    {
        var movies = await catalogQueryService.GetMoviesAsync(
            request.ToQuery(),
            cancellationToken);

        return Ok(movies);
    }

    [HttpGet("movies/{slug}")]
    [ProducesResponseType(typeof(MovieDetailDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    public async Task<ActionResult<MovieDetailDto>> GetMovieBySlug(
        string slug,
        CancellationToken cancellationToken)
    {
        var result = await catalogQueryService.GetMovieBySlugAsync(
            GetMovieBySlugQuery.Create(slug),
            cancellationToken);

        if (result.IsFailure)
        {
            return Problem(
                title: result.Error.Message,
                detail: result.Error.Code,
                statusCode: StatusCodes.Status404NotFound);
        }

        return Ok(result.Value);
    }

    [HttpGet("genres")]
    [ProducesResponseType(typeof(IReadOnlyList<GenreDto>), StatusCodes.Status200OK)]
    public async Task<ActionResult<IReadOnlyList<GenreDto>>> GetGenres(
        CancellationToken cancellationToken)
    {
        var genres = await catalogQueryService.GetGenresAsync(
            GetGenresQuery.Active,
            cancellationToken);

        return Ok(genres);
    }
}
