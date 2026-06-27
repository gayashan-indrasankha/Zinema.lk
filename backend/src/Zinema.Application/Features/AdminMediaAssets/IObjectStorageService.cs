namespace Zinema.Application.Features.AdminMediaAssets;

public interface IObjectStorageService
{
    string? BuildPublicUrl(string storageKey);
}
