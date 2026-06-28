using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoPlayback;
using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Application.Features.VideoPlayback;

public static class VideoPlaybackOutputFactory
{
    private const string DefaultPlaybackBasePath = "media-output/hls";
    private const string MasterPlaylistFileName = "master.m3u8";

    public static VideoPlaybackOutputDto NotPlayable(
        Guid videoId,
        string status,
        string message)
    {
        return new VideoPlaybackOutputDto(
            videoId,
            IsPlayable: false,
            status,
            PlaylistPath: null,
            PlaybackUrl: null,
            message);
    }

    public static Result<VideoPlaybackOutputDto> Playable(
        Guid videoId,
        string status,
        string playlistPath)
    {
        var playlist = NormalizeSafeRelativePath(playlistPath);
        if (playlist.IsFailure)
        {
            return Result<VideoPlaybackOutputDto>.Failure(playlist.Error);
        }

        return Result<VideoPlaybackOutputDto>.Success(new VideoPlaybackOutputDto(
            videoId,
            IsPlayable: true,
            status,
            playlist.Value,
            ToPlaybackUrl(playlist.Value),
            Message: null));
    }

    public static Result<VideoPlaybackOutputDto> FromManifest(
        Guid videoId,
        string status,
        HlsOutputManifest manifest,
        string playbackBasePath = DefaultPlaybackBasePath)
    {
        if (!manifest.IsValid || manifest.ValidationErrors.Count > 0)
        {
            return Result<VideoPlaybackOutputDto>.Failure(
                VideoPlaybackOutputErrors.Validation("HLS output manifest is not valid."));
        }

        var basePath = NormalizeSafeRelativePath(playbackBasePath);
        if (basePath.IsFailure)
        {
            return Result<VideoPlaybackOutputDto>.Failure(basePath.Error);
        }

        var relativePlaybackPath = NormalizeSafeRelativePath(manifest.RelativePlaybackPath);
        if (relativePlaybackPath.IsFailure)
        {
            return Result<VideoPlaybackOutputDto>.Failure(relativePlaybackPath.Error);
        }

        return Playable(
            videoId,
            status,
            CombineRelativePath(basePath.Value, relativePlaybackPath.Value));
    }

    public static Result<string> CreatePlaylistPathFromOutputPrefix(string outputStoragePrefix)
    {
        var outputPath = NormalizeSafeRelativePath(outputStoragePrefix);
        if (outputPath.IsFailure)
        {
            return outputPath;
        }

        return outputPath.Value.EndsWith(".m3u8", StringComparison.OrdinalIgnoreCase)
            ? outputPath
            : Result<string>.Success(CombineRelativePath(outputPath.Value, MasterPlaylistFileName));
    }

    private static Result<string> NormalizeSafeRelativePath(string value)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return Result<string>.Failure(
                VideoPlaybackOutputErrors.Validation("Playback path is required."));
        }

        var normalized = value.Trim()
            .Replace('\\', '/')
            .Trim('/');

        if (Path.IsPathRooted(value) || Path.IsPathRooted(normalized))
        {
            return Result<string>.Failure(
                VideoPlaybackOutputErrors.Validation("Playback path must be relative."));
        }

        if (Uri.TryCreate(value, UriKind.Absolute, out _))
        {
            return Result<string>.Failure(
                VideoPlaybackOutputErrors.Validation("Playback path must not be an absolute URI."));
        }

        var segments = normalized.Split('/', StringSplitOptions.RemoveEmptyEntries);
        if (segments.Length == 0 || segments.Any(segment => segment == ".."))
        {
            return Result<string>.Failure(
                VideoPlaybackOutputErrors.Validation("Playback path must not contain traversal segments."));
        }

        return Result<string>.Success(string.Join("/", segments));
    }

    private static string CombineRelativePath(string left, string right)
    {
        return $"{left.Trim('/')}/{right.Trim('/')}";
    }

    private static string ToPlaybackUrl(string playlistPath)
    {
        return $"/{playlistPath.TrimStart('/')}";
    }
}
