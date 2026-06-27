using Microsoft.Extensions.Options;
using Minio;
using Minio.DataModel.Args;
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

    public async Task<ObjectUploadResult> UploadAsync(
        ObjectUploadRequest request,
        CancellationToken cancellationToken = default)
    {
        var value = options.Value;
        var bucketName = GetBucketName(value);
        var storageKey = request.StorageKey.Trim().TrimStart('/');
        var client = CreateClient(value);

        if (value.EnsureBucketExists)
        {
            await EnsureBucketExistsAsync(client, bucketName, cancellationToken);
        }

        var putObjectArgs = new PutObjectArgs()
            .WithBucket(bucketName)
            .WithObject(storageKey)
            .WithStreamData(request.Content)
            .WithObjectSize(request.ContentLength)
            .WithContentType(request.ContentType);

        await client.PutObjectAsync(putObjectArgs, cancellationToken);

        return new ObjectUploadResult(
            storageKey,
            BuildPublicUrl(storageKey),
            request.ContentLength,
            request.ContentType);
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

        return $"{GetUrlEndpoint(value)}/{value.BucketName.Trim().Trim('/')}";
    }

    private static async Task EnsureBucketExistsAsync(
        IMinioClient client,
        string bucketName,
        CancellationToken cancellationToken)
    {
        var existsArgs = new BucketExistsArgs()
            .WithBucket(bucketName);

        var exists = await client.BucketExistsAsync(existsArgs, cancellationToken);
        if (exists)
        {
            return;
        }

        var makeBucketArgs = new MakeBucketArgs()
            .WithBucket(bucketName);

        await client.MakeBucketAsync(makeBucketArgs, cancellationToken);
    }

    private static IMinioClient CreateClient(ObjectStorageOptions value)
    {
        if (string.IsNullOrWhiteSpace(value.Endpoint))
        {
            throw new InvalidOperationException("Object storage endpoint is not configured.");
        }

        if (string.IsNullOrWhiteSpace(value.AccessKey) ||
            string.IsNullOrWhiteSpace(value.SecretKey))
        {
            throw new InvalidOperationException("Object storage credentials are not configured.");
        }

        return new MinioClient()
            .WithEndpoint(GetClientEndpoint(value.Endpoint))
            .WithCredentials(value.AccessKey, value.SecretKey)
            .WithSSL(value.UseSsl)
            .Build();
    }

    private static string GetBucketName(ObjectStorageOptions value)
    {
        if (string.IsNullOrWhiteSpace(value.BucketName))
        {
            throw new InvalidOperationException("Object storage bucket name is not configured.");
        }

        return value.BucketName.Trim();
    }

    private static string GetClientEndpoint(string endpoint)
    {
        var trimmed = endpoint.Trim().TrimEnd('/');

        return Uri.TryCreate(trimmed, UriKind.Absolute, out var uri)
            ? uri.Authority
            : trimmed;
    }

    private static string GetUrlEndpoint(ObjectStorageOptions value)
    {
        var endpoint = value.Endpoint.Trim().TrimEnd('/');
        if (endpoint.Contains("://", StringComparison.Ordinal))
        {
            return endpoint;
        }

        var scheme = value.UseSsl ? "https" : "http";
        return $"{scheme}://{endpoint}";
    }
}
