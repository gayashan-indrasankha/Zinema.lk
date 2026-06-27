namespace Zinema.Application.Features.AdminMediaAssets;

public sealed class ObjectStorageOptions
{
    public const string SectionName = "ObjectStorage";

    public string Endpoint { get; init; } = string.Empty;

    public string BucketName { get; init; } = "zinema-media";

    public string? PublicBaseUrl { get; init; }
}
