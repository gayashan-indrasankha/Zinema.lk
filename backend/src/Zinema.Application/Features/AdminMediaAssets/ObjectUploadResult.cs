namespace Zinema.Application.Features.AdminMediaAssets;

public sealed record ObjectUploadResult(
    string StorageKey,
    string? PublicUrl,
    long FileSizeBytes,
    string ContentType);
