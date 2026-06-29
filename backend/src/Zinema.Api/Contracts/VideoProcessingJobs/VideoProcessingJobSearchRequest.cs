using Zinema.Application.Common.Results;
using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Api.Contracts.VideoProcessingJobs;

public sealed class VideoProcessingJobSearchRequest
{
    public int? Page { get; init; }

    public int? PageSize { get; init; }

    public string? Status { get; init; }

    public Guid? MediaAssetId { get; init; }

    public Result<GetVideoProcessingJobsQuery> ToQuery()
    {
        return GetVideoProcessingJobsQuery.Create(
            Page,
            PageSize,
            Status,
            MediaAssetId);
    }
}
