using System.Net;
using System.Net.Http.Json;
using System.Text.Json;

namespace Zinema.IntegrationTests;

public sealed class ApiSmokeTests(ZinemaApiFactory factory) : IClassFixture<ZinemaApiFactory>
{
    [Fact]
    public async Task HealthEndpointReturnsSuccess()
    {
        using var client = factory.CreateClient();

        var response = await client.GetAsync("/health");

        Assert.True(response.IsSuccessStatusCode);
    }

    [Fact]
    public async Task CatalogGenresEndpointReturnsJsonArray()
    {
        using var client = factory.CreateClient();

        var response = await client.GetAsync("/api/catalog/genres");
        var json = await response.Content.ReadFromJsonAsync<JsonElement>();

        Assert.Equal(HttpStatusCode.OK, response.StatusCode);
        Assert.Equal(JsonValueKind.Array, json.ValueKind);
    }

    [Fact]
    public async Task AdminEndpointRequiresAuthentication()
    {
        using var client = factory.CreateClient();

        var response = await client.GetAsync("/api/admin/ping");

        Assert.True(response.StatusCode is HttpStatusCode.Unauthorized or HttpStatusCode.Forbidden);
    }
}
