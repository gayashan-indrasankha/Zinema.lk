namespace Zinema.Application.Features.VideoProcessingJobs;

public sealed record HlsOutputPlan(
    Guid ProcessingJobId,
    string OutputRoot,
    string JobOutputDirectory,
    string MasterPlaylistFileName,
    string MasterPlaylistPath,
    string SegmentFilePattern,
    string SegmentPathPattern);
