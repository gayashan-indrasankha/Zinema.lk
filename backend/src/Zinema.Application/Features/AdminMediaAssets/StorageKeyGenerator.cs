using System.Globalization;
using System.IO;
using System.Text;
using System.Text.RegularExpressions;

namespace Zinema.Application.Features.AdminMediaAssets;

public static partial class StorageKeyGenerator
{
    public static string Generate(
        string fileName,
        DateTimeOffset timestamp,
        Guid uploadId)
    {
        var safeFileName = ToSafeFileName(fileName);

        return string.Create(
            CultureInfo.InvariantCulture,
            $"media-assets/{timestamp:yyyy}/{timestamp:MM}/{uploadId:N}-{safeFileName}");
    }

    public static string ToSafeFileName(string fileName)
    {
        var name = Path.GetFileName(fileName.Trim());
        var extension = Path.GetExtension(name).ToLowerInvariant();
        var nameWithoutExtension = Path.GetFileNameWithoutExtension(name);

        var safeName = NormalizeForStorageKey(nameWithoutExtension);
        var safeExtension = NormalizeExtension(extension);

        return $"{safeName}{safeExtension}";
    }

    private static string NormalizeForStorageKey(string value)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return "upload";
        }

        var normalized = value.Trim().Normalize(NormalizationForm.FormD);
        var builder = new StringBuilder(normalized.Length);

        foreach (var character in normalized)
        {
            var category = CharUnicodeInfo.GetUnicodeCategory(character);
            if (category != UnicodeCategory.NonSpacingMark)
            {
                builder.Append(character);
            }
        }

        var safeValue = builder
            .ToString()
            .Normalize(NormalizationForm.FormC)
            .ToLowerInvariant();

        safeValue = NonAlphaNumericRegex().Replace(safeValue, "-");
        safeValue = DuplicateDashRegex().Replace(safeValue, "-").Trim('-');

        return string.IsNullOrWhiteSpace(safeValue) ? "upload" : safeValue;
    }

    private static string NormalizeExtension(string extension)
    {
        if (string.IsNullOrWhiteSpace(extension))
        {
            return string.Empty;
        }

        var safeExtension = NonAlphaNumericRegex()
            .Replace(extension.TrimStart('.').ToLowerInvariant(), string.Empty);

        return string.IsNullOrWhiteSpace(safeExtension) ? string.Empty : $".{safeExtension}";
    }

    [GeneratedRegex("[^a-z0-9]+")]
    private static partial Regex NonAlphaNumericRegex();

    [GeneratedRegex("-+")]
    private static partial Regex DuplicateDashRegex();
}
