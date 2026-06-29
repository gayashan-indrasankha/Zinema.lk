namespace Zinema.Application.DTOs.VideoPlayback;

public sealed record VideoPlaybackOutputDto(
    Guid VideoId,
    bool IsPlayable,
    string Status,
    string? PlaylistPath,
    string? PlaybackUrl,
    string? Message);
