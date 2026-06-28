using Microsoft.EntityFrameworkCore;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.Auth;
using Zinema.Application.Features.AdminCatalog;
using Zinema.Application.Features.AdminMediaAssets;
using Zinema.Application.Features.Auth;
using Zinema.Application.Features.Catalog;
using Zinema.Application.Features.VideoProcessingJobs;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;
using Zinema.Infrastructure.Persistence;
using Zinema.Infrastructure.Services;

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
}
