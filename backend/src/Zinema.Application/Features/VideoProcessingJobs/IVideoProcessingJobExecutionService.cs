using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoProcessingJobs;

namespace Zinema.Application.Features.VideoProcessingJobs;

public interface IVideoProcessingJobExecutionService
{
    Task<Result<VideoProcessingJobDto>> ExecuteAsync(
        VideoProcessingJobDto processingJob,
        CancellationToken cancellationToken = default);
}
