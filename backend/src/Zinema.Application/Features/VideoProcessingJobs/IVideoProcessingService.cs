using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoProcessingJobs;

namespace Zinema.Application.Features.VideoProcessingJobs;

public interface IVideoProcessingService
{
    Task<Result<VideoProcessingExecutionResult>> ProcessAsync(
        VideoProcessingJobDto processingJob,
        CancellationToken cancellationToken = default);
}
