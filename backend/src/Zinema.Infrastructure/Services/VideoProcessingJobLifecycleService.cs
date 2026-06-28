using Microsoft.EntityFrameworkCore;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoProcessingJobs;
using Zinema.Application.Features.VideoProcessingJobs;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;
using Zinema.Infrastructure.Persistence;

namespace Zinema.Infrastructure.Services;

public sealed class VideoProcessingJobLifecycleService(AppDbContext dbContext)
    : IVideoProcessingJobLifecycleService
{
    public async Task<Result<VideoProcessingJobDto>> StartProcessingJobAsync(
        Guid id,
        CancellationToken cancellationToken = default)
    {
        var validation = VideoProcessingJobValidation.ValidateStart(id);
        if (validation is not null)
        {
            return Result<VideoProcessingJobDto>.Failure(validation);
        }

        var job = await dbContext.VideoProcessingJobs
            .FirstOrDefaultAsync(item => item.Id == id, cancellationToken);

        if (job is null)
        {
            return Result<VideoProcessingJobDto>.Failure(
                VideoProcessingJobErrors.ProcessingJobNotFound(id));
        }

        validation = VideoProcessingJobValidation.ValidateStart(job.Status);
        if (validation is not null)
        {
            return Result<VideoProcessingJobDto>.Failure(validation);
        }

        job.Status = VideoProcessingJobStatus.Processing;
        job.StartedAt = DateTimeOffset.UtcNow;
        job.CompletedAt = null;
        job.ErrorMessage = null;
        job.AttemptCount += 1;

        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<VideoProcessingJobDto>.Success(
            await GetProcessingJobDtoAsync(job.Id, cancellationToken));
    }

    public async Task<Result<VideoProcessingJobDto>> CompleteProcessingJobAsync(
        Guid id,
        CancellationToken cancellationToken = default)
    {
        var validation = VideoProcessingJobValidation.ValidateComplete(id);
        if (validation is not null)
        {
            return Result<VideoProcessingJobDto>.Failure(validation);
        }

        var job = await dbContext.VideoProcessingJobs
            .FirstOrDefaultAsync(item => item.Id == id, cancellationToken);

        if (job is null)
        {
            return Result<VideoProcessingJobDto>.Failure(
                VideoProcessingJobErrors.ProcessingJobNotFound(id));
        }

        validation = VideoProcessingJobValidation.ValidateComplete(job.Status);
        if (validation is not null)
        {
            return Result<VideoProcessingJobDto>.Failure(validation);
        }

        job.Status = VideoProcessingJobStatus.Completed;
        job.CompletedAt = DateTimeOffset.UtcNow;
        job.ErrorMessage = null;

        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<VideoProcessingJobDto>.Success(
            await GetProcessingJobDtoAsync(job.Id, cancellationToken));
    }

    public async Task<Result<VideoProcessingJobDto>> FailProcessingJobAsync(
        FailVideoProcessingJobCommand command,
        CancellationToken cancellationToken = default)
    {
        var validation = VideoProcessingJobValidation.ValidateFail(command);
        if (validation is not null)
        {
            return Result<VideoProcessingJobDto>.Failure(validation);
        }

        var job = await dbContext.VideoProcessingJobs
            .FirstOrDefaultAsync(item => item.Id == command.ProcessingJobId, cancellationToken);

        if (job is null)
        {
            return Result<VideoProcessingJobDto>.Failure(
                VideoProcessingJobErrors.ProcessingJobNotFound(command.ProcessingJobId));
        }

        validation = VideoProcessingJobValidation.ValidateFail(job.Status);
        if (validation is not null)
        {
            return Result<VideoProcessingJobDto>.Failure(validation);
        }

        job.Status = VideoProcessingJobStatus.Failed;
        job.CompletedAt = DateTimeOffset.UtcNow;
        job.ErrorMessage = command.ErrorMessage.Trim();

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
