namespace Zinema.Application.Features.AdminMediaAssets;

public interface IObjectStorageService
{
    string? BuildPublicUrl(string storageKey);

    Task<ObjectUploadResult> UploadAsync(
        ObjectUploadRequest request,
        CancellationToken cancellationToken = default);
}
