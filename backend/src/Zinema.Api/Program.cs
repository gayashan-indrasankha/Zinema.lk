using Zinema.Api.Extensions;
using Zinema.Application;
using Zinema.Infrastructure;

var builder = WebApplication.CreateBuilder(args);

builder.Services
    .AddApiServices(builder.Configuration)
    .AddApplication()
    .AddInfrastructure(builder.Configuration);

var app = builder.Build();

app.UseApiPipeline();

await app.Services.ApplyDevelopmentDatabaseSetupAsync(
    app.Configuration,
    app.Environment.IsDevelopment());

app.Run();

public partial class Program;
