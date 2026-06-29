using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoProcessingJobs;

namespace Zinema.Application.Features.VideoProcessingJobs;

public interface IFfmpegCommandBuilder
{
    Result<FfmpegHlsCommand> BuildHlsCommand(VideoProcessingJobDto processingJob);
}
