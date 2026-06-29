using Microsoft.EntityFrameworkCore;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.Catalog;
using Zinema.Application.DTOs.VideoProcessingJobs;
using Zinema.Application.Features.VideoProcessingJobs;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;
using Zinema.Infrastructure.Persistence;

namespace Zinema.Infrastructure.Services;

public sealed class VideoProcessingJobService(
    AppDbContext dbContext,
    IVideoProcessingQueue videoProcessingQueue) : IVideoProcessingJobService
{
    public async Task<Result<VideoProcessingJobDto>> CreateProcessingJobAsync(
        CreateVideoProcessingJobCommand command,
        CancellationToken cancellationToken = default)
    {
        var validation = VideoProcessingJobValidation.ValidateCreate(command.MediaAssetId);
        if (validation is not null)
        {
            return Result<VideoProcessingJobDto>.Failure(validation);
        }

        var mediaAsset = await dbContext.MediaAssets
            .AsNoTracking()
            .FirstOrDefaultAsync(asset => asset.Id == command.MediaAssetId, cancellationToken);

        if (mediaAsset is null)
        {
            return Result<VideoProcessingJobDto>.Failure(
                VideoProcessingJobErrors.MediaAssetNotFound(command.MediaAssetId));
        }

        var job = new VideoProcessingJob
        {
            MediaAssetId = mediaAsset.Id,
            Status = VideoProcessingJobStatus.Pending,
            SourceStorageKey = mediaAsset.StorageKey,
            QueuedAt = DateTimeOffset.UtcNow
        };

        dbContext.VideoProcessingJobs.Add(job);
        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<VideoProcessingJobDto>.Success(
            await GetProcessingJobDtoAsync(job.Id, cancellationToken));
    }

    public async Task<Result<VideoProcessingJobDto>> CreateAndEnqueueProcessingJobAsync(
        CreateVideoProcessingJobCommand command,
        CancellationToken cancellationToken = default)
    {
        var createdJob = await CreateProcessingJobAsync(command, cancellationToken);
        if (createdJob.IsFailure)
        {
            return createdJob;
        }

        return await EnqueueProcessingJobAsync(
            new EnqueueVideoProcessingJobCommand(createdJob.Value.Id),
            cancellationToken);
    }

    public async Task<PagedResultDto<VideoProcessingJobDto>> GetProcessingJobsAsync(
        GetVideoProcessingJobsQuery query,
        CancellationToken cancellationToken = default)
    {
        var jobsQuery = dbContext.VideoProcessingJobs.AsNoTracking();

        if (query.Status.HasValue)
        {
            jobsQuery = jobsQuery.Where(job => job.Status == query.Status.Value);
        }

        if (query.MediaAssetId.HasValue)
        {
            jobsQuery = jobsQuery.Where(job => job.MediaAssetId == query.MediaAssetId.Value);
        }

        jobsQuery = jobsQuery
            .OrderByDescending(job => job.QueuedAt)
            .ThenBy(job => job.Id);

        var totalCount = await jobsQuery.CountAsync(cancellationToken);
        var skip = (query.Page - 1) * query.PageSize;

        var items = await SelectDto(jobsQuery)
            .Skip(skip)
            .Take(query.PageSize)
            .ToListAsync(cancellationToken);

        return new PagedResultDto<VideoProcessingJobDto>(
            items,
            query.Page,
            query.PageSize,
            totalCount);
    }

    public async Task<Result<VideoProcessingJobDto>> GetProcessingJobByIdAsync(
        Guid id,
        CancellationToken cancellationToken = default)
    {
        var job = await SelectDto(dbContext.VideoProcessingJobs.AsNoTracking())
            .FirstOrDefaultAsync(item => item.Id == id, cancellationToken);

        return job is null
            ? Result<VideoProcessingJobDto>.Failure(VideoProcessingJobErrors.ProcessingJobNotFound(id))
            : Result<VideoProcessingJobDto>.Success(job);
    }

    public Task<Result<VideoProcessingJobDto>> EnqueueProcessingJobAsync(
        EnqueueVideoProcessingJobCommand command,
        CancellationToken cancellationToken = default)
    {
        var validation = VideoProcessingJobValidation.ValidateEnqueue(command.ProcessingJobId);
        return validation is not null
            ? Task.FromResult(Result<VideoProcessingJobDto>.Failure(validation))
            : videoProcessingQueue.EnqueueAsync(command.ProcessingJobId, cancellationToken);
    }

    public async Task<Result<VideoProcessingJobDto>> CancelProcessingJobAsync(
        Guid id,
        CancellationToken cancellationToken = default)
    {
        var job = await dbContext.VideoProcessingJobs
            .FirstOrDefaultAsync(item => item.Id == id, cancellationToken);

        if (job is null)
        {
            return Result<VideoProcessingJobDto>.Failure(
                VideoProcessingJobErrors.ProcessingJobNotFound(id));
        }

        var validation = VideoProcessingJobValidation.ValidateCancel(job.Status);
        if (validation is not null)
        {
            return Result<VideoProcessingJobDto>.Failure(validation);
        }

        job.Status = VideoProcessingJobStatus.Cancelled;
        job.CompletedAt = DateTimeOffset.UtcNow;

        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<VideoProcessingJobDto>.Success(
            await GetProcessingJobDtoAsync(job.Id, cancellationToken));
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
}
