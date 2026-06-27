namespace Zinema.Application.Features.AdminMediaAssets;

public sealed class ObjectStorageOptions
{
    public const string SectionName = "ObjectStorage";

    public string Endpoint { get; init; } = string.Empty;

    public string AccessKey { get; init; } = string.Empty;

    public string SecretKey { get; init; } = string.Empty;

    public string BucketName { get; init; } = "zinema-media";

    public bool UseSsl { get; init; }

    public bool EnsureBucketExists { get; init; } = true;

    public string? PublicBaseUrl { get; init; }
}
