<?php

test('health endpoint identifies the panel as navspace', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJsonPath('data.app', 'navspace')
        ->assertJsonPath('data.api_version', 1)
        ->assertJsonStructure([
            'data' => ['app', 'api_version', 'name'],
        ]);
});

test('health endpoint is public', function () {
    $this->getJson('/api/health')->assertOk();
});

test('health endpoint reports the configured app name', function () {
    config(['app.name' => 'NavSpace DICI']);

    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('data.name', 'NavSpace DICI');
});
