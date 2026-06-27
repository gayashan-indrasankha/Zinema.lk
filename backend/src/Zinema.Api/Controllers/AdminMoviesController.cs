using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Zinema.Api.Contracts.AdminCatalog;
using Zinema.Application.Common.Errors;
using Zinema.Application.DTOs.AdminCatalog;
using Zinema.Application.Features.AdminCatalog;
using Zinema.Application.Features.Auth;

namespace Zinema.Api.Controllers;

[ApiController]
[Route("api/admin/movies")]
[Authorize(Roles = AuthRoles.Admin)]
public sealed class AdminMoviesController(IAdminCatalogService adminCatalogService) : ControllerBase
{
    [HttpPost]
    [ProducesResponseType(typeof(AdminMovieDto), StatusCodes.Status201Created)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status409Conflict)]
    public async Task<ActionResult<AdminMovieDto>> Create(
        AdminMovieRequest request,
        CancellationToken cancellationToken)
    {
        var command = request.ToCreateCommand();
        if (command.IsFailure)
        {
            return ToProblem(command.Error);
        }

        var result = await adminCatalogService.CreateMovieAsync(
            command.Value,
            cancellationToken);

        return result.IsSuccess
            ? Created($"/api/catalog/movies/{result.Value.Slug}", result.Value)
            : ToProblem(result.Error);
    }

    [HttpPut("{id:guid}")]
    [ProducesResponseType(typeof(AdminMovieDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status409Conflict)]
    public async Task<ActionResult<AdminMovieDto>> Update(
        Guid id,
        AdminMovieRequest request,
        CancellationToken cancellationToken)
    {
        var command = request.ToUpdateCommand(id);
        if (command.IsFailure)
        {
            return ToProblem(command.Error);
        }

        var result = await adminCatalogService.UpdateMovieAsync(
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
        var result = await adminCatalogService.DeleteMovieAsync(id, cancellationToken);

        return result.IsSuccess ? NoContent() : ToProblem(result.Error);
    }

    [HttpPatch("{id:guid}/publish")]
    [ProducesResponseType(typeof(AdminMovieDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    public async Task<ActionResult<AdminMovieDto>> Publish(
        Guid id,
        CancellationToken cancellationToken)
    {
        var result = await adminCatalogService.PublishMovieAsync(id, cancellationToken);

        return result.IsSuccess ? Ok(result.Value) : ToProblem(result.Error);
    }

    [HttpPatch("{id:guid}/unpublish")]
    [ProducesResponseType(typeof(AdminMovieDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    public async Task<ActionResult<AdminMovieDto>> Unpublish(
        Guid id,
        CancellationToken cancellationToken)
    {
        var result = await adminCatalogService.UnpublishMovieAsync(id, cancellationToken);

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

        if (errorCode.Contains("Conflict", StringComparison.OrdinalIgnoreCase))
        {
            return StatusCodes.Status409Conflict;
        }

        return StatusCodes.Status400BadRequest;
    }
}
