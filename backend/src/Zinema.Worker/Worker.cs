using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Worker;

public class Worker(
    ILogger<Worker> logger,
    IServiceScopeFactory serviceScopeFactory) : BackgroundService
{
    protected override async Task ExecuteAsync(CancellationToken stoppingToken)
    {
        while (!stoppingToken.IsCancellationRequested)
        {
            if (logger.IsEnabled(LogLevel.Information))
            {
                logger.LogInformation("Zinema worker heartbeat at: {time}", DateTimeOffset.UtcNow);
            }

            using var scope = serviceScopeFactory.CreateScope();
            var videoProcessingJobRunner = scope.ServiceProvider
                .GetRequiredService<IVideoProcessingJobRunner>();

            await videoProcessingJobRunner.RunNextAsync(stoppingToken);

            await Task.Delay(TimeSpan.FromMinutes(1), stoppingToken);
        }
    }
}
