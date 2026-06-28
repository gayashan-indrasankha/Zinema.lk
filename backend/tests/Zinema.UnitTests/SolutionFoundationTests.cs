using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Logging.Abstractions;
using Microsoft.Extensions.Options;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.Auth;
using Zinema.Application.DTOs.VideoProcessingJobs;
using Zinema.Application.Features.AdminCatalog;
using Zinema.Application.Features.AdminMediaAssets;
using Zinema.Application.Features.Auth;
using Zinema.Application.Features.Catalog;
using Zinema.Application.Features.VideoProcessingJobs;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;
using Zinema.Infrastructure.Persistence;
using Zinema.Infrastructure;
using Zinema.Infrastructure.Services;
using Zinema.Worker;

namespace Zinema.UnitTests;

public class SolutionFoundationTests
{
    [Fact]
    public void ResultSuccessCreatesSuccessfulResult()
    {
        var result = Result.Success();

        Assert.True(result.IsSuccess);
        Assert.False(result.IsFailure);
    }

    [Fact]
    public void MovieCanBeCreatedWithDraftPublishStatus()
    {
        var movie = new Movie
        {
            Title = "Sample Movie",
            Slug = "sample-movie"
        };

        Assert.NotEqual(Guid.Empty, movie.Id);
        Assert.Equal(PublishStatus.Draft, movie.PublishStatus);
        Assert.Equal("Sample Movie", movie.Title);
    }

    [Fact]
    public void GetMoviesQueryNormalizesPaginationAndDefaults()
    {
        var query = GetMoviesQuery.Create(
            page: -1,
            pageSize: 500,
            search: "  catalog  ",
            genre: "  drama  ",
            publishStatus: null,
            sortBy: "unknown");

        Assert.Equal(GetMoviesQuery.DefaultPage, query.Page);
        Assert.Equal(GetMoviesQuery.MaxPageSize, query.PageSize);
        Assert.Equal("catalog", query.Search);
        Assert.Equal("drama", query.Genre);
        Assert.Equal(PublishStatus.Published, query.PublishStatus);
        Assert.Equal(GetMoviesQuery.SortLatest, query.SortBy);
    }

    [Fact]
    public void GetMoviesQueryAcceptsSupportedSortAndPublishStatus()
    {
        var query = GetMoviesQuery.Create(
            page: 2,
            pageSize: 12,
            search: null,
            genre: null,
            publishStatus: "Scheduled",
            sortBy: "Title");

        Assert.Equal(2, query.Page);
        Assert.Equal(12, query.PageSize);
        Assert.Equal(PublishStatus.Scheduled, query.PublishStatus);
        Assert.Equal(GetMoviesQuery.SortTitle, query.SortBy);
    }

    [Fact]
    public void AuthRolesIncludeUserAndAdmin()
    {
        Assert.Contains(AuthRoles.User, AuthRoles.All);
        Assert.Contains(AuthRoles.Admin, AuthRoles.All);
    }

    [Fact]
    public void AuthResponseDoesNotExposePasswordData()
    {
        var user = new CurrentUserDto(
            Guid.NewGuid(),
            "Demo User",
            "demo@example.test",
            [AuthRoles.User]);

        var response = new AuthResponseDto(
            "token",
            DateTimeOffset.UtcNow.AddMinutes(30),
            user);

        Assert.Equal("token", response.AccessToken);
        Assert.Equal("demo@example.test", response.User.Email);
        Assert.Contains(AuthRoles.User, response.User.Roles);
    }

    [Theory]
    [InlineData("Demo Action Feature", "demo-action-feature")]
    [InlineData("  Sample: Drama Story!  ", "sample-drama-story")]
    [InlineData("Feature 2026", "feature-2026")]
    public void SlugGeneratorCreatesSafeSlugs(string value, string expected)
    {
        var slug = SlugGenerator.Generate(value);

        Assert.Equal(expected, slug);
    }

    [Fact]
    public void CreateMovieCommandCanCarryAdminCatalogInput()
    {
        var genreId = Guid.NewGuid();
        var command = new CreateMovieCommand(
            "Demo Action Feature",
            null,
            "Neutral admin catalog test entry.",
            2026,
            100,
            "English",
            [genreId],
            PublishStatus.Draft);

        Assert.Equal("Demo Action Feature", command.Title);
        Assert.Contains(genreId, command.GenreIds);
        Assert.Equal(PublishStatus.Draft, command.PublishStatus);
    }

    [Fact]
    public void GetAdminMediaAssetsQueryNormalizesPaginationAndFilters()
    {
        var movieId = Guid.NewGuid();
        var query = GetAdminMediaAssetsQuery.Create(
            page: -1,
            pageSize: 500,
            assetType: "  Poster  ",
            status: "Ready",
            movieId: movieId,
            seriesId: null,
            episodeId: null);

        Assert.True(query.IsSuccess);
        Assert.Equal(GetAdminMediaAssetsQuery.DefaultPage, query.Value.Page);
        Assert.Equal(GetAdminMediaAssetsQuery.MaxPageSize, query.Value.PageSize);
        Assert.Equal("poster", query.Value.AssetType);
        Assert.Equal(MediaStatus.Ready, query.Value.Status);
        Assert.Equal(movieId, query.Value.MovieId);
    }

    [Fact]
    public void GetAdminMediaAssetsQueryRejectsInvalidStatus()
    {
        var query = GetAdminMediaAssetsQuery.Create(
            page: 1,
            pageSize: 20,
            assetType: null,
            status: "unknown",
            movieId: null,
            seriesId: null,
            episodeId: null);

        Assert.True(query.IsFailure);
        Assert.Equal("AdminMediaAsset.Validation", query.Error.Code);
    }

    [Fact]
    public void MediaAssetValidationRequiresRelatedCatalogItem()
    {
        var error = AdminMediaAssetValidation.ValidateMetadata(
            "Poster",
            "poster",
            "image/jpeg",
            "poster.jpg",
            "movies/demo/poster.jpg",
            null,
            1200,
            movieId: null,
            seriesId: null,
            episodeId: null,
            collectionId: null);

        Assert.NotNull(error);
        Assert.Equal("AdminMediaAsset.Validation", error.Code);
    }

    [Fact]
    public void MediaAssetValidationRejectsNegativeFileSize()
    {
        var error = AdminMediaAssetValidation.ValidateMetadata(
            "Poster",
            "poster",
            "image/jpeg",
            "poster.jpg",
            "movies/demo/poster.jpg",
            null,
            -1,
            movieId: Guid.NewGuid(),
            seriesId: null,
            episodeId: null,
            collectionId: null);

        Assert.NotNull(error);
        Assert.Equal("AdminMediaAsset.Validation", error.Code);
    }

    [Fact]
    public void CreateMediaAssetCommandCanCarryMetadataInput()
    {
        var movieId = Guid.NewGuid();
        var command = new CreateMediaAssetCommand(
            "Demo Poster",
            "poster",
            "image/jpeg",
            "poster.jpg",
            "movies/demo/poster.jpg",
            null,
            1200,
            MediaStatus.PendingUpload,
            movieId,
            null,
            null,
            null);

        Assert.Equal("Demo Poster", command.Title);
        Assert.Equal("poster", command.AssetType);
        Assert.Equal(movieId, command.MovieId);
        Assert.Equal(MediaStatus.PendingUpload, command.Status);
    }

    [Fact]
    public void StorageKeyGeneratorCreatesSafeMediaAssetKeys()
    {
        var uploadId = Guid.Parse("11111111-2222-3333-4444-555555555555");
        var timestamp = new DateTimeOffset(2026, 6, 28, 10, 30, 0, TimeSpan.Zero);

        var storageKey = StorageKeyGenerator.Generate(
            "  Demo Poster Image.JPG  ",
            timestamp,
            uploadId);

        Assert.Equal(
            "media-assets/2026/06/11111111222233334444555555555555-demo-poster-image.jpg",
            storageKey);
    }

    [Fact]
    public void MediaUploadValidationAcceptsAllowedImageFile()
    {
        var error = MediaUploadValidation.ValidateUpload(
            "poster.png",
            "image/png",
            1200,
            new MediaUploadOptions());

        Assert.Null(error);
    }

    [Fact]
    public void MediaUploadValidationRejectsDangerousFileName()
    {
        var error = MediaUploadValidation.ValidateUpload(
            "../poster.png",
            "image/png",
            1200,
            new MediaUploadOptions());

        Assert.NotNull(error);
        Assert.Equal("AdminMediaAsset.Validation", error.Code);
    }

    [Fact]
    public void MediaUploadValidationRejectsUnsupportedContentType()
    {
        var error = MediaUploadValidation.ValidateUpload(
            "trailer.mp4",
            "video/mp4",
            1200,
            new MediaUploadOptions());

        Assert.NotNull(error);
        Assert.Equal("AdminMediaAsset.Validation", error.Code);
    }

    [Fact]
    public void MediaUploadValidationRejectsOversizedFiles()
    {
        var error = MediaUploadValidation.ValidateUpload(
            "poster.webp",
            "image/webp",
            1201,
            new MediaUploadOptions
            {
                MaxFileSizeBytes = 1200
            });

        Assert.NotNull(error);
        Assert.Equal("AdminMediaAsset.Validation", error.Code);
    }

    [Fact]
    public void VideoProcessingJobCreateValidationRequiresMediaAssetId()
    {
        var error = VideoProcessingJobValidation.ValidateCreate(Guid.Empty);

        Assert.NotNull(error);
        Assert.Equal("VideoProcessingJob.Validation", error.Code);
    }

    [Fact]
    public void CreateVideoProcessingJobCommandCanCarryMediaAssetId()
    {
        var mediaAssetId = Guid.NewGuid();
        var command = new CreateVideoProcessingJobCommand(mediaAssetId);

        Assert.Equal(mediaAssetId, command.MediaAssetId);
    }

    [Theory]
    [InlineData(VideoProcessingJobStatus.Pending, true)]
    [InlineData(VideoProcessingJobStatus.Queued, true)]
    [InlineData(VideoProcessingJobStatus.Processing, false)]
    [InlineData(VideoProcessingJobStatus.Completed, false)]
    [InlineData(VideoProcessingJobStatus.Failed, false)]
    [InlineData(VideoProcessingJobStatus.Cancelled, false)]
    public void VideoProcessingJobCancelValidationAllowsOnlyWaitingJobs(
        VideoProcessingJobStatus status,
        bool canCancel)
    {
        Assert.Equal(canCancel, VideoProcessingJobValidation.CanCancel(status));
    }

    [Fact]
    public void VideoProcessingJobCancelValidationRejectsProcessingJob()
    {
        var error = VideoProcessingJobValidation.ValidateCancel(
            VideoProcessingJobStatus.Processing);

        Assert.NotNull(error);
        Assert.Equal("VideoProcessingJob.InvalidStateTransition", error.Code);
    }

    [Fact]
    public void GetVideoProcessingJobsQueryNormalizesPaginationAndStatus()
    {
        var mediaAssetId = Guid.NewGuid();
        var query = GetVideoProcessingJobsQuery.Create(
            page: -1,
            pageSize: 500,
            status: "Queued",
            mediaAssetId: mediaAssetId);

        Assert.True(query.IsSuccess);
        Assert.Equal(GetVideoProcessingJobsQuery.DefaultPage, query.Value.Page);
        Assert.Equal(GetVideoProcessingJobsQuery.MaxPageSize, query.Value.PageSize);
        Assert.Equal(VideoProcessingJobStatus.Queued, query.Value.Status);
        Assert.Equal(mediaAssetId, query.Value.MediaAssetId);
    }

    [Fact]
    public void GetVideoProcessingJobsQueryRejectsInvalidStatus()
    {
        var query = GetVideoProcessingJobsQuery.Create(
            page: 1,
            pageSize: 20,
            status: "unknown",
            mediaAssetId: null);

        Assert.True(query.IsFailure);
        Assert.Equal("VideoProcessingJob.Validation", query.Error.Code);
    }

    [Fact]
    public void EnqueueVideoProcessingJobCommandCanCarryJobId()
    {
        var jobId = Guid.NewGuid();
        var command = new EnqueueVideoProcessingJobCommand(jobId);

        Assert.Equal(jobId, command.ProcessingJobId);
    }

    [Theory]
    [InlineData(VideoProcessingJobStatus.Pending, true)]
    [InlineData(VideoProcessingJobStatus.Queued, false)]
    [InlineData(VideoProcessingJobStatus.Processing, false)]
    [InlineData(VideoProcessingJobStatus.Completed, false)]
    [InlineData(VideoProcessingJobStatus.Failed, false)]
    [InlineData(VideoProcessingJobStatus.Cancelled, false)]
    public void VideoProcessingJobEnqueueValidationAllowsOnlyPendingJobs(
        VideoProcessingJobStatus status,
        bool canEnqueue)
    {
        Assert.Equal(canEnqueue, VideoProcessingJobValidation.CanEnqueue(status));
    }

    [Fact]
    public void VideoProcessingJobEnqueueValidationRejectsEmptyJobId()
    {
        var error = VideoProcessingJobValidation.ValidateEnqueue(Guid.Empty);

        Assert.NotNull(error);
        Assert.Equal("VideoProcessingJob.Validation", error.Code);
    }

    [Fact]
    public void VideoProcessingJobEnqueueValidationRejectsQueuedJob()
    {
        var error = VideoProcessingJobValidation.ValidateEnqueue(VideoProcessingJobStatus.Queued);

        Assert.NotNull(error);
        Assert.Equal("VideoProcessingJob.InvalidStateTransition", error.Code);
    }

    [Fact]
    public async Task VideoProcessingQueueReturnsNotFoundForMissingJob()
    {
        await using var dbContext = CreateInMemoryDbContext();
        var queue = new VideoProcessingQueueService(dbContext);

        var result = await queue.EnqueueAsync(Guid.NewGuid());

        Assert.True(result.IsFailure);
        Assert.Equal("VideoProcessingJob.NotFound", result.Error.Code);
    }

    [Fact]
    public async Task VideoProcessingQueueMovesPendingJobToQueued()
    {
        await using var dbContext = CreateInMemoryDbContext();
        var mediaAsset = CreateMediaAsset("media-assets/source.mp4");
        var job = new VideoProcessingJob
        {
            MediaAssetId = mediaAsset.Id,
            SourceStorageKey = mediaAsset.StorageKey,
            Status = VideoProcessingJobStatus.Pending,
            QueuedAt = DateTimeOffset.UtcNow.AddMinutes(-10)
        };

        dbContext.MediaAssets.Add(mediaAsset);
        dbContext.VideoProcessingJobs.Add(job);
        await dbContext.SaveChangesAsync();

        var queue = new VideoProcessingQueueService(dbContext);
        var result = await queue.EnqueueAsync(job.Id);

        Assert.True(result.IsSuccess);
        Assert.Equal(VideoProcessingJobStatus.Queued.ToString(), result.Value.Status);

        var saved = await dbContext.VideoProcessingJobs.FirstAsync(item => item.Id == job.Id);
        Assert.Equal(VideoProcessingJobStatus.Queued, saved.Status);
    }

    [Theory]
    [InlineData(VideoProcessingJobStatus.Queued)]
    [InlineData(VideoProcessingJobStatus.Completed)]
    [InlineData(VideoProcessingJobStatus.Cancelled)]
    public async Task VideoProcessingQueueRejectsNonPendingJobs(VideoProcessingJobStatus status)
    {
        await using var dbContext = CreateInMemoryDbContext();
        var job = new VideoProcessingJob
        {
            MediaAssetId = Guid.NewGuid(),
            SourceStorageKey = "media-assets/source.mp4",
            Status = status,
            QueuedAt = DateTimeOffset.UtcNow
        };

        dbContext.VideoProcessingJobs.Add(job);
        await dbContext.SaveChangesAsync();

        var queue = new VideoProcessingQueueService(dbContext);
        var result = await queue.EnqueueAsync(job.Id);

        Assert.True(result.IsFailure);
        Assert.Equal("VideoProcessingJob.InvalidStateTransition", result.Error.Code);
    }

    [Fact]
    public async Task VideoProcessingQueueReadsQueuedJobsOnly()
    {
        await using var dbContext = CreateInMemoryDbContext();
        var queuedMediaAsset = CreateMediaAsset("media-assets/queued.mp4");
        var pendingMediaAsset = CreateMediaAsset("media-assets/pending.mp4");

        dbContext.VideoProcessingJobs.AddRange(
            new VideoProcessingJob
            {
                MediaAssetId = queuedMediaAsset.Id,
                SourceStorageKey = queuedMediaAsset.StorageKey,
                Status = VideoProcessingJobStatus.Queued,
                QueuedAt = DateTimeOffset.UtcNow.AddMinutes(-5)
            },
            new VideoProcessingJob
            {
                MediaAssetId = pendingMediaAsset.Id,
                SourceStorageKey = pendingMediaAsset.StorageKey,
                Status = VideoProcessingJobStatus.Pending,
                QueuedAt = DateTimeOffset.UtcNow.AddMinutes(-10)
            });

        dbContext.MediaAssets.AddRange(queuedMediaAsset, pendingMediaAsset);
        await dbContext.SaveChangesAsync();

        var queue = new VideoProcessingQueueService(dbContext);
        var queuedJobs = await queue.GetQueuedJobsAsync(maxCount: 10);

        Assert.Single(queuedJobs);
        Assert.Equal(VideoProcessingJobStatus.Queued.ToString(), queuedJobs[0].Status);
    }

    [Theory]
    [InlineData(VideoProcessingJobStatus.Pending, false)]
    [InlineData(VideoProcessingJobStatus.Queued, true)]
    [InlineData(VideoProcessingJobStatus.Processing, false)]
    [InlineData(VideoProcessingJobStatus.Completed, false)]
    [InlineData(VideoProcessingJobStatus.Failed, false)]
    [InlineData(VideoProcessingJobStatus.Cancelled, false)]
    public void VideoProcessingJobStartValidationAllowsOnlyQueuedJobs(
        VideoProcessingJobStatus status,
        bool canStart)
    {
        Assert.Equal(canStart, VideoProcessingJobValidation.CanStart(status));
    }

    [Theory]
    [InlineData(VideoProcessingJobStatus.Pending, false)]
    [InlineData(VideoProcessingJobStatus.Queued, false)]
    [InlineData(VideoProcessingJobStatus.Processing, true)]
    [InlineData(VideoProcessingJobStatus.Completed, false)]
    [InlineData(VideoProcessingJobStatus.Failed, false)]
    [InlineData(VideoProcessingJobStatus.Cancelled, false)]
    public void VideoProcessingJobTerminalValidationAllowsOnlyProcessingJobs(
        VideoProcessingJobStatus status,
        bool canCompleteOrFail)
    {
        Assert.Equal(canCompleteOrFail, VideoProcessingJobValidation.CanComplete(status));
        Assert.Equal(canCompleteOrFail, VideoProcessingJobValidation.CanFail(status));
    }

    [Fact]
    public void FailVideoProcessingJobCommandRequiresErrorMessage()
    {
        var command = new FailVideoProcessingJobCommand(Guid.NewGuid(), " ");

        var error = VideoProcessingJobValidation.ValidateFail(command);

        Assert.NotNull(error);
        Assert.Equal("VideoProcessingJob.Validation", error.Code);
    }

    [Fact]
    public async Task VideoProcessingLifecycleMovesQueuedJobToProcessing()
    {
        await using var dbContext = CreateInMemoryDbContext();
        var mediaAsset = CreateMediaAsset("media-assets/lifecycle-start.mp4");
        var job = CreateProcessingJob(mediaAsset, VideoProcessingJobStatus.Queued);

        dbContext.MediaAssets.Add(mediaAsset);
        dbContext.VideoProcessingJobs.Add(job);
        await dbContext.SaveChangesAsync();

        var lifecycle = new VideoProcessingJobLifecycleService(dbContext);
        var result = await lifecycle.StartProcessingJobAsync(job.Id);

        Assert.True(result.IsSuccess);
        Assert.Equal(VideoProcessingJobStatus.Processing.ToString(), result.Value.Status);
        Assert.NotNull(result.Value.StartedAt);
        Assert.Equal(1, result.Value.AttemptCount);

        var saved = await dbContext.VideoProcessingJobs.FirstAsync(item => item.Id == job.Id);
        Assert.Equal(VideoProcessingJobStatus.Processing, saved.Status);
        Assert.NotNull(saved.StartedAt);
        Assert.Null(saved.CompletedAt);
    }

    [Fact]
    public async Task VideoProcessingLifecycleMovesProcessingJobToCompleted()
    {
        await using var dbContext = CreateInMemoryDbContext();
        var mediaAsset = CreateMediaAsset("media-assets/lifecycle-complete.mp4");
        var job = CreateProcessingJob(mediaAsset, VideoProcessingJobStatus.Processing);
        job.StartedAt = DateTimeOffset.UtcNow.AddMinutes(-5);
        job.AttemptCount = 1;

        dbContext.MediaAssets.Add(mediaAsset);
        dbContext.VideoProcessingJobs.Add(job);
        await dbContext.SaveChangesAsync();

        var lifecycle = new VideoProcessingJobLifecycleService(dbContext);
        var result = await lifecycle.CompleteProcessingJobAsync(job.Id);

        Assert.True(result.IsSuccess);
        Assert.Equal(VideoProcessingJobStatus.Completed.ToString(), result.Value.Status);
        Assert.NotNull(result.Value.CompletedAt);
        Assert.Null(result.Value.ErrorMessage);

        var saved = await dbContext.VideoProcessingJobs.FirstAsync(item => item.Id == job.Id);
        Assert.Equal(VideoProcessingJobStatus.Completed, saved.Status);
        Assert.NotNull(saved.CompletedAt);
    }

    [Fact]
    public async Task VideoProcessingLifecycleMovesProcessingJobToFailed()
    {
        await using var dbContext = CreateInMemoryDbContext();
        var mediaAsset = CreateMediaAsset("media-assets/lifecycle-fail.mp4");
        var job = CreateProcessingJob(mediaAsset, VideoProcessingJobStatus.Processing);
        job.StartedAt = DateTimeOffset.UtcNow.AddMinutes(-5);
        job.AttemptCount = 1;

        dbContext.MediaAssets.Add(mediaAsset);
        dbContext.VideoProcessingJobs.Add(job);
        await dbContext.SaveChangesAsync();

        var lifecycle = new VideoProcessingJobLifecycleService(dbContext);
        var result = await lifecycle.FailProcessingJobAsync(
            new FailVideoProcessingJobCommand(job.Id, " Placeholder processing failed. "));

        Assert.True(result.IsSuccess);
        Assert.Equal(VideoProcessingJobStatus.Failed.ToString(), result.Value.Status);
        Assert.Equal("Placeholder processing failed.", result.Value.ErrorMessage);
        Assert.NotNull(result.Value.CompletedAt);

        var saved = await dbContext.VideoProcessingJobs.FirstAsync(item => item.Id == job.Id);
        Assert.Equal(VideoProcessingJobStatus.Failed, saved.Status);
        Assert.Equal("Placeholder processing failed.", saved.ErrorMessage);
    }

    [Fact]
    public async Task VideoProcessingLifecycleRejectsPendingJobCompletion()
    {
        await using var dbContext = CreateInMemoryDbContext();
        var mediaAsset = CreateMediaAsset("media-assets/lifecycle-pending.mp4");
        var job = CreateProcessingJob(mediaAsset, VideoProcessingJobStatus.Pending);

        dbContext.MediaAssets.Add(mediaAsset);
        dbContext.VideoProcessingJobs.Add(job);
        await dbContext.SaveChangesAsync();

        var lifecycle = new VideoProcessingJobLifecycleService(dbContext);
        var result = await lifecycle.CompleteProcessingJobAsync(job.Id);

        Assert.True(result.IsFailure);
        Assert.Equal("VideoProcessingJob.InvalidStateTransition", result.Error.Code);
    }

    [Theory]
    [InlineData(VideoProcessingJobStatus.Completed)]
    [InlineData(VideoProcessingJobStatus.Cancelled)]
    public async Task VideoProcessingLifecycleRejectsTerminalJobClaim(VideoProcessingJobStatus status)
    {
        await using var dbContext = CreateInMemoryDbContext();
        var mediaAsset = CreateMediaAsset($"media-assets/lifecycle-{status}.mp4");
        var job = CreateProcessingJob(mediaAsset, status);
        job.CompletedAt = DateTimeOffset.UtcNow.AddMinutes(-1);

        dbContext.MediaAssets.Add(mediaAsset);
        dbContext.VideoProcessingJobs.Add(job);
        await dbContext.SaveChangesAsync();

        var lifecycle = new VideoProcessingJobLifecycleService(dbContext);
        var result = await lifecycle.StartProcessingJobAsync(job.Id);

        Assert.True(result.IsFailure);
        Assert.Equal("VideoProcessingJob.InvalidStateTransition", result.Error.Code);
    }

    [Fact]
    public void FfmpegCommandBuilderCreatesHlsCommand()
    {
        var jobId = Guid.Parse("11111111-2222-3333-4444-555555555555");
        var options = Options.Create(new VideoProcessingOptions
        {
            FfmpegPath = "ffmpeg",
            OutputRoot = Path.Combine("media-output", "hls"),
            HlsSegmentDurationSeconds = 8
        });
        var commandBuilder = new FfmpegHlsCommandBuilder(options);

        var result = commandBuilder.BuildHlsCommand(
            CreateProcessingJobDto(jobId, "media-assets/source.mp4"));

        Assert.True(result.IsSuccess);
        Assert.Equal(jobId, result.Value.ProcessingJobId);
        Assert.Contains("-f", result.Value.Arguments);
        Assert.Contains("hls", result.Value.Arguments);
        Assert.Contains("-hls_time", result.Value.Arguments);
        Assert.Contains("8", result.Value.Arguments);
        Assert.EndsWith("master.m3u8", result.Value.OutputPlan.MasterPlaylistPath);
        Assert.EndsWith("segment_%03d.ts", result.Value.OutputPlan.SegmentPathPattern);
    }

    [Fact]
    public void FfmpegCommandBuilderCreatesSafeHlsOutputPlan()
    {
        var jobId = Guid.Parse("aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee");
        var outputRoot = Path.Combine("media-output", "hls");
        var commandBuilder = new FfmpegHlsCommandBuilder(Options.Create(new VideoProcessingOptions
        {
            FfmpegPath = "ffmpeg",
            OutputRoot = outputRoot
        }));

        var result = commandBuilder.BuildHlsCommand(
            CreateProcessingJobDto(jobId, "media-assets/source.mp4"));

        Assert.True(result.IsSuccess);
        Assert.Equal(Path.GetFullPath(outputRoot), result.Value.OutputPlan.OutputRoot);
        Assert.Equal(
            Path.Combine(Path.GetFullPath(outputRoot), jobId.ToString("D")),
            result.Value.OutputPlan.JobOutputDirectory);
        Assert.Equal(
            Path.Combine(Path.GetFullPath(outputRoot), jobId.ToString("D"), "master.m3u8"),
            result.Value.OutputPlan.MasterPlaylistPath);
    }

    [Fact]
    public async Task LocalVideoProcessingServiceReturnsDisabledResultWhenExecutionIsOff()
    {
        var availabilityChecker = new FakeFfmpegAvailabilityChecker(new FfmpegAvailabilityDto(
            IsPathConfigured: true,
            IsAvailable: true,
            FfmpegPath: "ffmpeg",
            Version: "ffmpeg test",
            Message: "available"));
        var service = new LocalVideoProcessingService(
            Options.Create(new VideoProcessingOptions
            {
                FfmpegPath = "ffmpeg",
                OutputRoot = Path.Combine("media-output", "hls"),
                EnableExecution = false
            }),
            new FfmpegHlsCommandBuilder(Options.Create(new VideoProcessingOptions
            {
                FfmpegPath = "ffmpeg",
                OutputRoot = Path.Combine("media-output", "hls")
            })),
            availabilityChecker,
            NullLogger<LocalVideoProcessingService>.Instance);

        var result = await service.ProcessAsync(
            CreateProcessingJobDto(Guid.NewGuid(), "media-assets/source.mp4"));

        Assert.True(result.IsSuccess);
        Assert.Equal(VideoProcessingExecutionStatus.ExecutionDisabled, result.Value.Status);
        Assert.False(result.Value.WasExecuted);
        Assert.False(result.Value.Succeeded);
        Assert.Equal(0, availabilityChecker.CallCount);
    }

    [Fact]
    public async Task FfmpegAvailabilityCheckerReportsMissingPath()
    {
        var checker = new FfmpegAvailabilityChecker(
            Options.Create(new VideoProcessingOptions()),
            NullLogger<FfmpegAvailabilityChecker>.Instance);

        var result = await checker.CheckAvailabilityAsync();

        Assert.False(result.IsPathConfigured);
        Assert.False(result.IsAvailable);
        Assert.Equal("FFmpeg path is not configured.", result.Message);
    }

    [Fact]
    public async Task WorkerFailsClaimedJobWhenProcessingExecutionIsDisabled()
    {
        var queuedJob = CreateProcessingJobDto(
            Guid.Parse("22222222-3333-4444-5555-666666666666"),
            "media-assets/source.mp4",
            VideoProcessingJobStatus.Queued.ToString());
        var failedJob = queuedJob with
        {
            Status = VideoProcessingJobStatus.Failed.ToString(),
            ErrorMessage = "Video processing execution is disabled by configuration.",
            CompletedAt = DateTimeOffset.UtcNow
        };
        var queue = new FakeVideoProcessingQueue([queuedJob]);
        var executionService = new FakeVideoProcessingJobExecutionService(failedJob);
        var runner = new PlaceholderVideoProcessingJobRunner(
            NullLogger<PlaceholderVideoProcessingJobRunner>.Instance,
            queue,
            executionService);

        await runner.RunNextAsync();

        Assert.Equal(1, executionService.ExecuteCallCount);
        Assert.Equal(queuedJob.Id, executionService.LastProcessingJobId);
    }

    [Fact]
    public async Task VideoProcessingJobExecutionServiceCompletesSuccessfulProcessing()
    {
        var queuedJob = CreateProcessingJobDto(
            Guid.Parse("33333333-4444-5555-6666-777777777777"),
            "media-assets/source.mp4",
            VideoProcessingJobStatus.Queued.ToString());
        var processingJob = queuedJob with
        {
            Status = VideoProcessingJobStatus.Processing.ToString(),
            StartedAt = DateTimeOffset.UtcNow
        };
        var queue = new FakeVideoProcessingQueue([]);
        var lifecycle = new FakeVideoProcessingJobLifecycleService(processingJob);
        var processingService = new FakeVideoProcessingService(
            new VideoProcessingExecutionResult(
                processingJob.Id,
                VideoProcessingExecutionStatus.Succeeded,
                WasExecuted: true,
                Succeeded: true,
                Message: "Processing completed.",
                OutputPlan: null,
                Command: null,
                ExitCode: 0));
        var executionService = new VideoProcessingJobExecutionService(
            queue,
            lifecycle,
            processingService,
            NullLogger<VideoProcessingJobExecutionService>.Instance);

        var result = await executionService.ExecuteAsync(queuedJob);

        Assert.True(result.IsSuccess);
        Assert.Equal(VideoProcessingJobStatus.Completed.ToString(), result.Value.Status);
        Assert.Equal(1, lifecycle.StartCallCount);
        Assert.Equal(1, lifecycle.CompleteCallCount);
        Assert.Equal(0, lifecycle.FailCallCount);
        Assert.Equal(1, processingService.ProcessCallCount);
    }

    [Fact]
    public async Task VideoProcessingJobExecutionServiceFailsUnsuccessfulProcessing()
    {
        var queuedJob = CreateProcessingJobDto(
            Guid.Parse("44444444-5555-6666-7777-888888888888"),
            "media-assets/source.mp4",
            VideoProcessingJobStatus.Queued.ToString());
        var processingJob = queuedJob with
        {
            Status = VideoProcessingJobStatus.Processing.ToString(),
            StartedAt = DateTimeOffset.UtcNow
        };
        var queue = new FakeVideoProcessingQueue([]);
        var lifecycle = new FakeVideoProcessingJobLifecycleService(processingJob);
        var processingService = new FakeVideoProcessingService(
            new VideoProcessingExecutionResult(
                processingJob.Id,
                VideoProcessingExecutionStatus.Failed,
                WasExecuted: true,
                Succeeded: false,
                Message: "Processing command failed.",
                OutputPlan: null,
                Command: null,
                ExitCode: 1));
        var executionService = new VideoProcessingJobExecutionService(
            queue,
            lifecycle,
            processingService,
            NullLogger<VideoProcessingJobExecutionService>.Instance);

        var result = await executionService.ExecuteAsync(queuedJob);

        Assert.True(result.IsSuccess);
        Assert.Equal(VideoProcessingJobStatus.Failed.ToString(), result.Value.Status);
        Assert.Equal("Processing command failed.", result.Value.ErrorMessage);
        Assert.Equal(1, lifecycle.StartCallCount);
        Assert.Equal(0, lifecycle.CompleteCallCount);
        Assert.Equal(1, lifecycle.FailCallCount);
        Assert.Equal(1, processingService.ProcessCallCount);
    }

    [Fact]
    public async Task VideoProcessingJobExecutionServiceEnqueuesPendingJobBeforeProcessing()
    {
        var pendingJob = CreateProcessingJobDto(
            Guid.Parse("55555555-6666-7777-8888-999999999999"),
            "media-assets/source.mp4",
            VideoProcessingJobStatus.Pending.ToString());
        var queuedJob = pendingJob with { Status = VideoProcessingJobStatus.Queued.ToString() };
        var processingJob = pendingJob with
        {
            Status = VideoProcessingJobStatus.Processing.ToString(),
            StartedAt = DateTimeOffset.UtcNow
        };
        var queue = new FakeVideoProcessingQueue([], queuedJob);
        var lifecycle = new FakeVideoProcessingJobLifecycleService(processingJob);
        var processingService = new FakeVideoProcessingService(
            new VideoProcessingExecutionResult(
                processingJob.Id,
                VideoProcessingExecutionStatus.Succeeded,
                WasExecuted: true,
                Succeeded: true,
                Message: "Processing completed.",
                OutputPlan: null,
                Command: null,
                ExitCode: 0));
        var executionService = new VideoProcessingJobExecutionService(
            queue,
            lifecycle,
            processingService,
            NullLogger<VideoProcessingJobExecutionService>.Instance);

        var result = await executionService.ExecuteAsync(pendingJob);

        Assert.True(result.IsSuccess);
        Assert.Equal(VideoProcessingJobStatus.Completed.ToString(), result.Value.Status);
        Assert.Equal(1, queue.EnqueueCallCount);
        Assert.Equal(1, lifecycle.StartCallCount);
        Assert.Equal(1, lifecycle.CompleteCallCount);
    }

    [Fact]
    public void InfrastructureRegistersVideoProcessingJobExecutionDependencies()
    {
        var configuration = new ConfigurationBuilder()
            .AddInMemoryCollection(new Dictionary<string, string?>
            {
                ["ConnectionStrings:DefaultConnection"] = "Host=localhost;Database=zinema_test",
                ["VideoProcessing:EnableExecution"] = "false"
            })
            .Build();
        var services = new ServiceCollection();

        services.AddLogging();
        services.AddInfrastructure(configuration);

        using var serviceProvider = services.BuildServiceProvider();

        Assert.NotNull(serviceProvider.GetRequiredService<IVideoProcessingJobExecutionService>());
        Assert.NotNull(serviceProvider.GetRequiredService<IVideoProcessingService>());
        Assert.NotNull(serviceProvider.GetRequiredService<IFfmpegCommandBuilder>());
        Assert.NotNull(serviceProvider.GetRequiredService<IFfmpegAvailabilityChecker>());
    }

    [Fact]
    public void VideoProcessingOptionsDefaultsKeepExecutionDisabled()
    {
        var options = new VideoProcessingOptions();

        Assert.False(options.EnableExecution);
        Assert.Equal(string.Empty, options.FfmpegPath);
        Assert.Equal("media-output/hls", options.OutputRoot);
    }

    private static AppDbContext CreateInMemoryDbContext()
    {
        var options = new DbContextOptionsBuilder<AppDbContext>()
            .UseInMemoryDatabase(Guid.NewGuid().ToString())
            .Options;

        return new AppDbContext(options);
    }

    private static VideoProcessingJob CreateProcessingJob(
        MediaAsset mediaAsset,
        VideoProcessingJobStatus status)
    {
        return new VideoProcessingJob
        {
            MediaAssetId = mediaAsset.Id,
            SourceStorageKey = mediaAsset.StorageKey,
            Status = status,
            QueuedAt = DateTimeOffset.UtcNow.AddMinutes(-10)
        };
    }

    private static MediaAsset CreateMediaAsset(string storageKey)
    {
        return new MediaAsset
        {
            Title = "Queue Test Source",
            AssetType = "video-source",
            ContentType = "video/mp4",
            FileName = "source.mp4",
            StorageKey = storageKey,
            Status = MediaStatus.Uploaded
        };
    }

    private static VideoProcessingJobDto CreateProcessingJobDto(
        Guid id,
        string sourceStorageKey,
        string status = "Pending")
    {
        var now = DateTimeOffset.UtcNow;
        return new VideoProcessingJobDto(
            id,
            Guid.NewGuid(),
            "Video Source",
            sourceStorageKey,
            null,
            status,
            null,
            0,
            now,
            null,
            null,
            now,
            null);
    }

    private sealed class FakeFfmpegAvailabilityChecker(
        FfmpegAvailabilityDto availability) : IFfmpegAvailabilityChecker
    {
        public int CallCount { get; private set; }

        public Task<FfmpegAvailabilityDto> CheckAvailabilityAsync(
            CancellationToken cancellationToken = default)
        {
            CallCount++;
            return Task.FromResult(availability);
        }
    }

    private sealed class FakeVideoProcessingQueue(
        IReadOnlyList<VideoProcessingJobDto> queuedJobs,
        VideoProcessingJobDto? enqueuedJob = null) : IVideoProcessingQueue
    {
        public int EnqueueCallCount { get; private set; }

        public Task<Result<VideoProcessingJobDto>> EnqueueAsync(
            Guid processingJobId,
            CancellationToken cancellationToken = default)
        {
            EnqueueCallCount++;

            return Task.FromResult(enqueuedJob is null
                ? Result<VideoProcessingJobDto>.Failure(
                    VideoProcessingJobErrors.Validation("No queued test job was configured."))
                : Result<VideoProcessingJobDto>.Success(enqueuedJob));
        }

        public Task<IReadOnlyList<VideoProcessingJobDto>> GetQueuedJobsAsync(
            int maxCount,
            CancellationToken cancellationToken = default)
        {
            return Task.FromResult(queuedJobs);
        }
    }

    private sealed class FakeVideoProcessingJobLifecycleService(
        VideoProcessingJobDto processingJob) : IVideoProcessingJobLifecycleService
    {
        public int StartCallCount { get; private set; }

        public int CompleteCallCount { get; private set; }

        public int FailCallCount { get; private set; }

        public string? LastFailureMessage { get; private set; }

        public Task<Result<VideoProcessingJobDto>> StartProcessingJobAsync(
            Guid id,
            CancellationToken cancellationToken = default)
        {
            StartCallCount++;
            return Task.FromResult(Result<VideoProcessingJobDto>.Success(processingJob));
        }

        public Task<Result<VideoProcessingJobDto>> CompleteProcessingJobAsync(
            Guid id,
            CancellationToken cancellationToken = default)
        {
            CompleteCallCount++;
            return Task.FromResult(Result<VideoProcessingJobDto>.Success(
                processingJob with { Status = VideoProcessingJobStatus.Completed.ToString() }));
        }

        public Task<Result<VideoProcessingJobDto>> FailProcessingJobAsync(
            FailVideoProcessingJobCommand command,
            CancellationToken cancellationToken = default)
        {
            FailCallCount++;
            LastFailureMessage = command.ErrorMessage;
            return Task.FromResult(Result<VideoProcessingJobDto>.Success(
                processingJob with
                {
                    Status = VideoProcessingJobStatus.Failed.ToString(),
                    ErrorMessage = command.ErrorMessage
                }));
        }
    }

    private sealed class FakeVideoProcessingService(
        VideoProcessingExecutionResult executionResult) : IVideoProcessingService
    {
        public int ProcessCallCount { get; private set; }

        public Task<Result<VideoProcessingExecutionResult>> ProcessAsync(
            VideoProcessingJobDto processingJob,
            CancellationToken cancellationToken = default)
        {
            ProcessCallCount++;
            return Task.FromResult(Result<VideoProcessingExecutionResult>.Success(executionResult));
        }
    }

    private sealed class FakeVideoProcessingJobExecutionService(
        VideoProcessingJobDto executionResult) : IVideoProcessingJobExecutionService
    {
        public int ExecuteCallCount { get; private set; }

        public Guid? LastProcessingJobId { get; private set; }

        public Task<Result<VideoProcessingJobDto>> ExecuteAsync(
            VideoProcessingJobDto processingJob,
            CancellationToken cancellationToken = default)
        {
            ExecuteCallCount++;
            LastProcessingJobId = processingJob.Id;
            return Task.FromResult(Result<VideoProcessingJobDto>.Success(executionResult));
        }
    }
}
