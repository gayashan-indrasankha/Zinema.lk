using Zinema.Domain.Enums;

namespace Zinema.Application.Features.Catalog;

public sealed record GetMoviesQuery(
    int Page,
    int PageSize,
    string? Search,
    string? Genre,
    PublishStatus PublishStatus,
    string SortBy)
{
    public const int DefaultPage = 1;
    public const int DefaultPageSize = 20;
    public const int MaxPageSize = 100;
    public const string SortLatest = "latest";
    public const string SortTitle = "title";
    public const string SortYear = "year";

    public static GetMoviesQuery Create(
        int? page,
        int? pageSize,
        string? search,
        string? genre,
        string? publishStatus,
        string? sortBy)
    {
        return new GetMoviesQuery(
            NormalizePage(page),
            NormalizePageSize(pageSize),
            NormalizeText(search),
            NormalizeText(genre),
            NormalizePublishStatus(publishStatus),
            NormalizeSort(sortBy));
    }

    private static int NormalizePage(int? page)
    {
        return page is > 0 ? page.Value : DefaultPage;
    }

    private static int NormalizePageSize(int? pageSize)
    {
        if (pageSize is null or <= 0)
        {
            return DefaultPageSize;
        }

        return Math.Min(pageSize.Value, MaxPageSize);
    }

    private static string? NormalizeText(string? value)
    {
        return string.IsNullOrWhiteSpace(value)
            ? null
            : value.Trim();
    }

    private static PublishStatus NormalizePublishStatus(string? publishStatus)
    {
        return Enum.TryParse<PublishStatus>(publishStatus, ignoreCase: true, out var status)
            ? status
            : PublishStatus.Published;
    }

    private static string NormalizeSort(string? sortBy)
    {
        var normalized = NormalizeText(sortBy)?.ToLowerInvariant();

        return normalized is SortTitle or SortYear or SortLatest
            ? normalized
            : SortLatest;
    }
}
