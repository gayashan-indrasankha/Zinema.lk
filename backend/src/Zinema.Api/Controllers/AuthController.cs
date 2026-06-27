using System.Security.Claims;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Zinema.Application.DTOs.Auth;
using Zinema.Application.Features.Auth;

namespace Zinema.Api.Controllers;

[ApiController]
[Route("api/auth")]
public sealed class AuthController(IAuthService authService) : ControllerBase
{
    [HttpPost("register")]
    [ProducesResponseType(typeof(AuthResponseDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status400BadRequest)]
    public async Task<ActionResult<AuthResponseDto>> Register(
        RegisterRequestDto request,
        CancellationToken cancellationToken)
    {
        var result = await authService.RegisterAsync(request, cancellationToken);

        return result.IsSuccess
            ? Ok(result.Value)
            : AuthProblem(result.Error.Message, result.Error.Code, StatusCodes.Status400BadRequest);
    }

    [HttpPost("login")]
    [ProducesResponseType(typeof(AuthResponseDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status401Unauthorized)]
    public async Task<ActionResult<AuthResponseDto>> Login(
        LoginRequestDto request,
        CancellationToken cancellationToken)
    {
        var result = await authService.LoginAsync(request, cancellationToken);

        return result.IsSuccess
            ? Ok(result.Value)
            : AuthProblem(result.Error.Message, result.Error.Code, StatusCodes.Status401Unauthorized);
    }

    [Authorize]
    [HttpGet("me")]
    [ProducesResponseType(typeof(CurrentUserDto), StatusCodes.Status200OK)]
    [ProducesResponseType(typeof(ProblemDetails), StatusCodes.Status401Unauthorized)]
    public async Task<ActionResult<CurrentUserDto>> Me(CancellationToken cancellationToken)
    {
        var userIdValue = User.FindFirstValue(ClaimTypes.NameIdentifier);
        if (!Guid.TryParse(userIdValue, out var userId))
        {
            return AuthProblem(
                "Authenticated user id is missing.",
                "Auth.UserIdMissing",
                StatusCodes.Status401Unauthorized);
        }

        var result = await authService.GetCurrentUserAsync(userId, cancellationToken);

        return result.IsSuccess
            ? Ok(result.Value)
            : AuthProblem(result.Error.Message, result.Error.Code, StatusCodes.Status401Unauthorized);
    }

    private ActionResult AuthProblem(string title, string detail, int statusCode)
    {
        return Problem(title: title, detail: detail, statusCode: statusCode);
    }
}
