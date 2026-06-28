using System.Globalization;
using Microsoft.Extensions.Options;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoProcessingJobs;
using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Infrastructure.Services;

public sealed class FfmpegHlsCommandBuilder(
    IOptions<VideoProcessingOptions> options) : IFfmpegCommandBuilder
{
    private const string MasterPlaylistFileName = "master.m3u8";
    private const string SegmentFilePattern = "segment_%03d.ts";

    public Result<FfmpegHlsCommand> BuildHlsCommand(VideoProcessingJobDto processingJob)
    {
        var videoProcessingOptions = options.Value;

        if (string.IsNullOrWhiteSpace(videoProcessingOptions.FfmpegPath))
        {
            return Result<FfmpegHlsCommand>.Failure(
                VideoProcessingJobErrors.Validation("FFmpeg path is required."));
        }

        if (string.IsNullOrWhiteSpace(processingJob.SourceStorageKey))
        {
            return Result<FfmpegHlsCommand>.Failure(
                VideoProcessingJobErrors.Validation("Source media path is required."));
        }

        if (HasTraversalSegment(processingJob.SourceStorageKey))
        {
            return Result<FfmpegHlsCommand>.Failure(
                VideoProcessingJobErrors.Validation("Source media path cannot contain traversal segments."));
        }

        var outputPlan = BuildOutputPlan(processingJob.Id, videoProcessingOptions.OutputRoot);
        if (outputPlan.IsFailure)
        {
            return Result<FfmpegHlsCommand>.Failure(outputPlan.Error);
        }

        var segmentDuration = Math.Max(1, videoProcessingOptions.HlsSegmentDurationSeconds);
        var arguments = new[]
        {
            "-hide_banner",
            "-nostdin",
            "-y",
            "-i",
            processingJob.SourceStorageKey,
            "-c:v",
            "h264",
            "-c:a",
            "aac",
            "-f",
            "hls",
            "-hls_time",
            segmentDuration.ToString(CultureInfo.InvariantCulture),
            "-hls_playlist_type",
            "vod",
            "-hls_segment_filename",
            outputPlan.Value.SegmentPathPattern,
            outputPlan.Value.MasterPlaylistPath
        };

        var command = new FfmpegHlsCommand(
            processingJob.Id,
            videoProcessingOptions.FfmpegPath.Trim(),
            processingJob.SourceStorageKey,
            outputPlan.Value,
            arguments,
            BuildDisplayCommand(videoProcessingOptions.FfmpegPath.Trim(), arguments));

        return Result<FfmpegHlsCommand>.Success(command);
    }

    private static Result<HlsOutputPlan> BuildOutputPlan(Guid processingJobId, string outputRoot)
    {
        if (processingJobId == Guid.Empty)
        {
            return Result<HlsOutputPlan>.Failure(
                VideoProcessingJobErrors.Validation("Video processing job ID is required."));
        }

        if (string.IsNullOrWhiteSpace(outputRoot))
        {
            return Result<HlsOutputPlan>.Failure(
                VideoProcessingJobErrors.Validation("Video processing output root is required."));
        }

        var fullOutputRoot = Path.GetFullPath(outputRoot.Trim());
        var jobDirectoryName = processingJobId.ToString("D");
        var jobOutputDirectory = Path.GetFullPath(Path.Combine(fullOutputRoot, jobDirectoryName));

        if (!IsInsideRoot(jobOutputDirectory, fullOutputRoot))
        {
            return Result<HlsOutputPlan>.Failure(
                VideoProcessingJobErrors.Validation("Video processing output path is invalid."));
        }

        var masterPlaylistPath = Path.Combine(jobOutputDirectory, MasterPlaylistFileName);
        var segmentPathPattern = Path.Combine(jobOutputDirectory, SegmentFilePattern);

        return Result<HlsOutputPlan>.Success(new HlsOutputPlan(
            processingJobId,
            fullOutputRoot,
            jobOutputDirectory,
            MasterPlaylistFileName,
            masterPlaylistPath,
            SegmentFilePattern,
            segmentPathPattern));
    }

    private static bool HasTraversalSegment(string value)
    {
        return value
            .Split(['/', '\\'], StringSplitOptions.RemoveEmptyEntries)
            .Any(segment => segment == "..");
    }

    private static bool IsInsideRoot(string path, string root)
    {
        var normalizedRoot = root.TrimEnd(Path.DirectorySeparatorChar, Path.AltDirectorySeparatorChar)
            + Path.DirectorySeparatorChar;
        var normalizedPath = path.TrimEnd(Path.DirectorySeparatorChar, Path.AltDirectorySeparatorChar)
            + Path.DirectorySeparatorChar;

        return normalizedPath.StartsWith(normalizedRoot, StringComparison.OrdinalIgnoreCase);
    }

    private static string BuildDisplayCommand(string ffmpegPath, IEnumerable<string> arguments)
    {
        return string.Join(
            " ",
            new[] { Quote(ffmpegPath) }.Concat(arguments.Select(Quote)));
    }

    private static string Quote(string value)
    {
        return value.Contains(' ', StringComparison.Ordinal)
            ? $"\"{value.Replace("\"", "\\\"", StringComparison.Ordinal)}\""
            : value;
    }
}
