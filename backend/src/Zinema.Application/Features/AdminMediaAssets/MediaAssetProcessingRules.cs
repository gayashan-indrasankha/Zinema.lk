namespace Zinema.Application.Features.AdminMediaAssets;

public static class MediaAssetProcessingRules
{
    public const string SourceVideoAssetType = "video-source";

    public static bool ShouldQueueUploadedAsset(string assetType, string contentType)
    {
        return IsSourceVideoAssetType(assetType) && IsVideoContentType(contentType);
    }

    private static bool IsSourceVideoAssetType(string assetType)
    {
        return string.Equals(
            assetType?.Trim(),
            SourceVideoAssetType,
            StringComparison.OrdinalIgnoreCase);
    }

    private static bool IsVideoContentType(string contentType)
    {
        return contentType?.Trim().StartsWith("video/", StringComparison.OrdinalIgnoreCase) == true;
    }
}
