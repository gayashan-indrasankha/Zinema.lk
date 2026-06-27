using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Metadata.Builders;
using Zinema.Domain.Entities;

namespace Zinema.Infrastructure.Persistence.Configurations;

public sealed class EpisodeConfiguration : IEntityTypeConfiguration<Episode>
{
    public void Configure(EntityTypeBuilder<Episode> builder)
    {
        builder.ToTable("episodes");

        builder.HasKey(episode => episode.Id);

        builder.Property(episode => episode.Title)
            .HasMaxLength(200)
            .IsRequired();

        builder.Property(episode => episode.Slug)
            .HasMaxLength(260)
            .IsRequired();

        builder.Property(episode => episode.Description)
            .HasMaxLength(4000);

        builder.Property(episode => episode.PublishStatus)
            .HasConversion<string>()
            .HasMaxLength(32)
            .IsRequired();

        builder.HasOne(episode => episode.Series)
            .WithMany(series => series.Episodes)
            .HasForeignKey(episode => episode.SeriesId)
            .OnDelete(DeleteBehavior.Cascade);

        builder.HasIndex(episode => episode.Slug)
            .IsUnique();

        builder.HasIndex(episode => episode.Title);

        builder.HasIndex(episode => episode.PublishStatus);

        builder.HasIndex(episode => new
        {
            episode.SeriesId,
            episode.SeasonNumber,
            episode.EpisodeNumber
        }).IsUnique();
    }
}
