using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Metadata.Builders;
using Zinema.Domain.Entities;

namespace Zinema.Infrastructure.Persistence.Configurations;

public sealed class SeriesConfiguration : IEntityTypeConfiguration<Series>
{
    public void Configure(EntityTypeBuilder<Series> builder)
    {
        builder.ToTable("series");

        builder.HasKey(series => series.Id);

        builder.Property(series => series.Title)
            .HasMaxLength(200)
            .IsRequired();

        builder.Property(series => series.Slug)
            .HasMaxLength(220)
            .IsRequired();

        builder.Property(series => series.Description)
            .HasMaxLength(4000);

        builder.Property(series => series.Language)
            .HasMaxLength(50);

        builder.Property(series => series.PublishStatus)
            .HasConversion<string>()
            .HasMaxLength(32)
            .IsRequired();

        builder.HasIndex(series => series.Slug)
            .IsUnique();

        builder.HasIndex(series => series.Title);

        builder.HasIndex(series => series.PublishStatus);
    }
}
