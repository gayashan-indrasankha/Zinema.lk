using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Worker;

public sealed class PlaceholderVideoProcessingJobRunner(
    ILogger<PlaceholderVideoProcessingJobRunner> logger) : IVideoProcessingJobRunner
{
    public Task RunNextAsync(CancellationToken cancellationToken = default)
    {
        logger.LogDebug("Video processing runner placeholder is idle.");

        return Task.CompletedTask;
    }
}
