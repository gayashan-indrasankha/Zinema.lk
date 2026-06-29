using Zinema.Application.Common.Errors;
using Zinema.Domain.Enums;

namespace Zinema.Application.Features.VideoProcessingJobs;

public static class VideoProcessingJobValidation
{
    public static Error? ValidateCreate(Guid mediaAssetId)
    {
        return mediaAssetId == Guid.Empty
            ? VideoProcessingJobErrors.Validation("Media asset ID is required.")
            : null;
    }

    public static Error? ValidateEnqueue(Guid processingJobId)
    {
        return processingJobId == Guid.Empty
            ? VideoProcessingJobErrors.Validation("Video processing job ID is required.")
            : null;
    }

    public static bool CanEnqueue(VideoProcessingJobStatus status)
    {
        return status is VideoProcessingJobStatus.Pending;
    }

    public static Error? ValidateEnqueue(VideoProcessingJobStatus status)
    {
        return CanEnqueue(status)
            ? null
            : VideoProcessingJobErrors.InvalidStateTransition(
                $"Cannot enqueue a video processing job with status '{status}'.");
    }

    public static bool CanStart(VideoProcessingJobStatus status)
    {
        return status is VideoProcessingJobStatus.Queued;
    }

    public static Error? ValidateStart(Guid processingJobId)
    {
        return processingJobId == Guid.Empty
            ? VideoProcessingJobErrors.Validation("Video processing job ID is required.")
            : null;
    }

    public static Error? ValidateStart(VideoProcessingJobStatus status)
    {
        return CanStart(status)
            ? null
            : VideoProcessingJobErrors.InvalidStateTransition(
                $"Cannot start a video processing job with status '{status}'.");
    }

    public static bool CanComplete(VideoProcessingJobStatus status)
    {
        return status is VideoProcessingJobStatus.Processing;
    }

    public static Error? ValidateComplete(Guid processingJobId)
    {
        return processingJobId == Guid.Empty
            ? VideoProcessingJobErrors.Validation("Video processing job ID is required.")
            : null;
    }

    public static Error? ValidateComplete(VideoProcessingJobStatus status)
    {
        return CanComplete(status)
            ? null
            : VideoProcessingJobErrors.InvalidStateTransition(
                $"Cannot complete a video processing job with status '{status}'.");
    }

    public static bool CanFail(VideoProcessingJobStatus status)
    {
        return status is VideoProcessingJobStatus.Processing;
    }

    public static Error? ValidateFail(FailVideoProcessingJobCommand command)
    {
        if (command.ProcessingJobId == Guid.Empty)
        {
            return VideoProcessingJobErrors.Validation("Video processing job ID is required.");
        }

        if (string.IsNullOrWhiteSpace(command.ErrorMessage))
        {
            return VideoProcessingJobErrors.Validation("Failure error message is required.");
        }

        return command.ErrorMessage.Length > 4000
            ? VideoProcessingJobErrors.Validation("Failure error message cannot exceed 4000 characters.")
            : null;
    }

    public static Error? ValidateFail(VideoProcessingJobStatus status)
    {
        return CanFail(status)
            ? null
            : VideoProcessingJobErrors.InvalidStateTransition(
                $"Cannot fail a video processing job with status '{status}'.");
    }

    public static bool CanCancel(VideoProcessingJobStatus status)
    {
        return status is VideoProcessingJobStatus.Pending or VideoProcessingJobStatus.Queued;
    }

    public static Error? ValidateCancel(VideoProcessingJobStatus status)
    {
        return CanCancel(status)
            ? null
            : VideoProcessingJobErrors.InvalidStateTransition(
                $"Cannot cancel a video processing job with status '{status}'.");
    }
}
