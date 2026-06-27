using Zinema.Application.Common.Errors;

namespace Zinema.Application.Features.AdminCatalog;

public static class AdminCatalogErrors
{
    public static Error Validation(string message)
    {
        return Error.Create("AdminCatalog.Validation", message);
    }

    public static Error MovieNotFound(Guid id)
    {
        return Error.Create("AdminCatalog.MovieNotFound", $"Movie '{id}' was not found.");
    }

    public static Error GenreNotFound(Guid id)
    {
        return Error.Create("AdminCatalog.GenreNotFound", $"Genre '{id}' was not found.");
    }

    public static Error MissingGenres()
    {
        return Error.Create("AdminCatalog.GenreNotFound", "One or more genres were not found.");
    }

    public static Error DuplicateMovieSlug(string slug)
    {
        return Error.Create("AdminCatalog.MovieSlugConflict", $"Movie slug '{slug}' is already used.");
    }

    public static Error DuplicateGenreSlug(string slug)
    {
        return Error.Create("AdminCatalog.GenreSlugConflict", $"Genre slug '{slug}' is already used.");
    }

    public static Error DuplicateGenreName(string name)
    {
        return Error.Create("AdminCatalog.GenreNameConflict", $"Genre name '{name}' is already used.");
    }

    public static Error GenreInUse(string name)
    {
        return Error.Create("AdminCatalog.GenreInUseConflict", $"Genre '{name}' is used by one or more movies.");
    }
}
