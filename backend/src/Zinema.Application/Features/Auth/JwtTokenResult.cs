namespace Zinema.Application.Features.Auth;

public sealed record JwtTokenResult(
    string AccessToken,
    DateTimeOffset ExpiresAt);
