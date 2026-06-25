using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.EntityFrameworkCore;
using Zinema.Application.Features.Catalog;
using Zinema.Infrastructure.Persistence;
using Zinema.Infrastructure.Services;

namespace Zinema.Infrastructure;

public static class DependencyInjection
{
    public static IServiceCollection AddInfrastructure(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        services.AddDbContext<AppDbContext>(options =>
        {
            var connectionString = configuration.GetConnectionString("DefaultConnection");

            options.UseNpgsql(connectionString);
        });

        services.AddScoped<ICatalogQueryService, CatalogQueryService>();

        return services;
    }
}
