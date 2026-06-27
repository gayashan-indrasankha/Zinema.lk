using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Options;
using Zinema.Application.Common.Errors;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.AdminMediaAssets;
using Zinema.Application.Features.AdminMediaAssets;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;
using Zinema.Infrastructure.Persistence;

namespace Zinema.Infrastructure.Services;

public sealed class AdminMediaAssetUploadService(
    AppDbContext dbContext,
    IObjectStorageService objectStorageService,
    IOptions<MediaUploadOptions> uploadOptions) : IAdminMediaAssetUploadService
{
    public async Task<Result<AdminMediaAssetDto>> UploadMediaAssetAsync(
        UploadMediaAssetCommand command,
        CancellationToken cancellationToken = default)
    {
        var contentType = MediaUploadValidation.NormalizeContentType(command.ContentType);
        var uploadValidation = MediaUploadValidation.ValidateUpload(
            command.FileName,
            contentType,
            command.FileSizeBytes,
            uploadOptions.Value);

        if (uploadValidation is not null)
        {
            return Result<AdminMediaAssetDto>.Failure(uploadValidation);
        }

        var storageKey = StorageKeyGenerator.Generate(
            command.FileName,
            DateTimeOffset.UtcNow,
            Guid.NewGuid());

        var metadataValidation = AdminMediaAssetValidation.ValidateMetadata(
            command.Title,
            command.AssetType,
            contentType,
            command.FileName,
            storageKey,
            publicUrl: null,
            command.FileSizeBytes,
            command.MovieId,
            command.SeriesId,
            command.EpisodeId,
            command.CollectionId);

        if (metadataValidation is not null)
        {
            return Result<AdminMediaAssetDto>.Failure(metadataValidation);
        }

        if (await StorageKeyExistsAsync(storageKey, cancellationToken))
        {
            return Result<AdminMediaAssetDto>.Failure(
                AdminMediaAssetErrors.DuplicateStorageKey(storageKey));
        }

        var linkValidation = await ValidateCatalogLinksAsync(
            command.MovieId,
            command.SeriesId,
            command.EpisodeId,
            command.CollectionId,
            cancellationToken);

        if (linkValidation is not null)
        {
            return Result<AdminMediaAssetDto>.Failure(linkValidation);
        }

        ObjectUploadResult uploadResult;
        try
        {
            uploadResult = await objectStorageService.UploadAsync(
                new ObjectUploadRequest(
                    command.Content,
                    storageKey,
                    contentType,
                    command.FileSizeBytes),
                cancellationToken);
        }
        catch
        {
            return Result<AdminMediaAssetDto>.Failure(
                AdminMediaAssetErrors.StorageUploadFailed());
        }

        var mediaAsset = new MediaAsset
        {
            Title = command.Title.Trim(),
            AssetType = command.AssetType.Trim().ToLowerInvariant(),
            ContentType = uploadResult.ContentType,
            FileName = command.FileName.Trim(),
            StorageKey = uploadResult.StorageKey,
            PublicUrl = uploadResult.PublicUrl,
            FileSizeBytes = uploadResult.FileSizeBytes,
            Status = MediaStatus.Uploaded,
            MovieId = command.MovieId,
            SeriesId = command.SeriesId,
            EpisodeId = command.EpisodeId,
            CollectionId = command.CollectionId
        };

        dbContext.MediaAssets.Add(mediaAsset);
        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<AdminMediaAssetDto>.Success(
            await GetMediaAssetDtoAsync(mediaAsset.Id, cancellationToken));
    }

    private async Task<AdminMediaAssetDto> GetMediaAssetDtoAsync(
        Guid id,
        CancellationToken cancellationToken)
    {
        return await dbContext.MediaAssets
            .AsNoTracking()
            .Where(asset => asset.Id == id)
            .Select(asset => new AdminMediaAssetDto(
                asset.Id,
                asset.Title,
                asset.AssetType,
                asset.ContentType,
                asset.FileName,
                asset.StorageKey,
                asset.PublicUrl,
                asset.FileSizeBytes,
                asset.Status.ToString(),
                asset.MovieId,
                asset.SeriesId,
                asset.EpisodeId,
                asset.CollectionId,
                asset.CreatedAt,
                asset.UpdatedAt))
            .FirstAsync(cancellationToken);
    }

    private async Task<Error?> ValidateCatalogLinksAsync(
        Guid? movieId,
        Guid? seriesId,
        Guid? episodeId,
        Guid? collectionId,
        CancellationToken cancellationToken)
    {
        if (movieId.HasValue &&
            !await dbContext.Movies.AnyAsync(movie => movie.Id == movieId.Value, cancellationToken))
        {
            return AdminMediaAssetErrors.MovieNotFound(movieId.Value);
        }

        if (seriesId.HasValue &&
            !await dbContext.Series.AnyAsync(series => series.Id == seriesId.Value, cancellationToken))
        {
            return AdminMediaAssetErrors.SeriesNotFound(seriesId.Value);
        }

        if (episodeId.HasValue &&
            !await dbContext.Episodes.AnyAsync(episode => episode.Id == episodeId.Value, cancellationToken))
        {
            return AdminMediaAssetErrors.EpisodeNotFound(episodeId.Value);
        }

        if (collectionId.HasValue &&
            !await dbContext.Collections.AnyAsync(collection => collection.Id == collectionId.Value, cancellationToken))
        {
            return AdminMediaAssetErrors.CollectionNotFound(collectionId.Value);
        }

        return null;
    }

    private Task<bool> StorageKeyExistsAsync(
        string storageKey,
        CancellationToken cancellationToken)
    {
        return dbContext.MediaAssets.AnyAsync(
            asset => asset.StorageKey == storageKey,
            cancellationToken);
    }
}
