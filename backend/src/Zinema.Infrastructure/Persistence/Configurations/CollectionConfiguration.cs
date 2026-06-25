using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Metadata.Builders;
using Zinema.Domain.Entities;

namespace Zinema.Infrastructure.Persistence.Configurations;

public sealed class CollectionConfiguration : IEntityTypeConfiguration<Collection>
{
    public void Configure(EntityTypeBuilder<Collection> builder)
    {
        builder.ToTable("collections");

        builder.HasKey(collection => collection.Id);

        builder.Property(collection => collection.Title)
            .HasMaxLength(200)
            .IsRequired();

        builder.Property(collection => collection.Slug)
            .HasMaxLength(220)
            .IsRequired();

        builder.Property(collection => collection.Description)
            .HasMaxLength(4000);

        builder.Property(collection => collection.PublishStatus)
            .HasConversion<string>()
            .HasMaxLength(32)
            .IsRequired();

        builder.HasIndex(collection => collection.Slug)
            .IsUnique();

        builder.HasIndex(collection => collection.Title);

        builder.HasIndex(collection => collection.PublishStatus);
    }
}
