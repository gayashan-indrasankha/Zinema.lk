namespace Zinema.Application.Features.VideoProcessingJobs;

public enum VideoProcessingExecutionStatus
{
    ExecutionDisabled = 0,
    FfmpegUnavailable = 1,
    ValidationFailed = 2,
    Succeeded = 3,
    Failed = 4
}
