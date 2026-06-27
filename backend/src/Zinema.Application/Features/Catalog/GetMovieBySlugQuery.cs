using Zinema.Domain.Enums;

namespace Zinema.Application.Features.Catalog;

public sealed record GetMovieBySlugQuery(string Slug, PublishStatus PublishStatus)
{
    public static GetMovieBySlugQuery Create(string slug)
    {
        return new GetMovieBySlugQuery(slug.Trim(), PublishStatus.Published);
    }
}
