namespace Zinema.Application.Features.VideoProcessingJobs;

public interface IVideoProcessingJobRunner
{
    Task RunNextAsync(CancellationToken cancellationToken = default);
}
