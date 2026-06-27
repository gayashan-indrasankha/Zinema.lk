using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Zinema.Api.Contracts.AdminMediaAssets;
using Zinema.Application.Common.Errors;
using Zinema.Application.DTOs.AdminMediaAssets;
using Zinema.Application.DTOs.Catalog;
using Zinema.Application.Features.AdminMediaAssets;
using Zinema.Application.Features.Auth;

namespace Zinema.Api.Controllers;

[ApiController]
[Route("api/admin/media-assets")]
[Authorize(Roles = AuthRoles.Admin)]
public sealed class AdminMediaAssetsController(
    IAdminMediaAssetService adminMediaAssetService) : ControllerBase
{
    [HttpGet]
    [ProducesResponseType(typeof(PagedResultDto<AdminMediaAssetDto>), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    public async Task<ActionResult<PagedResultDto<AdminMediaAssetDto>>> GetMediaAssets(
        [FromQuery] AdminMediaAssetSearchRequest request,
        CancellationToken cancellationToken)
    {
        var query = request.ToQuery();
        if (query.IsFailure)
        {
            return ToProblem(query.Error);
        }

        var result = await adminMediaAssetService.GetMediaAssetsAsync(
            query.Value,
            cancellationToken);

        return Ok(result);
    }

    [HttpGet("{id:guid}")]
    [ProducesResponseType(typeof(AdminMediaAssetDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    public async Task<ActionResult<AdminMediaAssetDto>> GetMediaAsset(
        Guid id,
        CancellationToken cancellationToken)
    {
        var result = await adminMediaAssetService.GetMediaAssetByIdAsync(
            id,
            cancellationToken);

        return result.IsSuccess ? Ok(result.Value) : ToProblem(result.Error);
    }

    [HttpPost]
    [ProducesResponseType(typeof(AdminMediaAssetDto), StatusCodes.Status201Created)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status409Conflict)]
    public async Task<ActionResult<AdminMediaAssetDto>> Create(
        AdminMediaAssetRequest request,
        CancellationToken cancellationToken)
    {
        var command = request.ToCreateCommand();
        if (command.IsFailure)
        {
            return ToProblem(command.Error);
        }

        var result = await adminMediaAssetService.CreateMediaAssetAsync(
            command.Value,
            cancellationToken);

        return result.IsSuccess
            ? Created($"/api/admin/media-assets/{result.Value.Id}", result.Value)
            : ToProblem(result.Error);
    }

    [HttpPut("{id:guid}")]
    [ProducesResponseType(typeof(AdminMediaAssetDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status409Conflict)]
    public async Task<ActionResult<AdminMediaAssetDto>> Update(
        Guid id,
        AdminMediaAssetRequest request,
        CancellationToken cancellationToken)
    {
        var command = request.ToUpdateCommand(id);
        if (command.IsFailure)
        {
            return ToProblem(command.Error);
        }

        var result = await adminMediaAssetService.UpdateMediaAssetAsync(
            command.Value,
            cancellationToken);

        return result.IsSuccess ? Ok(result.Value) : ToProblem(result.Error);
    }

    [HttpDelete("{id:guid}")]
    [ProducesResponseType(StatusCodes.Status204NoContent)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    public async Task<IActionResult> Delete(
        Guid id,
        CancellationToken cancellationToken)
    {
        var result = await adminMediaAssetService.DeleteMediaAssetAsync(id, cancellationToken);

        return result.IsSuccess ? NoContent() : ToProblem(result.Error);
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

        if (errorCode.Contains("Conflict", StringComparison.OrdinalIgnoreCase))
        {
            return StatusCodes.Status409Conflict;
        }

        return StatusCodes.Status400BadRequest;
    }
}
