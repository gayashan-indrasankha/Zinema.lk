namespace Zinema.Application.Features.AdminMediaAssets;

public sealed record UploadMediaAssetCommand(
    string Title,
    string AssetType,
    string FileName,
    string ContentType,
    long FileSizeBytes,
    Stream Content,
    Guid? MovieId,
    Guid? SeriesId,
    Guid? EpisodeId,
    Guid? CollectionId);
