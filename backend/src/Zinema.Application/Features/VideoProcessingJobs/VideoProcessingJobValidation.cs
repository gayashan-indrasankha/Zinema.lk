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
