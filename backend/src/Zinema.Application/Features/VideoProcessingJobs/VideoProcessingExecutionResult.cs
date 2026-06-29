namespace Zinema.Application.Features.VideoProcessingJobs;

public sealed record VideoProcessingExecutionResult(
    Guid ProcessingJobId,
    VideoProcessingExecutionStatus Status,
    bool WasExecuted,
    bool Succeeded,
    string Message,
    HlsOutputPlan? OutputPlan,
    FfmpegHlsCommand? Command,
    int? ExitCode,
    HlsOutputManifest? OutputManifest = null);
