using Zinema.Application.Common.Results;
using Zinema.Application.Features.AdminCatalog;
using Zinema.Domain.Enums;

namespace Zinema.Api.Contracts.AdminCatalog;

public sealed class AdminMovieRequest
{
    public string Title { get; init; } = string.Empty;

    public string? Slug { get; init; }

    public string? Description { get; init; }

    public int? ReleaseYear { get; init; }

    public int? RuntimeMinutes { get; init; }

    public string? Language { get; init; }

    public IReadOnlyCollection<Guid>? GenreIds { get; init; }

    public string? PublishStatus { get; init; }

    public Result<CreateMovieCommand> ToCreateCommand()
    {
        var publishStatus = ParsePublishStatus(PublishStatus);
        if (publishStatus.IsFailure)
        {
            return Result<CreateMovieCommand>.Failure(publishStatus.Error);
        }

        return Result<CreateMovieCommand>.Success(new CreateMovieCommand(
            Title,
            Slug,
            Description,
            ReleaseYear,
            RuntimeMinutes,
            Language,
            GenreIds ?? [],
            publishStatus.Value));
    }

    public Result<UpdateMovieCommand> ToUpdateCommand(Guid id)
    {
        var publishStatus = ParsePublishStatus(PublishStatus);
        if (publishStatus.IsFailure)
        {
            return Result<UpdateMovieCommand>.Failure(publishStatus.Error);
        }

        return Result<UpdateMovieCommand>.Success(new UpdateMovieCommand(
            id,
            Title,
            Slug,
            Description,
            ReleaseYear,
            RuntimeMinutes,
            Language,
            GenreIds ?? [],
            publishStatus.Value));
    }

    private static Result<Zinema.Domain.Enums.PublishStatus> ParsePublishStatus(string? value)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return Result<Zinema.Domain.Enums.PublishStatus>.Success(
                Zinema.Domain.Enums.PublishStatus.Draft);
        }

        return Enum.TryParse<Zinema.Domain.Enums.PublishStatus>(value, ignoreCase: true, out var status)
            ? Result<Zinema.Domain.Enums.PublishStatus>.Success(status)
            : Result<Zinema.Domain.Enums.PublishStatus>.Failure(
                AdminCatalogErrors.Validation("Publish status is invalid."));
    }
}
