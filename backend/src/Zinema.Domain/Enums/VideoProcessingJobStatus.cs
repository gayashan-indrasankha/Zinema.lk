namespace Zinema.Domain.Enums;

public enum VideoProcessingJobStatus
{
    Pending = 0,
    Queued = 1,
    Processing = 2,
    Completed = 3,
    Failed = 4,
    Cancelled = 5
}
