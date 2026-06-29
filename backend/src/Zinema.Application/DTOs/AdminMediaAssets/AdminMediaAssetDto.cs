namespace Zinema.Application.DTOs.AdminMediaAssets;

public sealed record AdminMediaAssetDto(
    Guid Id,
    string Title,
    string AssetType,
    string ContentType,
    string FileName,
    string StorageKey,
    string? PublicUrl,
    long? FileSizeBytes,
    string Status,
    Guid? MovieId,
    Guid? SeriesId,
    Guid? EpisodeId,
    Guid? CollectionId,
    DateTimeOffset CreatedAt,
    DateTimeOffset? UpdatedAt);
