namespace Zinema.Application.Features.Auth;

public static class AuthRoles
{
    public const string User = "User";

    public const string Admin = "Admin";

    public static readonly IReadOnlyList<string> All = [User, Admin];
}
