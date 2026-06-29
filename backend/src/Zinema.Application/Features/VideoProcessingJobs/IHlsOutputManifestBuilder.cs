using Zinema.Application.Common.Results;

namespace Zinema.Application.Features.VideoProcessingJobs;

public interface IHlsOutputManifestBuilder
{
    Result<HlsOutputManifest> BuildManifest(
        HlsOutputPlan outputPlan,
        DateTimeOffset? generatedAt = null);
}
