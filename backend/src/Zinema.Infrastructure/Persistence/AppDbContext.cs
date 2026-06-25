using Microsoft.EntityFrameworkCore;
using Zinema.Domain.Entities;

namespace Zinema.Infrastructure.Persistence;

public sealed class AppDbContext(DbContextOptions<AppDbContext> options) : DbContext(options)
{
    public DbSet<Movie> Movies => Set<Movie>();

    public DbSet<Series> Series => Set<Series>();

    public DbSet<Episode> Episodes => Set<Episode>();

    public DbSet<Genre> Genres => Set<Genre>();

    public DbSet<Collection> Collections => Set<Collection>();

    public DbSet<MovieGenre> MovieGenres => Set<MovieGenre>();

    public DbSet<MediaAsset> MediaAssets => Set<MediaAsset>();

    public DbSet<VideoProcessingJob> VideoProcessingJobs => Set<VideoProcessingJob>();

    public override int SaveChanges()
    {
        SetAuditTimestamps();
        return base.SaveChanges();
    }

    public override Task<int> SaveChangesAsync(CancellationToken cancellationToken = default)
    {
        SetAuditTimestamps();
        return base.SaveChangesAsync(cancellationToken);
    }

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        modelBuilder.ApplyConfigurationsFromAssembly(typeof(AppDbContext).Assembly);
        base.OnModelCreating(modelBuilder);
    }

    private void SetAuditTimestamps()
    {
        var now = DateTimeOffset.UtcNow;

        foreach (var entry in ChangeTracker.Entries<AuditableEntity>())
        {
            if (entry.State == EntityState.Added)
            {
                entry.Entity.CreatedAt = now;
            }

            if (entry.State == EntityState.Modified)
            {
                entry.Entity.UpdatedAt = now;
            }
        }
    }
}
