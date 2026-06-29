namespace Zinema.Application.Features.VideoProcessingJobs;

public sealed record FfmpegAvailabilityDto(
    bool IsPathConfigured,
    bool IsAvailable,
    string? FfmpegPath,
    string? Version,
    string Message);
