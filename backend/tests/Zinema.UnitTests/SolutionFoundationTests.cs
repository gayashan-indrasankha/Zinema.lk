using Zinema.Application.Common.Results;
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
}
