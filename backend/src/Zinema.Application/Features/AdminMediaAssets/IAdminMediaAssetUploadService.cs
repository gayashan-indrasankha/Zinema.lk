using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.AdminMediaAssets;

namespace Zinema.Application.Features.AdminMediaAssets;

public interface IAdminMediaAssetUploadService
{
    Task<Result<AdminMediaAssetDto>> UploadMediaAssetAsync(
        UploadMediaAssetCommand command,
        CancellationToken cancellationToken = default);
}
