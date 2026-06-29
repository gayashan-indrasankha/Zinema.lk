using Microsoft.EntityFrameworkCore;
using Zinema.Application.Common.Errors;
using Zinema.Application.Common.Results;
using Zinema.Application.DTOs.Catalog;
using Zinema.Application.DTOs.VideoPlayback;
using Zinema.Application.Features.AdminMediaAssets;
using Zinema.Application.Features.Catalog;
using Zinema.Application.Features.VideoPlayback;
using Zinema.Domain.Entities;
using Zinema.Domain.Enums;
using Zinema.Infrastructure.Persistence;

namespace Zinema.Infrastructure.Services;

public sealed class CatalogQueryService(
    AppDbContext dbContext,
    IVideoPlaybackOutputService videoPlaybackOutputService) : ICatalogQueryService
{
    private const string PosterAssetType = "poster";
    private const string BackdropAssetType = "backdrop";
    private const string VideoMp4ContentType = "video/mp4";

    public async Task<PagedResultDto<MovieListItemDto>> GetMoviesAsync(
        GetMoviesQuery query,
        CancellationToken cancellationToken = default)
    {
        var moviesQuery = dbContext.Movies
            .AsNoTracking()
            .Where(movie => movie.PublishStatus == query.PublishStatus);

        if (!string.IsNullOrWhiteSpace(query.Search))
        {
            var pattern = $"%{query.Search}%";

            moviesQuery = moviesQuery.Where(movie =>
                EF.Functions.ILike(movie.Title, pattern) ||
                (movie.Description != null && EF.Functions.ILike(movie.Description, pattern)));
        }

        if (!string.IsNullOrWhiteSpace(query.Genre))
        {
            var genre = query.Genre;

            moviesQuery = moviesQuery.Where(movie =>
                movie.MovieGenres.Any(movieGenre =>
                    movieGenre.Genre != null &&
                    (EF.Functions.ILike(movieGenre.Genre.Slug, genre) ||
                     EF.Functions.ILike(movieGenre.Genre.Name, genre))));
        }

        moviesQuery = query.SortBy switch
        {
            GetMoviesQuery.SortTitle => moviesQuery
                .OrderBy(movie => movie.Title)
                .ThenByDescending(movie => movie.CreatedAt),
            GetMoviesQuery.SortYear => moviesQuery
                .OrderByDescending(movie => movie.ReleaseYear)
                .ThenBy(movie => movie.Title),
            _ => moviesQuery
                .OrderByDescending(movie => movie.CreatedAt)
                .ThenBy(movie => movie.Title)
        };

        var totalCount = await moviesQuery.CountAsync(cancellationToken);
        var skip = (query.Page - 1) * query.PageSize;

        var items = await moviesQuery
            .Skip(skip)
            .Take(query.PageSize)
            .Select(movie => new MovieListItemDto(
                movie.Id,
                movie.Title,
                movie.Slug,
                movie.Description,
                movie.ReleaseYear,
                movie.RuntimeMinutes,
                movie.Language,
                movie.MovieGenres
                    .OrderBy(movieGenre => movieGenre.Genre!.Name)
                    .Select(movieGenre => new GenreDto(
                        movieGenre.Genre!.Id,
                        movieGenre.Genre.Name,
                        movieGenre.Genre.Slug))
                    .ToList(),
                SelectMediaAsset(movie.MediaAssets, PosterAssetType)))
            .ToListAsync(cancellationToken);

        return new PagedResultDto<MovieListItemDto>(
            items,
            query.Page,
            query.PageSize,
            totalCount);
    }

    public async Task<Result<MovieDetailDto>> GetMovieBySlugAsync(
        GetMovieBySlugQuery query,
        CancellationToken cancellationToken = default)
    {
        if (string.IsNullOrWhiteSpace(query.Slug))
        {
            return Result<MovieDetailDto>.Failure(
                Error.Create("Catalog.MovieNotFound", "Movie was not found."));
        }

        var normalizedSlug = query.Slug.ToLowerInvariant();

        var movie = await dbContext.Movies
            .AsNoTracking()
            .Where(movie => movie.PublishStatus == query.PublishStatus)
            .Where(movie => movie.Slug.ToLower() == normalizedSlug)
            .Select(movie => new MovieDetailDto(
                movie.Id,
                movie.Title,
                movie.Slug,
                movie.Description,
                movie.ReleaseYear,
                movie.RuntimeMinutes,
                movie.Language,
                movie.MovieGenres
                    .OrderBy(movieGenre => movieGenre.Genre!.Name)
                    .Select(movieGenre => new GenreDto(
                        movieGenre.Genre!.Id,
                        movieGenre.Genre.Name,
                        movieGenre.Genre.Slug))
                    .ToList(),
                SelectMediaAsset(movie.MediaAssets, PosterAssetType),
                SelectMediaAsset(movie.MediaAssets, BackdropAssetType),
                movie.MediaAssets
                    .Where(asset => asset.Status == MediaStatus.Ready)
                    .Where(asset =>
                        asset.AssetType.ToLower() == PosterAssetType ||
                        asset.AssetType.ToLower() == BackdropAssetType)
                    .OrderBy(asset => asset.AssetType)
                    .ThenBy(asset => asset.Title)
                    .Select(asset => new MediaAssetDto(
                        asset.Id,
                        asset.Title,
                        asset.AssetType,
                        asset.ContentType,
                        asset.PublicUrl,
                        asset.FileSizeBytes))
                    .ToList()))
            .FirstOrDefaultAsync(cancellationToken);

        if (movie is null)
        {
            return Result<MovieDetailDto>.Failure(Error.Create("Catalog.MovieNotFound", "Movie was not found."));
        }

        var sourceVideoMediaAssetId = await GetSourceVideoMediaAssetIdAsync(
            movie.Id,
            cancellationToken);

        var playback = sourceVideoMediaAssetId.HasValue
            ? await GetPlaybackSummaryAsync(sourceVideoMediaAssetId.Value, cancellationToken)
            : CreateUnavailablePlaybackSummary(
                "NoSource",
                "Playback source is not available yet.");

        return Result<MovieDetailDto>.Success(movie with { Playback = playback });
    }

    public async Task<IReadOnlyList<GenreDto>> GetGenresAsync(
        GetGenresQuery query,
        CancellationToken cancellationToken = default)
    {
        return await dbContext.Genres
            .AsNoTracking()
            .Where(genre => genre.MovieGenres.Any(movieGenre =>
                movieGenre.Movie != null &&
                movieGenre.Movie.PublishStatus == PublishStatus.Published))
            .OrderBy(genre => genre.Name)
            .Select(genre => new GenreDto(
                genre.Id,
                genre.Name,
                genre.Slug))
            .ToListAsync(cancellationToken);
    }

    private static MediaAssetDto? SelectMediaAsset(
        IEnumerable<MediaAsset> mediaAssets,
        string assetType)
    {
        return mediaAssets
            .Where(asset => asset.Status == MediaStatus.Ready)
            .Where(asset => asset.AssetType.Equals(assetType, StringComparison.OrdinalIgnoreCase))
            .OrderBy(asset => asset.Title)
            .Select(asset => new MediaAssetDto(
                asset.Id,
                asset.Title,
                asset.AssetType,
                asset.ContentType,
                asset.PublicUrl,
                asset.FileSizeBytes))
            .FirstOrDefault();
    }

    private async Task<Guid?> GetSourceVideoMediaAssetIdAsync(
        Guid movieId,
        CancellationToken cancellationToken)
    {
        return await dbContext.MediaAssets
            .AsNoTracking()
            .Where(asset => asset.MovieId == movieId)
            .Where(asset => asset.AssetType.ToLower() == MediaAssetProcessingRules.SourceVideoAssetType)
            .Where(asset => asset.ContentType.ToLower() == VideoMp4ContentType)
            .OrderByDescending(asset => asset.CreatedAt)
            .ThenBy(asset => asset.Id)
            .Select(asset => (Guid?)asset.Id)
            .FirstOrDefaultAsync(cancellationToken);
    }

    private async Task<CatalogPlaybackSummaryDto> GetPlaybackSummaryAsync(
        Guid sourceVideoMediaAssetId,
        CancellationToken cancellationToken)
    {
        var playbackOutput = await videoPlaybackOutputService.GetPlaybackOutputAsync(
            sourceVideoMediaAssetId,
            cancellationToken);

        if (playbackOutput.IsFailure)
        {
            return CreateUnavailablePlaybackSummary(
                "NoSource",
                "Playback source is not available yet.");
        }

        return CreatePlaybackSummary(sourceVideoMediaAssetId, playbackOutput.Value);
    }

    private static CatalogPlaybackSummaryDto CreatePlaybackSummary(
        Guid sourceVideoMediaAssetId,
        VideoPlaybackOutputDto playbackOutput)
    {
        if (!playbackOutput.IsPlayable)
        {
            return CreateUnavailablePlaybackSummary(
                playbackOutput.Status,
                string.IsNullOrWhiteSpace(playbackOutput.Message)
                    ? "Playback output is not ready yet."
                    : playbackOutput.Message);
        }

        return new CatalogPlaybackSummaryDto(
            Available: true,
            PlaybackUrl: $"/api/videos/{sourceVideoMediaAssetId:D}/playback",
            ManifestUrl: playbackOutput.PlaybackUrl,
            Status: playbackOutput.Status,
            Reason: null);
    }

    private static CatalogPlaybackSummaryDto CreateUnavailablePlaybackSummary(
        string status,
        string reason)
    {
        return new CatalogPlaybackSummaryDto(
            Available: false,
            PlaybackUrl: null,
            ManifestUrl: null,
            Status: status,
            Reason: reason);
    }
}
