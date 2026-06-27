using Microsoft.Extensions.Options;
using Zinema.Application.Features.AdminMediaAssets;

namespace Zinema.Infrastructure.Services;

public sealed class ObjectStorageService(IOptions<ObjectStorageOptions> options) : IObjectStorageService
{
    public string? BuildPublicUrl(string storageKey)
    {
        if (string.IsNullOrWhiteSpace(storageKey))
        {
            return null;
        }

        var baseUrl = GetPublicBaseUrl();
        if (baseUrl is null)
        {
            return null;
        }

        return $"{baseUrl}/{storageKey.Trim().TrimStart('/')}";
    }

    private string? GetPublicBaseUrl()
    {
        var value = options.Value;
        if (!string.IsNullOrWhiteSpace(value.PublicBaseUrl))
        {
            return value.PublicBaseUrl.Trim().TrimEnd('/');
        }

        if (string.IsNullOrWhiteSpace(value.Endpoint) ||
            string.IsNullOrWhiteSpace(value.BucketName))
        {
            return null;
        }

        return $"{value.Endpoint.Trim().TrimEnd('/')}/{value.BucketName.Trim().Trim('/')}";
    }
}
