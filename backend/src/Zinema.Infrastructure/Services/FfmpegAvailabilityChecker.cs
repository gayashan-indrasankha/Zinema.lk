using System.Diagnostics;
using Microsoft.Extensions.Logging;
using Microsoft.Extensions.Options;
using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Infrastructure.Services;

public sealed class FfmpegAvailabilityChecker(
    IOptions<VideoProcessingOptions> options,
    ILogger<FfmpegAvailabilityChecker> logger) : IFfmpegAvailabilityChecker
{
    public async Task<FfmpegAvailabilityDto> CheckAvailabilityAsync(
        CancellationToken cancellationToken = default)
    {
        var ffmpegPath = options.Value.FfmpegPath?.Trim();
        if (string.IsNullOrWhiteSpace(ffmpegPath))
        {
            return new FfmpegAvailabilityDto(
                IsPathConfigured: false,
                IsAvailable: false,
                FfmpegPath: null,
                Version: null,
                Message: "FFmpeg path is not configured.");
        }

        try
        {
            using var process = new Process();
            process.StartInfo = new ProcessStartInfo
            {
                FileName = ffmpegPath,
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                UseShellExecute = false
            };
            process.StartInfo.ArgumentList.Add("-version");

            if (!process.Start())
            {
                return Unavailable(ffmpegPath, "FFmpeg process could not be started.");
            }

            var outputTask = process.StandardOutput.ReadToEndAsync(cancellationToken);
            var errorTask = process.StandardError.ReadToEndAsync(cancellationToken);

            await process.WaitForExitAsync(cancellationToken);

            var output = await outputTask;
            var error = await errorTask;
            var firstLine = GetFirstLine(output) ?? GetFirstLine(error);

            return process.ExitCode == 0
                ? new FfmpegAvailabilityDto(
                    IsPathConfigured: true,
                    IsAvailable: true,
                    FfmpegPath: ffmpegPath,
                    Version: firstLine,
                    Message: "FFmpeg is available.")
                : Unavailable(
                    ffmpegPath,
                    $"FFmpeg availability check failed with exit code {process.ExitCode}.");
        }
        catch (Exception ex) when (ex is not OperationCanceledException)
        {
            logger.LogDebug(ex, "FFmpeg availability check failed.");
            return Unavailable(ffmpegPath, "FFmpeg is not available at the configured path.");
        }
    }

    private static FfmpegAvailabilityDto Unavailable(string ffmpegPath, string message)
    {
        return new FfmpegAvailabilityDto(
            IsPathConfigured: true,
            IsAvailable: false,
            FfmpegPath: ffmpegPath,
            Version: null,
            Message: message);
    }

    private static string? GetFirstLine(string value)
    {
        return value
            .Split(Environment.NewLine, StringSplitOptions.RemoveEmptyEntries)
            .FirstOrDefault();
    }
}
