namespace Zinema.Application.Features.VideoProcessingJobs;

public sealed class VideoProcessingOptions
{
    public const string SectionName = "VideoProcessing";

    public string FfmpegPath { get; set; } = string.Empty;

    public string OutputRoot { get; set; } = "media-output/hls";

    public bool EnableExecution { get; set; }

    public int HlsSegmentDurationSeconds { get; set; } = 6;
}
