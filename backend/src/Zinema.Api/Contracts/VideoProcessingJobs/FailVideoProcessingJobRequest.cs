using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Api.Contracts.VideoProcessingJobs;

public sealed class FailVideoProcessingJobRequest
{
    public string? ErrorMessage { get; init; }

    public FailVideoProcessingJobCommand ToCommand(Guid id)
    {
        return new FailVideoProcessingJobCommand(id, ErrorMessage ?? string.Empty);
    }
}
