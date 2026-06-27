using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Zinema.Infrastructure.Persistence;
using Zinema.Infrastructure.Persistence.Seed;

namespace Zinema.Infrastructure;

public static class DatabaseSetupExtensions
{
    public static async Task ApplyDevelopmentDatabaseSetupAsync(
        this IServiceProvider services,
        IConfiguration configuration,
        bool isDevelopment,
        CancellationToken cancellationToken = default)
    {
        if (!isDevelopment || !IsSeedOnStartupEnabled(configuration))
        {
            return;
        }

        using var scope = services.CreateScope();
        var dbContext = scope.ServiceProvider.GetRequiredService<AppDbContext>();

        await dbContext.Database.MigrateAsync(cancellationToken);
        await IdentityDataSeeder.SeedAsync(scope.ServiceProvider, configuration, cancellationToken);
        await DevelopmentDatabaseSeeder.SeedAsync(dbContext, cancellationToken);
    }

    private static bool IsSeedOnStartupEnabled(IConfiguration configuration)
    {
        return bool.TryParse(configuration["Database:SeedOnStartup"], out var enabled) && enabled;
    }
}
