using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.Auth;
using Zinema.Application.Features.AdminCatalog;
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
}
