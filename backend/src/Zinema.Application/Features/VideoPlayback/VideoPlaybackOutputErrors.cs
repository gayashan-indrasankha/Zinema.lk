using Zinema.Application.Common.Errors;

namespace Zinema.Application.Features.VideoPlayback;

public static class VideoPlaybackOutputErrors
{
    public static Error VideoNotFound(Guid id)
    {
        return Error.Create("VideoPlayback.NotFound", $"Video '{id}' was not found.");
    }

    public static Error Validation(string message)
    {
        return Error.Create("VideoPlayback.Validation", message);
    }
}
