namespace Zinema.Application.DTOs.Auth;

public sealed record AuthResponseDto(
    string AccessToken,
    DateTimeOffset ExpiresAt,
    CurrentUserDto User);
