using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Zinema.Application.Features.Auth;

namespace Zinema.Api.Controllers;

[ApiController]
[Route("api/admin")]
[Authorize(Roles = AuthRoles.Admin)]
public sealed class AdminPingController : ControllerBase
{
    [HttpGet("ping")]
    [ProducesResponseType(StatusCodes.Status200OK)]
    [ProducesResponseType(StatusCodes.Status401Unauthorized)]
    [ProducesResponseType(StatusCodes.Status403Forbidden)]
    public IActionResult Ping()
    {
        return Ok(new
        {
            message = "Admin authorization is working."
        });
    }
}
