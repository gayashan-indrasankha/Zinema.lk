using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.Auth;
using Zinema.Application.Features.AdminCatalog;
using Zinema.Application.Features.AdminMediaAssets;
using Zinema.Application.Features.Auth;
using Zinema.Application.Features.Catalog;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;

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
}
