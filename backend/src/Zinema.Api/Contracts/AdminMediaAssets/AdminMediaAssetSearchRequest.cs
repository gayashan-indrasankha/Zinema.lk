using Zinema.Application.Common.Results;
using Zinema.Application.Features.AdminMediaAssets;

namespace Zinema.Api.Contracts.AdminMediaAssets;

public sealed class AdminMediaAssetSearchRequest
{
    public int? Page { get; init; }

    public int? PageSize { get; init; }

    public string? AssetType { get; init; }

    public string? Status { get; init; }

    public Guid? MovieId { get; init; }

    public Guid? SeriesId { get; init; }

    public Guid? EpisodeId { get; init; }

    public Result<GetAdminMediaAssetsQuery> ToQuery()
    {
        return GetAdminMediaAssetsQuery.Create(
            Page,
            PageSize,
            AssetType,
            Status,
            MovieId,
            SeriesId,
            EpisodeId);
    }
}
