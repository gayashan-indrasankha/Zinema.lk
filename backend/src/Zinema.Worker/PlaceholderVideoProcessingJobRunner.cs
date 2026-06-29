using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Worker;

public sealed class PlaceholderVideoProcessingJobRunner(
    ILogger<PlaceholderVideoProcessingJobRunner> logger,
    IVideoProcessingQueue videoProcessingQueue,
    IVideoProcessingJobExecutionService videoProcessingJobExecutionService) : IVideoProcessingJobRunner
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
            var executionResult = await videoProcessingJobExecutionService.ExecuteAsync(
                queuedJob,
                cancellationToken);

            if (executionResult.IsFailure)
            {
                logger.LogWarning(
                    "Video processing runner could not execute job {ProcessingJobId}: {ErrorCode}",
                    queuedJob.Id,
                    executionResult.Error.Code);

                continue;
            }

            logger.LogInformation(
                "Video processing runner finished job {ProcessingJobId} with status {Status}.",
                executionResult.Value.Id,
                executionResult.Value.Status);
        }
    }
}
