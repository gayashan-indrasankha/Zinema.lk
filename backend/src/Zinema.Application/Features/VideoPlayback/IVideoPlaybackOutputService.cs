using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoPlayback;

namespace Zinema.Application.Features.VideoPlayback;

public interface IVideoPlaybackOutputService
{
    Task<Result<VideoPlaybackOutputDto>> GetPlaybackOutputAsync(
        Guid videoId,
        CancellationToken cancellationToken = default);
}
