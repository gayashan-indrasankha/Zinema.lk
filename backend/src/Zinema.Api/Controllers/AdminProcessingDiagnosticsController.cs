using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Zinema.Application.Features.Auth;
using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Api.Controllers;

[ApiController]
[Route("api/admin/processing")]
[Authorize(Roles = AuthRoles.Admin)]
public sealed class AdminProcessingDiagnosticsController(
    IFfmpegAvailabilityChecker ffmpegAvailabilityChecker) : ControllerBase
{
    [HttpGet("ffmpeg/status")]
    [ProducesResponseType(typeof(FfmpegAvailabilityDto), StatusCodes.Status200OK)]
    public async Task<ActionResult<FfmpegAvailabilityDto>> GetFfmpegStatus(
        CancellationToken cancellationToken)
    {
        var result = await ffmpegAvailabilityChecker.CheckAvailabilityAsync(cancellationToken);
        return Ok(result);
    }
}
