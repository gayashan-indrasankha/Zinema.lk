using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Metadata.Builders;
using Zinema.Domain.Entities;

namespace Zinema.Infrastructure.Persistence.Configurations;

public sealed class MediaAssetConfiguration : IEntityTypeConfiguration<MediaAsset>
{
    public void Configure(EntityTypeBuilder<MediaAsset> builder)
    {
        builder.ToTable("media_assets");

        builder.HasKey(asset => asset.Id);

        builder.Property(asset => asset.Title)
            .HasMaxLength(200)
            .IsRequired();

        builder.Property(asset => asset.FileName)
            .HasMaxLength(255)
            .IsRequired();

        builder.Property(asset => asset.ContentType)
            .HasMaxLength(100)
            .IsRequired();

        builder.Property(asset => asset.AssetType)
            .HasMaxLength(50)
            .IsRequired();

        builder.Property(asset => asset.StorageKey)
            .HasMaxLength(1024)
            .IsRequired();

        builder.Property(asset => asset.PublicUrl)
            .HasMaxLength(2048);

        builder.Property(asset => asset.Status)
            .HasConversion<string>()
            .HasMaxLength(32)
            .IsRequired();

        builder.HasIndex(asset => asset.StorageKey)
            .IsUnique();

        builder.HasIndex(asset => asset.Title);

        builder.HasIndex(asset => asset.Status);

        builder.HasOne(asset => asset.Movie)
            .WithMany(movie => movie.MediaAssets)
            .HasForeignKey(asset => asset.MovieId)
            .OnDelete(DeleteBehavior.SetNull);

        builder.HasOne(asset => asset.Series)
            .WithMany(series => series.MediaAssets)
            .HasForeignKey(asset => asset.SeriesId)
            .OnDelete(DeleteBehavior.SetNull);

        builder.HasOne(asset => asset.Episode)
            .WithMany(episode => episode.MediaAssets)
            .HasForeignKey(asset => asset.EpisodeId)
            .OnDelete(DeleteBehavior.SetNull);

        builder.HasOne(asset => asset.Collection)
            .WithMany(collection => collection.MediaAssets)
            .HasForeignKey(asset => asset.CollectionId)
            .OnDelete(DeleteBehavior.SetNull);
    }
}
