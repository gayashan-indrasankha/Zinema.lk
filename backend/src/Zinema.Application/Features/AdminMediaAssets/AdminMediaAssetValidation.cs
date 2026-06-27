using Zinema.Application.Common.Errors;

namespace Zinema.Application.Features.AdminMediaAssets;

public static class AdminMediaAssetValidation
{
    public static Error? ValidateMetadata(
        string title,
        string assetType,
        string contentType,
        string fileName,
        string storageKey,
        string? publicUrl,
        long? fileSizeBytes,
        Guid? movieId,
        Guid? seriesId,
        Guid? episodeId,
        Guid? collectionId)
    {
        if (string.IsNullOrWhiteSpace(title))
        {
            return AdminMediaAssetErrors.Validation("Media asset title is required.");
        }

        if (title.Trim().Length > 200)
        {
            return AdminMediaAssetErrors.Validation("Media asset title must be 200 characters or fewer.");
        }

        if (string.IsNullOrWhiteSpace(assetType))
        {
            return AdminMediaAssetErrors.Validation("Asset type is required.");
        }

        if (assetType.Trim().Length > 50)
        {
            return AdminMediaAssetErrors.Validation("Asset type must be 50 characters or fewer.");
        }

        if (string.IsNullOrWhiteSpace(contentType))
        {
            return AdminMediaAssetErrors.Validation("Content type is required.");
        }

        if (contentType.Trim().Length > 100)
        {
            return AdminMediaAssetErrors.Validation("Content type must be 100 characters or fewer.");
        }

        if (string.IsNullOrWhiteSpace(fileName))
        {
            return AdminMediaAssetErrors.Validation("File name is required.");
        }

        if (fileName.Trim().Length > 255)
        {
            return AdminMediaAssetErrors.Validation("File name must be 255 characters or fewer.");
        }

        if (string.IsNullOrWhiteSpace(storageKey))
        {
            return AdminMediaAssetErrors.Validation("Storage key is required.");
        }

        if (storageKey.Trim().Length > 1024)
        {
            return AdminMediaAssetErrors.Validation("Storage key must be 1024 characters or fewer.");
        }

        if (!string.IsNullOrWhiteSpace(publicUrl) && publicUrl.Trim().Length > 2048)
        {
            return AdminMediaAssetErrors.Validation("Public URL must be 2048 characters or fewer.");
        }

        if (fileSizeBytes is < 0)
        {
            return AdminMediaAssetErrors.Validation("File size bytes cannot be negative.");
        }

        if (!movieId.HasValue &&
            !seriesId.HasValue &&
            !episodeId.HasValue &&
            !collectionId.HasValue)
        {
            return AdminMediaAssetErrors.Validation("At least one related catalog item is required.");
        }

        return null;
    }
}
