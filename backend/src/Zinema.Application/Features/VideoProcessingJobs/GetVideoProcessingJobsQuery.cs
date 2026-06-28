using Zinema.Application.Common.Results;
using Zinema.Domain.Enums;

namespace Zinema.Application.Features.VideoProcessingJobs;

public sealed record GetVideoProcessingJobsQuery(
    int Page,
    int PageSize,
    VideoProcessingJobStatus? Status,
    Guid? MediaAssetId)
{
    public const int DefaultPage = 1;
    public const int DefaultPageSize = 20;
    public const int MaxPageSize = 100;

    public static Result<GetVideoProcessingJobsQuery> Create(
        int? page,
        int? pageSize,
        string? status,
        Guid? mediaAssetId)
    {
        var parsedStatus = ParseStatus(status);
        if (parsedStatus.IsFailure)
        {
            return Result<GetVideoProcessingJobsQuery>.Failure(parsedStatus.Error);
        }

        return Result<GetVideoProcessingJobsQuery>.Success(new GetVideoProcessingJobsQuery(
            NormalizePage(page),
            NormalizePageSize(pageSize),
            parsedStatus.Value,
            mediaAssetId));
    }

    private static int NormalizePage(int? page)
    {
        return page is > 0 ? page.Value : DefaultPage;
    }

    private static int NormalizePageSize(int? pageSize)
    {
        if (pageSize is null or <= 0)
        {
            return DefaultPageSize;
        }

        return Math.Min(pageSize.Value, MaxPageSize);
    }

    private static Result<VideoProcessingJobStatus?> ParseStatus(string? status)
    {
        if (string.IsNullOrWhiteSpace(status))
        {
            return Result<VideoProcessingJobStatus?>.Success(null);
        }

        return Enum.TryParse<VideoProcessingJobStatus>(status.Trim(), ignoreCase: true, out var parsed)
            ? Result<VideoProcessingJobStatus?>.Success(parsed)
            : Result<VideoProcessingJobStatus?>.Failure(
                VideoProcessingJobErrors.Validation("Video processing job status is invalid."));
    }
}
