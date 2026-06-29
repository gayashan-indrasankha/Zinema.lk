using Zinema.Application.Common.Results;
using Zinema.Domain.Enums;

namespace Zinema.Application.Features.AdminMediaAssets;

public sealed record GetAdminMediaAssetsQuery(
    int Page,
    int PageSize,
    string? AssetType,
    MediaStatus? Status,
    Guid? MovieId,
    Guid? SeriesId,
    Guid? EpisodeId)
{
    public const int DefaultPage = 1;
    public const int DefaultPageSize = 20;
    public const int MaxPageSize = 100;

    public static Result<GetAdminMediaAssetsQuery> Create(
        int? page,
        int? pageSize,
        string? assetType,
        string? status,
        Guid? movieId,
        Guid? seriesId,
        Guid? episodeId)
    {
        var parsedStatus = ParseStatus(status);
        if (parsedStatus.IsFailure)
        {
            return Result<GetAdminMediaAssetsQuery>.Failure(parsedStatus.Error);
        }

        return Result<GetAdminMediaAssetsQuery>.Success(new GetAdminMediaAssetsQuery(
            NormalizePage(page),
            NormalizePageSize(pageSize),
            NormalizeText(assetType)?.ToLowerInvariant(),
            parsedStatus.Value,
            movieId,
            seriesId,
            episodeId));
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

    private static Result<MediaStatus?> ParseStatus(string? status)
    {
        if (string.IsNullOrWhiteSpace(status))
        {
            return Result<MediaStatus?>.Success(null);
        }

        return Enum.TryParse<MediaStatus>(status.Trim(), ignoreCase: true, out var parsed)
            ? Result<MediaStatus?>.Success(parsed)
            : Result<MediaStatus?>.Failure(AdminMediaAssetErrors.Validation("Media status is invalid."));
    }
}
