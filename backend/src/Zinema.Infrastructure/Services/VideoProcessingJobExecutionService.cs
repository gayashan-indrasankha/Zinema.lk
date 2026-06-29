using Microsoft.Extensions.Logging;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoProcessingJobs;
using Zinema.Application.Features.VideoProcessingJobs;
using Zinema.Domain.Enums;

namespace Zinema.Infrastructure.Services;

public sealed class VideoProcessingJobExecutionService(
    IVideoProcessingQueue videoProcessingQueue,
    IVideoProcessingJobLifecycleService videoProcessingJobLifecycleService,
    IVideoProcessingService videoProcessingService,
    ILogger<VideoProcessingJobExecutionService> logger) : IVideoProcessingJobExecutionService
{
    public async Task<Result<VideoProcessingJobDto>> ExecuteAsync(
        VideoProcessingJobDto processingJob,
        CancellationToken cancellationToken = default)
    {
        var readyJob = await PrepareJobForProcessingAsync(processingJob, cancellationToken);
        if (readyJob.IsFailure)
        {
            return readyJob;
        }

        var processing = await videoProcessingService.ProcessAsync(
            readyJob.Value,
            cancellationToken);

        if (processing.IsFailure)
        {
            return await FailProcessingJobAsync(
                readyJob.Value.Id,
                processing.Error.Message,
                cancellationToken);
        }

        if (!processing.Value.Succeeded)
        {
            logger.LogInformation(
                "Video processing job {ProcessingJobId} did not complete: {Status} - {Message}",
                readyJob.Value.Id,
                processing.Value.Status,
                processing.Value.Message);

            return await FailProcessingJobAsync(
                readyJob.Value.Id,
                processing.Value.Message,
                cancellationToken);
        }

        return await videoProcessingJobLifecycleService.CompleteProcessingJobAsync(
            readyJob.Value.Id,
            cancellationToken);
    }

    private async Task<Result<VideoProcessingJobDto>> PrepareJobForProcessingAsync(
        VideoProcessingJobDto processingJob,
        CancellationToken cancellationToken)
    {
        if (!Enum.TryParse<VideoProcessingJobStatus>(
                processingJob.Status,
                ignoreCase: true,
                out var status))
        {
            return Result<VideoProcessingJobDto>.Failure(
                VideoProcessingJobErrors.Validation("Video processing job status is invalid."));
        }

        if (status == VideoProcessingJobStatus.Pending)
        {
            var enqueueResult = await videoProcessingQueue.EnqueueAsync(
                processingJob.Id,
                cancellationToken);

            if (enqueueResult.IsFailure)
            {
                return enqueueResult;
            }
        }
        else if (status != VideoProcessingJobStatus.Queued)
        {
            return Result<VideoProcessingJobDto>.Failure(
                VideoProcessingJobErrors.InvalidStateTransition(
                    $"Cannot execute a video processing job with status '{status}'."));
        }

        return await videoProcessingJobLifecycleService.StartProcessingJobAsync(
            processingJob.Id,
            cancellationToken);
    }

    private async Task<Result<VideoProcessingJobDto>> FailProcessingJobAsync(
        Guid processingJobId,
        string errorMessage,
        CancellationToken cancellationToken)
    {
        var safeMessage = NormalizeFailureMessage(errorMessage);

        var failResult = await videoProcessingJobLifecycleService.FailProcessingJobAsync(
            new FailVideoProcessingJobCommand(processingJobId, safeMessage),
            cancellationToken);

        if (failResult.IsFailure)
        {
            logger.LogWarning(
                "Video processing job execution could not fail job {ProcessingJobId}: {ErrorCode}",
                processingJobId,
                failResult.Error.Code);
        }

        return failResult;
    }

    private static string NormalizeFailureMessage(string errorMessage)
    {
        var message = string.IsNullOrWhiteSpace(errorMessage)
            ? "Video processing failed."
            : errorMessage.Trim();

        return message.Length <= 4000 ? message : message[..4000];
    }
}
