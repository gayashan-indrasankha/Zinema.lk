using Microsoft.AspNetCore.Http;
using Zinema.Application.Common.Results;
using Zinema.Application.Features.AdminMediaAssets;

namespace Zinema.Api.Contracts.AdminMediaAssets;

public sealed class AdminMediaAssetUploadRequest
{
    public IFormFile? File { get; init; }

    public string Title { get; init; } = string.Empty;

    public string AssetType { get; init; } = string.Empty;

    public Guid? MovieId { get; init; }

    public Guid? SeriesId { get; init; }

    public Guid? EpisodeId { get; init; }

    public Guid? CollectionId { get; init; }

    public Result<UploadMediaAssetCommand> ToCommand(Stream content)
    {
        if (File is null)
        {
            return Result<UploadMediaAssetCommand>.Failure(
                AdminMediaAssetErrors.Validation("Uploaded file is required."));
        }

        return Result<UploadMediaAssetCommand>.Success(new UploadMediaAssetCommand(
            Title,
            AssetType,
            File.FileName,
            File.ContentType,
            File.Length,
            content,
            MovieId,
            SeriesId,
            EpisodeId,
            CollectionId));
    }
}
