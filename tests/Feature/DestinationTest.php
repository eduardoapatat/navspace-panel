<?php

use App\Models\Destination;
use App\Models\Node;

it('can exist without a node (node_id is nullable in the MVP)', function () {
    $destination = Destination::factory()->create([
        'display_name' => 'Licancabur',
    ]);

    expect($destination->display_name)->toBe('Licancabur')
        ->and($destination->node_id)->toBeNull()
        ->and($destination->building)->not->toBeNull()
        ->and($destination->spaceType)->not->toBeNull();
});

it('can exist without a group (node_group_id is optional)', function () {
    $destination = Destination::factory()->create();

    expect($destination->node_group_id)->toBeNull()
        ->and($destination->nodeGroup)->toBeNull();
});

it('can be anchored to a node', function () {
    $node = Node::factory()->create();
    $destination = Destination::factory()->create(['node_id' => $node->id]);

    expect($destination->node->id)->toBe($node->id);
});

it('survives with a null node when its node is deleted', function () {
    $node = Node::factory()->create();
    $destination = Destination::factory()->create(['node_id' => $node->id]);

    $node->delete();

    $this->assertDatabaseHas('destinations', [
        'id' => $destination->id,
        'node_id' => null,
    ]);
});
