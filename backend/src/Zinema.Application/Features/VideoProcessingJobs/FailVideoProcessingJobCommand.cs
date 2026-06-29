namespace Zinema.Application.Features.VideoProcessingJobs;

public sealed record FailVideoProcessingJobCommand(
    Guid ProcessingJobId,
    string ErrorMessage);
