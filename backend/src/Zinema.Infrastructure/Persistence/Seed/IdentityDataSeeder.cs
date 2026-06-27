using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Zinema.Application.Features.Auth;
using Zinema.Infrastructure.Identity;

namespace Zinema.Infrastructure.Persistence.Seed;

public static class IdentityDataSeeder
{
    public static async Task SeedAsync(
        IServiceProvider serviceProvider,
        IConfiguration configuration,
        CancellationToken cancellationToken = default)
    {
        var roleManager = serviceProvider.GetRequiredService<RoleManager<IdentityRole<Guid>>>();
        var userManager = serviceProvider.GetRequiredService<UserManager<ApplicationUser>>();

        foreach (var role in AuthRoles.All)
        {
            if (!await roleManager.RoleExistsAsync(role))
            {
                await roleManager.CreateAsync(new IdentityRole<Guid>(role));
            }
        }

        if (!IsDevelopmentAdminSeedEnabled(configuration))
        {
            return;
        }

        var email = configuration["Auth:DevelopmentAdminEmail"];
        var password = configuration["Auth:DevelopmentAdminPassword"];
        var displayName = configuration["Auth:DevelopmentAdminDisplayName"];

        if (string.IsNullOrWhiteSpace(email) || string.IsNullOrWhiteSpace(password))
        {
            throw new InvalidOperationException(
                "Development admin seeding is enabled, but admin email or password is missing.");
        }

        var admin = await userManager.FindByEmailAsync(email);
        if (admin is null)
        {
            admin = new ApplicationUser
            {
                UserName = email,
                Email = email,
                DisplayName = string.IsNullOrWhiteSpace(displayName)
                    ? "Development Admin"
                    : displayName.Trim(),
                EmailConfirmed = true
            };

            var createResult = await userManager.CreateAsync(admin, password);
            if (!createResult.Succeeded)
            {
                var message = string.Join(" ", createResult.Errors.Select(error => error.Description));
                throw new InvalidOperationException($"Development admin seed failed: {message}");
            }
        }

        if (!await userManager.IsInRoleAsync(admin, AuthRoles.Admin))
        {
            await userManager.AddToRoleAsync(admin, AuthRoles.Admin);
        }

        if (!await userManager.IsInRoleAsync(admin, AuthRoles.User))
        {
            await userManager.AddToRoleAsync(admin, AuthRoles.User);
        }
    }

    private static bool IsDevelopmentAdminSeedEnabled(IConfiguration configuration)
    {
        return bool.TryParse(configuration["Auth:SeedDevelopmentAdmin"], out var enabled) && enabled;
    }
}
