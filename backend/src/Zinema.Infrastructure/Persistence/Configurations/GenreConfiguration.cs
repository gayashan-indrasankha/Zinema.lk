using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Metadata.Builders;
using Zinema.Domain.Entities;

namespace Zinema.Infrastructure.Persistence.Configurations;

public sealed class GenreConfiguration : IEntityTypeConfiguration<Genre>
{
    public void Configure(EntityTypeBuilder<Genre> builder)
    {
        builder.ToTable("genres");

        builder.HasKey(genre => genre.Id);

        builder.Property(genre => genre.Name)
            .HasMaxLength(100)
            .IsRequired();

        builder.Property(genre => genre.Slug)
            .HasMaxLength(120)
            .IsRequired();

        builder.Property(genre => genre.Description)
            .HasMaxLength(1000);

        builder.HasIndex(genre => genre.Name)
            .IsUnique();

        builder.HasIndex(genre => genre.Slug)
            .IsUnique();
    }
}
