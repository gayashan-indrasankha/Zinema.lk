using Microsoft.EntityFrameworkCore;
using Zinema.Application.Common.Errors;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.AdminCatalog;
using Zinema.Application.DTOs.Catalog;
using Zinema.Application.Features.AdminCatalog;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;
using Zinema.Infrastructure.Persistence;

namespace Zinema.Infrastructure.Services;

public sealed class AdminCatalogService(AppDbContext dbContext) : IAdminCatalogService
{
    public async Task<Result<AdminMovieDto>> CreateMovieAsync(
        CreateMovieCommand command,
        CancellationToken cancellationToken = default)
    {
        var validation = ValidateMovie(command.Title, command.ReleaseYear, command.RuntimeMinutes);
        if (validation is not null)
        {
            return Result<AdminMovieDto>.Failure(validation);
        }

        var slug = SlugGenerator.Generate(command.Slug ?? command.Title);
        if (await MovieSlugExistsAsync(slug, excludedMovieId: null, cancellationToken))
        {
            return Result<AdminMovieDto>.Failure(AdminCatalogErrors.DuplicateMovieSlug(slug));
        }

        var genreIds = command.GenreIds.Distinct().ToArray();
        var genres = await LoadGenresAsync(genreIds, cancellationToken);
        if (genres.Count != genreIds.Length)
        {
            return Result<AdminMovieDto>.Failure(AdminCatalogErrors.MissingGenres());
        }

        var movie = new Movie
        {
            Title = command.Title.Trim(),
            Slug = slug,
            Description = NormalizeOptional(command.Description),
            ReleaseYear = command.ReleaseYear,
            RuntimeMinutes = command.RuntimeMinutes,
            Language = NormalizeOptional(command.Language),
            PublishStatus = command.PublishStatus
        };

        foreach (var genre in genres)
        {
            movie.MovieGenres.Add(new MovieGenre
            {
                MovieId = movie.Id,
                GenreId = genre.Id
            });
        }

        dbContext.Movies.Add(movie);
        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<AdminMovieDto>.Success(await GetMovieDtoAsync(movie.Id, cancellationToken));
    }

    public async Task<Result<AdminMovieDto>> UpdateMovieAsync(
        UpdateMovieCommand command,
        CancellationToken cancellationToken = default)
    {
        var movie = await dbContext.Movies
            .Include(item => item.MovieGenres)
            .FirstOrDefaultAsync(item => item.Id == command.Id, cancellationToken);

        if (movie is null)
        {
            return Result<AdminMovieDto>.Failure(AdminCatalogErrors.MovieNotFound(command.Id));
        }

        var validation = ValidateMovie(command.Title, command.ReleaseYear, command.RuntimeMinutes);
        if (validation is not null)
        {
            return Result<AdminMovieDto>.Failure(validation);
        }

        var slug = string.IsNullOrWhiteSpace(command.Slug)
            ? movie.Slug
            : SlugGenerator.Generate(command.Slug);

        if (await MovieSlugExistsAsync(slug, movie.Id, cancellationToken))
        {
            return Result<AdminMovieDto>.Failure(AdminCatalogErrors.DuplicateMovieSlug(slug));
        }

        var genreIds = command.GenreIds.Distinct().ToArray();
        var genres = await LoadGenresAsync(genreIds, cancellationToken);
        if (genres.Count != genreIds.Length)
        {
            return Result<AdminMovieDto>.Failure(AdminCatalogErrors.MissingGenres());
        }

        movie.Title = command.Title.Trim();
        movie.Slug = slug;
        movie.Description = NormalizeOptional(command.Description);
        movie.ReleaseYear = command.ReleaseYear;
        movie.RuntimeMinutes = command.RuntimeMinutes;
        movie.Language = NormalizeOptional(command.Language);
        movie.PublishStatus = command.PublishStatus;

        var removeLinks = movie.MovieGenres
            .Where(link => !genreIds.Contains(link.GenreId))
            .ToArray();

        dbContext.MovieGenres.RemoveRange(removeLinks);

        var existingGenreIds = movie.MovieGenres
            .Select(link => link.GenreId)
            .Except(removeLinks.Select(link => link.GenreId))
            .ToHashSet();

        foreach (var genre in genres.Where(genre => !existingGenreIds.Contains(genre.Id)))
        {
            movie.MovieGenres.Add(new MovieGenre
            {
                MovieId = movie.Id,
                GenreId = genre.Id
            });
        }

        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<AdminMovieDto>.Success(await GetMovieDtoAsync(movie.Id, cancellationToken));
    }

    public async Task<Result> DeleteMovieAsync(
        Guid id,
        CancellationToken cancellationToken = default)
    {
        var movie = await dbContext.Movies.FirstOrDefaultAsync(item => item.Id == id, cancellationToken);
        if (movie is null)
        {
            return Result.Failure(AdminCatalogErrors.MovieNotFound(id));
        }

        movie.PublishStatus = PublishStatus.Archived;
        await dbContext.SaveChangesAsync(cancellationToken);

        return Result.Success();
    }

    public Task<Result<AdminMovieDto>> PublishMovieAsync(
        Guid id,
        CancellationToken cancellationToken = default)
    {
        return SetMoviePublishStatusAsync(id, PublishStatus.Published, cancellationToken);
    }

    public Task<Result<AdminMovieDto>> UnpublishMovieAsync(
        Guid id,
        CancellationToken cancellationToken = default)
    {
        return SetMoviePublishStatusAsync(id, PublishStatus.Draft, cancellationToken);
    }

    public async Task<Result<AdminGenreDto>> CreateGenreAsync(
        CreateGenreCommand command,
        CancellationToken cancellationToken = default)
    {
        var validation = ValidateGenre(command.Name);
        if (validation is not null)
        {
            return Result<AdminGenreDto>.Failure(validation);
        }

        var name = command.Name.Trim();
        var slug = SlugGenerator.Generate(command.Slug ?? name);

        if (await GenreNameExistsAsync(name, excludedGenreId: null, cancellationToken))
        {
            return Result<AdminGenreDto>.Failure(AdminCatalogErrors.DuplicateGenreName(name));
        }

        if (await GenreSlugExistsAsync(slug, excludedGenreId: null, cancellationToken))
        {
            return Result<AdminGenreDto>.Failure(AdminCatalogErrors.DuplicateGenreSlug(slug));
        }

        var genre = new Genre
        {
            Name = name,
            Slug = slug,
            Description = NormalizeOptional(command.Description)
        };

        dbContext.Genres.Add(genre);
        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<AdminGenreDto>.Success(ToGenreDto(genre));
    }

    public async Task<Result<AdminGenreDto>> UpdateGenreAsync(
        UpdateGenreCommand command,
        CancellationToken cancellationToken = default)
    {
        var genre = await dbContext.Genres.FirstOrDefaultAsync(item => item.Id == command.Id, cancellationToken);
        if (genre is null)
        {
            return Result<AdminGenreDto>.Failure(AdminCatalogErrors.GenreNotFound(command.Id));
        }

        var validation = ValidateGenre(command.Name);
        if (validation is not null)
        {
            return Result<AdminGenreDto>.Failure(validation);
        }

        var name = command.Name.Trim();
        var slug = string.IsNullOrWhiteSpace(command.Slug)
            ? genre.Slug
            : SlugGenerator.Generate(command.Slug);

        if (await GenreNameExistsAsync(name, genre.Id, cancellationToken))
        {
            return Result<AdminGenreDto>.Failure(AdminCatalogErrors.DuplicateGenreName(name));
        }

        if (await GenreSlugExistsAsync(slug, genre.Id, cancellationToken))
        {
            return Result<AdminGenreDto>.Failure(AdminCatalogErrors.DuplicateGenreSlug(slug));
        }

        genre.Name = name;
        genre.Slug = slug;
        genre.Description = NormalizeOptional(command.Description);

        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<AdminGenreDto>.Success(ToGenreDto(genre));
    }

    public async Task<Result> DeleteGenreAsync(
        Guid id,
        CancellationToken cancellationToken = default)
    {
        var genre = await dbContext.Genres.FirstOrDefaultAsync(item => item.Id == id, cancellationToken);
        if (genre is null)
        {
            return Result.Failure(AdminCatalogErrors.GenreNotFound(id));
        }

        var isUsed = await dbContext.MovieGenres.AnyAsync(link => link.GenreId == id, cancellationToken);
        if (isUsed)
        {
            return Result.Failure(AdminCatalogErrors.GenreInUse(genre.Name));
        }

        dbContext.Genres.Remove(genre);
        await dbContext.SaveChangesAsync(cancellationToken);

        return Result.Success();
    }

    private async Task<Result<AdminMovieDto>> SetMoviePublishStatusAsync(
        Guid id,
        PublishStatus publishStatus,
        CancellationToken cancellationToken)
    {
        var movie = await dbContext.Movies.FirstOrDefaultAsync(item => item.Id == id, cancellationToken);
        if (movie is null)
        {
            return Result<AdminMovieDto>.Failure(AdminCatalogErrors.MovieNotFound(id));
        }

        movie.PublishStatus = publishStatus;
        await dbContext.SaveChangesAsync(cancellationToken);

        return Result<AdminMovieDto>.Success(await GetMovieDtoAsync(movie.Id, cancellationToken));
    }

    private async Task<AdminMovieDto> GetMovieDtoAsync(Guid id, CancellationToken cancellationToken)
    {
        return await dbContext.Movies
            .AsNoTracking()
            .Where(movie => movie.Id == id)
            .Select(movie => new AdminMovieDto(
                movie.Id,
                movie.Title,
                movie.Slug,
                movie.Description,
                movie.ReleaseYear,
                movie.RuntimeMinutes,
                movie.Language,
                movie.PublishStatus.ToString(),
                movie.MovieGenres
                    .OrderBy(link => link.Genre!.Name)
                    .Select(link => new GenreDto(
                        link.Genre!.Id,
                        link.Genre.Name,
                        link.Genre.Slug))
                    .ToList()))
            .FirstAsync(cancellationToken);
    }

    private async Task<List<Genre>> LoadGenresAsync(
        IReadOnlyCollection<Guid> genreIds,
        CancellationToken cancellationToken)
    {
        return await dbContext.Genres
            .Where(genre => genreIds.Contains(genre.Id))
            .ToListAsync(cancellationToken);
    }

    private Task<bool> MovieSlugExistsAsync(
        string slug,
        Guid? excludedMovieId,
        CancellationToken cancellationToken)
    {
        return dbContext.Movies.AnyAsync(
            movie => movie.Slug == slug && (!excludedMovieId.HasValue || movie.Id != excludedMovieId.Value),
            cancellationToken);
    }

    private Task<bool> GenreSlugExistsAsync(
        string slug,
        Guid? excludedGenreId,
        CancellationToken cancellationToken)
    {
        return dbContext.Genres.AnyAsync(
            genre => genre.Slug == slug && (!excludedGenreId.HasValue || genre.Id != excludedGenreId.Value),
            cancellationToken);
    }

    private Task<bool> GenreNameExistsAsync(
        string name,
        Guid? excludedGenreId,
        CancellationToken cancellationToken)
    {
        return dbContext.Genres.AnyAsync(
            genre => EF.Functions.ILike(genre.Name, name) &&
                     (!excludedGenreId.HasValue || genre.Id != excludedGenreId.Value),
            cancellationToken);
    }

    private static AdminGenreDto ToGenreDto(Genre genre)
    {
        return new AdminGenreDto(
            genre.Id,
            genre.Name,
            genre.Slug,
            genre.Description);
    }

    private static string? NormalizeOptional(string? value)
    {
        return string.IsNullOrWhiteSpace(value) ? null : value.Trim();
    }

    private static Error? ValidateMovie(
        string title,
        int? releaseYear,
        int? runtimeMinutes)
    {
        if (string.IsNullOrWhiteSpace(title))
        {
            return AdminCatalogErrors.Validation("Movie title is required.");
        }

        if (title.Trim().Length > 200)
        {
            return AdminCatalogErrors.Validation("Movie title must be 200 characters or fewer.");
        }

        if (releaseYear is <= 0)
        {
            return AdminCatalogErrors.Validation("Release year must be greater than zero.");
        }

        if (runtimeMinutes is <= 0)
        {
            return AdminCatalogErrors.Validation("Runtime minutes must be greater than zero.");
        }

        return null;
    }

    private static Error? ValidateGenre(string name)
    {
        if (string.IsNullOrWhiteSpace(name))
        {
            return AdminCatalogErrors.Validation("Genre name is required.");
        }

        if (name.Trim().Length > 100)
        {
            return AdminCatalogErrors.Validation("Genre name must be 100 characters or fewer.");
        }

        return null;
    }
}
