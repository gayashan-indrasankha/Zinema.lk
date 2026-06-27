using Zinema.Application.Features.Catalog;

namespace Zinema.Api.Contracts.Catalog;

public sealed class MovieSearchRequest
{
    public int? Page { get; init; }

    public int? PageSize { get; init; }

    public string? Search { get; init; }

    public string? Genre { get; init; }

    public string? PublishStatus { get; init; }

    public string? SortBy { get; init; }

    public GetMoviesQuery ToQuery()
    {
        return GetMoviesQuery.Create(
            Page,
            PageSize,
            Search,
            Genre,
            PublishStatus,
            SortBy);
    }
}
