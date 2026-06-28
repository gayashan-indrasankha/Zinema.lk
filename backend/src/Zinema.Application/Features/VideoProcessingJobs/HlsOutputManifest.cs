namespace Zinema.Application.Features.VideoProcessingJobs;

public sealed record HlsOutputManifest(
    Guid ProcessingJobId,
    string OutputRoot,
    string OutputDirectory,
    string MasterPlaylistFileName,
    string MasterPlaylistPath,
    string RelativePlaybackPath,
    string SegmentFilePattern,
    string SegmentPathPattern,
    string SegmentRelativePathPattern,
    DateTimeOffset GeneratedAt,
    bool IsValid,
    IReadOnlyList<string> ValidationErrors);
