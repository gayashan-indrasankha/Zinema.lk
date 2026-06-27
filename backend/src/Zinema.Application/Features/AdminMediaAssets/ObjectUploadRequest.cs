namespace Zinema.Application.Features.AdminMediaAssets;

public sealed record ObjectUploadRequest(
    Stream Content,
    string StorageKey,
    string ContentType,
    long ContentLength);
