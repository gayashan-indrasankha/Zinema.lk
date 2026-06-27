using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Metadata.Builders;
using Zinema.Domain.Entities;

namespace Zinema.Infrastructure.Persistence.Configurations;

public sealed class VideoProcessingJobConfiguration : IEntityTypeConfiguration<VideoProcessingJob>
{
    public void Configure(EntityTypeBuilder<VideoProcessingJob> builder)
    {
        builder.ToTable("video_processing_jobs");

        builder.HasKey(job => job.Id);

        builder.Property(job => job.Status)
            .HasConversion<string>()
            .HasMaxLength(32)
            .IsRequired();

        builder.Property(job => job.SourceStorageKey)
            .HasMaxLength(1024)
            .IsRequired();

        builder.Property(job => job.OutputStoragePrefix)
            .HasMaxLength(1024);

        builder.Property(job => job.ErrorMessage)
            .HasMaxLength(4000);

        builder.HasOne(job => job.MediaAsset)
            .WithMany(asset => asset.VideoProcessingJobs)
            .HasForeignKey(job => job.MediaAssetId)
            .OnDelete(DeleteBehavior.Cascade);

        builder.HasIndex(job => job.Status);

        builder.HasIndex(job => job.QueuedAt);
    }
}
