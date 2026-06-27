namespace Zinema.Application.DTOs.Auth;

public sealed record CurrentUserDto(
    Guid Id,
    string DisplayName,
    string Email,
    IReadOnlyList<string> Roles);
