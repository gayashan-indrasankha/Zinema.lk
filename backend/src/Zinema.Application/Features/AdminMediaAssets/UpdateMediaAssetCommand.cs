using Zinema.Domain.Enums;

namespace Zinema.Application.Features.AdminMediaAssets;

public sealed record UpdateMediaAssetCommand(
    Guid Id,
    string Title,
    string AssetType,
    string ContentType,
    string FileName,
    string StorageKey,
    string? PublicUrl,
    long? FileSizeBytes,
    MediaStatus Status,
    Guid? MovieId,
    Guid? SeriesId,
    Guid? EpisodeId,
    Guid? CollectionId);
