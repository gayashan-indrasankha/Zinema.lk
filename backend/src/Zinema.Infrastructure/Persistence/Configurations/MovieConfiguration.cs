using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Metadata.Builders;
using Zinema.Domain.Entities;

namespace Zinema.Infrastructure.Persistence.Configurations;

public sealed class MovieConfiguration : IEntityTypeConfiguration<Movie>
{
    public void Configure(EntityTypeBuilder<Movie> builder)
    {
        builder.ToTable("movies");

        builder.HasKey(movie => movie.Id);

        builder.Property(movie => movie.Title)
            .HasMaxLength(200)
            .IsRequired();

        builder.Property(movie => movie.Slug)
            .HasMaxLength(220)
            .IsRequired();

        builder.Property(movie => movie.Description)
            .HasMaxLength(4000);

        builder.Property(movie => movie.Language)
            .HasMaxLength(50);

        builder.Property(movie => movie.PublishStatus)
            .HasConversion<string>()
            .HasMaxLength(32)
            .IsRequired();

        builder.HasIndex(movie => movie.Slug)
            .IsUnique();

        builder.HasIndex(movie => movie.Title);

        builder.HasIndex(movie => movie.PublishStatus);

        builder.HasOne(movie => movie.Collection)
            .WithMany(collection => collection.Movies)
            .HasForeignKey(movie => movie.CollectionId)
            .OnDelete(DeleteBehavior.SetNull);
    }
}
