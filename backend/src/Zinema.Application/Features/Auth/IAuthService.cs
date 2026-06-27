using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.Auth;

namespace Zinema.Application.Features.Auth;

public interface IAuthService
{
    Task<Result<AuthResponseDto>> RegisterAsync(
        RegisterRequestDto request,
        CancellationToken cancellationToken = default);

    Task<Result<AuthResponseDto>> LoginAsync(
        LoginRequestDto request,
        CancellationToken cancellationToken = default);

    Task<Result<CurrentUserDto>> GetCurrentUserAsync(
        Guid userId,
        CancellationToken cancellationToken = default);
}
