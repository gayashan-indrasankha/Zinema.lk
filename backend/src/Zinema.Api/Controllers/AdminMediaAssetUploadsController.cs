using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Zinema.Api.Contracts.AdminMediaAssets;
using Zinema.Application.Common.Errors;
using Zinema.Application.DTOs.AdminMediaAssets;
using Zinema.Application.Features.AdminMediaAssets;
using Zinema.Application.Features.Auth;

namespace Zinema.Api.Controllers;

[ApiController]
[Route("api/admin/media-assets")]
[Authorize(Roles = AuthRoles.Admin)]
public sealed class AdminMediaAssetUploadsController(
    IAdminMediaAssetUploadService adminMediaAssetUploadService) : ControllerBase
{
    [HttpPost("upload")]
    [Consumes("multipart/form-data")]
    [ProducesResponseType(typeof(AdminMediaAssetDto), StatusCodes.Status201Created)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status409Conflict)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status500InternalServerError)]
    public async Task<ActionResult<AdminMediaAssetDto>> Upload(
        [FromForm] AdminMediaAssetUploadRequest request,
        CancellationToken cancellationToken)
    {
        if (request.File is null)
        {
            return ToProblem(AdminMediaAssetErrors.Validation("Uploaded file is required."));
        }

        await using var content = request.File.OpenReadStream();
        var command = request.ToCommand(content);
        if (command.IsFailure)
        {
            return ToProblem(command.Error);
        }

        var result = await adminMediaAssetUploadService.UploadMediaAssetAsync(
            command.Value,
            cancellationToken);

        return result.IsSuccess
            ? Created($"/api/admin/media-assets/{result.Value.Id}", result.Value)
            : ToProblem(result.Error);
    }

    private ActionResult ToProblem(Error error)
    {
        var statusCode = GetStatusCode(error.Code);
        return Problem(title: error.Message, detail: error.Code, statusCode: statusCode);
    }

    private static int GetStatusCode(string errorCode)
    {
        if (errorCode.Contains("StorageUploadFailed", StringComparison.OrdinalIgnoreCase))
        {
            return StatusCodes.Status500InternalServerError;
        }

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
