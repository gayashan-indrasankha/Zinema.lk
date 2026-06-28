using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Worker;

public sealed class PlaceholderVideoProcessingJobRunner(
    ILogger<PlaceholderVideoProcessingJobRunner> logger,
    IVideoProcessingQueue videoProcessingQueue,
    IVideoProcessingJobLifecycleService videoProcessingJobLifecycleService,
    IVideoProcessingService videoProcessingService) : IVideoProcessingJobRunner
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
                "Video processing runner claimed job {ProcessingJobId}.",
                claimResult.Value.Id);

            var processingResult = await videoProcessingService.ProcessAsync(
                claimResult.Value,
                cancellationToken);

            if (processingResult.IsFailure)
            {
                await FailClaimedJobAsync(
                    claimResult.Value.Id,
                    processingResult.Error.Message,
                    cancellationToken);

                continue;
            }

            if (!processingResult.Value.Succeeded)
            {
                logger.LogInformation(
                    "Video processing job {ProcessingJobId} did not complete: {Status} - {Message}",
                    claimResult.Value.Id,
                    processingResult.Value.Status,
                    processingResult.Value.Message);

                await FailClaimedJobAsync(
                    claimResult.Value.Id,
                    processingResult.Value.Message,
                    cancellationToken);

                continue;
            }

            var completeResult = await videoProcessingJobLifecycleService.CompleteProcessingJobAsync(
                claimResult.Value.Id,
                cancellationToken);

            if (completeResult.IsFailure)
            {
                logger.LogWarning(
                    "Video processing runner could not complete job {ProcessingJobId}: {ErrorCode}",
                    claimResult.Value.Id,
                    completeResult.Error.Code);
            }
        }
    }

    private async Task FailClaimedJobAsync(
        Guid processingJobId,
        string errorMessage,
        CancellationToken cancellationToken)
    {
        var failResult = await videoProcessingJobLifecycleService.FailProcessingJobAsync(
            new FailVideoProcessingJobCommand(processingJobId, errorMessage),
            cancellationToken);

        if (failResult.IsFailure)
        {
            logger.LogWarning(
                "Video processing runner could not fail job {ProcessingJobId}: {ErrorCode}",
                processingJobId,
                failResult.Error.Code);
        }
    }
}
