using Zinema.Application.Common.Errors;

namespace Zinema.Application.Features.AdminMediaAssets;

public static class AdminMediaAssetErrors
{
    public static Error Validation(string message)
    {
        return Error.Create("AdminMediaAsset.Validation", message);
    }

    public static Error MediaAssetNotFound(Guid id)
    {
        return Error.Create("AdminMediaAsset.NotFound", $"Media asset '{id}' was not found.");
    }

    public static Error MovieNotFound(Guid id)
    {
        return Error.Create("AdminMediaAsset.MovieNotFound", $"Movie '{id}' was not found.");
    }

    public static Error SeriesNotFound(Guid id)
    {
        return Error.Create("AdminMediaAsset.SeriesNotFound", $"Series '{id}' was not found.");
    }

    public static Error EpisodeNotFound(Guid id)
    {
        return Error.Create("AdminMediaAsset.EpisodeNotFound", $"Episode '{id}' was not found.");
    }

    public static Error CollectionNotFound(Guid id)
    {
        return Error.Create("AdminMediaAsset.CollectionNotFound", $"Collection '{id}' was not found.");
    }

    public static Error DuplicateStorageKey(string storageKey)
    {
        return Error.Create(
            "AdminMediaAsset.StorageKeyConflict",
            $"Storage key '{storageKey}' is already used.");
    }
}
