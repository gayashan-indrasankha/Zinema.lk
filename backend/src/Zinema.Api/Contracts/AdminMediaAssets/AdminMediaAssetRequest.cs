using Zinema.Application.Common.Results;
using Zinema.Application.Features.AdminMediaAssets;
using Zinema.Domain.Enums;

namespace Zinema.Api.Contracts.AdminMediaAssets;

public sealed class AdminMediaAssetRequest
{
    public string Title { get; init; } = string.Empty;

    public string AssetType { get; init; } = string.Empty;

    public string ContentType { get; init; } = string.Empty;

    public string FileName { get; init; } = string.Empty;

    public string StorageKey { get; init; } = string.Empty;

    public string? PublicUrl { get; init; }

    public long? FileSizeBytes { get; init; }

    public string? Status { get; init; }

    public Guid? MovieId { get; init; }

    public Guid? SeriesId { get; init; }

    public Guid? EpisodeId { get; init; }

    public Guid? CollectionId { get; init; }

    public Result<CreateMediaAssetCommand> ToCreateCommand()
    {
        var status = ParseStatus(Status);
        if (status.IsFailure)
        {
            return Result<CreateMediaAssetCommand>.Failure(status.Error);
        }

        return Result<CreateMediaAssetCommand>.Success(new CreateMediaAssetCommand(
            Title,
            AssetType,
            ContentType,
            FileName,
            StorageKey,
            PublicUrl,
            FileSizeBytes,
            status.Value,
            MovieId,
            SeriesId,
            EpisodeId,
            CollectionId));
    }

    public Result<UpdateMediaAssetCommand> ToUpdateCommand(Guid id)
    {
        var status = ParseStatus(Status);
        if (status.IsFailure)
        {
            return Result<UpdateMediaAssetCommand>.Failure(status.Error);
        }

        return Result<UpdateMediaAssetCommand>.Success(new UpdateMediaAssetCommand(
            id,
            Title,
            AssetType,
            ContentType,
            FileName,
            StorageKey,
            PublicUrl,
            FileSizeBytes,
            status.Value,
            MovieId,
            SeriesId,
            EpisodeId,
            CollectionId));
    }

    private static Result<MediaStatus> ParseStatus(string? value)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return Result<MediaStatus>.Success(MediaStatus.PendingUpload);
        }

        return Enum.TryParse<MediaStatus>(value.Trim(), ignoreCase: true, out var status)
            ? Result<MediaStatus>.Success(status)
            : Result<MediaStatus>.Failure(AdminMediaAssetErrors.Validation("Media status is invalid."));
    }
}
