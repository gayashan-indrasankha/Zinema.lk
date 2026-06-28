namespace Zinema.Application.Features.VideoProcessingJobs;

public interface IFfmpegAvailabilityChecker
{
    Task<FfmpegAvailabilityDto> CheckAvailabilityAsync(
        CancellationToken cancellationToken = default);
}
