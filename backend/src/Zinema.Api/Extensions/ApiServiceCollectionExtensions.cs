using Microsoft.OpenApi;

namespace Zinema.Api.Extensions;

public static class ApiServiceCollectionExtensions
{
    public static IServiceCollection AddApiServices(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        services.AddControllers();
        services.AddEndpointsApiExplorer();
        services.AddHealthChecks();

        services.AddSwaggerGen(options =>
        {
            options.SwaggerDoc("v1", new OpenApiInfo
            {
                Title = "Zinema.lk API",
                Version = "v1",
                Description = "Backend API foundation for Zinema.lk – Modern Full-Stack Movie Streaming Platform."
            });
        });

        return services;
    }
}
