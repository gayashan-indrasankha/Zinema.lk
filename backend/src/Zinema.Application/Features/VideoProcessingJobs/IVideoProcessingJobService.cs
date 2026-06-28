using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.Catalog;
using Zinema.Application.DTOs.VideoProcessingJobs;

namespace Zinema.Application.Features.VideoProcessingJobs;

public interface IVideoProcessingJobService
{
    Task<Result<VideoProcessingJobDto>> CreateProcessingJobAsync(
        CreateVideoProcessingJobCommand command,
        CancellationToken cancellationToken = default);

    Task<PagedResultDto<VideoProcessingJobDto>> GetProcessingJobsAsync(
        GetVideoProcessingJobsQuery query,
        CancellationToken cancellationToken = default);

    Task<Result<VideoProcessingJobDto>> GetProcessingJobByIdAsync(
        Guid id,
        CancellationToken cancellationToken = default);

    Task<Result<VideoProcessingJobDto>> CancelProcessingJobAsync(
        Guid id,
        CancellationToken cancellationToken = default);
}
