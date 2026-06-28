using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Worker;

public sealed class PlaceholderVideoProcessingJobRunner(
    ILogger<PlaceholderVideoProcessingJobRunner> logger,
    IVideoProcessingQueue videoProcessingQueue) : IVideoProcessingJobRunner
{
    public async Task RunNextAsync(CancellationToken cancellationToken = default)
    {
        var queuedJobs = await videoProcessingQueue.GetQueuedJobsAsync(
            maxCount: 5,
            cancellationToken);

        if (queuedJobs.Count == 0)
        {
            logger.LogDebug("Video processing runner placeholder found no queued jobs.");
            return;
        }

        logger.LogInformation(
            "Video processing runner placeholder observed {QueuedJobCount} queued job(s). No media processing is executed.",
            queuedJobs.Count);
    }
}
