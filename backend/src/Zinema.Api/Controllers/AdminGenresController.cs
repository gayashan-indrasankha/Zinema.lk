using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Zinema.Api.Contracts.AdminCatalog;
using Zinema.Application.Common.Errors;
using Zinema.Application.DTOs.AdminCatalog;
using Zinema.Application.Features.AdminCatalog;
using Zinema.Application.Features.Auth;

namespace Zinema.Api.Controllers;

[ApiController]
[Route("api/admin/genres")]
[Authorize(Roles = AuthRoles.Admin)]
public sealed class AdminGenresController(IAdminCatalogService adminCatalogService) : ControllerBase
{
    [HttpPost]
    [ProducesResponseType(typeof(AdminGenreDto), StatusCodes.Status201Created)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status409Conflict)]
    public async Task<ActionResult<AdminGenreDto>> Create(
        AdminGenreRequest request,
        CancellationToken cancellationToken)
    {
        var result = await adminCatalogService.CreateGenreAsync(
            request.ToCreateCommand(),
            cancellationToken);

        return result.IsSuccess
            ? Created("/api/catalog/genres", result.Value)
            : ToProblem(result.Error);
    }

    [HttpPut("{id:guid}")]
    [ProducesResponseType(typeof(AdminGenreDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status409Conflict)]
    public async Task<ActionResult<AdminGenreDto>> Update(
        Guid id,
        AdminGenreRequest request,
        CancellationToken cancellationToken)
    {
        var result = await adminCatalogService.UpdateGenreAsync(
            request.ToUpdateCommand(id),
            cancellationToken);

        return result.IsSuccess ? Ok(result.Value) : ToProblem(result.Error);
    }

    [HttpDelete("{id:guid}")]
    [ProducesResponseType(StatusCodes.Status204NoContent)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status404NotFound)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status409Conflict)]
    public async Task<IActionResult> Delete(
        Guid id,
        CancellationToken cancellationToken)
    {
        var result = await adminCatalogService.DeleteGenreAsync(id, cancellationToken);

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
