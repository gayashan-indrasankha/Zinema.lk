using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoProcessingJobs;

namespace Zinema.Application.Features.VideoProcessingJobs;

public interface IVideoProcessingJobLifecycleService
{
    Task<Result<VideoProcessingJobDto>> StartProcessingJobAsync(
        Guid id,
        CancellationToken cancellationToken = default);

    Task<Result<VideoProcessingJobDto>> CompleteProcessingJobAsync(
        Guid id,
        CancellationToken cancellationToken = default);

    Task<Result<VideoProcessingJobDto>> FailProcessingJobAsync(
        FailVideoProcessingJobCommand command,
        CancellationToken cancellationToken = default);
}
