using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.AdminMediaAssets;
using Zinema.Application.DTOs.Catalog;

namespace Zinema.Application.Features.AdminMediaAssets;

public interface IAdminMediaAssetService
{
    Task<PagedResultDto<AdminMediaAssetDto>> GetMediaAssetsAsync(
        GetAdminMediaAssetsQuery query,
        CancellationToken cancellationToken = default);

    Task<Result<AdminMediaAssetDto>> GetMediaAssetByIdAsync(
        Guid id,
        CancellationToken cancellationToken = default);

    Task<Result<AdminMediaAssetDto>> CreateMediaAssetAsync(
        CreateMediaAssetCommand command,
        CancellationToken cancellationToken = default);

    Task<Result<AdminMediaAssetDto>> UpdateMediaAssetAsync(
        UpdateMediaAssetCommand command,
        CancellationToken cancellationToken = default);

    Task<Result> DeleteMediaAssetAsync(
        Guid id,
        CancellationToken cancellationToken = default);
}
