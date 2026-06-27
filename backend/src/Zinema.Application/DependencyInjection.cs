using Microsoft.Extensions.DependencyInjection;

namespace Zinema.Application;

public static class DependencyInjection
{
    public static IServiceCollection AddApplication(this IServiceCollection services)
    {
        return services;
    }
}
