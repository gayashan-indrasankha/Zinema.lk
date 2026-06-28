using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Zinema.Api.Contracts.VideoProcessingJobs;
using Zinema.Application.Common.Errors;
using Zinema.Application.DTOs.Catalog;
using Zinema.Application.DTOs.VideoProcessingJobs;
using Zinema.Application.Features.Auth;
using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Api.Controllers;

[ApiController]
[Route("api/admin")]
[Authorize(Roles = AuthRoles.Admin)]
public sealed class AdminVideoProcessingJobsController(
    IVideoProcessingJobService videoProcessingJobService) : ControllerBase
{
    [HttpPost("media-assets/{id:guid}/processing-jobs")]
    [ProducesResponseType(typeof(VideoProcessingJobDto), StatusCodes.Status201Created)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    public async Task<ActionResult<VideoProcessingJobDto>> Create(
        Guid id,
        CancellationToken cancellationToken)
    {
        var result = await videoProcessingJobService.CreateProcessingJobAsync(
            new CreateVideoProcessingJobCommand(id),
            cancellationToken);

        return result.IsSuccess
            ? Created($"/api/admin/processing-jobs/{result.Value.Id}", result.Value)
            : ToProblem(result.Error);
    }

    [HttpGet("processing-jobs")]
    [ProducesResponseType(typeof(PagedResultDto<VideoProcessingJobDto>), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    public async Task<ActionResult<PagedResultDto<VideoProcessingJobDto>>> GetProcessingJobs(
        [FromQuery] VideoProcessingJobSearchRequest request,
        CancellationToken cancellationToken)
    {
        var query = request.ToQuery();
        if (query.IsFailure)
        {
            return ToProblem(query.Error);
        }

        var result = await videoProcessingJobService.GetProcessingJobsAsync(
            query.Value,
            cancellationToken);

        return Ok(result);
    }

    [HttpGet("processing-jobs/{id:guid}")]
    [ProducesResponseType(typeof(VideoProcessingJobDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    public async Task<ActionResult<VideoProcessingJobDto>> GetProcessingJob(
        Guid id,
        CancellationToken cancellationToken)
    {
        var result = await videoProcessingJobService.GetProcessingJobByIdAsync(
            id,
            cancellationToken);

        return result.IsSuccess ? Ok(result.Value) : ToProblem(result.Error);
    }

    [HttpPatch("processing-jobs/{id:guid}/cancel")]
    [ProducesResponseType(typeof(VideoProcessingJobDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    public async Task<ActionResult<VideoProcessingJobDto>> Cancel(
        Guid id,
        CancellationToken cancellationToken)
    {
        var result = await videoProcessingJobService.CancelProcessingJobAsync(id, cancellationToken);

        return result.IsSuccess ? Ok(result.Value) : ToProblem(result.Error);
    }

    private ActionResult ToProblem(Error error)
    {
        var statusCode = GetStatusCode(error.Code);
        return Problem(title: error.Message, detail: error.Code, statusCode: statusCode);
    }

    private static int GetStatusCode(string errorCode)
    {
        if (errorCode.Contains("NotFound", StringComparison.OrdinalIgnoreCase))
        {
            return StatusCodes.Status404NotFound;
        }

        return StatusCodes.Status400BadRequest;
    }
}
