using Zinema.Application.Features.AdminCatalog;

namespace Zinema.Api.Contracts.AdminCatalog;

public sealed class AdminGenreRequest
{
    public string Name { get; init; } = string.Empty;

    public string? Slug { get; init; }

    public string? Description { get; init; }

    public CreateGenreCommand ToCreateCommand()
    {
        return new CreateGenreCommand(Name, Slug, Description);
    }

    public UpdateGenreCommand ToUpdateCommand(Guid id)
    {
        return new UpdateGenreCommand(id, Name, Slug, Description);
    }
}
