namespace Zinema.Application.Features.AdminMediaAssets;

public sealed class MediaUploadOptions
{
    public const string SectionName = "MediaUpload";

    public long MaxFileSizeBytes { get; init; } = 500 * 1024 * 1024;

    public string[] AllowedContentTypes { get; init; } =
    [
        "image/jpeg",
        "image/png",
        "image/webp",
        "video/mp4"
    ];
}
