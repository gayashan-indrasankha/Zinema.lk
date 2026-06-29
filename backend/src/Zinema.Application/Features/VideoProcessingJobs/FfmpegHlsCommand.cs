namespace Zinema.Application.Features.VideoProcessingJobs;

public sealed record FfmpegHlsCommand(
    Guid ProcessingJobId,
    string FfmpegPath,
    string SourcePath,
    HlsOutputPlan OutputPlan,
    IReadOnlyList<string> Arguments,
    string DisplayCommand);
