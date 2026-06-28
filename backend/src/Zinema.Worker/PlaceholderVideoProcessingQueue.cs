using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Worker;

public sealed class PlaceholderVideoProcessingQueue(
    ILogger<PlaceholderVideoProcessingQueue> logger) : IVideoProcessingQueue
{
    public Task EnqueueAsync(
        Guid processingJobId,
        CancellationToken cancellationToken = default)
    {
        logger.LogDebug(
            "Video processing queue placeholder received job {ProcessingJobId}.",
            processingJobId);

        return Task.CompletedTask;
    }
}
