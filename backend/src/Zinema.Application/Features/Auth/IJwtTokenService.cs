namespace Zinema.Application.Features.Auth;

public interface IJwtTokenService
{
    Task<JwtTokenResult> GenerateAccessTokenAsync(
        Guid userId,
        string email,
        string displayName,
        IReadOnlyCollection<string> roles,
        CancellationToken cancellationToken = default);
}
