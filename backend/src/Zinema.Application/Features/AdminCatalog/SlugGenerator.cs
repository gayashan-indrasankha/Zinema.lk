using System.Globalization;
using System.Text;
using System.Text.RegularExpressions;

namespace Zinema.Application.Features.AdminCatalog;

public static partial class SlugGenerator
{
    public static string Generate(string value)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return "item";
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

        var slug = builder
            .ToString()
            .Normalize(NormalizationForm.FormC)
            .ToLowerInvariant();

        slug = NonAlphaNumericRegex().Replace(slug, "-");
        slug = DuplicateDashRegex().Replace(slug, "-").Trim('-');

        return string.IsNullOrWhiteSpace(slug) ? "item" : slug;
    }

    [GeneratedRegex("[^a-z0-9]+")]
    private static partial Regex NonAlphaNumericRegex();

    [GeneratedRegex("-+")]
    private static partial Regex DuplicateDashRegex();
}
