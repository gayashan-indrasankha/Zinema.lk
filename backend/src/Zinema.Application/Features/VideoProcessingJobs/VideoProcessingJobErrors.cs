using Zinema.Application.Common.Errors;

namespace Zinema.Application.Features.VideoProcessingJobs;

public static class VideoProcessingJobErrors
{
    public static Error Validation(string message)
    {
        return Error.Create("VideoProcessingJob.Validation", message);
    }

    public static Error MediaAssetNotFound(Guid id)
    {
        return Error.Create("VideoProcessingJob.MediaAssetNotFound", $"Media asset '{id}' was not found.");
    }

    public static Error ProcessingJobNotFound(Guid id)
    {
        return Error.Create("VideoProcessingJob.NotFound", $"Video processing job '{id}' was not found.");
    }

    public static Error InvalidStateTransition(string message)
    {
        return Error.Create("VideoProcessingJob.InvalidStateTransition", message);
    }
}
