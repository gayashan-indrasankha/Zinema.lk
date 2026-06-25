namespace Zinema.Domain.Entities;

public sealed class MovieGenre : AuditableEntity
{
    public Guid MovieId { get; set; }

    public Movie? Movie { get; set; }

    public Guid GenreId { get; set; }

    public Genre? Genre { get; set; }
}
