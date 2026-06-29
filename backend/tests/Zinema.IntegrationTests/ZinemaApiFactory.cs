using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Mvc.Testing;
using Microsoft.AspNetCore.TestHost;
using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Infrastructure;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.DependencyInjection.Extensions;
using Zinema.Infrastructure.Persistence;

namespace Zinema.IntegrationTests;

public sealed class ZinemaApiFactory : WebApplicationFactory<Program>
{
    private readonly Dictionary<string, string?> previousEnvironmentValues = new();

    public ZinemaApiFactory()
    {
        SetEnvironmentVariable(
            "ConnectionStrings__DefaultConnection",
            "Host=localhost;Database=zinema_integration_tests");
        SetEnvironmentVariable("Database__SeedOnStartup", "false");
        SetEnvironmentVariable("Jwt__Issuer", "Zinema.IntegrationTests");
        SetEnvironmentVariable("Jwt__Audience", "Zinema.IntegrationTests");
        SetEnvironmentVariable(
            "Jwt__SigningKey",
            "integration-test-signing-key-change-me-minimum-32-characters");
        SetEnvironmentVariable("VideoProcessing__EnableExecution", "false");
    }

    protected override void ConfigureWebHost(IWebHostBuilder builder)
    {
        builder.UseEnvironment("Testing");

        builder.ConfigureTestServices(services =>
        {
            services.RemoveAll<AppDbContext>();
            services.RemoveAll<DbContextOptions>();
            services.RemoveAll<DbContextOptions<AppDbContext>>();
            services.RemoveAll<IDbContextOptionsConfiguration<AppDbContext>>();

            services.AddDbContext<AppDbContext>(options =>
            {
                options.UseInMemoryDatabase($"zinema-integration-tests-{Guid.NewGuid():N}");
            });
        });
    }

    protected override void Dispose(bool disposing)
    {
        foreach (var environmentValue in previousEnvironmentValues)
        {
            Environment.SetEnvironmentVariable(environmentValue.Key, environmentValue.Value);
        }

        base.Dispose(disposing);
    }

    private void SetEnvironmentVariable(string name, string value)
    {
        previousEnvironmentValues[name] = Environment.GetEnvironmentVariable(name);
        Environment.SetEnvironmentVariable(name, value);
    }
}
