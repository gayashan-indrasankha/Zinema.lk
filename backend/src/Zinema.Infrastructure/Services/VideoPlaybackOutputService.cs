using Microsoft.EntityFrameworkCore;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoPlayback;
using Zinema.Application.Features.VideoPlayback;
using Zinema.Domain.Enums;
using Zinema.Infrastructure.Persistence;

namespace Zinema.Infrastructure.Services;

public sealed class VideoPlaybackOutputService(AppDbContext dbContext)
    : IVideoPlaybackOutputService
{
    public async Task<Result<VideoPlaybackOutputDto>> GetPlaybackOutputAsync(
        Guid videoId,
        CancellationToken cancellationToken = default)
    {
        var mediaAssetExists = await dbContext.MediaAssets
            .AsNoTracking()
            .AnyAsync(asset => asset.Id == videoId, cancellationToken);

        if (!mediaAssetExists)
        {
            return Result<VideoPlaybackOutputDto>.Failure(
                VideoPlaybackOutputErrors.VideoNotFound(videoId));
        }

        var processingJob = await dbContext.VideoProcessingJobs
            .AsNoTracking()
            .Where(job => job.MediaAssetId == videoId)
            .OrderByDescending(job => job.CompletedAt)
            .ThenByDescending(job => job.QueuedAt)
            .ThenByDescending(job => job.CreatedAt)
            .Select(job => new
            {
                job.Status,
                job.OutputStoragePrefix,
                job.ErrorMessage
            })
            .FirstOrDefaultAsync(cancellationToken);

        if (processingJob is null)
        {
            return Result<VideoPlaybackOutputDto>.Success(
                VideoPlaybackOutputFactory.NotPlayable(
                    videoId,
                    "NotProcessed",
                    "Video processing has not started yet."));
        }

        var status = processingJob.Status.ToString();
        if (processingJob.Status != VideoProcessingJobStatus.Completed)
        {
            return Result<VideoPlaybackOutputDto>.Success(
                VideoPlaybackOutputFactory.NotPlayable(
                    videoId,
                    status,
                    GetNotCompletedMessage(processingJob.Status, processingJob.ErrorMessage)));
        }

        if (string.IsNullOrWhiteSpace(processingJob.OutputStoragePrefix))
        {
            return Result<VideoPlaybackOutputDto>.Success(
                VideoPlaybackOutputFactory.NotPlayable(
                    videoId,
                    status,
                    "Processed video output is not available yet."));
        }

        var playlistPath = VideoPlaybackOutputFactory.CreatePlaylistPathFromOutputPrefix(
            processingJob.OutputStoragePrefix);
        if (playlistPath.IsFailure)
        {
            return Result<VideoPlaybackOutputDto>.Success(
                VideoPlaybackOutputFactory.NotPlayable(
                    videoId,
                    "InvalidOutput",
                    "Processed video output path is not safe to expose."));
        }

        return VideoPlaybackOutputFactory.Playable(videoId, status, playlistPath.Value);
    }

    private static string GetNotCompletedMessage(
        VideoProcessingJobStatus status,
        string? errorMessage)
    {
        return status switch
        {
            VideoProcessingJobStatus.Pending => "Video processing is pending.",
            VideoProcessingJobStatus.Queued => "Video processing is queued.",
            VideoProcessingJobStatus.Processing => "Video processing is still running.",
            VideoProcessingJobStatus.Failed => string.IsNullOrWhiteSpace(errorMessage)
                ? "Video processing failed."
                : errorMessage,
            VideoProcessingJobStatus.Cancelled => "Video processing was cancelled.",
            _ => "Video is not playable yet."
        };
    }
}
