using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoProcessingJobs;

namespace Zinema.Application.Features.VideoProcessingJobs;

public interface IVideoProcessingQueue
{
    Task<Result<VideoProcessingJobDto>> EnqueueAsync(
        Guid processingJobId,
        CancellationToken cancellationToken = default);

    Task<IReadOnlyList<VideoProcessingJobDto>> GetQueuedJobsAsync(
        int maxCount,
        CancellationToken cancellationToken = default);
}
