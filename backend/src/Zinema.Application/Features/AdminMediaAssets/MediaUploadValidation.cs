using System.IO;
using Zinema.Application.Common.Errors;

namespace Zinema.Application.Features.AdminMediaAssets;

public static class MediaUploadValidation
{
    public static Error? ValidateUpload(
        string fileName,
        string contentType,
        long fileSizeBytes,
        MediaUploadOptions options)
    {
        if (fileSizeBytes <= 0)
        {
            return AdminMediaAssetErrors.Validation("Uploaded file cannot be empty.");
        }

        if (options.MaxFileSizeBytes > 0 && fileSizeBytes > options.MaxFileSizeBytes)
        {
            return AdminMediaAssetErrors.Validation(
                $"Uploaded file must be {options.MaxFileSizeBytes} bytes or smaller.");
        }

        if (IsDangerousFileName(fileName))
        {
            return AdminMediaAssetErrors.Validation("Uploaded file name is invalid.");
        }

        var normalizedContentType = NormalizeContentType(contentType);
        if (!options.AllowedContentTypes.Any(type =>
                string.Equals(type, normalizedContentType, StringComparison.OrdinalIgnoreCase)))
        {
            return AdminMediaAssetErrors.Validation("Uploaded file content type is not supported.");
        }

        return null;
    }

    public static bool IsDangerousFileName(string fileName)
    {
        if (string.IsNullOrWhiteSpace(fileName))
        {
            return true;
        }

        var trimmed = fileName.Trim();
        if (trimmed != Path.GetFileName(trimmed))
        {
            return true;
        }

        if (trimmed.Contains("..", StringComparison.Ordinal))
        {
            return true;
        }

        return trimmed.IndexOfAny(Path.GetInvalidFileNameChars()) >= 0;
    }

    public static string NormalizeContentType(string contentType)
    {
        return string.IsNullOrWhiteSpace(contentType)
            ? "application/octet-stream"
            : contentType.Trim().ToLowerInvariant();
    }
}
