using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Metadata.Builders;
using Zinema.Domain.Entities;

namespace Zinema.Infrastructure.Persistence.Configurations;

public sealed class MovieGenreConfiguration : IEntityTypeConfiguration<MovieGenre>
{
    public void Configure(EntityTypeBuilder<MovieGenre> builder)
    {
        builder.ToTable("movie_genres");

        builder.HasKey(movieGenre => movieGenre.Id);

        builder.HasOne(movieGenre => movieGenre.Movie)
            .WithMany(movie => movie.MovieGenres)
            .HasForeignKey(movieGenre => movieGenre.MovieId)
            .OnDelete(DeleteBehavior.Cascade);

        builder.HasOne(movieGenre => movieGenre.Genre)
            .WithMany(genre => genre.MovieGenres)
            .HasForeignKey(movieGenre => movieGenre.GenreId)
            .OnDelete(DeleteBehavior.Cascade);

        builder.HasIndex(movieGenre => new
        {
            movieGenre.MovieId,
            movieGenre.GenreId
        }).IsUnique();
    }
}
