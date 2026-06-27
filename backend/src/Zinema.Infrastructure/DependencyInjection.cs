using Microsoft.AspNetCore.Identity;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.EntityFrameworkCore;
using Zinema.Application.Features.AdminCatalog;
using Zinema.Application.Features.AdminMediaAssets;
using Zinema.Application.Features.Auth;
using Zinema.Application.Features.Catalog;
using Zinema.Infrastructure.Authentication;
using Zinema.Infrastructure.Identity;
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

        services.AddIdentityCore<ApplicationUser>(options =>
            {
                options.User.RequireUniqueEmail = true;
                options.Password.RequiredLength = 8;
                options.Password.RequireDigit = true;
                options.Password.RequireLowercase = true;
                options.Password.RequireUppercase = true;
                options.Password.RequireNonAlphanumeric = false;
            })
            .AddRoles<IdentityRole<Guid>>()
            .AddEntityFrameworkStores<AppDbContext>();

        services.Configure<JwtOptions>(configuration.GetSection(JwtOptions.SectionName));
        services.Configure<ObjectStorageOptions>(
            configuration.GetSection(ObjectStorageOptions.SectionName));
        services.Configure<MediaUploadOptions>(
            configuration.GetSection(MediaUploadOptions.SectionName));

        services.AddScoped<IAuthService, AuthService>();
        services.AddScoped<IJwtTokenService, JwtTokenService>();
        services.AddScoped<IAdminCatalogService, AdminCatalogService>();
        services.AddScoped<IAdminMediaAssetService, AdminMediaAssetService>();
        services.AddScoped<IAdminMediaAssetUploadService, AdminMediaAssetUploadService>();
        services.AddSingleton<IObjectStorageService, ObjectStorageService>();
        services.AddScoped<ICatalogQueryService, CatalogQueryService>();

        return services;
    }
}
