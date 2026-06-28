using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Worker;

public sealed class PlaceholderVideoProcessingJobRunner(
    ILogger<PlaceholderVideoProcessingJobRunner> logger,
    IVideoProcessingQueue videoProcessingQueue,
    IVideoProcessingJobLifecycleService videoProcessingJobLifecycleService) : IVideoProcessingJobRunner
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
            "Video processing runner placeholder observed {QueuedJobCount} queued job(s).",
            queuedJobs.Count);

        foreach (var queuedJob in queuedJobs)
        {
            var claimResult = await videoProcessingJobLifecycleService.StartProcessingJobAsync(
                queuedJob.Id,
                cancellationToken);

            if (claimResult.IsFailure)
            {
                logger.LogWarning(
                    "Video processing runner placeholder could not claim job {ProcessingJobId}: {ErrorCode}",
                    queuedJob.Id,
                    claimResult.Error.Code);

                continue;
            }

            logger.LogInformation(
                "Video processing runner placeholder claimed job {ProcessingJobId}. Real media processing is not implemented.",
                claimResult.Value.Id);
        }
    }
}
