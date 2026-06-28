using Microsoft.AspNetCore.Mvc;
using Zinema.Application.Common.Errors;
using Zinema.Application.DTOs.VideoPlayback;
using Zinema.Application.Features.VideoPlayback;

namespace Zinema.Api.Controllers;

[ApiController]
[Route("api/videos")]
public sealed class VideoPlaybackController(
    IVideoPlaybackOutputService videoPlaybackOutputService) : ControllerBase
{
    [HttpGet("{videoId:guid}/playback")]
    [ProducesResponseType(typeof(VideoPlaybackOutputDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    public async Task<ActionResult<VideoPlaybackOutputDto>> GetPlaybackOutput(
        Guid videoId,
        CancellationToken cancellationToken)
    {
        var result = await videoPlaybackOutputService.GetPlaybackOutputAsync(
            videoId,
            cancellationToken);

        return result.IsSuccess ? Ok(result.Value) : ToProblem(result.Error);
    }

    private ActionResult ToProblem(Error error)
    {
        return Problem(
            title: error.Message,
            detail: error.Code,
            statusCode: StatusCodes.Status404NotFound);
    }
}
