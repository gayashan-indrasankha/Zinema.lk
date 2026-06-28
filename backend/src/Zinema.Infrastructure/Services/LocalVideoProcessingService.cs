using System.Diagnostics;
using Microsoft.Extensions.Logging;
using Microsoft.Extensions.Options;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.VideoProcessingJobs;
using Zinema.Application.Features.VideoProcessingJobs;

namespace Zinema.Infrastructure.Services;

public sealed class LocalVideoProcessingService(
    IOptions<VideoProcessingOptions> options,
    IFfmpegCommandBuilder ffmpegCommandBuilder,
    IFfmpegAvailabilityChecker ffmpegAvailabilityChecker,
    ILogger<LocalVideoProcessingService> logger) : IVideoProcessingService
{
    public async Task<Result<VideoProcessingExecutionResult>> ProcessAsync(
        VideoProcessingJobDto processingJob,
        CancellationToken cancellationToken = default)
    {
        if (!options.Value.EnableExecution)
        {
            return Result<VideoProcessingExecutionResult>.Success(new VideoProcessingExecutionResult(
                processingJob.Id,
                VideoProcessingExecutionStatus.ExecutionDisabled,
                WasExecuted: false,
                Succeeded: false,
                Message: "Video processing execution is disabled by configuration.",
                OutputPlan: null,
                Command: null,
                ExitCode: null));
        }

        var availability = await ffmpegAvailabilityChecker.CheckAvailabilityAsync(cancellationToken);
        if (!availability.IsAvailable)
        {
            return Result<VideoProcessingExecutionResult>.Success(new VideoProcessingExecutionResult(
                processingJob.Id,
                VideoProcessingExecutionStatus.FfmpegUnavailable,
                WasExecuted: false,
                Succeeded: false,
                Message: availability.Message,
                OutputPlan: null,
                Command: null,
                ExitCode: null));
        }

        var command = ffmpegCommandBuilder.BuildHlsCommand(processingJob);
        if (command.IsFailure)
        {
            return Result<VideoProcessingExecutionResult>.Success(new VideoProcessingExecutionResult(
                processingJob.Id,
                VideoProcessingExecutionStatus.ValidationFailed,
                WasExecuted: false,
                Succeeded: false,
                Message: command.Error.Message,
                OutputPlan: null,
                Command: null,
                ExitCode: null));
        }

        if (!File.Exists(command.Value.SourcePath))
        {
            return Result<VideoProcessingExecutionResult>.Success(new VideoProcessingExecutionResult(
                processingJob.Id,
                VideoProcessingExecutionStatus.ValidationFailed,
                WasExecuted: false,
                Succeeded: false,
                Message: "Source media file was not found on the local file system.",
                OutputPlan: command.Value.OutputPlan,
                Command: command.Value,
                ExitCode: null));
        }

        Directory.CreateDirectory(command.Value.OutputPlan.JobOutputDirectory);

        return await ExecuteFfmpegAsync(command.Value, cancellationToken);
    }

    private async Task<Result<VideoProcessingExecutionResult>> ExecuteFfmpegAsync(
        FfmpegHlsCommand command,
        CancellationToken cancellationToken)
    {
        try
        {
            using var process = new Process();
            process.StartInfo = new ProcessStartInfo
            {
                FileName = command.FfmpegPath,
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                UseShellExecute = false
            };

            foreach (var argument in command.Arguments)
            {
                process.StartInfo.ArgumentList.Add(argument);
            }

            logger.LogInformation(
                "Starting FFmpeg HLS processing for job {ProcessingJobId}.",
                command.ProcessingJobId);

            if (!process.Start())
            {
                return ExecutionFailure(command, null, "FFmpeg process could not be started.");
            }

            var outputTask = process.StandardOutput.ReadToEndAsync(cancellationToken);
            var errorTask = process.StandardError.ReadToEndAsync(cancellationToken);

            await process.WaitForExitAsync(cancellationToken);

            _ = await outputTask;
            var error = await errorTask;

            if (process.ExitCode == 0)
            {
                return Result<VideoProcessingExecutionResult>.Success(
                    new VideoProcessingExecutionResult(
                        command.ProcessingJobId,
                        VideoProcessingExecutionStatus.Succeeded,
                        WasExecuted: true,
                        Succeeded: true,
                        Message: "FFmpeg HLS processing completed successfully.",
                        OutputPlan: command.OutputPlan,
                        Command: command,
                        ExitCode: process.ExitCode));
            }

            return ExecutionFailure(
                command,
                process.ExitCode,
                string.IsNullOrWhiteSpace(error)
                    ? "FFmpeg HLS processing failed."
                    : TrimMessage(error));
        }
        catch (Exception ex) when (ex is not OperationCanceledException)
        {
            logger.LogWarning(
                ex,
                "FFmpeg HLS processing failed for job {ProcessingJobId}.",
                command.ProcessingJobId);

            return ExecutionFailure(command, null, ex.Message);
        }
    }

    private static Result<VideoProcessingExecutionResult> ExecutionFailure(
        FfmpegHlsCommand command,
        int? exitCode,
        string message)
    {
        return Result<VideoProcessingExecutionResult>.Success(new VideoProcessingExecutionResult(
            command.ProcessingJobId,
            VideoProcessingExecutionStatus.Failed,
            WasExecuted: exitCode.HasValue,
            Succeeded: false,
            Message: TrimMessage(message),
            OutputPlan: command.OutputPlan,
            Command: command,
            ExitCode: exitCode));
    }

    private static string TrimMessage(string message)
    {
        var trimmed = message.Trim();
        return trimmed.Length <= 1000 ? trimmed : trimmed[..1000];
    }
}
