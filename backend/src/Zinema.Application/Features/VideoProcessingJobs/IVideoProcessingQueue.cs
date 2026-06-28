namespace Zinema.Application.Features.VideoProcessingJobs;

public interface IVideoProcessingQueue
{
    Task EnqueueAsync(Guid processingJobId, CancellationToken cancellationToken = default);
}
