namespace Zinema.Application.DTOs.Catalog;

public sealed record MediaAssetDto(
    Guid Id,
    string Title,
    string AssetType,
    string ContentType,
    string? PublicUrl,
    long? FileSizeBytes);
