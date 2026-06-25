using Zinema.Application.Common.Results;
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
}
