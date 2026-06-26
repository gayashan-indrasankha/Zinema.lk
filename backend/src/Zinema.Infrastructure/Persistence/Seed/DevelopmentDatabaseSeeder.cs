using Microsoft.EntityFrameworkCore;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;

namespace Zinema.Infrastructure.Persistence.Seed;

public static class DevelopmentDatabaseSeeder
{
    public static async Task SeedAsync(
        AppDbContext dbContext,
        CancellationToken cancellationToken = default)
    {
        var genres = await SeedGenresAsync(dbContext, cancellationToken);
        var movies = await SeedMoviesAsync(dbContext, cancellationToken);

        await SeedMovieGenresAsync(dbContext, movies, genres, cancellationToken);
    }

    private static async Task<Dictionary<string, Genre>> SeedGenresAsync(
        AppDbContext dbContext,
        CancellationToken cancellationToken)
    {
        var genreSeeds = new[]
        {
            new GenreSeed("Action", "action"),
            new GenreSeed("Drama", "drama"),
            new GenreSeed("Family", "family"),
            new GenreSeed("Mystery", "mystery")
        };

        var slugs = genreSeeds.Select(genre => genre.Slug).ToArray();
        var existingGenres = await dbContext.Genres
            .Where(genre => slugs.Contains(genre.Slug))
            .ToDictionaryAsync(genre => genre.Slug, cancellationToken);

        foreach (var seed in genreSeeds)
        {
            if (existingGenres.ContainsKey(seed.Slug))
            {
                continue;
            }

            var genre = new Genre
            {
                Name = seed.Name,
                Slug = seed.Slug,
                Description = $"{seed.Name} catalog demo genre."
            };

            dbContext.Genres.Add(genre);
            existingGenres[genre.Slug] = genre;
        }

        await dbContext.SaveChangesAsync(cancellationToken);

        return existingGenres;
    }

    private static async Task<Dictionary<string, Movie>> SeedMoviesAsync(
        AppDbContext dbContext,
        CancellationToken cancellationToken)
    {
        var movieSeeds = new[]
        {
            new MovieSeed(
                "Demo Action Feature",
                "demo-action-feature",
                "A neutral demo catalog entry for testing public movie browsing.",
                2026,
                118,
                "English"),
            new MovieSeed(
                "Sample Drama Story",
                "sample-drama-story",
                "A safe sample entry for validating public catalog detail pages.",
                2025,
                104,
                "English"),
            new MovieSeed(
                "Neutral Family Adventure",
                "neutral-family-adventure",
                "A family-friendly demo record for local development workflows.",
                2024,
                96,
                "English"),
            new MovieSeed(
                "Catalog Mystery Sample",
                "catalog-mystery-sample",
                "A neutral mystery entry for search and filtering checks.",
                2023,
                111,
                "English")
        };

        var slugs = movieSeeds.Select(movie => movie.Slug).ToArray();
        var existingMovies = await dbContext.Movies
            .Where(movie => slugs.Contains(movie.Slug))
            .ToDictionaryAsync(movie => movie.Slug, cancellationToken);

        foreach (var seed in movieSeeds)
        {
            if (existingMovies.ContainsKey(seed.Slug))
            {
                continue;
            }

            var movie = new Movie
            {
                Title = seed.Title,
                Slug = seed.Slug,
                Description = seed.Description,
                ReleaseYear = seed.ReleaseYear,
                RuntimeMinutes = seed.RuntimeMinutes,
                Language = seed.Language,
                PublishStatus = PublishStatus.Published
            };

            dbContext.Movies.Add(movie);
            existingMovies[movie.Slug] = movie;
        }

        await dbContext.SaveChangesAsync(cancellationToken);

        return existingMovies;
    }

    private static async Task SeedMovieGenresAsync(
        AppDbContext dbContext,
        IReadOnlyDictionary<string, Movie> movies,
        IReadOnlyDictionary<string, Genre> genres,
        CancellationToken cancellationToken)
    {
        var links = new[]
        {
            new MovieGenreSeed("demo-action-feature", "action"),
            new MovieGenreSeed("sample-drama-story", "drama"),
            new MovieGenreSeed("neutral-family-adventure", "family"),
            new MovieGenreSeed("catalog-mystery-sample", "mystery"),
            new MovieGenreSeed("catalog-mystery-sample", "drama")
        };

        foreach (var link in links)
        {
            if (!movies.TryGetValue(link.MovieSlug, out var movie) ||
                !genres.TryGetValue(link.GenreSlug, out var genre))
            {
                continue;
            }

            var exists = await dbContext.MovieGenres.AnyAsync(
                movieGenre => movieGenre.MovieId == movie.Id && movieGenre.GenreId == genre.Id,
                cancellationToken);

            if (exists)
            {
                continue;
            }

            dbContext.MovieGenres.Add(new MovieGenre
            {
                MovieId = movie.Id,
                GenreId = genre.Id
            });
        }

        await dbContext.SaveChangesAsync(cancellationToken);
    }

    private sealed record GenreSeed(string Name, string Slug);

    private sealed record MovieSeed(
        string Title,
        string Slug,
        string Description,
        int ReleaseYear,
        int RuntimeMinutes,
        string Language);

    private sealed record MovieGenreSeed(string MovieSlug, string GenreSlug);
}
