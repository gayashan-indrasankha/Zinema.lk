namespace Zinema.Application.Features.VideoProcessingJobs;

public static class HlsOutputManifestValidation
{
    public static IReadOnlyList<string> Validate(
        HlsOutputPlan outputPlan,
        string relativePlaybackPath,
        string segmentRelativePathPattern)
    {
        var errors = new List<string>();

        if (outputPlan.ProcessingJobId == Guid.Empty)
        {
            errors.Add("Video processing job ID is required.");
        }

        AddRequiredPathError(errors, outputPlan.OutputRoot, "Output root is required.");
        AddRequiredPathError(errors, outputPlan.JobOutputDirectory, "Output directory is required.");
        AddRequiredPathError(errors, outputPlan.MasterPlaylistPath, "Master playlist path is required.");
        AddRequiredPathError(errors, outputPlan.SegmentPathPattern, "Segment path pattern is required.");
        AddRequiredPathError(errors, relativePlaybackPath, "Relative playback path is required.");
        AddRequiredPathError(errors, segmentRelativePathPattern, "Segment relative path pattern is required.");

        if (!string.IsNullOrWhiteSpace(outputPlan.MasterPlaylistFileName)
            && !outputPlan.MasterPlaylistFileName.EndsWith(".m3u8", StringComparison.OrdinalIgnoreCase))
        {
            errors.Add("Master playlist file must be an m3u8 file.");
        }

        ValidateRelativePath(errors, relativePlaybackPath, "Relative playback path");
        ValidateRelativePath(errors, segmentRelativePathPattern, "Segment relative path pattern");

        return errors;
    }

    private static void AddRequiredPathError(
        ICollection<string> errors,
        string value,
        string message)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            errors.Add(message);
        }
    }

    private static void ValidateRelativePath(
        ICollection<string> errors,
        string value,
        string label)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return;
        }

        if (Path.IsPathRooted(value))
        {
            errors.Add($"{label} must not be rooted.");
        }

        var segments = value.Split(['/', '\\'], StringSplitOptions.RemoveEmptyEntries);
        if (segments.Any(segment => segment == ".."))
        {
            errors.Add($"{label} must not contain traversal segments.");
        }
    }
}
