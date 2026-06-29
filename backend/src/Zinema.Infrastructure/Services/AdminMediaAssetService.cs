using Microsoft.EntityFrameworkCore;
using Zinema.Application.Common.Errors;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.AdminMediaAssets;
using Zinema.Application.DTOs.Catalog;
using Zinema.Application.Features.AdminMediaAssets;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;
using Zinema.Infrastructure.Persistence;

namespace Zinema.Infrastructure.Services;

public sealed class AdminMediaAssetService(
    AppDbContext dbContext,
    IObjectStorageService objectStorageService) : IAdminMediaAssetService
{
    public async Task<PagedResultDto<AdminMediaAssetDto>> GetMediaAssetsAsync(
        GetAdminMediaAssetsQuery query,
        CancellationToken cancellationToken = default)
    {
        var mediaAssetsQuery = dbContext.MediaAssets.AsNoTracking();

        if (!string.IsNullOrWhiteSpace(query.AssetType))
        {
            mediaAssetsQuery = mediaAssetsQuery.Where(asset =>
                EF.Functions.ILike(asset.AssetType, query.AssetType));
        }

        if (query.Status.HasValue)
        {
            mediaAssetsQuery = mediaAssetsQuery.Where(asset => asset.Status == query.Status.Value);
        }

        if (query.MovieId.HasValue)
        {
            mediaAssetsQuery = mediaAssetsQuery.Where(asset => asset.MovieId == query.MovieId.Value);
        }

        if (query.SeriesId.HasValue)
        {
            mediaAssetsQuery = mediaAssetsQuery.Where(asset => asset.SeriesId == query.SeriesId.Value);
        }

        if (query.EpisodeId.HasValue)
        {
            mediaAssetsQuery = mediaAssetsQuery.Where(asset => asset.EpisodeId == query.EpisodeId.Value);
        }

        mediaAssetsQuery = mediaAssetsQuery
            .OrderByDescending(asset => asset.CreatedAt)
            .ThenBy(asset => asset.Title);

        var totalCount = await mediaAssetsQuery.CountAsync(cancellationToken);
        var skip = (query.Page - 1) * query.PageSize;

        var items = await SelectDto(mediaAssetsQuery)
            .Skip(skip)
            .Take(query.PageSize)
            .ToListAsync(cancellationToken);

        return new PagedResultDto<AdminMediaAssetDto>(
            items,
            query.Page,
            query.PageSize,
            totalCount);
    }

    public async Task<Result<AdminMediaAssetDto>> GetMediaAssetByIdAsync(
        Guid id,
        CancellationToken cancellationToken = default)
    {
        var mediaAsset = await SelectDto(dbContext.MediaAssets.AsNoTracking())
            .FirstOrDefaultAsync(asset => asset.Id == id, cancellationToken);

        return mediaAsset is null
            ? Result<AdminMediaAssetDto>.Failure(AdminMediaAssetErrors.MediaAssetNotFound(id))
            : Result<AdminMediaAssetDto>.Success(mediaAsset);
    }

    public async Task<Result<AdminMediaAssetDto>> CreateMediaAssetAsync(
        CreateMediaAssetCommand command,
        CancellationToken cancellationToken = default)
    {
        var validation = ValidateCommand(command);
        if (validation is not null)
        {
            return Result<AdminMediaAssetDto>.Failure(validation);
        }

        var storageKey = NormalizeRequired(command.StorageKey);
        if (await StorageKeyExistsAsync(storageKey, excludedMediaAssetId: null, cancellationToken))
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

        var mediaAsset = new MediaAsset
        {
            Title = NormalizeRequired(command.Title),
            AssetType = NormalizeAssetType(command.AssetType),
            ContentType = NormalizeRequired(command.ContentType),
            FileName = NormalizeRequired(command.FileName),
            StorageKey = storageKey,
            PublicUrl = NormalizePublicUrl(command.PublicUrl, storageKey),
            FileSizeBytes = command.FileSizeBytes,
            Status = command.Status,
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

    public async Task<Result<AdminMediaAssetDto>> UpdateMediaAssetAsync(
        UpdateMediaAssetCommand command,
        CancellationToken cancellationToken = default)
    {
        var mediaAsset = await dbContext.MediaAssets
            .FirstOrDefaultAsync(asset => asset.Id == command.Id, cancellationToken);

        if (mediaAsset is null)
        {
            return Result<AdminMediaAssetDto>.Failure(
                AdminMediaAssetErrors.MediaAssetNotFound(command.Id));
        }

        var validation = ValidateCommand(command);
        if (validation is not null)
        {
            return Result<AdminMediaAssetDto>.Failure(validation);
        }

        var storageKey = NormalizeRequired(command.StorageKey);
        if (await StorageKeyExistsAsync(storageKey, mediaAsset.Id, cancellationToken))
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

        mediaAsset.Title = NormalizeRequired(command.Title);
        mediaAsset.AssetType = NormalizeAssetType(command.AssetType);
        mediaAsset.ContentType = NormalizeRequired(command.ContentType);
        mediaAsset.FileName = NormalizeRequired(command.FileName);
        mediaAsset.StorageKey = storageKey;
        mediaAsset.PublicUrl = NormalizePublicUrl(command.PublicUrl, storageKey);
        mediaAsset.FileSizeBytes = command.FileSizeBytes;
        mediaAsset.Status = command.Status;
        mediaAsset.MovieId = command.MovieId;
        mediaAsset.SeriesId = command.SeriesId;
        mediaAsset.EpisodeId = command.EpisodeId;
        mediaAsset.CollectionId = command.CollectionId;

        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<AdminMediaAssetDto>.Success(
            await GetMediaAssetDtoAsync(mediaAsset.Id, cancellationToken));
    }

    public async Task<Result> DeleteMediaAssetAsync(
        Guid id,
        CancellationToken cancellationToken = default)
    {
        var mediaAsset = await dbContext.MediaAssets
            .FirstOrDefaultAsync(asset => asset.Id == id, cancellationToken);

        if (mediaAsset is null)
        {
            return Result.Failure(AdminMediaAssetErrors.MediaAssetNotFound(id));
        }

        mediaAsset.Status = MediaStatus.Archived;
        await dbContext.SaveChangesAsync(cancellationToken);

        return Result.Success();
    }

    private async Task<AdminMediaAssetDto> GetMediaAssetDtoAsync(
        Guid id,
        CancellationToken cancellationToken)
    {
        return await SelectDto(dbContext.MediaAssets.AsNoTracking())
            .FirstAsync(asset => asset.Id == id, cancellationToken);
    }

    private static IQueryable<AdminMediaAssetDto> SelectDto(IQueryable<MediaAsset> query)
    {
        return query.Select(asset => new AdminMediaAssetDto(
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
            asset.UpdatedAt));
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
        Guid? excludedMediaAssetId,
        CancellationToken cancellationToken)
    {
        return dbContext.MediaAssets.AnyAsync(
            asset => asset.StorageKey == storageKey &&
                     (!excludedMediaAssetId.HasValue || asset.Id != excludedMediaAssetId.Value),
            cancellationToken);
    }

    private Error? ValidateCommand(CreateMediaAssetCommand command)
    {
        return AdminMediaAssetValidation.ValidateMetadata(
            command.Title,
            command.AssetType,
            command.ContentType,
            command.FileName,
            command.StorageKey,
            command.PublicUrl,
            command.FileSizeBytes,
            command.MovieId,
            command.SeriesId,
            command.EpisodeId,
            command.CollectionId);
    }

    private Error? ValidateCommand(UpdateMediaAssetCommand command)
    {
        return AdminMediaAssetValidation.ValidateMetadata(
            command.Title,
            command.AssetType,
            command.ContentType,
            command.FileName,
            command.StorageKey,
            command.PublicUrl,
            command.FileSizeBytes,
            command.MovieId,
            command.SeriesId,
            command.EpisodeId,
            command.CollectionId);
    }

    private string? NormalizePublicUrl(string? publicUrl, string storageKey)
    {
        return NormalizeOptional(publicUrl) ?? objectStorageService.BuildPublicUrl(storageKey);
    }

    private static string NormalizeAssetType(string value)
    {
        return NormalizeRequired(value).ToLowerInvariant();
    }

    private static string NormalizeRequired(string value)
    {
        return value.Trim();
    }

    private static string? NormalizeOptional(string? value)
    {
        return string.IsNullOrWhiteSpace(value) ? null : value.Trim();
    }
}
