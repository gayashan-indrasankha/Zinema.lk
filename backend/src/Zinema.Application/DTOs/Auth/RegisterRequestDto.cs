namespace Zinema.Application.DTOs.Auth;

public sealed record RegisterRequestDto(
    string DisplayName,
    string Email,
    string Password);
