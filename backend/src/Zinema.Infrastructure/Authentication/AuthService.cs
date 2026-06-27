using Microsoft.AspNetCore.Identity;
using Zinema.Application.Common.Errors;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.Auth;
using Zinema.Application.Features.Auth;
using Zinema.Infrastructure.Identity;

namespace Zinema.Infrastructure.Authentication;

public sealed class AuthService(
    UserManager<ApplicationUser> userManager,
    RoleManager<IdentityRole<Guid>> roleManager,
    IJwtTokenService jwtTokenService) : IAuthService
{
    public async Task<Result<AuthResponseDto>> RegisterAsync(
        RegisterRequestDto request,
        CancellationToken cancellationToken = default)
    {
        var validationError = ValidateRegistration(request);
        if (validationError is not null)
        {
            return Result<AuthResponseDto>.Failure(validationError);
        }

        var email = request.Email.Trim();
        var existingUser = await userManager.FindByEmailAsync(email);
        if (existingUser is not null)
        {
            return Result<AuthResponseDto>.Failure(
                Error.Create("Auth.EmailAlreadyRegistered", "An account already exists for this email."));
        }

        var user = new ApplicationUser
        {
            UserName = email,
            Email = email,
            DisplayName = request.DisplayName.Trim()
        };

        var createResult = await userManager.CreateAsync(user, request.Password);
        if (!createResult.Succeeded)
        {
            return Result<AuthResponseDto>.Failure(ToIdentityError("Auth.RegisterFailed", createResult));
        }

        await EnsureRoleExistsAsync(AuthRoles.User);

        var roleResult = await userManager.AddToRoleAsync(user, AuthRoles.User);
        if (!roleResult.Succeeded)
        {
            return Result<AuthResponseDto>.Failure(ToIdentityError("Auth.RoleAssignmentFailed", roleResult));
        }

        return Result<AuthResponseDto>.Success(await CreateAuthResponseAsync(user, cancellationToken));
    }

    public async Task<Result<AuthResponseDto>> LoginAsync(
        LoginRequestDto request,
        CancellationToken cancellationToken = default)
    {
        if (string.IsNullOrWhiteSpace(request.Email) || string.IsNullOrWhiteSpace(request.Password))
        {
            return Result<AuthResponseDto>.Failure(
                Error.Create("Auth.InvalidCredentials", "Invalid email or password."));
        }

        var user = await userManager.FindByEmailAsync(request.Email.Trim());
        if (user is null)
        {
            return Result<AuthResponseDto>.Failure(
                Error.Create("Auth.InvalidCredentials", "Invalid email or password."));
        }

        if (!await userManager.CheckPasswordAsync(user, request.Password))
        {
            return Result<AuthResponseDto>.Failure(
                Error.Create("Auth.InvalidCredentials", "Invalid email or password."));
        }

        return Result<AuthResponseDto>.Success(await CreateAuthResponseAsync(user, cancellationToken));
    }

    public async Task<Result<CurrentUserDto>> GetCurrentUserAsync(
        Guid userId,
        CancellationToken cancellationToken = default)
    {
        var user = await userManager.FindByIdAsync(userId.ToString());
        if (user is null)
        {
            return Result<CurrentUserDto>.Failure(
                Error.Create("Auth.UserNotFound", "Authenticated user was not found."));
        }

        return Result<CurrentUserDto>.Success(await CreateCurrentUserDtoAsync(user));
    }

    private async Task<AuthResponseDto> CreateAuthResponseAsync(
        ApplicationUser user,
        CancellationToken cancellationToken)
    {
        var currentUser = await CreateCurrentUserDtoAsync(user);
        var token = await jwtTokenService.GenerateAccessTokenAsync(
            user.Id,
            currentUser.Email,
            currentUser.DisplayName,
            currentUser.Roles,
            cancellationToken);

        return new AuthResponseDto(
            token.AccessToken,
            token.ExpiresAt,
            currentUser);
    }

    private async Task<CurrentUserDto> CreateCurrentUserDtoAsync(ApplicationUser user)
    {
        var roles = await userManager.GetRolesAsync(user);

        return new CurrentUserDto(
            user.Id,
            user.DisplayName,
            user.Email ?? string.Empty,
            roles.OrderBy(role => role).ToArray());
    }

    private async Task EnsureRoleExistsAsync(string roleName)
    {
        if (await roleManager.RoleExistsAsync(roleName))
        {
            return;
        }

        await roleManager.CreateAsync(new IdentityRole<Guid>(roleName));
    }

    private static Error? ValidateRegistration(RegisterRequestDto request)
    {
        if (string.IsNullOrWhiteSpace(request.DisplayName))
        {
            return Error.Create("Auth.DisplayNameRequired", "Display name is required.");
        }

        if (string.IsNullOrWhiteSpace(request.Email))
        {
            return Error.Create("Auth.EmailRequired", "Email is required.");
        }

        if (string.IsNullOrWhiteSpace(request.Password))
        {
            return Error.Create("Auth.PasswordRequired", "Password is required.");
        }

        return null;
    }

    private static Error ToIdentityError(string code, IdentityResult result)
    {
        var message = string.Join(" ", result.Errors.Select(error => error.Description));
        return Error.Create(code, message);
    }
}
