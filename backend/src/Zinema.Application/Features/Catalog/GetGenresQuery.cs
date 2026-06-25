namespace Zinema.Application.Features.Catalog;

public sealed record GetGenresQuery
{
    public static readonly GetGenresQuery Active = new();

    private GetGenresQuery()
    {
    }
}
