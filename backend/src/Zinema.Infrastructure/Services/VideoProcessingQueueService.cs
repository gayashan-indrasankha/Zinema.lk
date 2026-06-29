using Microsoft.EntityFrameworkCore;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoProcessingJobs;
using Zinema.Application.Features.VideoProcessingJobs;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;
using Zinema.Infrastructure.Persistence;

namespace Zinema.Infrastructure.Services;

public sealed class VideoProcessingQueueService(AppDbContext dbContext) : IVideoProcessingQueue
{
    private const int DefaultMaxCount = 5;
    private const int HardMaxCount = 50;

    public async Task<Result<VideoProcessingJobDto>> EnqueueAsync(
        Guid processingJobId,
        CancellationToken cancellationToken = default)
    {
        var validation = VideoProcessingJobValidation.ValidateEnqueue(processingJobId);
        if (validation is not null)
        {
            return Result<VideoProcessingJobDto>.Failure(validation);
        }

        var job = await dbContext.VideoProcessingJobs
            .FirstOrDefaultAsync(item => item.Id == processingJobId, cancellationToken);

        if (job is null)
        {
            return Result<VideoProcessingJobDto>.Failure(
                VideoProcessingJobErrors.ProcessingJobNotFound(processingJobId));
        }

        validation = VideoProcessingJobValidation.ValidateEnqueue(job.Status);
        if (validation is not null)
        {
            return Result<VideoProcessingJobDto>.Failure(validation);
        }

        job.Status = VideoProcessingJobStatus.Queued;
        job.QueuedAt = DateTimeOffset.UtcNow;

        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<VideoProcessingJobDto>.Success(
            await GetProcessingJobDtoAsync(job.Id, cancellationToken));
    }

    public async Task<IReadOnlyList<VideoProcessingJobDto>> GetQueuedJobsAsync(
        int maxCount,
        CancellationToken cancellationToken = default)
    {
        var take = NormalizeMaxCount(maxCount);

        var queuedJobsQuery = dbContext.VideoProcessingJobs
            .AsNoTracking()
            .Where(job => job.Status == VideoProcessingJobStatus.Queued)
            .OrderBy(job => job.QueuedAt)
            .ThenBy(job => job.Id);

        return await SelectDto(queuedJobsQuery)
            .Take(take)
            .ToListAsync(cancellationToken);
    }

    private async Task<VideoProcessingJobDto> GetProcessingJobDtoAsync(
        Guid id,
        CancellationToken cancellationToken)
    {
        return await SelectDto(dbContext.VideoProcessingJobs.AsNoTracking())
            .FirstAsync(item => item.Id == id, cancellationToken);
    }

    private static IQueryable<VideoProcessingJobDto> SelectDto(
        IQueryable<VideoProcessingJob> query)
    {
        return query.Select(job => new VideoProcessingJobDto(
            job.Id,
            job.MediaAssetId,
            job.MediaAsset != null ? job.MediaAsset.Title : null,
            job.SourceStorageKey,
            job.OutputStoragePrefix,
            job.Status.ToString(),
            job.ErrorMessage,
            job.AttemptCount,
            job.QueuedAt,
            job.StartedAt,
            job.CompletedAt,
            job.CreatedAt,
            job.UpdatedAt));
    }

    private static int NormalizeMaxCount(int maxCount)
    {
        if (maxCount <= 0)
        {
            return DefaultMaxCount;
        }

        return Math.Min(maxCount, HardMaxCount);
    }
}
