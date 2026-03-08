<?php

it('renders the group invitation mail preview template', function () {
    $response = $this->get('/dev/mail-preview?template=group-invitation');

    $response->assertOk()
        ->assertSee('You were invited to join', false)
        ->assertSee('Open Invitation', false);
});

it('renders the existing member added to group preview template', function () {
    $response = $this->get('/dev/mail-preview?template=existing-member-added-to-group');

    $response->assertOk()
        ->assertSee('You were added to', false)
        ->assertSee('Open TogetherPay', false);
});

it('returns validation error for unknown preview template', function () {
    $response = $this->get('/dev/mail-preview?template=does-not-exist');

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Unknown template.');
});
