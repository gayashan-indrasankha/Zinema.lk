using Zinema.Application.Common.Results;
using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Infrastructure.Services;

public sealed class HlsOutputManifestBuilder : IHlsOutputManifestBuilder
{
    public Result<HlsOutputManifest> BuildManifest(
        HlsOutputPlan outputPlan,
        DateTimeOffset? generatedAt = null)
    {
        var outputRoot = Path.GetFullPath(outputPlan.OutputRoot);
        var outputDirectory = Path.GetFullPath(outputPlan.JobOutputDirectory);
        var masterPlaylistPath = Path.GetFullPath(outputPlan.MasterPlaylistPath);
        var segmentPathPattern = Path.GetFullPath(outputPlan.SegmentPathPattern);

        if (!IsInsideRoot(outputDirectory, outputRoot)
            || !IsInsideRoot(masterPlaylistPath, outputRoot)
            || !IsInsideRoot(segmentPathPattern, outputRoot))
        {
            return Result<HlsOutputManifest>.Failure(
                VideoProcessingJobErrors.Validation("HLS output paths must stay inside the output root."));
        }

        var relativePlaybackPath = NormalizeRelativePath(
            Path.GetRelativePath(outputRoot, masterPlaylistPath));
        var segmentRelativePathPattern = NormalizeRelativePath(
            Path.GetRelativePath(outputRoot, segmentPathPattern));

        var validationErrors = HlsOutputManifestValidation.Validate(
            outputPlan,
            relativePlaybackPath,
            segmentRelativePathPattern);

        if (validationErrors.Count > 0)
        {
            return Result<HlsOutputManifest>.Failure(
                VideoProcessingJobErrors.Validation(string.Join(" ", validationErrors)));
        }

        return Result<HlsOutputManifest>.Success(new HlsOutputManifest(
            outputPlan.ProcessingJobId,
            outputRoot,
            outputDirectory,
            outputPlan.MasterPlaylistFileName,
            masterPlaylistPath,
            relativePlaybackPath,
            outputPlan.SegmentFilePattern,
            segmentPathPattern,
            segmentRelativePathPattern,
            generatedAt ?? DateTimeOffset.UtcNow,
            IsValid: true,
            ValidationErrors: []));
    }

    private static bool IsInsideRoot(string path, string root)
    {
        var normalizedRoot = root.TrimEnd(Path.DirectorySeparatorChar, Path.AltDirectorySeparatorChar)
            + Path.DirectorySeparatorChar;
        var normalizedPath = path.TrimEnd(Path.DirectorySeparatorChar, Path.AltDirectorySeparatorChar)
            + Path.DirectorySeparatorChar;

        return normalizedPath.StartsWith(normalizedRoot, StringComparison.OrdinalIgnoreCase);
    }

    private static string NormalizeRelativePath(string value)
    {
        return value.Replace(Path.DirectorySeparatorChar, '/')
            .Replace(Path.AltDirectorySeparatorChar, '/');
    }
}
