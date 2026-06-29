using Zinema.Worker;
using Zinema.Application;
using Zinema.Application.Features.VideoProcessingJobs;
using Zinema.Infrastructure;

var builder = Host.CreateApplicationBuilder(args);
builder.Services.AddApplication();
builder.Services.AddInfrastructure(builder.Configuration);
builder.Services.AddScoped<IVideoProcessingJobRunner, PlaceholderVideoProcessingJobRunner>();
builder.Services.AddHostedService<Worker>();

var host = builder.Build();
host.Run();
